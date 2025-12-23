<?php

	/**
	 * Template Name: Tuyển dụng
	 */
	$limit = 8;

	if (!empty($_GET['isAjax'])) {
		$data = [
			'continue' => 0,
			'content' => '',
		];
		$offset = $_GET['offset'];

		if (!$offset) {
			wp_send_json_success($data);
			exit;
		}

		$sql = "SELECT * FROM `wp_connest_job` WHERE `show` = 1 LIMIT $offset, $limit";
		$jobs = $wpdb->get_results($sql);

		if (empty($jobs)) {
			wp_send_json_success($data);exit;
		}

		$html = '';

		foreach ($jobs as $k => $job) {
			ob_start();
			get_template_part('template-parts/job', null, ['job' => $job]);
			$html .= ob_get_clean();
		}

		$data['continue'] = count($jobs) >= $limit ? 1 : 0;
		$data['content'] = $html;

		wp_send_json_success($data);
		exit;
	}

	get_header();
	$websiteName = get_bloginfo('name');
	$sql = "SELECT * FROM `wp_connest_job` WHERE `show` = 1 LIMIT $limit";
	$data = $wpdb->get_results($sql);
?>
<style>
	.banner .content {
		background-image: url("<?= get_template_directory_uri(); ?>/assets/images/career-banner.jpg");
	}

	.increase {
		background-image: url("<?= get_template_directory_uri(); ?>/assets/images/career-increase-bg.jpg");
	}

	.increase .body .content {
		background-image: url("<?= get_template_directory_uri(); ?>/assets/images/career-increase.png");
	}
</style>

<div class="banner">
	<div class="bg-header"></div>
	<div class="content">
		<div class="bg-blur"></div>
		<p class="desc">Tuyển dụng <?= $websiteName ?></p>
		<p class="title">Nơi kết nối<br class="d-block d-md-none">thành công & khơi nguồn cuộc sống mới</p>
	</div>
</div>
<div class="welcome margin-section">
	<div class="title">
		<p>Chào mừng bạn đến với <span><?= $websiteName ?></span>!</p>
	</div>
	<div class="desc">
		<p>Thế giới rộng lớn và cuộc sống thay đổi hàng giờ khiến cho bạn cảm thấy mệt nhoài và chông chênh?</p>
		<p>Áp lực công việc làm cho bạn cảm thấy bị bào mòn?</p>
		<p>Tại <?= $websiteName ?>, chúng tôi tin rằng một con người hạnh phúc là khi họ cân bằng Thân - Tâm - Trí. Vì thế chúng tôi nỗ lực để đem đến cho những người cộng sự của mình tại <?= $websiteName ?> một môi trường làm việc - nơi mà bạn có thể tự do phát triển, sống thật với con người mình.</p>
		<p class="highlight">Tự tin yêu và sống kết nối với chính mình.</p>
		<p class="highlight">Hoà thuận và giao cảm với thiên nhiên trong từng hơi thở.</p>
	</div>
</div>
<div class="opportunity margin-section">
	<div>
		<p class="title">Hãy để <?= $websiteName ?></p>
		<p class="desc">trở thành trạm dừng chân đáng nhớ trong cuộc đời của bạn</p>
	</div>
	<div class="count">
		<p class="total">+30</p>
		<p class="count-desc">cơ hội đang chờ bạn!</p>
	</div>
</div>
<div class="job margin-section">
	<?php if (!empty($data)): ?>
		<div class="list">
			<?php 
				foreach ($data as $k => $job) {
					get_template_part('template-parts/job', null, ['index' => $k, 'job' => $job]);
				} 
			?>
		</div>
		<div class="text-center">
			<button class="btn btn-load-more" value="0" data-url="<?= home_url('tuyen-dung') ?>" data-limit="<?= $limit ?>" type="button">Tải thêm</button>
		</div>
	<?php endif ?>
