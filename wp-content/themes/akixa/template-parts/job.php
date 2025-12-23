<?php
	if (empty($args['job'])) return false;
	$job = $args['job'];
	$k = !empty($args['index']) ? $args['index'] : '';
	$desc = explode('\n', nl2br($job->desc));
	$benefits = explode('\n', nl2br($job->benefits));
	$websiteName = get_bloginfo('name');
	$benefitIcons = [
		'<img src="'.get_template_directory_uri().'/assets/images/icon/salary.svg" alt="salary">',
		'<img src="'.get_template_directory_uri().'/assets/images/icon/increase.svg" alt="increase">',
		'<img src="'.get_template_directory_uri().'/assets/images/icon/plus-circle.svg" alt="plus-circle">',
		'<img src="'.get_template_directory_uri().'/assets/images/icon/graduate.svg" alt="graduate">',
	];
?>
<div class="item <?= $k == 0 ? 'active' : '' ?>" data-bs-toggle="modal" data-bs-target="#jobModal<?= $job->id ?>">
	<p class="title"><?= $job->position ?></p>
	<div class="location">
		<p class="address"><img src="<?= get_template_directory_uri(); ?>/assets/images/icon/place.svg" alt="address"> <?= $job->address ?></p>
		<p class="time"><img src="<?= get_template_directory_uri(); ?>/assets/images/icon/clock.svg" alt="time"> <?= $job->application_deadline ?></p>
	</div>
	<p class="salary"><img src="<?= get_template_directory_uri(); ?>/assets/images/icon/salary.svg" alt="salary"> <?= $job->salary ?></p>
</div>

<div class="modal fade jobModal" id="jobModal<?= $job->id ?>" tabindex="-1" aria-labelledby="jobModal<?= $job->id ?>Label" aria-hidden="true">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			<div class="modal-body">
				<div class="banner">
					<div class="bg-header"></div>
					<div class="content">
						<div class="bg-blur"></div>
						<p class="desc">Tuyển dụng <?= $websiteName ?></p>
						<p class="title"><?= $job->position ?></p>
					</div>
				</div>
				<div class="job-detail">
					<div class="left">
						<p class="title">Thông tin việc làm</p>	
						<hr>
						<div class="modal-item">
							<p class="title">Mức lương</p>
							<span class="detail"><?= $job->salary ?></span>	
						</div>
						<div class="modal-item">
							<p class="title">Thời gian làm việc</p>
							<span class="detail"><?= $job->working_time ?></span>	
						</div>
						<div class="modal-item">
							<p class="title">Địa điểm làm việc</p>
							<span class="detail"><?= $job->address ?></span>	
						</div>
						<div class="modal-item">
							<p class="title">Thời gian nộp hồ sơ</p>
							<span class="detail"><?= $job->application_deadline ?></span>	
						</div>
					</div>
					<div class="right">
						<div class="desc">
							<p class="title">Mô tả công việc</p>
							<?php foreach ($desc as $val): ?>
								<p><span class="line"></span> <span class="text"><?= $val ?></span></p>
							<?php endforeach ?>
						</div>	
						<div class="benefit">
							<p class="title">Quyền lợi</p>
							<?php foreach ($benefits as $k => $val): ?>
								<p><?= $k <= 3 ? $benefitIcons[$k] : '<img src="'.get_template_directory_uri().'/assets/images/icon/plus-circle.svg" alt="salary">' ?> <?= $val ?></p>
							<?php endforeach ?>
						</div>	
					</div>
				</div>
			</div>
			<p class="btn-close-modal" data-bs-dismiss="modal">Đóng</p>
		</div>
	</div>
</div>