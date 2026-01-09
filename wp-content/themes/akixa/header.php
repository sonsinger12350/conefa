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

	if (is_shop()) $post_title = 'SHOP';
	if (is_product()) $post_name = 'single-product';

	$websiteName = get_bloginfo('name');
	$title = $post_title . ' - '.$websiteName;

	if ($post_name == 'trang-chu') $title = $websiteName;

	if (!empty($_GET['type'])) {
		$term = get_term_by('slug', $_GET['type'], 'product_cat');
		if (!empty($term)) $title = 'Dự án '.$term->name.' - '.$websiteName;
	}

	$description = get_bloginfo('description');
	$menu_locations = get_nav_menu_locations();
	$primary_menu_items = wp_get_nav_menu_items($menu_locations['primary']);
	$cssFiles = [
		'trang-chu' => get_template_directory_uri().'/assets/css/index.css',
		'trang-chu-moi' => get_template_directory_uri().'/assets/css/index.css',
		'dich-vu' => get_template_directory_uri().'/assets/css/services.css',
		'dich-vu-moi' => get_template_directory_uri().'/assets/css/services.css',
		'du-an' => get_template_directory_uri().'/assets/css/projects.css',
		'single-project' => get_template_directory_uri().'/assets/css/single-project.css',
		'blog' => get_template_directory_uri().'/assets/css/blog.css',
		've-chung-toi' => get_template_directory_uri().'/assets/css/about.css',
		'post' => get_template_directory_uri().'/assets/css/single-post.css',
		'tuyen-dung' => get_template_directory_uri().'/assets/css/career.css',
		'category' => get_template_directory_uri().'/assets/css/blog.css',
		'lien-he' => get_template_directory_uri().'/assets/css/contact.css',
		'product' => get_template_directory_uri().'/assets/css/archive-product.css',
		'product_cat' => get_template_directory_uri().'/assets/css/archive-product.css',
		'single-product' => get_template_directory_uri().'/assets/css/single-product.css',
		'thanh-toan' => get_template_directory_uri().'/assets/css/checkout.css',
	];

	$pageHeader2 = [
		'dich-vu',
		'dich-vu-moi',
		'du-an',
		'single-project',
		'blog',
		've-chung-toi',
		'post',
		'tuyen-dung',
		'category',
		'lien-he',
		'product',
		'product_cat',
		'single-product',
		'thanh-toan',
	];

	$isHeader2 = (in_array($post_name, $pageHeader2) || in_array($post_type, $pageHeader2)) ? true : false;
	
	$config = getConnestConfig();
	
	// Get logo from config or use default
	$logoBlack = !empty($config['logo_black']) ? $config['logo_black'] : get_template_directory_uri()."/assets/images/logo.png?v=1";
	$logoWhite = !empty($config['logo_white']) ? $config['logo_white'] : get_template_directory_uri()."/assets/images/logo-white.png?v=1";
	$logo = $logoBlack;
	
	// Get favicon from config or use logo black as fallback
	$favicon = !empty($config['favicon']) ? $config['favicon'] : $logoBlack;

	$categories_tree = get_product_categories_tree();

	if ($isHeader2) $logo = $logoWhite;

	$body_class = '';
	if (is_user_logged_in()) $body_class .= ' logged-in';
	if (current_user_can('administrator')) $body_class .= ' admin-bar';
	if ($isHeader2) $body_class .= ' header-2';
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<title><?= $title ?></title>
	<meta name="description" content="<?= $description ?>">
	<link rel="shortcut icon" href="<?= esc_url($favicon) ?>" sizes="32x32"/>
	<link rel="icon" type="image/png" href="<?= esc_url($favicon) ?>" sizes="32x32"/>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<?php wp_head(); ?>
	
	<?php
		// Override CSS variables with config values
		$main_green = !empty($config['main_green']) ? esc_attr($config['main_green']) : '#48684B';
		$light_green = !empty($config['light_green']) ? esc_attr($config['light_green']) : '#8EBC43';
	?>
	<style>
		:root {
			--main-green: <?= $main_green ?>;
			--light-green: <?= $light_green ?>;
		}
	</style>
	
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css"/>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.min.js"></script>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"/>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

	<!-- OwlCarousel2 -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>

	<!-- FancyBox -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css"/>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js"></script>

	<!-- JqueryUI -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.14.1/themes/base/jquery-ui.min.css" />
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.14.1/jquery-ui.min.js"></script>

	<!-- elevatezoom -->
	<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/elevatezoom/2.2.3/jquery.elevatezoom.min.js"></script> -->
	
	<link rel="stylesheet" href="<?= get_template_directory_uri(); ?>/style.css">

	<?php if (!empty($cssFiles[$post_name])): ?>
		<link rel="stylesheet" href="<?= $cssFiles[$post_name] ?>">
	<?php else:?>
		<?php if (!empty($cssFiles[$post_type])): ?>
			<link rel="stylesheet" href="<?= $cssFiles[$post_type] ?>">
		<?php endif ?>
	<?php endif ?>

	<link rel="stylesheet" href="<?= get_template_directory_uri(); ?>/assets/css/responsive.css">