</div>
<div class="policy margin-section">
	<div class="head">
		<p class="title">Chính sách phát triển <span>con người</span></p>
		<div class="content">
			<p>Ở <?= $websiteName ?>, chúng tôi luôn coi con người là trọng tâm của sự phát triển.</p>
			<p>Mỗi người trong tập thể phải làm tốt vai trò của mình thì công ty mới phát triển.</p>
			<p>Do đó, <?= $websiteName ?> đặc biệt chú trọng đầu tư vào các hoạt động đào tạo, hỗ trợ nhân viên.</p>
		</div>
	</div>
	<div class="list">
		<div class="item">
			<img src="<?= get_template_directory_uri(); ?>/assets/images/career-policy-1.jpg" alt="policy-1">
			<div class="content">
				<p class="title">đào tạo phát triển</p>
				<div class="desc">
					<p>Nhân viên liên tục được đào tạo, bồi dưỡng kỹ năng chuyên môn định kỳ, nâng cao về kiến thức, kỹ năng nghiệp vụ để đáp ứng nhu cầu công việc.</p>
					<p><?= $websiteName ?> đầu tư chính sách, quỹ khen thưởng minh bạch để tạo động lực cho nhân viên liên tục học hỏi, phát triển bản thân không giới hạn.</p>
				</div>
			</div>
		</div>
		<div class="item">
			<img src="<?= get_template_directory_uri(); ?>/assets/images/career-policy-2.jpg" alt="policy-2">
			<div class="content">
				<p class="title">cùng xây dựng mục tiêu</p>
				<div class="desc">
					<p><?= $websiteName ?> chia mục tiêu ra làm 2 nhóm: <b>cá nhân và tập thể</b>.</p>
					<p>Dù ở mức độ nào, nhân sự đều có quyền tham gia đóng góp, điều chỉnh mục tiêu cùng tổ chức.</p>
					<p><?= $websiteName ?> cũng phát triển quy trình: kiểm tra, đánh giá mức độ liên tục để theo sát, có biện pháp hỗ trợ nhân sự hoàn thành. Đồng thời, xếp hạng, xem xét thăng tiến theo kết quả làm việc từng giai đoạn.</p>
				</div>
			</div>
		</div>
		<div class="item">
			<img src="<?= get_template_directory_uri(); ?>/assets/images/career-policy-3.jpg" alt="policy-3">
			<div class="content">
				<p class="title">hỗ trợ đạt mục tiêu</p>
				<div class="desc">
					<p><b>“KHÔNG AI PHẢI ĐI MỘT MÌNH”</b></p>
					<p>Đó là quan điểm quản trị của ban lãnh đạo <?= $websiteName ?>. Chúng tôi không để nhân viên phải tự giải các bài toán khó. Mà hơn hết, nhân sự luôn được hỗ trợ, đồng hành từ lãnh đạo và tập thể, đảm bảo mục tiêu cá nhân và mục tiêu chung của tổ chức được hoàn thành đúng hạn với kết quả tối ưu nhất.</p>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="increase margin-section">
	<div class="head">
		<p class="title">Thăng tiến cùng <span><?= $websiteName ?></span></p>
		<div class="desc">
			<p>Ở <?= $websiteName ?>, nhân sự sẽ luôn được trao cơ hội và trao quyền một cách công bằng, có đủ mọi điều kiện để đạt được kết quả công việc với hiệu suất tối ưu nhất.</p>
			<p>Mỗi nhân sự sẽ được thiết kế lộ trình thăng tiến riêng biệt, phù hợp với khả năng, đảm bảo tính minh bạch cùng với tiêu chí, quy trình đánh giá rõ ràng.</p>
		</div>
	</div>
	<div class="body">
		<div class="content">
			<table>
				<tbody>
					<tr><td colspan="2">Deputy<br>General Manager</td></tr>
					<tr><td colspan="2">Senior Manager</td></tr>
					<tr>
						<td class="left">Expert<br>Professional</td>
						<td class="right">Middle<br>Manager</td>
					</tr>
					<tr>
						<td>Professional</td>
						<td>Supervisor</td>
					</tr>
					<tr><td colspan="2">Senior</td></tr>
					<tr><td colspan="2">junior</td></tr>
				</tbody>
			</table>
		</div>
	</div>
	<div class="bg-blur"></div>
