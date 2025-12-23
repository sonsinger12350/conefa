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
		'trang-chu' => get_template_directory_uri().'/assets/css/index.css?v='.time(),
		'trang-chu-moi' => get_template_directory_uri().'/assets/css/index.css?v='.time(),
		'dich-vu' => get_template_directory_uri().'/assets/css/services.css?v='.time(),
		'dich-vu-moi' => get_template_directory_uri().'/assets/css/services.css?v='.time(),
		'du-an' => get_template_directory_uri().'/assets/css/projects.css?v='.time(),
		'single-project' => get_template_directory_uri().'/assets/css/single-project.css?v='.time(),
		'blog' => get_template_directory_uri().'/assets/css/blog.css?v='.time(),
		've-'.strtolower($websiteName) => get_template_directory_uri().'/assets/css/about.css?v='.time(),
		'post' => get_template_directory_uri().'/assets/css/single-post.css?v='.time(),
		'tuyen-dung' => get_template_directory_uri().'/assets/css/career.css?v='.time(),
		'category' => get_template_directory_uri().'/assets/css/blog.css?v='.time(),
		'lien-he' => get_template_directory_uri().'/assets/css/contact.css?v='.time(),
		'product' => get_template_directory_uri().'/assets/css/archive-product.css?v='.time(),
		'product_cat' => get_template_directory_uri().'/assets/css/archive-product.css?v='.time(),
		'single-product' => get_template_directory_uri().'/assets/css/single-product.css?v='.time(),
	];

	$pageHeader2 = [
		'dich-vu',
		'dich-vu-moi',
		'du-an',
		'single-project',
		'blog',
		've-'.strtolower($websiteName),
		'post',
		'tuyen-dung',
		'category',
		'lien-he',
		'product',
		'product_cat',
		'single-product',
	];

	$isHeader2 = (in_array($post_name, $pageHeader2) || in_array($post_type, $pageHeader2)) ? true : false;
	$logoBlack = get_template_directory_uri()."/assets/images/logo.png?v=1";
	$logoWhite = get_template_directory_uri()."/assets/images/logo-white.png?v=1";
	$logo = $logoBlack;

	if ($isHeader2) $logo = $logoWhite;

	$config = getConnestConfig();

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
	<link rel="shortcut icon" href="<?= $logoBlack ?>" sizes=32x32/>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<?php wp_head(); ?>
	
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
	
	<link rel="stylesheet" href="<?= get_template_directory_uri(); ?>/style.css?v=<?=time()?>">

	<?php if (!empty($cssFiles[$post_name])): ?>
		<link rel="stylesheet" href="<?= $cssFiles[$post_name] ?>">
	<?php else:?>
		<?php if (!empty($cssFiles[$post_type])): ?>
			<link rel="stylesheet" href="<?= $cssFiles[$post_type] ?>">
		<?php endif ?>
	<?php endif ?>

	<link rel="stylesheet" href="<?= get_template_directory_uri(); ?>/assets/css/responsive.css?v=<?=time()?>">
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
			</div>
			<?php if (!$isHeader2): ?>
				<div class="content">
					<h1 class="title">Thiết kế kiến trúc<br>vi khí hậu</h1>
					<p class="text-grey block-desc">Nơi không chỉ thiết kế những công trình kiến trúc có <b>giá trị thẩm mỹ</b> tinh tế, mà còn tạo <b>môi trường sống lí tưởng</b> cho sức khỏe thể chất và tinh thần của bạn và gia đình.</p>
				</div>
			<?php endif ?>
		</div>
		<div class="hamburger d-flex gap-3">
			<a href="javascript:void(0)" class="open-menu-desktop d-md-flex d-none">
				<i class="fa-solid fa-bars"></i>
				<i class="fa-solid fa-xmark"></i>
			</a>
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
	</header>
	<div class="box-overlay"></div>