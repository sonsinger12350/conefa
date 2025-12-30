<?php
	$websiteName = get_bloginfo('name');
	$config = getConnestConfig();
?>
	<footer>
		<div class="footer-content">
			<div class="logo">
				<img src="<?= get_template_directory_uri(); ?>/assets/images/logo-white.png?v=1" alt="">
				<?= get_template_part('template-parts/btn-explore', null, ['type' => 'register']); ?>
			</div>
			<div class="content">
				<div class="menu-footer">
					<div class="d-flex justify-content-between align-items-start">
						<div class="row">
							<div class="col-lg-3 col-6">
								<p class="fw-bold">Về <?= $websiteName ?></p>
								<!-- <a href="<?= home_url( '/ve-connest' ) ?>#thong-diep">Thông điệp nhà sáng lập</a> -->
								<a href="<?= home_url( '/ve-connest' ) ?>#tam-nhin">Tầm nhìn - Sứ mệnh</a>
								<a href="<?= home_url( '/ve-connest' ) ?>#doi-ngu">Đội ngũ</a>
							</div>
							<div class="col-lg-3 col-6">
								<p class="fw-bold">Dịch vụ</p>
								<a href="<?= home_url( '/dich-vu' ) ?>">Thiết kế vi khi hậu</a>
								<a href="<?= home_url( '/dich-vu' ) ?>">Thiết kế nhanh</a>
							</div>
							<div class="col-lg-3 col-6">
								<p class="fw-bold">Dự án</p>
								<a href="<?= home_url( '/du-an' ) ?>">Thiết kế sân vườn</a>
								<a href="<?= home_url( '/du-an' ) ?>">Thiết kế nhà 1 tầng</a>
								<a href="<?= home_url( '/du-an' ) ?>">Thiết kế nhà 2 tầng</a>
							</div>
							<div class="col-lg-3 col-6">
								<p class="fw-bold">Tin tức</p>
								<a href="<?= home_url( '/blog' ) ?>">Blog</a>
								<a href="<?= home_url( '/tuyen-dung' ) ?>">Tuyển dụng</a>
							</div>
						</div>
						<?= get_template_part('template-parts/btn-explore', null, ['type' => 'register']); ?>
					</div>
				</div>
				<hr class="text-green">
				<div class="info">
					<div class="d-flex justify-content-between align-items-end">
						<div class="row">
							<div class="col-lg-3 col-12">
								<p class="fw-bold">Công ty kiến trúc và xây dựng <?= $websiteName ?></p>
							</div>
							<div class="col-lg-3 col-6">
								<p class="fw-bold"><?= !empty($config['department_1']) ? $config['department_1']['name'] : '' ?></p>
								<a href="#"><?= !empty($config['department_1']) ? nl2br($config['department_1']['address']) : '' ?></a>
							</div>
							<div class="col-lg-3 col-6">
							<p class="fw-bold"><?= !empty($config['department_2']) ? $config['department_2']['name'] : '' ?></p>
								<a href="#"><?= !empty($config['department_2']) ? nl2br($config['department_2']['address']) : '' ?></a>
							</div>
							<div class="col-lg-3 col-6">
								<p class="fw-bold">Kết nối với chúng tôi</p>
								<a href="tel: <?= !empty($config['hotline']) ? $config['hotline'] : '' ?>">Hotline: <?= !empty($config['hotline']) ? $config['hotline'] : '' ?></a>
							</div>
						</div>
						<div class="social-icon gap-3">
							
							<a href="<?= !empty($config['social']['facebook']) ? $config['social']['facebook'] : 'javascript:void(0)'?>">
								<img src="<?= get_template_directory_uri(); ?>/assets/images/facebook-logo.svg" alt="Zalo">
							</a>
							<a href="<?= !empty($config['social']['youtube']) ? $config['social']['youtube'] : 'javascript:void(0)'?>">
								<img src="<?= get_template_directory_uri(); ?>/assets/images/youtube-logo.svg" alt="Zalo">
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="copy-right d-flex justify-content-between gap-3">
			<p>ConNest © 2024, All Rights Reserved</p>
			<!-- <p>Designed by DuocViec Agency</p> -->
		</div>

		<?php wp_footer(); ?>
	</footer>

</body>
<?php
	global $post;

	$query = get_queried_object();

	if (!empty($query) && $query->name == 'du-an') {
		$post_type = $query->name;
		$post_title = $query->label;
		$post_name = $query->post_name ?? '';
	} 
	else if (!empty($post)) {
		$post_type = $post->post_type == 'du-an' ? 'single-project' : $post->post_type;
		$post_title = $post->post_title;
		$post_name = $post->post_name;
	}
	else {
		$post_type = $query->taxonomy;
		$post_title = $query->name;
		$post_name = $query->post_name ?? '';
	}

	if (is_product()) $post_name = 'single-product';

	$jsFiles = [
		'trang-chu' => get_template_directory_uri().'/assets/js/index.js',
		'trang-chu-moi' => get_template_directory_uri().'/assets/js/index.js',
		'du-an' => get_template_directory_uri().'/assets/js/projects.js',
		'single-project' => get_template_directory_uri().'/assets/js/single-project.js',
		'blog' => get_template_directory_uri().'/assets/js/blog.js',
		'post' => get_template_directory_uri().'/assets/js/blog.js',
		'category' => get_template_directory_uri().'/assets/js/blog.js',
		'dich-vu' => get_template_directory_uri().'/assets/js/services.js',
		'tuyen-dung' => get_template_directory_uri().'/assets/js/career.js',
		'lien-he' => get_template_directory_uri().'/assets/js/contact.js',
		'product' => get_template_directory_uri().'/assets/js/archive-product.js',
		'product_cat' => get_template_directory_uri().'/assets/js/archive-product.js',
		'single-product' => get_template_directory_uri().'/assets/js/single-product.js',
		'thanh-toan' => get_template_directory_uri().'/assets/js/checkout.js',
	];
?>
<script>
	var adminAjaxUrl = '<?=admin_url('admin-ajax.php')?>';
</script>
<script src="<?= get_template_directory_uri().'/assets/js/main.js' ?>"></script>
<?php if (!empty($jsFiles[$post_name])): ?>
	<script src="<?= $jsFiles[$post_name] ?>"></script>
<?php else:?>
	<?php if (!empty($jsFiles[$post_type])): ?>
		<script src="<?= $jsFiles[$post_type] ?>"></script>
	<?php endif ?>
<?php endif ?>
</html>