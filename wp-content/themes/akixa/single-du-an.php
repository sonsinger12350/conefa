<?php
	get_header();

	global $post;

	$websiteName = get_bloginfo('name');

	$categories = get_the_terms($post->ID, 'danh-muc-du-an');
	$category_ids = array_map(function($category) {
		return $category->term_id;
	}, $categories);
	$category = !empty($categories[0]) ? $categories[0] : [];
	$cf_data = get_post_meta($post->ID);
	$custom_field = array_map(function($value) {
		return $value[0];
	}, $cf_data);

	$args = array(
        'post_type' => 'du-an',
        'posts_per_page' => 4,
        'post__not_in' => array( $post->ID ),
        'tax_query' => array(
            array(
                'taxonomy' => 'danh-muc-du-an',
                'field'    => 'id',
                'terms'    => $category_ids,
                'operator' => 'IN',
            ),
        ),
    );
	$related_data = new WP_Query( $args );
	$block_content = parse_blocks($post->post_content);
	$gallery_block = [];
	$content_block = [];
	$gallery = [];
	$content = '';

	foreach ($block_content as $block) {
		if (empty($block['blockName'])) continue;

		if ($block['blockName'] === 'core/gallery') $gallery_block = $block;
		else $content_block[] = $block;
	}

	if (!empty($content_block)) {
		foreach ($content_block as $block) {
			$content .= render_block($block);
		}
	}

	if (!empty($gallery_block['innerBlocks'])) {
		foreach ($gallery_block['innerBlocks'] as $block) {
			if ($block['blockName'] === 'core/image') $gallery[] = wp_get_attachment_url($block['attrs']['id']);
		}
	}
?>

<div class="page">
	<div class="product-info">
		<div class="left">
			<div class="content">
				<h1 class="block-title"><?= $post->name?></h1>
				<?php if (!empty($category)): ?>
					<div class="category">
						<p class="mb-0 title">Loại công trình</p>
						<p class="mb-0 info"><?= $category->name ?></p>
					</div>
				<?php endif ?>
				<div class="complete-time">
					<p class="mb-0 title">Thời gian hoàn thành</p>
					<p class="mb-0 info"><?= $custom_field['complete_time'] ?></p>
				</div>
			</div>
			<div class="slide">
				<?php if (!empty($gallery)): ?>
					<div class="owl-carousel owl-theme position-relative">
						<?php foreach ($gallery as $k => $image): ?>
							<a data-fancybox="gallery" href="<?= $image ?>"><img src="<?= $image ?>" alt="slide-<?= $k ?>" loading="lazy"></a>
						<?php endforeach ?>
					</div>
					<span class="owl-dot-number"></span>
				<?php endif ?>
			</div>
		</div>
		<div class="right">
			<div class="list-image custom-scrollbar">
				<?php if (!empty($gallery)): ?>
					<?php foreach ($gallery as $k => $image): ?>
						<div class="item gallery-<?= $k+1 ?>" data-slide="<?= $k ?>"><img src="<?= $image ?>" alt="gallery-<?= $k ?>" loading="lazy"></div>
					<?php endforeach ?>
				<?php endif ?>
			</div>
		</div>
	</div>
	<div class="product-content margin-section">
		<div class="left">
			<div class="general-info">
				<h3 class="block-title">Thông tin dự án</h3>
				<div class="detail">
					<p class="title">Địa điểm</p>
					<p class="mb-0 info"><?= $custom_field['address'] ?></p>
				</div>
				<div class="detail">
					<p class="title">Diện tích đất</p>
					<p class="mb-0 info"><?= $custom_field['land_area'] ?></p>
				</div>
				<div class="detail">
					<p class="title">Diện tích xây dựng</p>
					<p class="mb-0 info"><?= $custom_field['construction_area'] ?></p>
				</div>
			</div>
			<div class="hr"></div>
			<div class="product-description">
				<?= $content ?>
			</div>
		</div>
		<div class="right">
			<div class="contact">
				<h5 class="title">Bạn thích dự án<br>thiết kế này?</h5>
				<div>
					<p class="desc">Đăng ký ngay<br>để nhận tư vấn</p>
					<?= get_template_part('template-parts/btn-explore', null, ['type' => 'register']); ?>
				</div>
			</div>
		</div>
	</div>
	<div class="hr"></div>
	<?php if (!empty($related_data->posts)): ?>
		<div class="product-related margin-section">
			<h5 class="title text-uppercase">Gợi ý<br>công trình khác<br>cùng hạng mục</h5>
			<div class="list-related">
				<div class="list custom-scrollbar">
					<?php foreach ($related_data->posts as $article): ?>
						<div class="item product">
							<div class="image">
								<img src="<?= get_the_post_thumbnail_url( $article->ID, 'full' ) ?>" alt="<?= $article->post_title ?>" loading="lazy">
								<a class="bg-detail" href="<?= get_permalink($article->ID) ?>">Chi tiết</a>
							</div>
							<div class="content">
								<p class="title"><?= $article->post_title ?></p>
							</div>
						</div>
					<?php endforeach ?>
				</div>
				<div class="bg-overlay"></div>
			</div>
		</div>
	<?php endif ?>
	<?php if (!empty($_SESSION['product_recently'])): ?>
		<div class="hr"></div>
		<div class="product-recently margin-section mb-4">
			<h5 class="title text-uppercase">Dự án<br>gần nhất<br>bạn đã xem</h5>
			<div class="list-recently">
				<div class="list custom-scrollbar">
					<?php 
						foreach ($_SESSION['product_recently'] as $id): 
						$data = wc_get_product($id);
						$image = get_the_post_thumbnail_url($id, 'full');
					?>
						<div class="item">
							<div class="image">
								<img src="<?= $image ?>" alt="<?= $data->name ?>" loading="lazy">
								<div class="name"><?= $data->name ?></div>
							</div>
						</div>
					<?php endforeach ?>
				</div>
				<div class="bg-overlay"></div>
			</div>
		</div>
	<?php endif ?>
</div>
<div class="form-contact margin-section d-none d-md-flex">
	<h3 class="title">Để lại thông tin nhận<br>tư vấn miễn phí</h3>
	<form id="contactForm" action="post" enctype="multipart/form-data" novalidate>
		<input type="hidden" name="type" value="get-advice">
		<div class="input">
			<input type="text" class="d-block mb-2" name="full_name" placeholder="Họ và tên" required>
		</div>
		<div class="input">
			<input type="text" class="d-block" name="phone" placeholder="Số điện thoại" required>
		</div>
	</form>
	<button class="btn btn-success btn-explore" type="submit" form="contactForm">Gửi cho <?= $websiteName ?> <i class="fa-solid fa-angle-right"></i></button>
</div>
	
<?php
	get_footer();
?>