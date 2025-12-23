<?php
	$limit = 12;
	$category = !empty($_GET['type']) ? $_GET['type'] : '';

	if (!empty($_GET['isAjax'])) {
		$response = [
			'continue' => 0,
			'content' => '',
		];
		$offset = $_GET['offset'];

		if (!$offset) {
			wp_send_json_success($response);exit;
		}

		$args = array(
			'posts_per_page' => $limit,
			'offset' => $offset,
			'post_type' => 'du-an',
			'orderby' => 'id',
			'order'   => 'DESC'
		);

		if (!empty($category)) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'danh-muc-du-an', // hoặc taxonomy phù hợp với post type 'du-an'
					'field'    => 'slug', // hoặc 'term_id', tùy theo dữ liệu truyền vào
					'terms'    => $category,
				),
			);
		}
	
		$result = new WP_Query($args);
		$data = !empty($result->posts) ? $result->posts : [];

		if (empty($data)) {
			wp_send_json_success($response);exit;
		}

		$html = '';

		foreach ($data as $k => $v) {
			ob_start();
			get_template_part('template-parts/project', null, ['item' => $v]);
			$html .= ob_get_clean();
		}

		$response['continue'] = count($data) >= $limit ? 1 : 0;
		$response['content'] = $html;

		wp_send_json_success($response);
		exit;
	}

	get_header();
	$websiteName = get_bloginfo('name');
	$args = array(
		'posts_per_page' => $limit,
		'post_type' => 'du-an',
		'orderby' => 'id',
		'order'   => 'DESC'
	);

	if (!empty($category)) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'danh-muc-du-an', // hoặc taxonomy phù hợp với post type 'du-an'
				'field'    => 'slug', // hoặc 'term_id', tùy theo dữ liệu truyền vào
				'terms'    => $category,
			),
		);
	}

	$result = new WP_Query($args);
	$data = !empty($result->posts) ? $result->posts : [];

	$categories = get_terms(array(
		'taxonomy' => 'danh-muc-du-an',
		'hide_empty' => false,
		'orderby' => 'menu_order',
		'order' => 'ASC',
	));
	$all_category = (object) array(
		'term_id' => 0,
		'name' => 'Tất cả',
		'slug' => 'tat-ca',
	);

	array_unshift($categories, $all_category);

	$showBtnLoadMore = 0;
	if (count($data) >= $limit) $showBtnLoadMore = 1;

	$post = get_page_by_path('du-an-sample', OBJECT, 'page');

	if (!empty($post)) {
		$slides = get_post_gallery($post->ID, false);
		if (!empty($slides)) $slides = $slides['src'];
	}
?>

<div class="slide">
	<div class="bg-header"></div>
	<div class="content">
		<?php if (!empty($slides)): ?>
			<div class="owl-carousel owl-theme">
				<?php foreach ($slides as $k => $v): ?>
					<div><img src="<?= $v ?>" alt="slide-<?= $k ?>" loading="lazy"></div>
				<?php endforeach ?>
			</div>
		<?php endif ?>
	</div>
</div>
<div class="page">
	<div class="about">
		<div class="container">
			<h3 class="block-title">
				Bạn vừa mở ra cánh cửa<br>đến không gian kiến trúc<br><span class="text-green">Vi Khí Hậu</span>
				<p class="block-desc">Không gian sống mà ở đó<br>mỗi khách hàng của <?=  $websiteName ?> đều khỏe mạnh<br>và hạnh phúc</p>
			</h3>
		</div>
	</div>
	<div class="container">
		<div class="categories margin-section">
			<div class="list-category">
				<?php if (!empty($categories)): ?>
					<?php 
						foreach ($categories as $k => $cat): 

						if ($cat->slug == 'tat-ca') {
							$cat->slug = '';
							$url = home_url('du-an');
						} 
						else {
							$url = home_url('du-an').'?type='.$cat->slug;
						}
						
					?>
						<a class="item <?= $cat->slug == $category ? 'active' : ''?>" href="<?= $url ?>"><?= $cat->name ?></a>
					<?php endforeach ?>
				<?php endif ?>
			</div>
		</div>
		<div class="list-product margin-section">
			<?php if (!empty($data)): ?>
				<div class="list row mt-4">
					<?php foreach ($data as $k => $v): ?>
						<?php
							get_template_part('template-parts/project', null, ['index' => $k, 'item' => $v]);
						?>
					<?php endforeach ?>
				</div>
				<?php if ($showBtnLoadMore): ?>
					<div class="text-center mt-4 mb-4">
						<button class="btn btn-load-more" value="0" data-url="<?= home_url('du-an') ?>" data-limit="<?= $limit ?>">XEM THÊM DỰ ÁN</button>
					</div>
				<?php endif ?>
			<?php else: ?>
				<p class="text-center mb-0">Chưa có dự án</p>
			<?php endif ?>	
		</div>
	</div>
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