</div>
<div class="standard">
	<p class="title">Bộ tiêu chuẩn đánh giá năng lực</p>
	<div class="content">
		<div class="item">
			<p class="count">01</p>
			<div class="line">
				<svg xmlns="http://www.w3.org/2000/svg" width="216" height="8" viewBox="0 0 216 8" fill="none">
					<path d="M0.333333 4C0.333333 5.47276 1.52724 6.66667 3 6.66667C4.47276 6.66667 5.66667 5.47276 5.66667 4C5.66667 2.52724 4.47276 1.33333 3 1.33333C1.52724 1.33333 0.333333 2.52724 0.333333 4ZM215.354 4.35355C215.549 4.15829 215.549 3.84171 215.354 3.64645L212.172 0.464466C211.976 0.269204 211.66 0.269204 211.464 0.464466C211.269 0.659728 211.269 0.976311 211.464 1.17157L214.293 4L211.464 6.82843C211.269 7.02369 211.269 7.34027 211.464 7.53553C211.66 7.7308 211.976 7.7308 212.172 7.53553L215.354 4.35355ZM3 4.5H215V3.5H3V4.5Z" fill="#8EBC43"/>
				</svg>
			</div>
			<div class="desc">
				<p class="item-title">Đánh giá kiến thức</p>
				<p class="item-desc">Nhân sự phải có đầy đủ kiến thức chuyên môn cơ bản để hoàn thiện công việc hàng ngày.</p>
			</div>
		</div>
		<div class="item">
			<p class="count">02</p>
			<div class="line">
				<svg xmlns="http://www.w3.org/2000/svg" width="216" height="8" viewBox="0 0 216 8" fill="none">
					<path d="M0.333333 4C0.333333 5.47276 1.52724 6.66667 3 6.66667C4.47276 6.66667 5.66667 5.47276 5.66667 4C5.66667 2.52724 4.47276 1.33333 3 1.33333C1.52724 1.33333 0.333333 2.52724 0.333333 4ZM215.354 4.35355C215.549 4.15829 215.549 3.84171 215.354 3.64645L212.172 0.464466C211.976 0.269204 211.66 0.269204 211.464 0.464466C211.269 0.659728 211.269 0.976311 211.464 1.17157L214.293 4L211.464 6.82843C211.269 7.02369 211.269 7.34027 211.464 7.53553C211.66 7.7308 211.976 7.7308 212.172 7.53553L215.354 4.35355ZM3 4.5H215V3.5H3V4.5Z" fill="#8EBC43"/>
				</svg>
			</div>
			<div class="desc">
				<p class="item-title">Đánh giá kỹ năng</p>
				<p class="item-desc">Nhân sự phải trau dồi được bộ kỹ năng chuyên môn, kỹ năng mềm như giao tiếp, thấu hiểu khách hàng, trình bày...</p>
			</div>
		</div>
		<div class="item">
			<p class="count">03</p>
			<div class="line">
				<svg xmlns="http://www.w3.org/2000/svg" width="216" height="8" viewBox="0 0 216 8" fill="none">
					<path d="M0.333333 4C0.333333 5.47276 1.52724 6.66667 3 6.66667C4.47276 6.66667 5.66667 5.47276 5.66667 4C5.66667 2.52724 4.47276 1.33333 3 1.33333C1.52724 1.33333 0.333333 2.52724 0.333333 4ZM215.354 4.35355C215.549 4.15829 215.549 3.84171 215.354 3.64645L212.172 0.464466C211.976 0.269204 211.66 0.269204 211.464 0.464466C211.269 0.659728 211.269 0.976311 211.464 1.17157L214.293 4L211.464 6.82843C211.269 7.02369 211.269 7.34027 211.464 7.53553C211.66 7.7308 211.976 7.7308 212.172 7.53553L215.354 4.35355ZM3 4.5H215V3.5H3V4.5Z" fill="#8EBC43"/>
				</svg>
			</div>
			<div class="desc">
				<p class="item-title">Đánh giá phẩm chất</p>
				<p class="item-desc">Nhân sự phải là người có cả đức lẫn tài. Phẩm chất nhân sự sẽ đại diện cho bộ mặt của <?= $websiteName ?>.</p>
			</div>
		</div>
		<div class="item">
			<p class="count">04</p>
			<div class="line">
				<svg xmlns="http://www.w3.org/2000/svg" width="216" height="8" viewBox="0 0 216 8" fill="none">
					<path d="M0.333333 4C0.333333 5.47276 1.52724 6.66667 3 6.66667C4.47276 6.66667 5.66667 5.47276 5.66667 4C5.66667 2.52724 4.47276 1.33333 3 1.33333C1.52724 1.33333 0.333333 2.52724 0.333333 4ZM215.354 4.35355C215.549 4.15829 215.549 3.84171 215.354 3.64645L212.172 0.464466C211.976 0.269204 211.66 0.269204 211.464 0.464466C211.269 0.659728 211.269 0.976311 211.464 1.17157L214.293 4L211.464 6.82843C211.269 7.02369 211.269 7.34027 211.464 7.53553C211.66 7.7308 211.976 7.7308 212.172 7.53553L215.354 4.35355ZM3 4.5H215V3.5H3V4.5Z" fill="#8EBC43"/>
				</svg>
			</div>
			<div class="desc">
				<p class="item-title">Năng lực quản lý</p>
				<p class="item-desc">Nhân sự có khả năng quản lý đội ngũ, cùng đội ngũ thực hiện mục tiêu cá nhân và mục tiêu chung.</p>
			</div>
		</div>
	</div>
