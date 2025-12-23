<style>
	.wrap h1.wp-heading-inline {
		font-size: 36px;
		width: 100%;
    	text-align: center;
		margin-bottom: 16px;
	}

	.form {
		width: 50%;
		margin: 0 auto;
	}

	.form form {
		background: #fff;
    	padding: 16px 32px;
    	border-radius: 8px;
	}

	.form .footer {
		display: flex;
		justify-content: center;
		column-gap: 16px;
	}

	.form label {
		display: block;
		margin-bottom: 8px;
		font-size: 15px;
		font-weight: 500;
	}

	.form input:not([type="checkbox"]),
	.form textarea,
	.form select {
		padding: 4px 8px !important;
		box-shadow: 0 0 0 transparent;
		border-radius: 4px;
		border: 1px solid #8c8f94;
		background-color: #fff;
		color: #2c3338;
		width: 100%;
		max-width: 100%;
	}

	.form .item {
		margin-bottom: 16px;
		margin-top: 16px;
	}
</style>
<?php
	$job = [
		'position' => @$data->position,
		'address' => @$data->address,
		'working_time' => @$data->working_time,
		'salary' => @$data->salary,
		'application_deadline' => @$data->application_deadline,
		'desc' => @$data->desc,
		'benefits' => @$data->benefits,
		'show' => @$data->show,
	];

	$working_time = [
		'',
		'Toàn thời gian',
		'Bán thời gian'
	];
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?=!empty($data->name) ? 'Chỉnh sửa tin tuyển dụng' : 'Thêm mới tin tuyển dụng'?></h1>
	<div class="form">
		<form action="" method="POST">
			<input type="hidden" name="submit-job-form" value="1">
			<input type="hidden" name="id" value="<?= @$data->id ?>">
			<div class="item">
				<label for="position">Vị trí:</label>
				<input name="position" id="position" value="<?= $job['position'] ?>" type="text" required>
			</div>
			<div class="item">
				<label for="address">Địa điểm làm việc:</label>
				<input name="address" id="address" value="<?= $job['address'] ?>" type="text" required>
			</div>
			<div class="item">
				<label for="working_time">Thời gian làm việc:</label>
				<select name="working_time" id="working_time" required>
					<?php foreach ($working_time as $v): ?>
						<option value="<?= $v ?>" <?= $v == $job['working_time'] ? 'selected' : '' ?>><?= $v ?></option>
					<?php endforeach ?>
				</select>
			</div>
			<div class="item">
				<label for="salary">Mức lương:</label>
				<input name="salary" id="salary" value="<?= $job['salary'] ?>" type="text" required>
			</div>
			<div class="item">
				<label for="application_deadline">Thời gian nộp hồ sơ:</label>
				<input name="application_deadline" id="application_deadline" value="<?= $job['application_deadline'] ?>" type="text" required>
			</div>
			<div class="item">
				<label for="desc">Mô tả công việc:</label>
				<textarea name="desc" id="desc" rows="6" required><?= str_replace('\n', "\n", $job['desc']) ?></textarea>
			</div>
			<div class="item">
				<label for="benefits">Quyền lợi:</label>
				<textarea name="benefits" id="benefits" rows="6" required><?= str_replace('\n', "\n", $job['benefits']) ?></textarea>
			</div>
			<div class="item">
				<label for="show">Hiển thị:</label>
				<input type="checkbox" name="show" id="show" <?= $job['show'] == 1 ? 'checked' : '' ?> value="1" required>
			</div>
			<div class="footer">
				<a class="button button-large" href="admin.php?page=career">Quay lại</a>
				<button type="submit" class="button button-primary button-large" style="height: 100%">Lưu</button>
			</div>
		</form>
	</div>
</div>