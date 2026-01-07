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

	.form .footer button {
		margin-top: 16px;
	}

	.form .item {
		margin-bottom: 16px;
		margin-top: 16px;
	}

	.form .item label {
		display: block;
		margin-bottom: 8px;
		font-size: 15px;
		font-weight: 500;
	}

	.form .item .child {
		display: flex;
		align-items: center;
		gap: 16px;
		margin-bottom: 4px;
	}

	.form .item .child label {
		margin-bottom: 0;
		width: 100px;
	}

	.form .group {
		border: 1px solid;
		border-radius: 8px;
		padding: 16px;
	}

	.form .group .item {
		margin-top: 8px;
		margin-bottom: 4px;
	}

	.form input:not([type="checkbox"]),
	.form textarea,
	.form select {
		padding: 2px 8px !important;
		box-shadow: 0 0 0 transparent;
		border-radius: 4px;
		border: 1px solid #8c8f94;
		background-color: #fff;
		color: #2c3338;
		width: 100%;
		max-width: 100%;
	}

	.form .logo-upload {
		display: flex;
		align-items: center;
		gap: 16px;
	}

	.form .logo-upload img {
		max-width: 150px;
		max-height: 80px;
		object-fit: contain;
		border: 1px solid #ddd;
		padding: 8px;
		border-radius: 4px;
		background: #f9f9f9;
	}

	.form .logo-upload img#logo_white_preview {
		background: #a2a2a2;
	}

	.form .logo-upload input[type="file"] {
		flex: 1;
	}

	.form .logo-upload .logo-preview {
		display: flex;
		flex-direction: column;
		gap: 8px;
	}

</style>
<div class="wrap">
	<h1 class="wp-heading-inline">Cấu hình Connest</h1>
	<div class="form">
		<form action="" method="POST" enctype="multipart/form-data">
			<input type="hidden" name="submit-config-form" value="1">
			<h3>Mạng xã hội:</h3>
			<div class="item">
				<div class="child">
					<label for="facebook">Facebook:</label>
					<input name="social[facebook]" id="facebook" value="<?= @$config['social']['facebook'] ?>" type="text">
				</div>
				<div class="child">
					<label for="zalo">Zalo:</label>
					<input name="social[zalo]" id="zalo" value="<?= @$config['social']['zalo'] ?>" type="text">
				</div>
				<div class="child">
					<label for="youtube">Youtube:</label>
					<input name="social[youtube]" id="youtube" value="<?= @$config['social']['youtube'] ?>" type="text">
				</div>
			</div>
			<div class="item">
				<label for="hotline">Hotline:</label>
				<input name="hotline" id="hotline" value="<?= @$config['hotline'] ?>" type="text">
			</div>
			<div class="item">
				<label for="email">Email:</label>
				<input name="email" id="email" value="<?= @$config['email'] ?>" type="text">
			</div>
			<div class="item">
				<label for="iframe_map">Nhúng bản đồ:</label>
				<input name="iframe_map" id="iframe_map" value='<?= @$config['iframe_map'] ?>' type="text">
			</div>
			<h3>Logo:</h3>
			<div class="item">
				<label for="logo_black">Logo Black:</label>
				<div class="logo-upload">
					<div class="logo-preview">
						<?php if (!empty($config['logo_black'])): ?>
							<img src="<?= esc_url($config['logo_black']) ?>" alt="Logo Black" id="logo_black_preview">
						<?php else: ?>
							<img src="" alt="Logo Black" id="logo_black_preview" style="display: none;">
						<?php endif; ?>
						<?php if (!empty($config['logo_black'])): ?>
							<input type="hidden" name="logo_black" value="<?= esc_attr($config['logo_black']) ?>">
						<?php endif; ?>
					</div>
					<input type="file" name="logo_black_file" id="logo_black" accept="image/*" onchange="previewLogo(this, 'logo_black_preview')">
				</div>
			</div>
			<div class="item">
				<label for="logo_white">Logo White:</label>
				<div class="logo-upload">
					<div class="logo-preview">
						<?php if (!empty($config['logo_white'])): ?>
							<img src="<?= esc_url($config['logo_white']) ?>" alt="Logo White" id="logo_white_preview">
						<?php else: ?>
							<img src="" alt="Logo White" id="logo_white_preview" style="display: none;">
						<?php endif; ?>
						<?php if (!empty($config['logo_white'])): ?>
							<input type="hidden" name="logo_white" value="<?= esc_attr($config['logo_white']) ?>">
						<?php endif; ?>
					</div>
					<input type="file" name="logo_white_file" id="logo_white" accept="image/*" onchange="previewLogo(this, 'logo_white_preview')">
				</div>
			</div>
			<h3>Chi nhánh 1:</h3>
			<div class="group">
				<div class="item">
					<label for="department_1_name">Tên chi nhánh:</label>
					<input name="department_1[name]" id="department_1_name" value="<?= @$config['department_1']['name'] ?>" type="text">
				</div>
				<div class="item">
					<label for="department_1_address">Địa chỉ chi nhánh:</label>
					<textarea name="department_1[address]" id="department_1_address"><?= @$config['department_1']['address'] ?></textarea>
				</div>
				<div class="item">
					<label for="phone">Điện thoại:</label>
					<input name="department_1[phone]" id="department_1_phone" value="<?= @$config['department_1']['phone'] ?>" type="text">
				</div>
				<div class="item">
					<label for="email">Email:</label>
					<input name="department_1[email]" id="department_1_email" value="<?= @$config['department_1']['email'] ?>" type="text">
				</div>
			</div>
			<h3>Chi nhánh 2:</h3>
			<div class="group">
				<div class="item">
					<label for="department_2_name">Tên chi nhánh:</label>
					<input name="department_2[name]" id="department_2_name" value="<?= @$config['department_2']['name'] ?>" type="text">
				</div>
				<div class="item">
					<label for="department_2_address">Địa chỉ chi nhánh:</label>
					<textarea name="department_2[address]" id="department_2_address"><?= @$config['department_2']['address'] ?></textarea>
				</div>
				<div class="item">
					<label for="phone">Điện thoại:</label>
					<input name="department_2[phone]" id="department_2_phone" value="<?= @$config['department_2']['phone'] ?>" type="text">
				</div>
				<div class="item">
					<label for="email">Email:</label>
					<input name="department_2[email]" id="department_2_email" value="<?= @$config['department_2']['email'] ?>" type="text">
				</div>
			</div>
			<div class="footer">
				<button type="submit" class="button button-primary button-large" style="height: 100%">Lưu</button>
			</div>
		</form>
	</div>
</div>
<script>
	function previewLogo(input, previewId) {
		if (input.files && input.files[0]) {
			var reader = new FileReader();
			reader.onload = function(e) {
				var preview = document.getElementById(previewId);
				preview.src = e.target.result;
				preview.style.display = 'block';
			}
			reader.readAsDataURL(input.files[0]);
		}
	}
</script>