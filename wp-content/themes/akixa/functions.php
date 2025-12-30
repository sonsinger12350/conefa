<?php
	if (file_exists(get_template_directory() . '/inc/functions/custom.php')) {
		require_once get_template_directory() . '/inc/functions/custom.php';
	}

	add_action('after_setup_theme', function () {
		add_theme_support('woocommerce');
	});

	function theme_setup() {
		// Thêm hỗ trợ cho menu
		add_theme_support('menus');

		// Đăng ký các vị trí menu
		register_nav_menus(array(
			'primary' => __('Primary Menu'), // Đăng ký vị trí menu "Primary"
			'footer'  => __('Footer Menu'),  // Có thể thêm nhiều vị trí khác
		));
	}
	add_action('after_setup_theme', 'theme_setup');

	function register_my_menus() {
		register_nav_menus(array(
			'primary' => __('Primary Menu'), // Vị trí menu với tên 'primary'
		));
	}
	add_action('init', 'register_my_menus');

	function save_recently_viewed_product() {
		if (is_product()) {
			global $post;

			$product_id = $post->ID;
			if (!isset($_SESSION['product_recently'])) $_SESSION['product_recently'] = [];
			if (($key = array_search($product_id, $_SESSION['product_recently'])) !== false) unset($_SESSION['product_recently'][$key]);
			array_unshift($_SESSION['product_recently'], $product_id);

			// Giới hạn danh sách sản phẩm đã xem gần đây
			$_SESSION['product_recently'] = array_slice($_SESSION['product_recently'], 0, 10);
		}
	}

	add_action('template_redirect', 'save_recently_viewed_product');

	function ajax_load_posts() {
		$paged = isset($_POST['page']) ? intval($_POST['page']) : 1;
		$excludeIds = isset($_POST['excludeIds']) ? $_POST['excludeIds'] : array();

		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => 8,
			'post_status'    => 'publish',
			'post__not_in'   => $excludeIds,
			'paged'          => $paged
		);

		$query = new WP_Query($args);

		if ($query->have_posts()) :
			while ($query->have_posts()) : $query->the_post();
				// Trả về dữ liệu HTML hoặc JSON
				get_template_part('template-parts/content', get_post_format());
			endwhile;
		else :
			echo 'No more posts.';
		endif;

		wp_die(); // Kết thúc AJAX
	}

	add_action('wp_ajax_load_posts', 'ajax_load_posts');
	add_action('wp_ajax_nopriv_load_posts', 'ajax_load_posts');

	// AJAX handler for checkout
	function ajax_create_checkout_order() {
		check_ajax_referer('checkout_nonce', 'nonce');
		
		$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
		$customer_name = isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '';
		$customer_email = isset($_POST['customer_email']) ? sanitize_email($_POST['customer_email']) : '';

		if (strlen($customer_name) > 255) wp_send_json_error(['message' => 'Tên quá dài.']);
		if (strlen($customer_email) > 255) wp_send_json_error(['message' => 'Email quá dài.']);
		
		if (empty($product_id) || empty($customer_name) || empty($customer_email) || !is_email($customer_email)) {
			wp_send_json_error(['message' => 'Vui lòng điền đầy đủ thông tin hợp lệ.']);
		}
		
		$product = wc_get_product($product_id);
		if (!$product || !$product->is_purchasable()) wp_send_json_error(['message' => 'Sản phẩm không hợp lệ hoặc không thể mua.']);
		
		// Create WooCommerce order
		$order = wc_create_order();
		
		if (is_wp_error($order)) wp_send_json_error(['message' => 'Không thể tạo đơn hàng. Vui lòng thử lại.']);
		
		// Add product to order
		$order->add_product($product, 1);
		
		// Set billing information
		$order->set_billing_first_name($customer_name);
		$order->set_billing_email($customer_email);
		$order->set_billing_phone('');
		
		// Set order status
		$order->set_status('on-hold', 'Đơn hàng đang chờ thanh toán');
		$order->set_payment_method('sepay');
		$order->set_payment_method_title('Chuyển khoản ngân hàng');
		
		// Calculate totals
		$order->calculate_totals();
		$order->save();
		
		$order_id = $order->get_id();
		
		// Get Sepay gateway
		$gateways = WC()->payment_gateways->get_available_payment_gateways();
		$sepay_gateway = isset($gateways['sepay']) ? $gateways['sepay'] : null;
		
		$sepay_data = [];
		
		if ($sepay_gateway && $sepay_gateway instanceof WC_Gateway_SePay) {
			// Get remark using public method
			$remark = $sepay_gateway->get_remark($order_id);
			
			$account_number = '';
			$account_holder_name = '';
			$bank_bin = '';
			$bank_logo_url = '';
			$displayed_bank_name = '';
			
			// Get bank account data using public method
			$bank_account_data = $sepay_gateway->get_bank_account_data();
			
			// Check if we have bank account data from API
			if ($bank_account_data) {
				$bank_account_id = $sepay_gateway->get_option('bank_account');
				
				// Try to get bank account via API
				$api = new WC_SePay_API();
				$bank_account = null;
				if ($api->is_connected() && $bank_account_id) $bank_account = $api->get_bank_account($bank_account_id);
				
				if ($bank_account) {
					$required_sub_account_banks = ['BIDV', 'OCB', 'MSB', 'KienLongBank'];
					$bank_short_name = $bank_account_data['bank']['short_name'];
					
					if (in_array($bank_short_name, $required_sub_account_banks)) {
						$account_number = $sepay_gateway->get_option('sub_account');
						if (empty($account_number)) $account_number = $bank_account['account_number'];
					}
					else {
						$account_number = $sepay_gateway->get_option('sub_account') ? $sepay_gateway->get_option('sub_account') : $bank_account['account_number'];
					}
					
					$account_holder_name = $bank_account_data['account_holder_name'];
					$bank_bin = $bank_account_data['bank']['bin'];
					$bank_logo_url = $bank_account_data['bank']['logo_url'];
					$displayed_bank_name = $sepay_gateway->displayed_bank_name;
				}
				else {
					// Use data from bank_account_data directly
					$account_number = $bank_account_data['account_number'] ?? $sepay_gateway->get_option('bank_account_number');
					$account_holder_name = $bank_account_data['account_holder_name'] ?? $sepay_gateway->get_option('bank_account_holder');
					$bank_bin = $bank_account_data['bank']['bin'] ?? '';
					$bank_logo_url = $bank_account_data['bank']['logo_url'] ?? '';
					$displayed_bank_name = $sepay_gateway->displayed_bank_name;
				}
			}
			
			// Fallback to manual settings if no API connection
			if (empty($account_number) || empty($bank_bin)) {
				// Fallback to manual settings - replicate bank data array
				$bank_data = array(
					'vietcombank' => array('bin' => '970436', 'code' => 'VCB', 'short_name' => 'Vietcombank', 'full_name' => 'Ngân hàng TMCP Ngoại Thương Việt Nam'),
					'vpbank' => array('bin' => '970432', 'code' => 'VPB', 'short_name' => 'VPBank', 'full_name' => 'Ngân hàng TMCP Việt Nam Thịnh Vượng'),
					'acb' => array('bin' => '970416', 'code' => 'ACB', 'short_name' => 'ACB', 'full_name' => 'Ngân hàng TMCP Á Châu'),
					'sacombank' => array('bin' => '970403', 'code' => 'STB', 'short_name' => 'Sacombank', 'full_name' => 'Ngân hàng TMCP Sài Gòn Thương Tín'),
					'hdbank' => array('bin' => '970437', 'code' => 'HDB', 'short_name' => 'HDBank', 'full_name' => 'Ngân hàng TMCP Phát triển Thành phố Hồ Chí Minh'),
					'vietinbank' => array('bin' => '970415', 'code' => 'ICB', 'short_name' => 'VietinBank', 'full_name' => 'Ngân hàng TMCP Công thương Việt Nam'),
					'techcombank' => array('bin' => '970407', 'code' => 'TCB', 'short_name' => 'Techcombank', 'full_name' => 'Ngân hàng TMCP Kỹ thương Việt Nam'),
					'mbbank' => array('bin' => '970422', 'code' => 'MB', 'short_name' => 'MBBank', 'full_name' => 'Ngân hàng TMCP Quân đội'),
					'bidv' => array('bin' => '970418', 'code' => 'BIDV', 'short_name' => 'BIDV', 'full_name' => 'Ngân hàng TMCP Đầu tư và Phát triển Việt Nam'),
					'msb' => array('bin' => '970426', 'code' => 'MSB', 'short_name' => 'MSB', 'full_name' => 'Ngân hàng TMCP Hàng Hải Việt Nam'),
					'shinhanbank' => array('bin' => '970424', 'code' => 'SHBVN', 'short_name' => 'ShinhanBank', 'full_name' => 'Ngân hàng TNHH MTV Shinhan Việt Nam'),
					'tpbank' => array('bin' => '970423', 'code' => 'TPB', 'short_name' => 'TPBank', 'full_name' => 'Ngân hàng TMCP Tiên Phong'),
					'eximbank' => array('bin' => '970431', 'code' => 'EIB', 'short_name' => 'Eximbank', 'full_name' => 'Ngân hàng TMCP Xuất Nhập khẩu Việt Nam'),
					'vib' => array('bin' => '970441', 'code' => 'VIB', 'short_name' => 'VIB', 'full_name' => 'Ngân hàng TMCP Quốc tế Việt Nam'),
					'agribank' => array('bin' => '970405', 'code' => 'VBA', 'short_name' => 'Agribank', 'full_name' => 'Ngân hàng Nông nghiệp và Phát triển Nông thôn Việt Nam'),
					'publicbank' => array('bin' => '970439', 'code' => 'PBVN', 'short_name' => 'PublicBank', 'full_name' => 'Ngân hàng TNHH MTV Public Việt Nam'),
					'kienlongbank' => array('bin' => '970452', 'code' => 'KLB', 'short_name' => 'KienLongBank', 'full_name' => 'Ngân hàng TMCP Kiên Long'),
					'ocb' => array('bin' => '970448', 'code' => 'OCB', 'short_name' => 'OCB', 'full_name' => 'Ngân hàng TMCP Phương Đông'),
					'abbank' => array('bin' => '970425', 'code' => 'ABBANK', 'short_name' => 'ABBANK', 'full_name' => 'Ngân hàng TMCP An Bình'),
				);
				
				$bank_select = $sepay_gateway->get_option('bank_select');
				$bank_info = null;
				
				if (isset($bank_data[$bank_select])) {
					$bank_info = $bank_data[$bank_select];
				}
				else {
					foreach ($bank_data as $key => $bank) {
						if (
							strtolower($bank['code']) === strtolower($bank_select) ||
							$bank['bin'] === $bank_select ||
							$bank['short_name'] === $bank_select ||
							strtolower($bank['short_name']) === strtolower($bank_select)
						) {
							$bank_info = $bank;
							break;
						}
					}
				}
				
				$account_number = $sepay_gateway->get_option('sub_account') ? $sepay_gateway->get_option('sub_account') : $sepay_gateway->get_option('bank_account_number');
				$account_holder_name = $sepay_gateway->get_option('bank_account_holder');
				
				if ($bank_info) {
					$bank_bin = $bank_info['bin'];
					$bank_logo_url = sprintf('https://my.sepay.vn/assets/images/banklogo/%s.png', strtolower($bank_info['short_name']));
					
					$bank_name_display_type = $sepay_gateway->get_option('show_bank_name');

					if ($bank_name_display_type == "brand_name") $displayed_bank_name = $bank_info['short_name'];
					else if ($bank_name_display_type == "full_name") $displayed_bank_name = $bank_info['full_name'];
					else if ($bank_name_display_type == "full_include_brand") $displayed_bank_name = $bank_info['full_name'] . " (" . $bank_info['short_name'] . ")";
					else $displayed_bank_name = $bank_info['short_name'];
				}
			}
			
			// Generate QR code URL
			if (!empty($account_number) && !empty($bank_bin)) {
				$qr_code_url = sprintf(
					'https://qr.sepay.vn/img?acc=%s&bank=%s&amount=%s&des=%s&template=compact',
					urlencode($account_number),
					urlencode($bank_bin),
					$order->get_total(),
					urlencode($remark)
				);
				
				$sepay_data = [
					'qr_code_url' => $qr_code_url,
					'account_number' => $account_number,
					'account_holder_name' => $account_holder_name,
					'bank_bin' => $bank_bin,
					'bank_logo_url' => $bank_logo_url,
					'displayed_bank_name' => $displayed_bank_name,
					'amount' => $order->get_total(),
					'amount_formatted' => wc_price($order->get_total()),
					'remark' => $remark,
					'order_id' => $order_id
				];
			}
		}
		
		if (empty($sepay_data)) wp_send_json_error(['message' => 'Không thể lấy thông tin thanh toán. Vui lòng kiểm tra cấu hình Sepay.']);
		
		wp_send_json_success($sepay_data);
	}
	
	add_action('wp_ajax_create_checkout_order', 'ajax_create_checkout_order');
	add_action('wp_ajax_nopriv_create_checkout_order', 'ajax_create_checkout_order');

	// AJAX handler to check order status
	function ajax_check_order_status() {
		check_ajax_referer('checkout_nonce', 'nonce');
		
		$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
		if (empty($order_id)) wp_send_json_error(['message' => 'Thiếu thông tin đơn hàng.']);
		
		$order = wc_get_order($order_id);
		if (!$order) wp_send_json_error(['message' => 'Đơn hàng không tồn tại.']);
		
		// Kiểm tra email trùng khớp với đơn hàng
		$customer_email = isset($_POST['customer_email']) ? sanitize_email($_POST['customer_email']) : '';
		if (empty($customer_email)) wp_send_json_error(['message' => 'Thiếu thông tin.']);
		
		$order_email = $order->get_billing_email();
		if (empty($order_email) || !hash_equals(strtolower(trim($order_email)), strtolower(trim($customer_email)))) wp_send_json_error(['message' => 'Không có quyền thao tác.']);
		
		$order_status = $order->get_status();
		$response_data = [
			'status' => $order_status,
			'downloads' => []
		];
		
		// If order is completed, get downloadable files
		if ($order_status === 'completed') {
			// Check if order allows downloads
			if ($order->is_download_permitted()) {
				$items = $order->get_items();
				
				foreach ($items as $item) {
					// Check if item is a product item
					if (!is_a($item, 'WC_Order_Item_Product')) continue;
					
					$product = $item->get_product();
					
					if ($product && $product->is_downloadable()) {
						$downloads = $item->get_item_downloads();
						
						if (!empty($downloads)) {
							foreach ($downloads as $download_id => $download) {
								$response_data['downloads'][] = [
									'id' => $download_id,
									'name' => isset($download['name']) ? $download['name'] : 'Download',
									'url' => isset($download['download_url']) ? $download['download_url'] : ''
								];
							}
						}
					}
				}
			}
		}
		
		wp_send_json_success($response_data);
	}
	
	add_action('wp_ajax_check_order_status', 'ajax_check_order_status');
	add_action('wp_ajax_nopriv_check_order_status', 'ajax_check_order_status');

	add_filter( 'manage_edit-product_columns', 'add_custom_product_column', 10 );
	function add_custom_product_column( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $column ) {
			$new_columns[ $key ] = $column;
			if ( 'name' === $key ) $new_columns['pin_home'] = __( 'Ghim trang chủ', 'your-textdomain' );
		}

		return $new_columns;
	}

	add_action( 'manage_product_posts_custom_column', 'show_custom_field_in_product_column', 10, 2 );
	function show_custom_field_in_product_column( $column, $post_id ) {
		if ( 'pin_home' === $column ) {
			$custom_field_value = get_post_meta( $post_id, 'pin_home', true ) == 1 ? 'Có' : 'Không';
			echo !empty( $custom_field_value ) ? esc_html( $custom_field_value ) : __( 'Không', 'your-textdomain' );
		}
	}

	add_filter( 'manage_edit-product_sortable_columns', 'make_custom_field_sortable' );
	function make_custom_field_sortable( $sortable_columns ) {
		$sortable_columns['pin_home'] = 'pin_home';
		return $sortable_columns;
	}

	add_action( 'pre_get_posts', 'custom_field_orderby' );
	function custom_field_orderby( $query ) {
		if ( !is_admin() ) return;
		$orderby = $query->get( 'orderby' );

		if ( 'custom_field' === $orderby ) {
			$query->set( 'meta_key', 'pin_home' );
			$query->set( 'orderby', 'meta_value' );
		}
	}

	add_action('wpcf7_before_send_mail', function($contact_form) {
		$submission = WPCF7_Submission::get_instance();
		if ($submission) {
			$posted_data = $submission->get_posted_data();
			error_log('Contact Form Sent:' .json_encode($posted_data));
		}
	});

	// Register Elementor Widget Category
	function add_elementor_widget_category($elements_manager) {
		$elements_manager->add_category(
			'widget-custom',
			[
				'title' => __('Custom Widgets', 'akixa'),
				'icon' => 'fa fa-plug',
			]
		);
	}
	add_action('elementor/elements/categories_registered', 'add_elementor_widget_category');

	// Register Elementor Widgets
	function register_elementor_widgets($widgets_manager) {
		// Include widget files
		require_once get_template_directory() . '/widgets/projects.php';
		require_once get_template_directory() . '/widgets/services.php';
		require_once get_template_directory() . '/widgets/testimonials.php';

		// Register widgets
		$widgets_manager->register(new \Custom_Elementor_Widget_Projects());
		$widgets_manager->register(new \Custom_Elementor_Widget_Services());
		$widgets_manager->register(new \Custom_Elementor_Widget_Testimonials());
	}
	add_action('elementor/widgets/register', 'register_elementor_widgets');

	function get_breadcrumb() {
		if (function_exists('rank_math_the_breadcrumbs')) {
			rank_math_the_breadcrumbs();
		}
		elseif (function_exists('yoast_breadcrumb')) {
			yoast_breadcrumb();
		}
	}
?>