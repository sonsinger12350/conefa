<?php

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class Custom_Elementor_Widget_Projects extends \Elementor\Widget_Base {

	public function get_name() {
		return 'custom_widget_projects';
	}

	public function get_title() {
		return __('Custom Widget Projects', 'astra-child');
	}

	public function get_icon() {
		return 'eicon-slider-push';
	}

	public function get_categories() {
		return ['widget-custom'];
	}

	protected function _register_controls() {
		// --- CONTENT: Projects Items ---
		$this->start_controls_section(
			'content_section',
			[
				'label' => __('Nội dung', 'astra-child'),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		// Get categories for select
		$categories = get_terms(array(
			'taxonomy' => 'danh-muc-du-an',
			'hide_empty' => false,
		));
		
		$category_options = ['all' => __('Tất cả', 'astra-child')];
		if (!empty($categories) && !is_wp_error($categories)) {
			foreach ($categories as $cat) {
				$category_options[$cat->slug] = $cat->name;
			}
		}

		$this->add_control(
			'project_category',
			[
				'label' => __('Danh mục dự án', 'astra-child'),
				'type' => Controls_Manager::SELECT,
				'default' => 'all',
				'options' => $category_options,
			]
		);

		$this->add_control(
			'posts_per_page',
			[
				'label' => __('Số lượng dự án', 'astra-child'),
				'type' => Controls_Manager::NUMBER,
				'default' => 8,
				'min' => 1,
				'max' => 50,
				'step' => 1,
			]
		);

		$this->add_control(
			'orderby',
			[
				'label' => __('Sắp xếp theo', 'astra-child'),
				'type' => Controls_Manager::SELECT,
				'default' => 'date',
				'options' => [
					'date' => __('Ngày đăng', 'astra-child'),
					'title' => __('Tiêu đề', 'astra-child'),
					'menu_order' => __('Thứ tự menu', 'astra-child'),
					'ID' => __('ID', 'astra-child'),
				],
			]
		);

		$this->add_control(
			'order',
			[
				'label' => __('Thứ tự', 'astra-child'),
				'type' => Controls_Manager::SELECT,
				'default' => 'DESC',
				'options' => [
					'ASC' => __('Tăng dần', 'astra-child'),
					'DESC' => __('Giảm dần', 'astra-child'),
				],
			]
		);

		$this->end_controls_section();

		// --- STYLE: Projects ---
		$this->start_controls_section(
			'style_projects_section',
			[
				'label' => __('Dự án', 'astra-child'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'category_typography',
				'label'    => __('Kiểu chữ danh mục', 'astra-child'),
				'selector' => '{{WRAPPER}} .projects-slide .slide-item .item-category',
			]
		);

		$this->add_control(
			'category_color',
			[
				'label' => __('Màu danh mục', 'astra-child'),
				'type' => Controls_Manager::COLOR,
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .projects-slide .slide-item .item-category' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'name_typography',
				'label'    => __('Kiểu chữ tên dự án', 'astra-child'),
				'selector' => '{{WRAPPER}} .projects-slide .slide-item .item-name',
			]
		);

		$this->add_control(
			'name_color',
			[
				'label' => __('Màu tên dự án', 'astra-child'),
				'type' => Controls_Manager::COLOR,
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .projects-slide .slide-item .item-name' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'info_box_bg',
			[
				'label' => __('Màu nền hộp thông tin', 'astra-child'),
				'type' => Controls_Manager::COLOR,
				'default' => '#000000',
				'selectors' => [
					'{{WRAPPER}} .projects-slide .slide-item .item-info' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		// Query arguments
		$args = array(
			'post_type' => 'du-an',
			'posts_per_page' => !empty($settings['posts_per_page']) ? intval($settings['posts_per_page']) : 8,
			'orderby' => !empty($settings['orderby']) ? $settings['orderby'] : 'date',
			'order' => !empty($settings['order']) ? $settings['order'] : 'DESC',
			'post_status' => 'publish',
		);

		// Filter by category if selected
		if (!empty($settings['project_category']) && $settings['project_category'] !== 'all') {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'danh-muc-du-an',
					'field' => 'slug',
					'terms' => $settings['project_category'],
				),
			);
		}

		$query = new \WP_Query($args);

		if (!$query->have_posts()) {
			return;
		}

		$widget_id = 'projects-slide-' . $this->get_id();
		
		$output = '<div class="projects-slide" id="' . esc_attr($widget_id) . '">';
		$output .= '<div class="main-carousel owl-carousel owl-theme">';

		$index = 0;
		while ($query->have_posts()) {
			$query->the_post();
			$post_id = get_the_ID();
			
			// Get featured image
			$image_id = get_post_thumbnail_id($post_id);
			$image_url = '';
			if ($image_id) $image_url = wp_get_attachment_image_url($image_id, 'full');
			
			// Get category (first term from taxonomy)
			$categories = get_the_terms($post_id, 'danh-muc-du-an');
			$category = '';

			if (!empty($categories) && !is_wp_error($categories)) {
				$first_category = reset($categories);
				$category = $first_category->name;
			}
			
			// Get post title
			$name = get_the_title();
			
			// Get permalink
			$link = get_permalink($post_id);

			$output .= '<div class="slide-item" data-slide-index="' . esc_attr($index) . '">';
			$output .= '<a href="' . esc_url($link) . '">';
			
			if ($image_url) {
				$output .= '<div class="item-image">';
				$output .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($name ? $name : $category) . '">';
				$output .= '</div>';
			}
			
			$output .= '<div class="item-info">';
			if ($category) $output .= '<p class="item-category">' . esc_html($category) . '</p>';
			if ($name) $output .= '<p class="item-name">' . esc_html($name) . '</p>';
			$output .= '</div>';
			
			$output .= '</a>';
			$output .= '</div>';
			
			$index++;
		}

		wp_reset_postdata();

		$output .= '</div>'; // End main-carousel

		$output .= '</div>'; // End projects-slide

		echo $output;
	}
}
