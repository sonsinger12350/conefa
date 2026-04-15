<?php
if (!defined('ABSPATH')) {
    exit;
}

function site__get( $name = '', $default = '' ) {
	$value = $default;

	if( isset($_GET[$name]) ) {
		if( is_array($default) ) {
			return array_map('sanitize_text_field', $_GET[$name]);
		}
		
		$value = sanitize_text_field( $_GET[$name] );
		if( is_numeric($default) ) {
			$value = (int) $value;
		}
	}

	return $value;
}

function get_product_near( $id, $type ) {
	if (empty($id) || empty($type)) return null;

	global $wpdb;

	$where = $type == 'prev' ? " AND p.ID < $id " : " AND p.ID > $id ";

	$sql = "SELECT 
		p.ID, 
		p.post_title AS name
		FROM wp_posts p 
		WHERE p.post_type = 'product' AND p.post_status = 'publish' $where
		ORDER BY p.ID ASC 
		LIMIT 1
	";
	$result = $wpdb->get_results($sql);

	if (empty($result)) return null;
	
	$result[0]->image = get_the_post_thumbnail_url($result[0]->ID, 'thumbnail');

	return $result[0];
}

function get_product_categories_tree() {
    $args = array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'parent'     => 0,
    );

    $categories_lv1 = get_terms($args);
    $categories_tree = [];

    if (!empty($categories_lv1) && !is_wp_error($categories_lv1)) {
        foreach ($categories_lv1 as $category_lv1) {
            $args_lv2 = array(
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
                'parent'     => $category_lv1->term_id,
            );

            $image_id_lv1 = get_term_meta($category_lv1->term_id, 'thumbnail_id', true);
            $image_lv1 = !empty($image_id_lv1) ? wp_get_attachment_image($image_id_lv1, 'thumbnail') : '';

            $categories_lv2 = get_terms($args_lv2);

            $categories_tree[$category_lv1->term_id] = array(
                'id'       => $category_lv1->term_id,
                'name'     => $category_lv1->name,
                'slug'     => $category_lv1->slug,
                'count'    => $category_lv1->count,
                'image'    => $image_lv1,
                'link'     => get_term_link($category_lv1->term_id, 'product_cat'),
                'children' => []
            );

            if (!empty($categories_lv2) && !is_wp_error($categories_lv2)) {
                foreach ($categories_lv2 as $category_lv2) {
                    $args_lv3 = array(
                        'taxonomy'   => 'product_cat',
                        'hide_empty' => false,
                        'parent'     => $category_lv2->term_id,
                    );

                    $image_id_lv2 = get_term_meta($category_lv2->term_id, 'thumbnail_id', true);
                    $image_lv2 = !empty($image_id_lv2) ? wp_get_attachment_image($image_id_lv2, 'thumbnail') : '';

                    $categories_lv3 = get_terms($args_lv3);

                    $categories_tree[$category_lv1->term_id]['children'][$category_lv2->term_id] = array(
                        'id'       => $category_lv2->term_id,
                        'name'     => $category_lv2->name,
                        'slug'     => $category_lv2->slug,
                        'count'    => $category_lv2->count,
                        'image'    => $image_lv2,
                        'link'     => get_term_link($category_lv2->term_id, 'product_cat'),
                        'children' => []
                    );

                    if (!empty($categories_lv3) && !is_wp_error($categories_lv3)) {
                        foreach ($categories_lv3 as $category_lv3) {
                            $image_id_lv3 = get_term_meta($category_lv3->term_id, 'thumbnail_id', true);
                            $image_lv3 = !empty($image_id_lv3) ? wp_get_attachment_image($image_id_lv3, 'thumbnail') : '';

                            $categories_tree[$category_lv1->term_id]['children'][$category_lv2->term_id]['children'][$category_lv3->term_id] = array(
                                'id'    => $category_lv3->term_id,
                                'name'  => $category_lv3->name,
                                'slug'  => $category_lv3->slug,
                                'count' => $category_lv3->count,
                                'image' => $image_lv3,
                                'link'  => get_term_link($category_lv3->term_id, 'product_cat'),
                            );
                        }
                    }
                }
            }
        }
    }

    return $categories_tree;
}

