<?php 
	get_header();

	$websiteName = get_bloginfo('name');
	$seekTabs = [
		1 => [
			'desc1' => 'Kiến trúc từ thiên nhiên, kiến trúc từ khí hậu hay theo sân vườn, biệt thự... có phải tất cả đều ở trong những quy luật thiết kế bao la và cần định nghĩa theo cách riêng?',
			'desc2' => 'Con người - một bản thể vốn được vận hành đồng điệu với thiên nhiên. Vậy nên việc nghiên cứu và ứng dụng các yếu tố ánh sáng, không khí, nhiệt độ, âm thanh... vào không gian sống sẽ tác động tích cực đến sức khỏe của chúng ta.',
		],
		2 => [
			'desc1' => 'Trái Đất gieo mầm cho các khu vực với khí hậu khác nhau từ đất đai, độ ẩm và không khí riêng biệt. Vậy “sự sống” sẽ thay đổi như thế nào ở mỗi khu vực?',
			'desc2' => "Hành trình sống của chúng ta giống như gieo trồng một cái cây, thu hoạch quả ngọt hay đắng còn tùy thuộc và hạt mầm và cách ta chăm sóc nó. Để gieo những hạt mầm tích cực đầu tiên cho không gian sống của bạn - Hãy để $websiteName song hành nhé!",
		],
		3 => [
			'desc1' => 'Sự tỏa sáng của Mặt trời, Mặt trăng hay các Ngôi sao...như gửi tới thông điệp “luôn có ánh sáng phía cuối con đường – chỉ cần có niềm tin và hành động là sẽ thấy”.',
			'desc2' => "$websiteName tin rằng khi bạn hiểu mình, hiểu sứ mệnh, năng lượng tiềm ẩn bên trong cũng như kiên trì ươm mầm cho không gian sống, rồi sẽ đến lúc nơi đó tỏa sáng với vầng hào quang rực rỡ nhất.",
		],
		4 => [
			'desc1' => 'Bạn có biết: Vũ trụ không bao giờ đứng yên mà luôn luôn thay đổi, cũng như chúng ta luôn luôn vận động để trở thành phiên bản tốt nhất - chuyển hóa theo hướng tích cực và lan tỏa năng lượng tới mọi người.',
			'desc2' => "“Hành trình vạn dặm bắt đầu từ một bước chân”, nếu bạn chưa biết nên bắt đầu từ đâu, thì $websiteName luôn ở đây lắng nghe và đồng hành cùng bạn dựng xây những nét vẽ, viên gạch đầu tiên cho không gian sống an hòa của bạn.",
		]
	];

	$seekTabsMenu = [
		1 => 'Khai phá',
		2 => 'Ươm mầm',
		3 => 'Tỏa sáng',
		4 => 'Sẻ chia',
	];

	$seekTabsMenuMobile = [
		1 => 'Seek',
		2 => 'Sow',
		3 => 'Shine',
		4 => 'Share',
	];

	$slides = get_post_gallery($post->ID, false);
	if (!empty($slides)) $slides = $slides['src'];
	$totalSlide = !empty($slides) ? count($slides) : 0;

	$args = array(
		'post_type'  => 'du-an',
		'post_status'  => 'publish',
		'meta_query' => array(
			array(
				'key'   => 'pin_home',
				'value' => '1',
				'compare' => '='
			)
			),
		'posts_per_page' => 9
	);
	$projects = new WP_Query($args);
	$projects = !empty($projects->posts) ? $projects->posts : [];

	$args = array(
		'fields' => 'ids',
		'post_type' => 'product',
		'posts_per_page' => 8,
		'post_status' => 'publish',
	);
	$query = new WP_Query($args);
	$products = $query->posts ?? [];
