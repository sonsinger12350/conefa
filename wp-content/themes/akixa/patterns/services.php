<?php

	/**
	 * Template Name: Dịch Vụ
	 */

	get_header();
	$websiteName = get_bloginfo('name');

	$partners = get_post_gallery($post->ID, false);
	if (!empty($partners)) $partners = $partners['src'];
	$partnerCount = !empty($partners) ? count($partners) : 1;
	$page_content = get_post_field('post_content', $post->ID);
	$price_list = [];

	if (!empty(parse_blocks($page_content))) {
		foreach (parse_blocks($page_content) as $block) {
			if ($block['blockName'] == 'core/html') $price_list = $block['innerHTML'];
		}
	}
?>
<style>
	.banner .content {
		background-image: url("<?= get_template_directory_uri(); ?>/assets/images/services-banner.jpg");
	}
</style>

<div class="banner">
	<div class="bg-header"></div>
	<div class="content">
		<div class="bg-blur"></div>
		<h3 class="title">Thiết kế kiến trúc<br>vi khí hậu</h3>
		<div class="desc">
			<p><?= $websiteName ?> cùng bạn nuôi dưỡng<br>sức khỏe thân - tâm - trí cho gia đình</p>
			<?= get_template_part('template-parts/btn-explore', null, ['type' => 'about']); ?>
		</div>
	</div>
</div>
<div class="vision bullet-point">
	<p>Khi thiết kế kiến trúc và thi công được gắn vào một bức tranh tổng quát sẽ tạo ra một công trình không chỉ có khả năng chống chịu với các điều kiện khí hậu của khu vực mà còn cung cấp một môi trường sống lành mạnh và thoải mái cho người dùng. </p>
	<p>Đó là lí do <?= $websiteName ?> theo đuổi định hướng: <b>KIẾN TRÚC VI KHÍ HẬU - KIẾN TRÚC CỦA TƯƠNG LAI</b></p>
</div>

<div class="why-choose margin-section">
	<p class="title-mini mb-2">KHÁM PHÁ LÍ DO TẠI SAO</p>
	<p class="title mb-2">khách hàng lựa chọn <?= $websiteName ?></p>
	<p class="title-mini mb-0">CHO NGÔI NHÀ CỦA MÌNH</p>
	<div class="services">
		<div class="tab">
			<div class="item active" data-tab="service-design">Dịch vụ thiết kế kiến trúc</div>
			<div class="item" data-tab="service-construction">Dịch vụ thi công</div>
		</div>
		<div class="tab-content">
			<div class="item service-design active">
				<div class="content">
					<div class="desc">
						<p>Dịch vụ thiết kế kiến trúc của <?= $websiteName ?> không chỉ tập trung vào việc tạo ra một không gian sống độc - đẹp, mà còn chú trọng đến việc nuôi dưỡng sức khỏe thân - tâm - trí cho mỗi cá nhân sống trong đó, vì chúng tôi hiểu rằng môi trường xung quanh ảnh hưởng rất lớn đến chất lượng cuộc sống của mỗi người.</p>
						<p class="mb-0"><?= $websiteName ?> áp dụng các nguyên tắc thiết kế vi khí hậu, đề cao tính bền vững thông qua việc tối ưu hóa sự tương tác đối với khí hậu địa phương trên khía cạnh nghiên cứu về <b>ánh sáng tự nhiên và thông gió.</b></p>
					</div>
					<div>
						<p class="slogan mb-4 d-none d-md-block">“Trọng tâm trong mỗi bản thiết kế từ <?= $websiteName ?><br>đều hướng về con người”</p>
						<p class="slogan mb-4 d-block d-md-none">“Trọng tâm trong mỗi bản thiết kế từ<br><?= $websiteName ?> đều hướng về con người”</p>
						<?= get_template_part('template-parts/btn-explore', null, ['type' => 'contact']); ?>
					</div>
				</div>
				<div class="image">
					<img src="<?= get_template_directory_uri(); ?>/assets/images/project-1.jpg" alt="service-design">
				</div>
			</div>
			<div class="item service-construction">
				<div class="content">
					<div class="desc">
						<p>Thi công một công trình đòi hỏi việc tính toán, lên kế hoạch kỹ càng để tạo nền móng cho các bước xây dựng, hoàn thiện.</p>
						<p class="mb-0">Đội ngũ kiến trúc sư và thi công của <?= $websiteName ?> làm việc chặt chẽ từ những khâu đầu tiên để lên kế hoạch, sau đó đưa vào triển khai thực tế. Đâu là phương án khả thi, <b>chọn vật liệu</b> xây dựng nào đáp ứng được mục tiêu <b>thân thiện với môi trường</b>, có thể áp dụng các phương pháp <b>xây dựng tiết kiệm nang lượng,</b>, và đảm bảo rằng các hệ thống như thông gió và chiếu sáng được lắp đặt một cách hiệu quả để giảm thiểu sử dụng năng lượng ra sao?</p>
					</div>
					<div>
						<p class="slogan mb-4">Hạnh phúc của <?= $websiteName ?> là mỗi ngày được thấy thêm một<br>công trình hoàn thiện, mà ở trong đó nuôi dưỡng, kết nối<br>con người bằng một bản thể hạnh phúc</p>
						<?= get_template_part('template-parts/btn-explore', null, ['type' => 'contact']); ?>
					</div>
				</div>
				<div class="image">
					<img src="<?= get_template_directory_uri(); ?>/assets/images/project-7.jpg" alt="service-construction">
				</div>
			</div>
		</div>
	</div>
