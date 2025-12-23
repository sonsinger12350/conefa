<?php
	defined('ABSPATH') || exit; // Ngăn truy cập trái phép
	if (empty($args['article'])) return;
	$article = $args['article'];
	$article_img = get_the_post_thumbnail_url($article->ID, 'full');
	$cat = get_the_category($article->ID);
	$default_img = get_template_directory_uri().'/assets/images/new-default.jpg';
?>

<a class="new" href="<?= get_the_permalink($article->ID) ?>">
	<div class="image">
		<img src="<?= !empty($article_img) ? $article_img : $default_img ?>" alt="<?= $article->post_title ?>">
	</div>
	<div class="content">
		<?php if (!empty($cat)): ?>
			<p class="category"><?= $cat[0]->name ?></p>
		<?php endif ?>
		<p class="title"><?= $article->post_title ?></p>
		<p class="post-date"><?= date('d/m/Y', strtotime($article->post_date)) ?></p>
	</div>
</a>