</div>
<div class="step margin-section mb-5">
	<div class="content">
		<img src="<?= get_template_directory_uri(); ?>/assets/images/career-step.jpg" alt="step">
		<div class="block">
			<p class="desc">Dù là một sinh viên mới ra trường hay một ứng viên đã có kinh nghiệm,<br>chúng tôi luôn sẵn sàng tạo điều kiện để bạn phát huy hết khả năng của mình.</p>
			<svg xmlns="http://www.w3.org/2000/svg" width="73" height="6" viewBox="0 0 73 6" fill="none">
				<path d="M67.3333 3C67.3333 4.47276 68.5272 5.66667 70 5.66667C71.4728 5.66667 72.6667 4.47276 72.6667 3C72.6667 1.52724 71.4728 0.333333 70 0.333333C68.5272 0.333333 67.3333 1.52724 67.3333 3ZM0 3.5H70V2.5H0V3.5Z" fill="white"/>
			</svg>
			<p class="title">Hãy tới và TIẾN BƯỚC cùng <?= $websiteName ?> nhé!</p>
		</div>
	</div>
</div>
<div class="work-place margin-section d-none">
	<p class="title">Cùng khám phá</p>
	<p class="title-mini">Không gian làm việc tại <?= $websiteName ?></p>
	<div class="slide">
		<div class="owl-carousel owl-theme">
			<div class="item">
				<img src="<?= get_template_directory_uri(); ?>/assets/images/career-workplace-1.jpg" alt="career-workplace-1" loading="lazy">
			</div>
			<div class="item">
				<img src="<?= get_template_directory_uri(); ?>/assets/images/career-workplace-2.jpg" alt="career-workplace-2" loading="lazy">
			</div>
			<div class="item">
				<img src="<?= get_template_directory_uri(); ?>/assets/images/career-workplace-3.jpg" alt="career-workplace-3" loading="lazy">
			</div>
			<div class="item">
				<img src="<?= get_template_directory_uri(); ?>/assets/images/career-workplace-4.jpg" alt="career-workplace-4" loading="lazy">
			</div>
			<div class="item">
				<img src="<?= get_template_directory_uri(); ?>/assets/images/career-workplace-4.jpg" alt="career-workplace-4" loading="lazy">
			</div>
			<div class="item">
				<img src="<?= get_template_directory_uri(); ?>/assets/images/career-workplace-4.jpg" alt="career-workplace-4" loading="lazy">
			</div>
		</div>
	</div>
</div>

<?php get_footer(); ?>