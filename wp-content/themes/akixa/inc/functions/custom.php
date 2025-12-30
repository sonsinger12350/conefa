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