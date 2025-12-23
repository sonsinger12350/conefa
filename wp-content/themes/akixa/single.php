<?php
	get_header();
	$websiteName = get_bloginfo('name');
	$image = get_the_post_thumbnail_url($post->ID, 'full');
	$category = get_the_category($post->ID);

	$related_posts_args = array(
		'post__not_in'   => array($post->ID),
		'posts_per_page' => 3,
		'orderby'        => 'date',
		'order'          => 'DESC'
	);
	
	$related_posts_query = new WP_Query($related_posts_args);
	$related_posts = !empty($related_posts_query->posts) ? $related_posts_query->posts : [];
?>
<style>
	.banner .bg-banner {
		background-image: url("<?= $image ?>");
	}
</style>
<div class="banner">
	<div class="bg-header"></div>
	<div class="bg-banner">
		<div class="banner-content d-none d-md-block">
			<h1 class="title"><?= $post->post_title ?></h1>
			<div class="content-detail">
				<div class="d-flex">
					<p class="category"><?= $category[0]->name ?></p>
					<p class="post-date d-flex align-items-center gap-2">
						<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
							<circle cx="12" cy="12" r="9.5" stroke="#8EBC43"/>
							<path d="M12 5V13H17" stroke="#8EBC43" stroke-linecap="round"/>
						</svg>
						<?= date('d/m/Y', strtotime($post->post_date)) ?>
					</p>
				</div>
				<div class="social-share d-flex">
					<p>Chia sẻ</p>
					<div class="d-flex gap-4">
						<a href="https://facebook.com" target="_blank"><i class="fa-brands fa-facebook"></i></a>
						<a href="https://linkedin.com" target="_blank"><i class="fa-brands fa-linkedin-in"></i></a>
						<a href="https://twitter.com" target="_blank"><i class="fa-brands fa-twitter"></i></a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="banner-content d-block d-md-none">
		<h1 class="title"><?= $post->post_title ?></h1>
		<div class="content-detail">
			<div class="d-flex justify-content-between align-items-center mb-4">
				<p class="category"><?= $category[0]->name ?></p>
				<p class="post-date"><?= date('d/m/Y', strtotime($post->post_date)) ?></p>
			</div>
			<div class="social-share d-flex align-items-center gap-4">
				<p>Chia sẻ</p>
				<div class="d-flex gap-4">
					<a href="https://facebook.com" target="_blank"><i class="fa-brands fa-facebook"></i></a>
					<a href="https://linkedin.com" target="_blank"><i class="fa-brands fa-linkedin-in"></i></a>
					<a href="https://twitter.com" target="_blank"><i class="fa-brands fa-twitter"></i></a>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="post-content">
	<div class="left">
		<div class="content">
			<?php the_content(); ?>
		</div>
		<div class="social-share">
			<p class="mb-0">Chia sẻ</p>
			<div class="d-flex gap-4">
				<a href="https://facebook.com" target="_blank"><i class="fa-brands fa-facebook"></i></a>
				<a href="https://linkedin.com" target="_blank"><i class="fa-brands fa-linkedin-in"></i></a>
				<a href="https://twitter.com" target="_blank"><i class="fa-brands fa-twitter"></i></a>
			</div>
		</div>
	</div>
	<div class="right">
		<?php if (!empty($related_posts)): ?>
			<div class="related-post">
				<p class="title">Có thể bạn quan tâm</p>
				<div class="list">
					<?php 
						foreach ($related_posts as $p): 
							$cat = get_the_category($p->ID);
					?>
						<a class="item" href="<?= get_the_permalink($p->ID) ?>">
							<p class="category mb-0"><?= $cat[0]->name ?></p>
							<p class="post-title mb-0"><?= $p->post_title ?></p>
							<p class="post-date mb-0"><?= date('d/m/Y', strtotime($p->post_date)) ?></p>
						</a>
					<?php endforeach ?>
				</div>
			</div>
		<?php endif ?>
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

	
<?php
	get_footer();
?>