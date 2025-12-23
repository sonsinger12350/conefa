<?php
	/**
	 * Template Name: Tin Tức
	 */
	$limit = 8;

	if (!empty($_GET['isAjax'])) {
		$data = '';
		$page = $_GET['nextPage'];
		$excludeIds = !empty($_GET['excludeIds']) ? explode(',', $_GET['excludeIds']) : [];

		$args = array(
			'post_type' => 'post',
			'posts_per_page' => $limit,
			'post_status' => 'publish',
			'post__not_in' => $excludeIds,
			'paged' => $page
		);

		$query = new WP_Query($args);

		if (empty($query->posts)) {
			wp_send_json_success($data);exit;
		}

		$html = '';

		foreach ($query->posts as $article) {
			ob_start();
			get_template_part('template-parts/article', null, ['article' => $article]);
			$html .= ob_get_clean();
		}

		$data = $html;

		wp_send_json_success($data);
		exit;
	}

	get_header();
	$websiteName = get_bloginfo('name');
	$default_img = get_template_directory_uri().'/assets/images/new-default.jpg';

	$args = array(
		'orderby'    => 'id',
		'order'      => 'ASC',
		'hide_empty' => false
	);
	$categories = get_categories($args);

	$args = array(
		'post_type' => 'post',
		'posts_per_page' => 4,
		'post_status' => 'publish'
	);
	$list_first_news = new WP_Query($args);
	$first_news = $list_first_news->have_posts() ? $list_first_news->posts[0] : [];
	$first_news_cats = !empty($first_news) ? get_the_category($first_news->ID) : [];
	$first_news_img = !empty($first_news) ? get_the_post_thumbnail_url($first_news->ID, 'full') : '';

	$list_new = [];
	$total_page = 0;
	$excludeIds = array_map(function($val) {
		return $val->ID;
	}, $list_first_news->posts);

	if (count($list_first_news->posts) == 4) {
		$args = array(
			'post_type' => 'post',
			'posts_per_page' => $limit,
			'post_status' => 'publish',
			'post__not_in' => $excludeIds,
			'paged' => 1
		);
		$query = new WP_Query($args);

		if (!empty($query->posts)) {
			$list_new = $query->posts;
			$total_page = $query->max_num_pages;
		}
	}
?>
<style>
	.banner .bg-banner {
		background-image: url("<?= get_template_directory_uri(); ?>/assets/images/news-header.jpg");
	}
</style>
<div class="banner">
	<div class="bg-header"></div>
	<div class="bg-banner">
		<h1 class="block-title">Tin tức - Sự kiện</h1>
	</div>
</div>

<div class="page">
	<div class="categories custom-scrollbar">
		<a class="item active" href="<?= home_url('blog') ?>">Tất cả</a>
		<?php if (!empty($categories)): ?>
			<?php foreach ($categories as $category): ?>
				<a class="item" href="<?= home_url($category->slug) ?>"><?= $category->name ?></a>
			<?php endforeach ?>
		<?php endif ?>
	</div>
	<div class="news">
		<?php if ($list_first_news->have_posts()) : ?>
			<div class="first-news">
				<div class="left">
					<a class="new" href="<?= $first_news->post_name ?>" style="background-image: url('<?= !empty($first_news_img) ? $first_news_img : $default_img ?>')">
						<div class="content">
							<h5 class="title"><?= $first_news->post_title ?></h5>
							<div class="d-flex justify-content-between align-items-center">
								<div class="list-category">
									<?php if (!empty($first_news_cats[0])): ?>
										<span class="category"><?= $first_news_cats[0]->name ?></span>
									<?php endif ?>
								</div>
								<div class="post-date">
									<?= date('d/m/Y', strtotime($first_news->post_date)) ?>
								</div>
							</div>
						</div>
					</a>
				</div>
				<div class="right">
					<?php 
						foreach ($list_first_news->posts as $k => $article) {
							if ($k == 0) continue;
							get_template_part('template-parts/article', null, ['article' => $article]);
						}
					?>		
				</div>
			</div>
			<?php if (!empty($list_new)) : ?>
				<div class="list-news">
					<div class="left">
						<div class="list">
							<?php 
								foreach ($list_new as $article) {
									get_template_part('template-parts/article', null, ['article' => $article]);
								}
							?>
						</div>
						<?php if ($total_page > 1): ?>
							<div class="pagination" data-url="<?= home_url('blog/') ?>" data-ids="<?= implode(',', $excludeIds) ?>">
								<div class="btn-paginate" data-action="prev"><i class="fa-solid fa-angle-left"></i></div>
								<div class="list-page">
									<?php for ($i = 1; $i <= $total_page; $i++): ?>
										<span class="item <?= $i==1 ? 'active' : '' ?> <?= $i==$total_page ? 'last' : '' ?>" data-page="<?= $i ?>"></span>
									<?php endfor?>
								</div>
								<div class="btn-paginate" data-action="next"><i class="fa-solid fa-angle-right"></i></div>
							</div>
						<?php endif ?>
					</div>
					<div class="right">
						<div class="ads-banner">
							<img src="<?= get_template_directory_uri(); ?>/assets/images/ads-bg.jpg" alt="ads">
						</div>
						<div class="form-contact">
							<h5 class="title">Cập nhật tin tức<br>mới nhất từ <?= $websiteName ?></h5>
							<form action="" enctype="multipart/form-data" novalidate>
								<input type="hidden" name="type" value="register-news">
								<div class="input">
									<input type="text" name="full_name" placeholder="Họ và tên" required>
								</div>
								<div class="input">
									<input type="phone" name="phone" placeholder="Số điện thoại" required>
								</div>
								<button class="btn btn-success btn-explore" type="submit">Đăng ký <i class="fa-solid fa-angle-right"></i></button>
							</form>
						</div>
					</div>
				</div>
			<?php endif; ?>
		<?php else : ?>
			<p>Không có bài viết nào đã xuất bản.</p>
		<?php endif; ?>
	</div>

<?php wp_reset_postdata(); // Khôi phục dữ liệu gốc sau vòng lặp ?>
</div>

	
<?php
	get_footer();
?>