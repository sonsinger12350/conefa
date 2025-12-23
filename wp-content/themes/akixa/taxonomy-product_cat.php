<?php
	$limit = 12;
	$current_cat = get_queried_object();
	$category = !empty($current_cat) ? $current_cat->term_id : '';
	$keyword = !empty($_GET['keyword']) ? sanitize_text_field($_GET['keyword']) : '';

	if (!empty($_GET['isAjax'])) {
		$data = [
			'continue' => 0,
			'content' => '',
		];
		$offset = $_GET['offset'];

		if (!$offset) {
			wp_send_json_success($data);exit;
		}

		$args = array(
			'limit' => $limit,
			'offset' => $offset
		);
		if (!empty($category)) $args['category'] = [$category];
		if (!empty($keyword)) $args['s'] = $keyword;
	
		$products = wc_get_products($args);

		if (empty($products)) {
			wp_send_json_success($data);exit;
		}

		$html = '';

		foreach ($products as $k => $product) {
			ob_start();
			get_template_part('template-parts/product', null, ['product' => $product]);
			$html .= ob_get_clean();
		}

		$data['continue'] = count($products) >= $limit ? 1 : 0;
		$data['content'] = $html;

		wp_send_json_success($data);
		exit;
	}

	get_header();

	$priceRange = getMinMaxSizeProduct();
	$min_price = !empty(site__get('min-size')) ? site__get('min-size') : $priceRange['min'];
	$max_price = !empty(site__get('max-size')) ? site__get('max-size') : $priceRange['max'];

	$websiteName = get_bloginfo('name');

	$args = array(
		'fields' => 'ids',
		'post_type' => 'product',
		'posts_per_page' => $limit,
		'post_status' => 'publish',
	);

	if (!empty($category)) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'id',
				'terms'    => array($category),
			),
		);
	}

	if (!empty($keyword)) $args['s'] = $keyword;

	$meta_query = ['relation' => 'AND'];

	if (!empty(site__get('min-size'))) {
		$meta_query[] = array(
			'key'     => 'size',
			'value'   => $min_price,
			'compare' => '>=',
			'type'    => 'NUMERIC',
		);
	}

	if (!empty(site__get('max-size'))) {
		$meta_query[] = array(
			'key'     => 'size',
			'value'   => $max_price,
			'compare' => '<=',
			'type'    => 'NUMERIC',
		);
	}

	if (!empty($meta_query)) $args['meta_query'] = $meta_query;

	$query = new WP_Query($args);
	$products = $query->posts;

	$categories = get_terms(array(
		'taxonomy' => 'product_cat',
		'hide_empty' => false,
		'orderby' => 'menu_order',
		'order' => 'ASC',
	));
	$all_category = (object) array(
		'term_id' => 0,
		'name' => 'Tất cả',
		'slug' => 'tat-ca',
	);

	array_unshift($categories, $all_category);

	$showBtnLoadMore = 0;
	if (count($products) >= $limit) $showBtnLoadMore = 1;

	$slides = !empty($post) ? get_post_gallery($post->ID, false) : [];
	if (!empty($slides)) $slides = $slides['src'];

	$categories_tree = get_product_categories_tree();
?>
<script>
	var priceRange = <?= json_encode($priceRange) ?>;
	var current_min_price = <?= $min_price ?>;
	var current_max_price = <?= $max_price ?>;
</script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.14.1/themes/base/jquery-ui.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.14.1/jquery-ui.min.js"></script>
<div class="page">
	<div class="container">
		<div class="breadcrumb">
			<?php
				if ( function_exists('yoast_breadcrumb') ) {
					yoast_breadcrumb();
				}
			?>
		</div>
		<div class="body">
			<?php include('wp-content/themes/akixa/template-parts/list-product-content.php') ?>
		</div>
	</div>
</div>
<?php
	get_footer();
?>