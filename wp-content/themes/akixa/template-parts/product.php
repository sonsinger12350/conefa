<?php
	defined('ABSPATH') || exit;

	if (empty($args['product'])) return;

	$product     = wc_get_product($args['product']);
	$product_id  = $product->get_id();
	$categories  = get_the_terms($product_id, 'product_cat');
	$cf_product  = get_post_meta($product_id);
	$cols        = !empty($args['cols']) ? $args['cols'] : 'col-xxl-4 col-lg-6 col-sm-6';

	$star_rating    = get_post_meta($product_id, '_akixa_star_rating', true);
	$purchase_count = get_post_meta($product_id, '_akixa_purchase_count', true);
	$consult_url    = get_option('akixa_consult_url', '');
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

			<?php if ($star_rating !== '' && $star_rating !== false && (float)$star_rating > 0): ?>
				<?= akixa_render_stars((float)$star_rating) ?>
			<?php endif ?>

			<?php if ($purchase_count !== '' && $purchase_count !== false && (int)$purchase_count > 0): ?>
				<p class="product-purchase-count">
					<span class="count-number"><?= number_format((int)$purchase_count) ?></span> lượt mua
				</p>
			<?php endif ?>

			<p class="price mb-3"><?= !empty($product->get_price()) ? wc_price($product->get_price()) : 'Liên hệ' ?></p>
			<div class="btn-buy">
				<?php if (!empty($product->get_price())): ?>
					<a class="btn btn-dark" href="<?= home_url('thanh-toan?id='.$product_id) ?>">Mua ngay</a>
				<?php endif ?>
				<a class="btn btn-outline-dark btn-consult" href="<?= esc_url($consult_url) ?>">Tư vấn</a>
			</div>
		</div>
	</div>
</div>