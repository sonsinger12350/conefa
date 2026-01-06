<?php
	/**
	 * Plugin Name: Connest
	 * Description: Plugin custom for connest
	 * Author: Lucas
	 * Version: 1
	 *
	 * Text Domain: elementor
	 *
	 */

	// Add menu custom
	function add_custom_admin_menu() {
		add_menu_page(
			'',     // Tiêu đề của mục menu
			'Tuyển dụng',     // Tiêu đề của menu
			'manage_options',        // Quyền truy cập cần thiết để thấy mục menu
			'career',     // Slug của menu
			'career_list', // Hàm callback để hiển thị trang của menu
			'dashicons-list-view',
			10
		);

		add_submenu_page(
			'career', // Slug của menu chính
			'Thêm mới',          // Tiêu đề của menu con
			'Thêm mới',          // Tiêu đề của trang con
			'manage_options',        // Quyền truy cập cần thiết để thấy trang con
			'career-create',          // Slug của trang con
			'career_create'  // Hàm callback để hiển thị trang con
		);

		// add_menu_page(
		// 	'',     // Tiêu đề của mục menu
		// 	'Liên hệ',     // Tiêu đề của menu
		// 	'manage_options',        // Quyền truy cập cần thiết để thấy mục menu
		// 	'contact',     // Slug của menu
		// 	'contact_list', // Hàm callback để hiển thị trang của menu
		// 	'dashicons-email',
		// 	11
		// );

		add_menu_page(
			'',     // Tiêu đề của mục menu
			'Cấu hình website',     // Tiêu đề của menu
			'manage_options',        // Quyền truy cập cần thiết để thấy mục menu
			'connest-config',     // Slug của menu
			'website_config', // Hàm callback để hiển thị trang của menu
			'dashicons-admin-settings',
			12
		);
	}

	add_action('admin_menu', 'add_custom_admin_menu');

	function career_list() {
		global $wpdb;

		$search_input = '';

		if (!empty($_POST['post-search-input'])) {
			$search_input = $_POST['post-search-input'];
		}

		$table = "wp_connest_job";

		$current_page = !empty($_GET['paged']) ? $_GET['paged'] : 1;
		$items_per_page = 10; // Số mục trên mỗi trang
		$offset = ( $current_page - 1 ) * $items_per_page;
		$query = "SELECT * FROM `$table` ";

		if (!empty($search_input)) {
			$query .= " WHERE `position` LIKE '%$search_input%'";
		}

		$query .= " ORDER BY created_at DESC LIMIT {$items_per_page} OFFSET {$offset}";
		$data = $wpdb->get_results( $query );

		$total_items = $wpdb->get_var( "SELECT COUNT(id) FROM `$table`" );
		$total_pages = ceil( $total_items / $items_per_page );

		$pagination = [
			'base'      => add_query_arg( 'paged', '%#%' ),
			'format'    => '?paged=%#%',
			'current'   => $current_page,
			'total'     => $total_pages
		];

		include 'view/job-list.php';
	}

	function career_create() {
		global $wpdb;
		$table = "wp_connest_job";
		$data = [];

		if (!empty($_GET['id'])) {
			$data = $wpdb->get_results("SELECT * FROM `$table` WHERE `id` = ".sanitize_text_field($_GET['id']));
			$data = !empty($data[0]) ? $data[0] : [];
		}
		
		if (!empty($_POST['submit-job-form'])) {
			$inputs = [
				'position'  =>  sanitize_text_field($_POST['position']),
				'address'  =>  sanitize_text_field($_POST['address']),
				'working_time'  =>  sanitize_text_field($_POST['working_time']),
				'salary'  =>  sanitize_text_field($_POST['salary']),
				'application_deadline'  =>  sanitize_text_field($_POST['application_deadline']),
				'desc'  =>  sanitize_text_field(str_replace(PHP_EOL, "\\n", $_POST['desc'])),
				'benefits'  =>  sanitize_text_field(str_replace(PHP_EOL, "\\n", $_POST['benefits'])),
				'show'  =>  !empty($_POST['show']) ? sanitize_text_field($_POST['show']) : 0,
			];

			if (!empty($_GET['id'])) {
				if (empty($data)) {
					$notify = '
						<div id="message" class="notice notice-error is-dismissible" style="margin-left: 2px;">
							<button type="button" class="notice-dismiss" onclick="this.parentNode.remove()">
								<span class="screen-reader-text">Dismiss this notice.</span>
							</button>
						</div>
					';
					echo $notify;
					goto DONE;
				}

				$sql = "SELECT `id` FROM `$table` WHERE `position` LIKE '".$inputs['position']."' AND `id` <> ".$data->id;
				$isExist = $wpdb->get_results($sql);

				if (!empty($isExist)) {
					$notify = '
						<div id="message" class="notice notice-error is-dismissible" style="margin-left: 2px;">
							<p>Tin tuyển dụng đã tồn tại.</p>
							<button type="button" class="notice-dismiss" onclick="this.parentNode.remove()">
								<span class="screen-reader-text">Dismiss this notice.</span>
							</button>
						</div>
					';
					echo $notify;
					goto DONE;
				}

				$where = [
					'id'    =>  $data->id
				];
				$wpdb->update( $table, $inputs, $where );
				
				$notify = '
					<div id="message" class="updated notice notice-success is-dismissible" style="margin-left: 2px;">
						<p>Cập nhật dịch vụ thành công.</p>
						<button type="button" class="notice-dismiss" onclick="this.parentNode.remove()">
							<span class="screen-reader-text">Dismiss this notice.</span>
						</button>
					</div>
					<script>setTimeout(function() {window.location.href="'.admin_url('admin.php?page=career').'"},1000)</script>
				';

				echo $notify;
				goto DONE;
			} else {
				$sql = "SELECT `id` FROM `$table` WHERE `position` LIKE '".$inputs['position']."'";
				$isExist = $wpdb->get_results($sql);

				if (!empty($isExist) && empty($_GET['id'])) {
					$notify = '
						<div id="message" class="notice notice-error is-dismissible" style="margin-left: 2px;">
							<p>Tin tuyển dụng đã tồn tại.</p>
							<button type="button" class="notice-dismiss" onclick="this.parentNode.remove()">
								<span class="screen-reader-text">Dismiss this notice.</span>
							</button>
						</div>
					';
					echo $notify;
					goto DONE;
				}
				$wpdb->insert($table, $inputs);
				$message = 'Thêm mới tin tuyển dụng thành công.';
			}
			
			$notify = '
				<div id="message" class="updated notice notice-success is-dismissible" style="margin-left: 2px;">
					<p>'.$message.'</p>
					<button type="button" class="notice-dismiss" onclick="this.parentNode.remove()">
						<span class="screen-reader-text">Dismiss this notice.</span>
					</button>
				</div>
			';

			echo $notify;
			goto DONE;
		}
		
		DONE:
		include 'view/job-create.php';
	}

	function contact_list() {
		global $wpdb;

		$search_input = '';

		if (!empty($_POST['post-search-input'])) {
			$search_input = $_POST['post-search-input'];
		}

		$table = "wp_connest_form";

		$current_page = !empty($_GET['paged']) ? $_GET['paged'] : 1;
		$items_per_page = 10; // Số mục trên mỗi trang
		$offset = ( $current_page - 1 ) * $items_per_page;
		$query = "SELECT * FROM `$table` ";

		if (!empty($search_input)) {
			$query .= " WHERE `full_name` LIKE '%$search_input%' OR `phone` LIKE '%$search_input%'";
		}

		$query .= " ORDER BY created_at DESC LIMIT {$items_per_page} OFFSET {$offset}";
		$data = $wpdb->get_results( $query );

		$total_items = $wpdb->get_var( "SELECT COUNT(id) FROM `$table`" );
		$total_pages = ceil( $total_items / $items_per_page );

		$pagination = [
			'base'      => add_query_arg( 'paged', '%#%' ),
			'format'    => '?paged=%#%',
			'current'   => $current_page,
			'total'     => $total_pages
		];

		include 'view/contact-list.php';
	}

	function website_config() {
		global $wpdb;

		$table = "wp_connest_config";

		if (!empty($_POST['submit-config-form'])) {
			require_once(ABSPATH . 'wp-admin/includes/file.php');
			
			$department_1 = json_encode($_POST['department_1'], JSON_UNESCAPED_UNICODE);
			$department_1 = str_replace('\r\n', "<br>", $department_1);
			$department_2 = json_encode($_POST['department_2'], JSON_UNESCAPED_UNICODE);
			$department_2 = str_replace('\r\n', "<br>", $department_2);

			$inputs = [
				'social'  =>  sanitize_text_field(json_encode($_POST['social'])),
				'hotline'  =>  sanitize_text_field($_POST['hotline']),
				'email'  =>  sanitize_text_field($_POST['email']),	
				'iframe_map'  =>  $_POST['iframe_map'],	
				'department_1'  =>  $department_1,
				'department_2'  =>  $department_2,
			];

			// Handle logo_black upload
			if (!empty($_FILES['logo_black_file']['name'])) {
				$allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
				$file_type = wp_check_filetype($_FILES['logo_black_file']['name']);
				
				if (in_array($file_type['type'], $allowed_types)) {
					$file = [
						'name' => $_FILES['logo_black_file']['name'],
						'type' => $_FILES['logo_black_file']['type'],
						'tmp_name' => $_FILES['logo_black_file']['tmp_name'],
						'error' => $_FILES['logo_black_file']['error'],
						'size' => $_FILES['logo_black_file']['size'],
					];
					$movefile = wp_handle_upload($file, ['test_form' => false]);
					
					if ($movefile && empty($movefile['error'])) $inputs['logo_black'] = $movefile['url'];
				}
			}
			else if (!empty($_POST['logo_black'])) {
				$inputs['logo_black'] = sanitize_text_field($_POST['logo_black']);
			}

			// Handle logo_white upload
			if (!empty($_FILES['logo_white_file']['name'])) {
				$allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
				$file_type = wp_check_filetype($_FILES['logo_white_file']['name']);
				
				if (in_array($file_type['type'], $allowed_types)) {
					$file = [
						'name' => $_FILES['logo_white_file']['name'],
						'type' => $_FILES['logo_white_file']['type'],
						'tmp_name' => $_FILES['logo_white_file']['tmp_name'],
						'error' => $_FILES['logo_white_file']['error'],
						'size' => $_FILES['logo_white_file']['size'],
					];
					$movefile = wp_handle_upload($file, ['test_form' => false]);
					
					if ($movefile && empty($movefile['error'])) $inputs['logo_white'] = $movefile['url'];
				}
			}
			else if (!empty($_POST['logo_white'])) $inputs['logo_white'] = sanitize_text_field($_POST['logo_white']);

			foreach ($inputs as $k => $v) {
				$sql = "SELECT `key` FROM `$table` WHERE `key` = '$k'";
				$isExist = $wpdb->get_var($sql);

				if (!empty($isExist)) $sql = "UPDATE `$table` SET `value` = '$v' WHERE `key` = '$k'";
				else $sql = "INSERT INTO `$table`(`key`, `value`) VALUES ('$k', '$v')";

				$wpdb->query($sql);
			}
		
			$notify = '
				<div id="message" class="updated notice notice-success is-dismissible" style="margin-left: 2px;">
					<p>Đã lưu cài đặt</p>
					<button type="button" class="notice-dismiss" onclick="this.parentNode.remove()">
						<span class="screen-reader-text">Dismiss this notice.</span>
					</button>
				</div>
			';

			echo $notify;
		}

		$config = getConnestConfig();

		DONE:
		include 'view/config.php';
	}

	// Save form
	function save_form_custom() {
		if (empty($_POST)) {
			wp_send_json_error( 'Vui lòng nhập đầy đủ thông tin.' );
			exit;
		}

		global $wpdb;
		$inputs = $_POST;
		$insert = [
			'full_name'	=>	$inputs['full_name'],
			'phone'		=> 	$inputs['phone'],
			'address'	=>	@$inputs['address'],
			'type'		=>	$inputs['type'],
		];

		if ($inputs['type'] == 'contact') {
			$extraData = [
				'area_address' => @$inputs['area_address'],
				'land_area' => @$inputs['land_area'],
				'number_bedrooms' => @$inputs['number_bedrooms'],
				'other' => @$inputs['other'],
			];

			if (!empty($inputs['date']['day'])) {
				$extraData['date'] = @$inputs['date']['day'].'/'.@$inputs['date']['month'].'/'.@$inputs['date']['year'];
			}

			if (!empty($_FILES['image'])) {
				$image = [];

				if (!isset($_POST['custom_nonce']) || !wp_verify_nonce($_POST['custom_nonce'], 'custom_upload_action')) {
					wp_send_json_error( 'Sai nonce' );
				}

				foreach ($_FILES['image']['tmp_name'] as $k => $tmp) {
					$allowed_types = array('image/jpeg', 'image/png', 'image/gif');
					$file_type = wp_check_filetype($_FILES['image']['name'][$k]);

					if (getimagesize($tmp) === false) continue;
					if (!in_array($file_type['type'], $allowed_types)) continue;

					$file = [
						'name' => $_FILES['image']['name'][$k],
						'type' => $_FILES['image']['type'][$k],
						'tmp_name' => $_FILES['image']['tmp_name'][$k],
						'error' => $_FILES['image']['error'][$k],
						'size' => $_FILES['image']['size'][$k],
					];
					$movefile = wp_handle_upload($file, ['test_form' => false]);

					if ($movefile && empty($movefile['error'])) {
						$wp_upload_dir = wp_upload_dir();
						$relative_path = str_replace($wp_upload_dir['baseurl'], '', $movefile['url']);
						$image[] = $relative_path;
					}
				}

				$extraData['image'] = $image;
			}

			$insert['extra_data'] = json_encode($extraData, JSON_UNESCAPED_UNICODE);
		}

		$sql = "INSERT INTO wp_connest_form(`".implode('`,`', array_keys($insert))."`) VALUE ('".implode("','", array_values($insert))."')";

		if (empty($wpdb->query($sql))) {
			wp_send_json_error( 'Có lỗi. Vui lòng thử lại' );
			exit;
		}

		wp_send_json_success('Đã lưu thông tin.');exit;

	}

	add_action( 'wp_ajax_save_form_custom', 'save_form_custom' );
	add_action( 'wp_ajax_nopriv_save_form_custom', 'save_form_custom' );

	// Delete data admin
	function delete_data_connest() {
		$rs = [
			'success'	=>	0,
			'message'	=>	'',
			'data'		=>	[],
		];

		if (empty($_POST['table']) || empty($_POST['id'])) {
			wp_send_json_error( 'Vui lòng nhập đầy đủ thông tin.' );exit;
		}

		global $wpdb;
		
		$table_name = $_POST['table'];
		$where_condition = ['id'=>$_POST['id']];
		
		if (empty($wpdb->delete($table_name, $where_condition))) {
			wp_send_json_error( 'Có lỗi. Vui lòng thử lại' );exit;
		}

		wp_send_json_success('Xóa dữ liệu thành công.');exit;

	}

	add_action( 'wp_ajax_delete_data_connest', 'delete_data_connest' );
	add_action( 'wp_ajax_nopriv_delete_data_connest', 'delete_data_connest' );

	function getConnestConfig() {
		global $wpdb;

		$result = $wpdb->get_results("SELECT * FROM `wp_connest_config`");
		$data = [];

		if (empty($result)) return $data;

		foreach ($result as $v) {
			$data[$v->key] = $v->value;
		}

		$data['social'] = json_decode($data['social'], true);
		$data['department_1'] = json_decode($data['department_1'], true);
		$data['department_2'] = json_decode($data['department_2'], true);

		if (!empty($data['department_1']['address'])) $data['department_1']['address'] = str_replace('<br>', "\r\n", $data['department_1']['address']);
		if (!empty($data['department_2']['address'])) $data['department_2']['address'] = str_replace('<br>', "\r\n", $data['department_2']['address']);

		return $data;
	}