function getMinMaxSizeProduct() {
	global $wpdb;

	$results = $wpdb->get_row("
		SELECT 
			MIN(CAST(pm.meta_value AS UNSIGNED)) AS min,
			MAX(CAST(pm.meta_value AS UNSIGNED)) AS max
		FROM {$wpdb->postmeta} pm
		JOIN {$wpdb->posts} p ON pm.post_id = p.ID
		WHERE pm.meta_key = 'size'
		AND pm.meta_value > 0
		AND p.post_type = 'product'
		AND p.post_status = 'publish'
	");

	$min = 0;
	$max = 0;

	if (!empty($results)) {
		$min = $results->min;
		$max = $results->max;
	}

	return ['min' => $min, 'max' => $max];
}

/**
 * Tìm attachment ID từ URL uploads, có xử lý fallback cho webp/scaled/sized.
 *
 * @param string $url URL ảnh.
 * @return int Attachment ID hoặc 0.
 */
function akixa_find_attachment_id_from_url($url) {
	global $wpdb;

	$url = (string) $url;
	if ($url === '') {
		return 0;
	}

	// Thử cách chuẩn của WP trước.
	$id = attachment_url_to_postid($url);
	if ($id) {
		return (int) $id;
	}

	$upload = wp_upload_dir();
	$base_url_path = wp_parse_url($upload['baseurl'], PHP_URL_PATH);
	$target_path = wp_parse_url($url, PHP_URL_PATH);

	if (empty($base_url_path) || empty($target_path) || strpos($target_path, $base_url_path) !== 0) {
		return 0;
	}

	$relative = ltrim(substr($target_path, strlen($base_url_path)), '/');
	if ($relative === '') {
		return 0;
	}

	$relative = rawurldecode($relative);
	$ext = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
	$name_without_ext = preg_replace('/\.[^.]+$/', '', $relative);
	$without_scaled = preg_replace('/-scaled$/', '', $name_without_ext);
	$without_dimension = preg_replace('/-\d+x\d+$/', '', $without_scaled);

	$candidates = [$relative];
	$ext_candidates = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

	foreach ($ext_candidates as $candidate_ext) {
		$candidates[] = $name_without_ext . '.' . $candidate_ext;
		$candidates[] = $without_scaled . '.' . $candidate_ext;
		$candidates[] = $without_dimension . '.' . $candidate_ext;
	}

	$candidates = array_values(array_unique(array_filter($candidates)));
	if (empty($candidates)) {
		return 0;
	}

	$placeholders = implode(',', array_fill(0, count($candidates), '%s'));
	$sql = $wpdb->prepare(
		"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value IN ($placeholders) LIMIT 1",
		$candidates
	);
	$found_id = (int) $wpdb->get_var($sql);

	if ($found_id > 0) {
		return $found_id;
	}

	return 0;
}

/**
 * Ảnh từ Elementor MEDIA: dùng attachment ID + srcset; fallback URL nếu không có ID.
 *
 * @param array  $media        Phần tử control MEDIA (có 'id', 'url').
 * @param string $size         Tên size đã đăng ký (vd. akixa-card).
 * @param string $alt          Alt text.
 * @param array  $extra_attrs  Thuộc tính bổ sung cho wp_get_attachment_image.
 * @return string HTML img an toàn.
 */
function akixa_elementor_attachment_image($media, $size, $alt, $extra_attrs = []) {
	$id = !empty($media['id']) ? (int) $media['id'] : 0;
	$alt = (string) $alt;

	if (!$id && !empty($media['url'])) {
		$id = akixa_find_attachment_id_from_url($media['url']);
	}

	if ($id) {
		$attrs = array_merge(
			[
				'decoding' => 'async',
				'loading'  => 'lazy',
				'alt'      => $alt,
			],
			$extra_attrs
		);
		return wp_get_attachment_image($id, $size, false, $attrs);
	}

	$url = !empty($media['url']) ? $media['url'] : '';
	if ($url === '') {
		return '';
	}

	return '<img src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '" decoding="async" loading="lazy" />';
}