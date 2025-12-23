<?php
	get_header();

	$websiteName = get_bloginfo('name');
	$product_id = get_queried_object_id();
	$product = wc_get_product( $product_id );
	$images = [];
	$gallery_image_ids = $product->get_gallery_image_ids();

	if (!empty($gallery_image_ids)) {
		foreach ( $gallery_image_ids as $image_id ) {
			$images[] = wp_get_attachment_url( $image_id );
		}
	}

	$category_ids = $product->get_category_ids();
	$category = !empty($category_ids[0]) ? get_term( $category_ids[0], 'product_cat' ) : [];
	$tag_ids = $product->get_tag_ids();;

	$cf_data = get_post_meta($product_id);
	$cf_product = array_map(function($value) {
		return $value[0];
	}, $cf_data);

	$args = array(
        'post_type' => 'product',
        'posts_per_page' => 4,
        'post__not_in' => array( $product_id ),
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'id',
                'terms'    => $category_ids,
                'operator' => 'IN',
            ),
        ),
    );
	$related_products = new WP_Query( $args );
	$description = nl2br($product->get_description());

	$argsHot = array(
		'post_type'  => 'product',
		'post_status'  => 'publish',
		'meta_query' => array(
			array(
				'key'   => 'hot',
				'value' => '1',
				'compare' => '='
			)
			),
		'posts_per_page' => 4
	);
	$product_hot = new WP_Query($argsHot);
	$product_hot = !empty($product_hot->posts) ? $product_hot->posts : [];

	$product_prev = get_product_near($product_id, 'prev');
	$product_next = get_product_near($product_id, 'next');
