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
	
	// Force Elementor Kit CSS to load before post CSS
	add_action('wp_enqueue_scripts', function() {
		if (class_exists('\Elementor\Plugin')) {
			$kit = \Elementor\Plugin::$instance->kits_manager->get_kit_for_frontend();
			if ($kit) {
				if ($kit->is_autosave()) {
					$css_file = \Elementor\Core\Files\CSS\Post_Preview::create($kit->get_id());
				} else {
					$css_file = \Elementor\Core\Files\CSS\Post::create($kit->get_id());
				}
				$css_file->enqueue();
			}
		}
	}, 5); // Priority 5 để tải trước Elementor's default priority (20)
?>