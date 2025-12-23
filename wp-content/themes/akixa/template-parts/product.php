<?php
	defined('ABSPATH') || exit;

	if (empty($args['product'])) return;

	$product = wc_get_product($args['product']);
	$product_id = $product->get_id();
	$categories = get_the_terms($product_id, 'product_cat');
	$cf_product = get_post_meta($product_id);
	$cols = !empty($args['cols']) ? $args['cols'] : 'col-xxl-4 col-lg-6 col-sm-6';
?>

<div class="item product <?= $cols ?>">
	<a class="image" href="<?= $product->get_permalink() ?>">
		<?= $product->get_image('full') ?>
	</a>
	<div class="content">
		<div>
			<div class="category-list">
				<?php if (!empty($categories)): ?>
					<?php foreach ($categories as $k => $category): ?><a href="<?= get_term_link($category) ?>"><?= $k != 0 ? ',' : '' ?> <?= $category->name ?></a><?php endforeach ?>
				<?php endif ?>
			</div>
			<a class="name" href="<?= $product->get_permalink() ?>"><?= $product->get_name() ?></a>
			<p class="price mb-3"><?= !empty($product->get_price()) ? wc_price($product->get_price()) : 'Liên hệ' ?></p>
			<!-- <div class="btn-buy">
				<?php if (!empty($product->get_price())): ?>
					<button class="btn btn-outline-dark btn-sm" type="button"><i class="fa-solid fa-cart-shopping"></i> Thêm vào giỏ hàng</button>
					<button class="btn btn-dark" type="button">Mua ngay</button>
				<?php endif ?>
			</div> -->
		</div>
	</div>
</div>