</div>
<div class="working margin-section">
	<p class="title">Tôn chỉ làm việc của<br><?= $websiteName ?></p>
	<div class="list">
		<div class="item">
			<p class="number">01</p>
			<p class="content">Quy trình rõ ràng và linh hoạt, luôn lấy mong muốn của khách hàng làm kim chỉ nam trong thiết kế.</p>
		</div>
		<div class="item">
			<p class="number">02</p>
			<p class="content">Sáng tạo với tính ứng dụng cao, mỗi công trình đều là sản phẩm nghệ thuật để phục vụ cuộc sống.</p>
		</div>
		<div class="item">
			<p class="number">03</p>
			<p class="content">Cẩn trọng nhưng tối ưu thời gian. Kiểm soát chất lượng chặt chẽ, bám sát tiến độ.</p>
		</div>
	</div>
</div>
<div class="workflow margin-section">
	<p class="title">Quy trình làm việc <span>8</span> bước</p>
	<div class="list">
		<div class="item">
			<div class="step">01</div>
			<div class="content">
				<div class="text">
					<p class="name">KHÁCH HÀNG LIÊN HỆ</p>
					<p class="desc">Khách hàng cung cấp thông tin sơ bộ về khu đất và nhu cầu thiết kế để chúng tôi tư vấn.</p>
				</div>
			</div>
		</div>
		<div class="item">
			<div class="step">02</div>
			<div class="content">
				<div class="text">
					<p class="name">KHẢO SÁT</p>
					<p class="desc">KTS tiến hành khảo sát khu đất và tư vấn sơ bộ về phương án bố trí mặt bằng ngay trong buổi gặp gỡ này.</p>
				</div>
			</div>
		</div>
		<div class="item">
			<div class="step">03</div>
			<div class="content">
				<div class="text">
					<p class="name">THIẾT KẾ CƠ SỞ</p>
					<p class="desc">Giai đoan concept: Mặt bằng công năng chi tiết, hình khối kiến trúc, nội thất, phong cách thiết kế, vật liệu chủ đạo và khái toán công trình.</p>
				</div>
			</div>
		</div>
		<div class="item">
			<div class="step">04</div>
			<div class="content">
				<div class="text">
					<p class="name">THẢO LUẬN LẦN 1</p>
					<p class="desc">Hai bên sẽ có buổi thảo luận và thống nhất hoặc điều chỉnh hình khối công trình và sơ đồ công năng trước khi sang giai đoạn diễn họa chi tiết.</p>
				</div>
			</div>
		</div>
		<div class="item">
			<div class="step">05</div>
			<div class="content">
				<div class="text">
					<p class="name">DIỄN HỌA 3D</p>
					<p class="desc">Các bản vẽ 3D diễn họa ngoại thất và nội thất, thể hiện rõ vật liệu, màu sắc và ánh sáng, bóng đổ lên công trình.</p>
				</div>
			</div>
		</div>
		<div class="item">
			<div class="step">06</div>
			<div class="content">
				<div class="text">
					<p class="name">THẢO LUẬN LẦN 2</p>
					<p class="desc">Hai bên thảo luận về màu sắc, vật liệu và ánh sáng; thống nhất hoặc điều chỉnh trước khi sang giai đoạn thiết kế bản vẽ thi công.</p>
				</div>
			</div>
		</div>
		<div class="item">
			<div class="step">07</div>
			<div class="content">
				<div class="text">
					<p class="name">THIẾT KẾ THI CÔNG</p>
					<p class="desc">Sau khi thống nhất được phương án concept chúng tôi thực hiện các bản vẽ chi tiết thiết kế thi công bao gồm: Hồ sơ kiến trúc; Hồ sơ kết cấu; Hồ sơ điện nước và Dự toán chi tiết.</p>
				</div>
			</div>
		</div>
		<div class="item">
			<div class="step">08</div>
			<div class="content">
				<div class="text">
					<p class="name">NGHIỆM THU THANH LÝ</p>
					<p class="desc">In ấn, đóng dấu gửi hồ sơ cho khách hàng và ký nghiệm thu hoàn thành thiết kế.</p>
				</div>
			</div>
		</div>
	</div>
	<div class="text-center">
		<?= get_template_part('template-parts/btn-explore', null, ['type' => 'contact']); ?>
	</div>