</head>

<body <?= body_class($body_class); ?>>
<header class="<?= $isHeader2 ? 'scroll-down header-2' : ''?>">
		<a class="logo d-block" href="<?= home_url() ?>">
			<img src="<?= $logo ?>" alt="<?= $websiteName ?>" data-black="<?= $logoBlack ?>" data-white="<?= $logoWhite ?>">
		</a>
		<div class="center">
			<div class="main-menu">
				<?php
					wp_nav_menu(
						array(
							'container'  => '',
							'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s</ul>',
							'theme_location' => 'primary',
						)
					);
				?>
				<?php if (!empty($categories_tree)): ?>
					<h5 class="title-category"><span>Danh mục sản phẩm</span> <i class="fa-solid fa-bars"></i></h5>
					<div class="category-tree">
						<ul class="parent-element">
							<?php foreach ($categories_tree as $cat): ?>
								<li class="cat-item cat-parent">
									<a href="<?= $cat['link'] ?>" class="link-parent"><?= $cat['image'] ?><?= $cat['name'] ?></a>
									<?php if (!empty($cat['children'])): ?>
										<div class="children-categories">
											<ul>
												<?php foreach ($cat['children'] as $child): ?>
													<li class="cat-item cat-children">
														<a href="<?= $child['link'] ?>" class="link-children"><?= $child['name'] ?></a>
													</li>
												<?php endforeach ?>
											</ul>
										</div>
									<?php endif ?>
								</li>
							<?php endforeach ?>
						</ul>
					</div>
				<?php endif ?>
			</div>
			<?php if (!$isHeader2): ?>
				<div class="content">
					<?php 
						$hero_title = !empty($config['hero_title']) ? wp_kses_post($config['hero_title']) : 'Thiết kế kiến trúc<br>vi khí hậu';
						$hero_description = !empty($config['hero_description']) ? wp_kses_post($config['hero_description']) : 'Nơi không chỉ thiết kế những công trình kiến trúc có <b>giá trị thẩm mỹ</b> tinh tế, mà còn tạo <b>môi trường sống lí tưởng</b> cho sức khỏe thể chất và tinh thần của bạn và gia đình.';
					?>
					<h1 class="title"><?= $hero_title ?></h1>
					<p class="text-grey block-desc"><?= $hero_description ?></p>
				</div>
			<?php endif ?>
		</div>
		<div class="hamburger d-flex gap-3">
			<a href="javascript:void(0)" class="open-menu-desktop d-md-flex d-none">
				<i class="fa-solid fa-bars"></i>
				<i class="fa-solid fa-xmark"></i>
			</a>
			<a href="javascript:void(0)" class="open-category-mobile d-md-none">Mẫu thiết kế <i class="fa-solid fa-bars"></i></a>
			<a href="javascript:void(0)" class="open-menu-mobile d-md-none">Menu <i class="fa-solid fa-bars"></i><i class="fa-solid fa-xmark"></i></a>
		</div>

		<div class="menu-collapse-mobile d-md-none">
			<div class="card card-body">
				<?php
					wp_nav_menu(
						array(
							'container'  => '',
							'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s</ul>',
							'theme_location' => 'primary',
						)
					);
				?>
			</div>
		</div>
		<div class="menu-mobile-overlay"></div>

		<?php if (!empty($categories_tree)): ?>
			<div class="category-tree-mobile">
				<ul class="parent-element">
					<?php foreach ($categories_tree as $cat): ?>
						<li class="cat-item cat-parent">
							<a href="<?= $cat['link'] ?>" class="link-parent"><?= $cat['image'] ?><?= $cat['name'] ?></a>
							<?php if (!empty($cat['children'])): ?>
								<i class="fa-solid fa-plus open-children-categories"></i>
								<div class="children-categories">
									<ul>
										<?php foreach ($cat['children'] as $child): ?>
											<li class="cat-item cat-children">
												<a href="<?= $child['link'] ?>" class="link-children"><?= $child['name'] ?></a>
											</li>
										<?php endforeach ?>
									</ul>
								</div>
							<?php endif ?>
						</li>
					<?php endforeach ?>
				</ul>
			</div>
		<?php endif ?>
	</header>
	<div class="box-overlay"></div>