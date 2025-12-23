<?php
	defined('ABSPATH') || exit; // Ngăn truy cập trái phép

	if (empty($args['item'])) return;

	$item = $args['item'];
	$custom_field = get_post_meta($item->ID);
	$col = 'col-lg-4';
	$permalink = get_permalink($item->ID);

	// if (!empty($args['index'])) {
	// 	if (in_array($args['index'], [2,6])) $col = 'col-lg-8';
	// }
?>

<a class="item <?= $col ?> product" href="<?= $permalink ?>">
	<div class="image">
		<?= get_the_post_thumbnail($item, 'full') ?>
		<!-- <a class="bg-detail" href="<?= $permalink ?>">Chi tiết</a> -->
	</div>
	<div class="content">
		<div>
			<h4><?= $item->post_title ?></h4>
			<ul>
				<li>Diện tích đất: <?= $custom_field['land_area'][0] ?></li>
				<li>Diện tích xây dựng: <?= $custom_field['construction_area'][0] ?></li>
			</ul>
		</div>
	</div>
</a>