?>
<style>
	.body .seek-tabs .content .seek-tab.tab-1 .left {
		background-image: url("<?= get_template_directory_uri(); ?>/assets/images/seak-tab-1.jpg");
	}

	.body .seek-tabs .content .seek-tab.tab-2 .left {
		background-image: url("<?= get_template_directory_uri(); ?>/assets/images/seak-tab-2.jpg");
	}

	.body .seek-tabs .content .seek-tab.tab-3 .left {
		background-image: url("<?= get_template_directory_uri(); ?>/assets/images/seak-tab-3.jpg");
	}

	.body .seek-tabs .content .seek-tab.tab-4 .left {
		background-image: url("<?= get_template_directory_uri(); ?>/assets/images/seak-tab-4.jpg");
	}

	.body .invitation {
		background-image: url("<?= get_template_directory_uri(); ?>/assets/images/invitation.jpg");
	}

	.body .seek-tabs .bg-gif {
		background-image: url("<?= get_template_directory_uri(); ?>/assets/images/video-bg-seek-tab.gif?v=3");
	}
</style>
<div class="page container-fluid">
	<div class="body">
		<div class="slide position-relative">
			<h3 class="title-mobile d-sm-none">Thiết kế kiến trúc vi khí hậu</h3>
			<div class="bg-mobile d-sm-none"></div>
			<div class="bg-blur-mobile d-sm-none"></div>
			<?php if (!empty($slides)): ?>
				<div class="owl-carousel owl-theme">
					<?php foreach ($slides as $k => $slideImg): ?>
						<div><img src="<?= $slideImg ?>" alt="slide-<?= $k ?>" loading="lazy"></div>
					<?php endforeach ?>
				</div>
			<?php endif ?>
			<?= get_template_part('template-parts/btn-explore', null, ['type' => 'explore']); ?>
		</div>
		<div class="content-slide-mobile bullet-point d-sm-none">
			<p class="text-grey block-desc">Nơi không chỉ thiết kế những công trình kiến trúc có <b>giá trị thẩm mỹ</b> tinh tế, mà còn tạo <b>môi trường sống lí tưởng</b> cho sức khỏe thể chất và tinh thần của bạn và gia đình.</p>
		</div>
		<div class="about margin-section">
			<h3 class="block-title">Nền tảng thiết kế<br><span class="text-green">vì sức khỏe</span> của<br>bạn và gia đình.</h3>
			<div class="content bullet-point">
				<p class="block-desc text-white">Với <?= $websiteName ?>, kiến trúc là phải hòa hợp với thiên nhiên và con người; hình thức bên ngoài chịu ảnh hưởng bởi địa hình; khí hậu, tính bản địa; hình thức bên trong ảnh hưởng nhiều bởi nhu cầu tiện nghi của con người gồm nhiệt độ, độ ẩm, âm thanh, ánh sáng; Và người kiến trúc sư muốn tạo ra được hình thức công trình thì bắt buộc phải giải quyết các vấn đề nảy sinh của bối cảnh khu đất và nhu cầu sử dụng;</p>
				<?= get_template_part('template-parts/btn-explore', null, ['type' => 'product']); ?>
			</div>
		</div>
		<div class="project margin-section">
			<div class="project-gallery">
				<?php if (!empty($projects)): ?>
					<?php foreach ($projects as $v): ?>
						<a href="<?= get_permalink($v->ID) ?>"><img src="<?= get_the_post_thumbnail_url($v->ID) ?>" alt="<?= $v->post_title ?>" loading="lazy"></a>
					<?php endforeach ?>
				<?php endif ?>
			</div>
			<div class="content">
				<h3 class="block-title">Cùng khám phá<br>những <span class="text-green">dự án tuyệt vời</span><br>mà chúng tôi đã<br>đồng hành.</h3>
				<div class="detail bullet-point">
					<p class="text-grey block-desc">Ngôi nhà của chúng ta không chỉ dừng lại là một nơi để ở. Với triết lý ”Kiến trúc vi khí hậu - Thiết kế kiến trúc phải kết hợp nghệ thuật và khoa học thực tiễn” chúng tôi mong muốn mọi công trình kiến trúc của mình sẽ giúp bạn hạnh phúc cả về tinh thần và thể chất. Đó cũng chính là điều khiến <?= $websiteName ?> trở nên độc đáo và đặc biệt.</p>
					<p class="text-grey block-desc">Chúng tôi tin rằng, người kể truyện không chỉ đến từ <?= $websiteName ?>. Hãy cùng khám phá những dự án tuyệt vời mà chúng tôi đã cùng đồng hành.</p>
					<?= get_template_part('template-parts/btn-explore', null, ['type' => 'register']); ?>
				</div>
			</div>
		</div>
		<!-- <div class="seek-tabs margin-section d-none d-md-block">
			<div class="bg-blur-gif"></div>
			<div class="bg-gif"></div>
			<div class="head d-flex justify-content-between">
				<div class="left">HÀNH TRÌNH<br>CỦA THIÊN NHIÊN</div>
				<div class="center d-flex justify-content-center">
					<div class="d-flex align-items-center">
						<span class="border-left"></span>
						<p>SONG HÀNH</p>
						<span class="border-right"></span>
					</div>
				</div>
				<div class="right">CÙNG HÀNH TRÌNH<br>CỦA BẠN</div>
			</div>
			<div class="content">
				<div class="position-relative">
					<?php foreach ($seekTabs as $k => $tab):?>
						<div class="seek-tab tab-<?=$k?> <?= $k == 1 ? 'active' : ''?>" data-tab="<?=$k?>">
							<div class="left">
								<p class="title-mobile d-sm-none"><?=$seekTabsMenuMobile[$k]?></p>
								<p class="title">Hành trình<br>của thiên nhiên</p>
								<p class="desc"><?=$tab['desc1']?></p>
							</div>
							<div class="right">
								<p class="title">Hành trình<br>của bạn</p>
								<p class="desc"><?=$tab['desc2']?></p>
							</div>
						</div>
					<?php endforeach?>
					<div class="seek-tab-menu">
						<?php foreach ($seekTabsMenu as $k => $v):?>
							<div class="item <?= $k == 1 ? 'active' : ''?> <?= $k > 2 ? 'hide' : ''?>" data-tab="<?=$k?>">
								<span class="process-line"></span>
								<p class="menu-title"><?=$v?></p>
								<p class="dot"><span></span></p>
							</div>
						<?php endforeach?>
					</div>
					<div class="bg-overlay"></div>
				</div>
			</div>
		</div> -->
		<div class="container list-product margin-section">
			<h3 class="title block-title text-center mb-4">Mẫu nhà vườn</h3>
			<div class="list row">
				<?php foreach ($products as $k => $product): ?>
					<?php
						get_template_part('template-parts/product', null, ['index' => $k, 'product' => $product, 'cols' => 'col-xxl-3 col-lg-6 col-sm-6']);
					?>
				<?php endforeach ?>
			</div>
			<div class="text-center mt-4 mb-4">
				<a class="btn btn-load-more" href="<?= home_url('shop') ?>">XEM THÊM</a>
			</div>
		</div>
		<div class="invitation margin-section">
			<h3 class="title block-title">Hãy để <span class="text-green"><?= $websiteName ?></span><br>trở thành người đồng hành<br>trong hành trình của bạn!</h3>
			<div class="position-relative">
				<div class="bullet-point">
					<div class="content">
						<p class="block-desc text-grey">Vì cuộc đời là của chính mình, nên hãy trải nghiệm sự khác biệt, để nâng cao bản thân mình lên một tầng cao mới, cùng <b class="text-black">thiết kế kiến trúc vi khí hậu đến từ <?= $websiteName ?>.</b></p>
						<?= get_template_part('template-parts/btn-explore', null, ['type' => 'explore']); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php get_footer(); ?>