</div>
<div class="price-list margin-section">
	<div class="title">Bảng báo giá thiết kế</div>
	<div class="title-mini">lựa chọn giải pháp phù hợp với nhu cầu của bạn</div>
	<!-- <div class="table-price-list">
		<div class="item item-1">
			<div class="icon"><img src="<?= get_template_directory_uri(); ?>/assets/images/icon/price-list-1.svg" alt="price-list-1"></div>
			<p class="title">Gói cơ bản</p>
			<p class="price">220.000đ/m2</p>
			<p class="detail-1">(Thiết kế kiến trúc)</p>
			<p class="detail-2">Phù hợp với những KH xây căn nhà thứ 2 không ở thường xuyên, mức độ đầu tư vừa phải</p>
			<div class="hr"></div>
			<div class="desc">
				<p class="desc-title">-	Hồ sơ: Bản vẽ phối cảnh 3D; Hồ sơ thiết kế thi công, dự toán kiến trúc</p>
				<p class="desc-title mb-1">- Ưu đãi:</p>
				<ul class="desc-content">
					<li>Tặng bản vẽ quy hoạch sân vườn</li>
				</ul>
			</div>
		</div>
		<div class="item item-2">
			<div class="icon"><img src="<?= get_template_directory_uri(); ?>/assets/images/icon/price-list-2.svg" alt="price-list-2"></div>
			<p class="title">Gói chuyên nghiệp</p>
			<p class="price">350.000đ/m2</p>
			<p class="detail-1">(Thiết kế kiến trúc + Nội thất)</p>
			<p class="detail-2">Dành cho KH ở thường xuyên, mong muốn một không gian sống hoàn hảo; Thống nhất từ kiến trúc đến nội thất</p>
			<div class="hr"></div>
			<div class="desc">
				<p class="desc-title">-	Hồ sơ: Bản vẽ phối cảnh 3D; Hồ sơ thiết kế thi công, dự toán kiến trúc và <b>nội thất</b></p>
				<p class="desc-title mb-1">- Ưu đãi:</p>
				<ul class="desc-content">
					<li>Quy mô thiết kế lớn hơn 200m2 giảm 5%/Đơn giá thiết kế</li>
					<li>Tặng bản vẽ quy hoạch sân vườn</li>
					<li>Tặng phối cảnh 3d sân vườn với diện tích sân vườn < 100m2, (diện tích vượt tính 30N/m2)</li>
				</ul>
			</div>
		</div>
		<div class="item item-3">
			<div class="icon"><img src="<?= get_template_directory_uri(); ?>/assets/images/icon/price-list-3.svg" alt="price-list-3"></div>
			<p class="title">Gói Thiết kế cao cấp</p>
			<p class="price">Bao gồm chi phí Gói chuyên nghiệp + Chi phí thiết kế cảnh quan (100N/m2)</p>
			<p class="detail-1">(Chi phí thiết kế cảnh quan chỉ tính cho diện tích cần thiết kế chi tiết)</p>
			<p class="detail-2">Dành cho KH ở thường xuyên có Gu thẩm mỹ cao; Mong muốn một không gian sống chất lượng đến từng chi tiết.</p>
			<div class="hr"></div>
			<div class="desc">
				<p class="desc-title">-	Hồ sơ: Bản vẽ phối cảnh 3D; Hồ sơ thiết kế thi công, dự toán kiến trúc, <b>nội thất, sân vườn</b></p>
				<p class="desc-title mb-1">- Ưu đãi:</p>
				<ul class="desc-content">
					<li>Quy mô thiết kế lớn hơn 200m2 giảm 5%/Đơn giá thiết kế</li>
					<li>Quy mô thiết kế cảnh quan lớn hơn 300m2 giảm 10%/Đơn giá thiết kế cảnh quan</li>
					<li>Tặng bản vẽ quy hoạch cảnh quan toàn bộ khu đất</li>
				</ul>
			</div>
		</div>
	</div> -->
	<?= $price_list ?>
	<div class="text-center">
		<?= get_template_part('template-parts/btn-explore', null, ['type' => 'contact']); ?>
	</div>
</div>
<div class="partner margin-section">
	<p class="title-mini">Chúng tôi tự hào khi được đồng hành cùng các</p>
	<p class="title">đối tác hàng đầu</p>
	<div class="list-scroll custom-scrollbar">
		<div class="list">
			<?php if (!empty($partners)): ?>
				<?php foreach ($partners as $k => $v): ?>
					<div class="item active"><img src="<?= $v ?>" alt="partner-<?= $k ?>"></div>
				<?php endforeach ?>
			<?php endif ?>
			<?php for ($i = $partnerCount; $i < 12; $i++): ?>
				<div class="item <?= $i == 1 ? 'active' : '' ?>">
					<img src="<?= get_template_directory_uri(); ?>/assets/images/partner-google.jpg" alt="partner-<?= $i ?>">
				</div>
			<?php endfor ?>
		</div>
	</div>
</div>

<?php get_footer(); ?>