?>
<div class="page">
	<div class="breadcrumb">
		<?php
			if ( function_exists('yoast_breadcrumb') ) {
				yoast_breadcrumb();
			}
		?>
	</div>
	<hr>
	<div class="row">
		<div class="col-lg-9">
			<div class="row mb-4">
				<div class="col-lg-6 slide">
					<?php if (!empty($images)): ?>
						<div class="big-image owl-carousel owl-theme position-relative">
							<?php foreach ($images as $k => $image): ?>
								<a class="item d-block" data-fancybox="gallery" href="<?= $image ?>"><img src="<?= $image ?>" alt="slide-<?= $k ?>" loading="lazy"></a>
							<?php endforeach ?>
						</div>
						<div class="list-image owl-carousel owl-theme position-relative">
							<?php foreach ($images as $k => $image): ?>
								<div class="item gallery-<?= $k+1 ?> <?= $k == 0 ? 'selected' : '' ?>" data-slide="<?= $k ?>"><img src="<?= $image ?>" alt="gallery-<?= $k ?>" loading="lazy"></div>
							<?php endforeach ?>
						</div>
					<?php endif ?>
				</div>
				<div class="col-lg-6 content">
					<div class="product-name">
						<h1><?= $product->get_name().(!empty($product->get_sku()) ? ' - '.$product->get_sku() : '')?></h1>
						<div class="product-near">
							<?php if (!empty($product_prev)): ?>
								<a class="item product-prev" href="<?= get_permalink($product_prev->ID) ?>">
									<i class="fa-solid fa-angle-left"></i>
									<div class="popup">
										<img src="<?= $product_prev->image ?>" alt="<?= $product_prev->name ?>" loading="lazy">
										<span class="name"><?= $product_prev->name ?></span>
									</div>
								</a>
							<?php endif ?>
							<?php if (!empty($product_next)): ?>
								<a class="item product-next" href="<?= get_permalink($product_next->ID) ?>">
									<i class="fa-solid fa-angle-right"></i>
									<div class="popup">
										<img src="<?= $product_next->image ?>" alt="<?= $product_next->name ?>" loading="lazy">
										<span class="name"><?= $product_next->name ?></span>
									</div>
								</a>
							<?php endif ?>
						</div>
					</div>
					<h1 class="product-price"><?= wc_price($product->get_price()) ?></h1>
					<?php if (!empty($cf_product['youtube'])): ?>
						<div class="youtube">
							<?= $cf_product['youtube'] ?>
						</div>
					<?php endif?>
					<?php if (!empty($product->get_sku())): ?>
						<div class="product-sku">
							<p class="mb-0">SKU: <span><?= $product->get_sku() ?></span></p>
						</div>
					<?php endif ?>
					<?php if (!empty($product->get_sku())): ?>
						<div class="product-sku">
							<p class="mb-0">Kích thước (m): <span><?= $cf_product['size'] ?></span></p>
						</div>
					<?php endif ?>
					<?php if (!empty($category_ids)): ?>
						<div class="product-category">
							<p class="mb-0">Danh mục: <?= wc_get_product_category_list($product_id) ?></p>
						</div>
					<?php endif ?>
					<?php if (!empty($tag_ids)): ?>
						<div class="product-tag">
							<p class="mb-0">Tags:</p>
							<div>
								<?php foreach ($tag_ids as $k => $tag_id): 
									$term = get_term($tag_id, 'product_tag');
								?>
									<a href="<?= esc_url(get_term_link($term)) ?>"><?= $k != 0 ? ', ' : '' ?><?= esc_html($term->name) ?></a>
								<?php endforeach ?>
							</div>
						</div>
					<?php endif ?>
					<!-- <hr> -->
					<!-- <div class="product-cart">
						<div class="quantity input-group">
							<span class="minus"><i class="fa-solid fa-minus"></i></span>
							<input type="number" class="form-control" min="1" max="100" step="1" value="1">
							<span class="plus"><i class="fa-solid fa-plus"></i></span>
						</div>
						<button class="btn btn-dark" type="button"><i class="fa-solid fa-cart-shopping"></i> Thêm vào giỏ hàng</button>
						<button class="btn btn-dark" type="button">Mua ngay</button>
					</div> -->
					<hr>
					<div class="social-share d-flex">
						<p class="mb-0">Chia sẻ</p>
						<div class="d-flex gap-4">
							<a href="https://facebook.com" target="_blank"><i class="fa-brands fa-facebook"></i></a>
							<a href="https://linkedin.com" target="_blank"><i class="fa-brands fa-linkedin-in"></i></a>
							<a href="https://twitter.com" target="_blank"><i class="fa-brands fa-twitter"></i></a>
						</div>
					</div>
				</div>
			</div>
			<div class="tabs">
				<nav class="mb-4">
					<div class="nav nav-tabs" id="nav-tab" role="tablist">
						<button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab" aria-controls="description" aria-selected="true">MÔ TẢ</button>
						<button class="nav-link" id="include-tab" data-bs-toggle="tab" data-bs-target="#include" type="button" role="tab" aria-controls="include" aria-selected="false">BẠN NHẬN ĐƯỢC GÌ</button>
						<button class="nav-link" id="not-include-tab" data-bs-toggle="tab" data-bs-target="#not-include" type="button" role="tab" aria-controls="not-include" aria-selected="false">KHÔNG BAO GỒM</button>
					</div>
				</nav>
				<div class="tab-content" id="nav-tabContent">
					<div class="tab-pane fade show active" id="description" role="tabpanel" aria-labelledby="description-tab">
						<?= $description ?>
					</div>
					<div class="tab-pane fade" id="include" role="tabpanel" aria-labelledby="include-tab">
						<?= nl2br($cf_product['include']) ?>
					</div>
					<div class="tab-pane fade" id="not-include" role="tabpanel" aria-labelledby="not-include-tab">
						<?= nl2br($cf_product['not_include']) ?>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-3">
			<div class="list-product-hot">
				<h5>Sản phẩm nổi bật</h5>
				<div class="product-hot">
					<?php if (!empty($product_hot)): ?>
						<?php foreach ($product_hot as $k => $v):
							$product = wc_get_product($v->ID);
						?>
							<a class="item mb-3" href="<?= $product->get_permalink() ?>">
								<?= $product->get_image('thumbnail') ?>
								<div class="info">
									<p class="name"><?= $product->get_name() ?></p>
									<p class="price"><?= !empty($product->get_price()) ? wc_price($product->get_price()) : 'Liên hệ' ?></p>
								</div>
							</a>
						<?php endforeach ?>
					<?php endif ?>
				</div>
			</div>
			</div>
	</div>
	<div class="hr"></div>
	<?php if ($related_products->have_posts()): ?>
		<div class="product-related margin-section">
			<h5 class="title text-uppercase mb-0">Sản phẩm liên quan</h5>
			<hr>
			<div class="list owl-carousel owl-theme">
				<?php while ($related_products->have_posts()): ?>
					<?php 
						$id = get_the_ID();
						$related_products->the_post();
						$data = wc_get_product( $id );
						$image = get_the_post_thumbnail_url( $id, 'full' );
					?>
					<div class="item product">
						<a class="image" href="<?= $data->get_permalink() ?>">
							<img src="<?= $image ?>" alt="<?= $data->get_name() ?>" loading="lazy">
						</a>
						<div class="content">
							<div class="categories">
								<?= wc_get_product_category_list($id) ?>
							</div>
							<p class="name"><?= $data->get_name() ?></p>
							<p class="price"><?= !empty($data->get_price()) ? wc_price($data->get_price()) : 'Liên hệ' ?></p>
							<!-- <div class="btn-buy">
								<?php if (!empty($product->get_price())): ?>
									<button class="btn btn-outline-dark btn-sm" type="button"><i class="fa-solid fa-cart-shopping"></i> Thêm vào giỏ hàng</button>
									<button class="btn btn-dark" type="button">Mua ngay</button>
								<?php endif ?>
							</div> -->
						</div>
					</div>
				<?php endwhile ?>
			</div>
		</div>
	<?php endif ?>
</div>
	
<?php
	get_footer();
?>