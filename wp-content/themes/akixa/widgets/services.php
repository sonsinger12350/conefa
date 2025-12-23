<?php

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;

class Custom_Elementor_Widget_Services extends \Elementor\Widget_Base {

	public function get_name() {
		return 'custom_widget_services';
	}

	public function get_title() {
		return __('Custom Widget Services', 'astra-child');
	}

	public function get_icon() {
		return 'eicon-slider-push';
	}

	public function get_categories() {
		return ['widget-custom'];
	}

	protected function _register_controls() {
		// --- CONTENT: Services Items ---
		$this->start_controls_section(
			'content_section',
			[
				'label' => __('Nội dung', 'astra-child'),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'item_image',
			[
				'label' => __('Ảnh', 'astra-child'),
				'type' => Controls_Manager::MEDIA,
				'default' => [
					'url' => \Elementor\Utils::get_placeholder_image_src(),
				],
			]
		);

		$repeater->add_control(
			'item_title',
			[
				'label' => __('Tiêu đề', 'astra-child'),
				'type' => Controls_Manager::TEXT,
				'default' => __('Tiêu đề dịch vụ', 'astra-child'),
				'placeholder' => __('Nhập tiêu đề', 'astra-child'),
			]
		);

		$repeater->add_control(
			'item_description',
			[
				'label' => __('Mô tả', 'astra-child'),
				'type' => Controls_Manager::TEXTAREA,
				'rows' => 4,
				'default' => __('Nhập mô tả dịch vụ', 'astra-child'),
			]
		);

		$repeater->add_control(
			'item_link',
			[
				'label' => __('Link', 'astra-child'),
				'type' => Controls_Manager::URL,
				'placeholder' => __('https://your-link.com', 'astra-child'),
				'show_external' => true,
				'default' => [
					'url' => '',
					'is_external' => true,
					'nofollow' => true,
				],
			]
		);

		$this->add_control(
			'services_items',
			[
				'label' => __('Danh sách dịch vụ', 'astra-child'),
				'type' => Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'default' => [
					[ 
						'item_title' => __('Dịch vụ 1', 'astra-child'),
						'item_description' => __('Mô tả dịch vụ 1', 'astra-child'), 
					],
					[ 
						'item_title' => __('Dịch vụ 2', 'astra-child'),
						'item_description' => __('Mô tả dịch vụ 2', 'astra-child'), 
					],
				],
				'title_field' => '{{{ item_title }}}',
			]
		);

		$this->end_controls_section();

		// --- STYLE: Services ---
		$this->start_controls_section(
			'style_services_section',
			[
				'label' => __('Dịch vụ', 'astra-child'),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'label'    => __('Kiểu chữ tiêu đề', 'astra-child'),
				'selector' => '{{WRAPPER}} .services-slide .slide-item .item-title',
			]
		);

		$this->add_control(
			'title_color',
			[
				'label' => __('Màu tiêu đề', 'astra-child'),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .services-slide .slide-item .item-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'description_typography',
				'label'    => __('Kiểu chữ mô tả', 'astra-child'),
				'selector' => '{{WRAPPER}} .services-slide .slide-item .item-description',
			]
		);

		$this->add_control(
			'description_color',
			[
				'label' => __('Màu mô tả', 'astra-child'),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .services-slide .slide-item .item-description' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		if (empty($settings['services_items'])) {
			return;
		}

		$widget_id = 'services-slide-' . $this->get_id();
		
		$output = '<div class="services-slide" id="' . esc_attr($widget_id) . '">';
		$output .= '<div class="main-carousel owl-carousel owl-theme">';

		foreach ($settings['services_items'] as $index => $item) {
			$image_url = !empty($item['item_image']['url']) ? $item['item_image']['url'] : '';
			$title = !empty($item['item_title']) ? $item['item_title'] : '';
			$description = !empty($item['item_description']) ? $item['item_description'] : '';
			$link = !empty($item['item_link']['url']) ? $item['item_link']['url'] : '';
			$target = !empty($item['item_link']['is_external']) ? ' target="_blank"' : '';
			$nofollow = !empty($item['item_link']['nofollow']) ? ' rel="nofollow"' : '';

			$output .= '<div class="slide-item" data-slide-index="' . esc_attr($index) . '">';
			if ($link) $output .= '<a href="' . esc_url($link) . '"' . $target . $nofollow . '>';
			
			if ($image_url) {
				$output .= '<div class="item-image">';
				$output .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($title ? $title : $description) . '">';
				$output .= '</div>';
			}
			
			$output .= '<div class="item-description">';
			if ($title) $output .= '<h3 class="item-title">' . esc_html($title) . '</h3>';
			if ($description) $output .= '<div class="item-text">' . wp_kses_post($description) . '</div>';
			$output .= '</div>';
			
			if ($link) $output .= '</a>';
			
			$output .= '</div>';
		}

		$output .= '</div>'; // End main-carousel

		// Navigation thumbnails
		$output .= '<div class="navigation-thumbnails">';

		foreach ($settings['services_items'] as $index => $item) {
			$image_url = !empty($item['item_image']['url']) ? $item['item_image']['url'] : '';
			$active_class = ($index === 0) ? ' active' : '';
			
			if ($image_url) {
				$output .= '<div class="thumbnail-item' . $active_class . '" data-slide="' . esc_attr($index) . '">';
				$output .= '<img src="' . esc_url($image_url) . '" alt="Thumbnail ' . esc_attr($index + 1) . '">';
				$output .= '</div>';
			}
		}

		$output .= '</div>'; // End navigation-thumbnails

		$output .= '</div>'; // End services-slide

		echo $output;
	}
}
