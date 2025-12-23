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