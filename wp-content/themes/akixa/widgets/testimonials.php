<?php

use Elementor\Controls_Manager;
use Elementor\Repeater;

class Custom_Elementor_Widget_Testimonials extends \Elementor\Widget_Base {

	public function get_name() {
		return 'custom_widget_testimonials';
	}

	public function get_title() {
		return __('Custom Widget Testimonials', 'astra-child');
	}

	public function get_icon() {
		return 'eicon-testimonial-carousel';
	}

	public function get_categories() {
		return ['widget-custom'];
	}

	protected function _register_controls() {
		// --- CONTENT: Testimonials Items ---
		$this->start_controls_section(
			'content_section',
			[
				'label' => __('Nội dung', 'astra-child'),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'profile_image',
			[
				'label' => __('Ảnh đại diện', 'astra-child'),
				'type' => Controls_Manager::MEDIA,
				'default' => [
					'url' => \Elementor\Utils::get_placeholder_image_src(),
				],
			]
		);

		$repeater->add_control(
			'name',
			[
				'label' => __('Tên', 'astra-child'),
				'type' => Controls_Manager::TEXT,
				'default' => __('Tên người dùng', 'astra-child'),
				'placeholder' => __('Nhập tên', 'astra-child'),
			]
		);

		$repeater->add_control(
			'title',
			[
				'label' => __('Chức danh', 'astra-child'),
				'type' => Controls_Manager::TEXT,
				'default' => __('Kiến Trúc Sư', 'astra-child'),
				'placeholder' => __('Nhập chức danh', 'astra-child'),
			]
		);

		$repeater->add_control(
			'rating',
			[
				'label' => __('Đánh giá (sao)', 'astra-child'),
				'type' => Controls_Manager::NUMBER,
				'default' => 5,
				'min' => 1,
				'max' => 5,
				'step' => 1,
			]
		);

		$repeater->add_control(
			'architectural_image',
			[
				'label' => __('Ảnh kiến trúc', 'astra-child'),
				'type' => Controls_Manager::MEDIA,
				'default' => [
					'url' => \Elementor\Utils::get_placeholder_image_src(),
				],
			]
		);

		$repeater->add_control(
			'testimonial_text',
			[
				'label' => __('Nội dung đánh giá', 'astra-child'),
				'type' => Controls_Manager::TEXTAREA,
				'rows' => 6,
				'default' => __('Nhập nội dung đánh giá', 'astra-child'),
				'placeholder' => __('Nhập nội dung đánh giá', 'astra-child'),
			]
		);

		$this->add_control(
			'testimonials_items',
			[
				'label' => __('Danh sách đánh giá', 'astra-child'),
				'type' => Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'default' => [
					[ 
						'name' => __('Tên người dùng', 'astra-child'),
						'title' => __('Chức danh', 'astra-child'),
						'rating' => 5,
						'testimonial_text' => __('Nội dung đánh giá', 'astra-child'),
					],
				],
				'title_field' => '{{{ name }}}',
			]
		);

		$this->add_control(
			'slides_to_show',
			[
				'label' => __('Số slide hiển thị', 'astra-child'),
				'type' => Controls_Manager::NUMBER,
				'default' => 1,
				'min' => 1,
				'max' => 6,
				'step' => 1,
			]
		);

		$this->add_control(
			'autoplay',
			[
				'label' => __('Tự động chạy', 'astra-child'),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __('Có', 'astra-child'),
				'label_off' => __('Không', 'astra-child'),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->add_control(
			'autoplay_timeout',
			[
				'label' => __('Thời gian tự động (ms)', 'astra-child'),
				'type' => Controls_Manager::NUMBER,
				'default' => 5000,
				'min' => 1000,
				'max' => 10000,
				'step' => 500,
				'condition' => [
					'autoplay' => 'yes',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		if (empty($settings['testimonials_items'])) {
			return;
		}

		$widget_id = 'testimonials-slide-' . $this->get_id();
		$slides_to_show = !empty($settings['slides_to_show']) ? intval($settings['slides_to_show']) : 1;
		$autoplay = !empty($settings['autoplay']) && $settings['autoplay'] === 'yes' ? 'true' : 'false';
		$autoplay_timeout = !empty($settings['autoplay_timeout']) ? intval($settings['autoplay_timeout']) : 5000;
		
		$output = '<div class="testimonials-slide" id="' . esc_attr($widget_id) . '" data-slides="' . esc_attr($slides_to_show) . '" data-autoplay="' . esc_attr($autoplay) . '" data-timeout="' . esc_attr($autoplay_timeout) . '">';
		$output .= '<div class="testimonials-carousel main-carousel owl-carousel owl-theme">';

		foreach ($settings['testimonials_items'] as $index => $item) {
			$profile_image_url = !empty($item['profile_image']['url']) ? $item['profile_image']['url'] : '';
			$name = !empty($item['name']) ? $item['name'] : '';
			$title = !empty($item['title']) ? $item['title'] : '';
			$rating = !empty($item['rating']) ? intval($item['rating']) : 5;
			$architectural_image_url = !empty($item['architectural_image']['url']) ? $item['architectural_image']['url'] : '';
			$testimonial_text = !empty($item['testimonial_text']) ? $item['testimonial_text'] : '';

			$output .= '<div class="testimonial-card" data-slide-index="' . esc_attr($index) . '">';
			
			// Profile section
			$output .= '<div class="testimonial-header">';
			if ($profile_image_url) {
				$output .= '<div class="testimonial-profile-image">';
				$output .= '<img src="' . esc_url($profile_image_url) . '" alt="' . esc_attr($name) . '">';
				$output .= '</div>';
			}
			
			$output .= '<div class="testimonial-info">';
			if ($name) {
				$output .= '<h3 class="testimonial-name">' . esc_html($name) . '</h3>';
			}
			if ($title) {
				$output .= '<p class="testimonial-title">' . esc_html($title) . '</p>';
			}
			$output .= '</div>';
			$output .= '</div>'; // End testimonial-header

			// Rating stars
			$output .= '<div class="testimonial-rating">';
			for ($i = 1; $i <= 5; $i++) {
				$star_class = ($i <= $rating) ? 'star filled' : 'star';
				$output .= '<span class="' . esc_attr($star_class) . '">★</span>';
			}
			$output .= '</div>';

			// Architectural image
			if ($architectural_image_url) {
				$output .= '<div class="testimonial-architectural-image">';
				$output .= '<img src="' . esc_url($architectural_image_url) . '" alt="' . esc_attr($name) . ' - Kiến trúc">';
				$output .= '</div>';
			}

			// Testimonial text
			if ($testimonial_text) {
				$output .= '<div class="testimonial-text">';
				$output .= '<p>' . wp_kses_post($testimonial_text) . '</p>';
				$output .= '</div>';
			}

			$output .= '</div>'; // End testimonial-card
		}

		$output .= '</div>'; // End testimonials-carousel
		$output .= '</div>'; // End testimonials-slide

		echo $output;
	}
}

