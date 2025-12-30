<?php

	/**
	 * Template Name: Thanh toán
	 */

	get_header();
	$websiteName = get_bloginfo('name');
	
	// Get product ID from URL
	$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	
	if (!$product_id) {
		wp_redirect(home_url());
		exit;
	}
	
	$product = wc_get_product($product_id);
	
	if (!$product || !$product->is_purchasable()) {
		wp_redirect(home_url());
		exit;
	}
	
	
	$product_image = $product->get_image('full', ['class' => 'img-fluid']);
	$product_name = $product->get_name();
	$product_price = $product->get_price() ? wc_price($product->get_price()) : 'Liên hệ';
	$cf_data = get_post_meta($product_id);
	$cf_product = array_map(function($value) {
		return $value[0];
	}, $cf_data);
?>
<style>
	.checkout-page {
		padding: 40px 0;
	}
	.checkout-form-wrapper {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 40px;
		margin-top: 40px;
	}
	.checkout-form {
		background: #fff;
		padding: 30px;
		border-radius: 8px;
		box-shadow: 0 2px 10px rgba(0,0,0,0.1);
	}
	.checkout-product-info {
		background: #f8f9fa;
		padding: 30px;
		border-radius: 8px;
	}
	.checkout-form .form-group {
		margin-bottom: 20px;
	}
	.checkout-form label {
		display: block;
		margin-bottom: 8px;
		font-weight: 600;
		color: #333;
	}
	.checkout-form input[type="text"],
	.checkout-form input[type="email"] {
		width: 100%;
		padding: 12px;
		border: 1px solid #ddd;
		border-radius: 4px;
		font-size: 16px;
	}
	.checkout-form button {
		width: 100%;
		padding: 12px;
		background: #000;
		color: #fff;
		border: none;
		border-radius: 4px;
		font-size: 16px;
		font-weight: 600;
		cursor: pointer;
		transition: background 0.3s;
	}
	.checkout-form button:hover:not(:disabled) {
		background: #333;
	}
	.checkout-form button:disabled {
		opacity: 0.6;
		cursor: not-allowed;
	}
	.checkout-product-info img {
		width: 100%;
		height: auto;
		border-radius: 8px;
		margin-bottom: 20px;
	}
	.checkout-product-info h3 {
		margin-bottom: 15px;
		font-size: 24px;
	}
	.checkout-product-info .price {
		font-size: 28px;
		font-weight: bold;
		color: #000;
		margin-bottom: 20px;
	}
	.modal-qr {
		display: none;
		position: fixed;
		z-index: 10000;
		left: 0;
		top: 0;
		width: 100%;
		height: 100%;
		background-color: rgba(0,0,0,0.7);
	}
	.modal-qr.active {
		display: flex;
		align-items: center;
		justify-content: center;
	}
	.modal-qr-content {
		background: #fff;
		padding: 40px;
		border-radius: 8px;
		max-width: 600px;
		width: 90%;
		max-height: 90vh;
		overflow-y: auto;
		position: relative;
	}
	.modal-qr-close {
		position: absolute;
		top: 15px;
		right: 15px;
		font-size: 28px;
		cursor: pointer;
		color: #999;
	}
	.modal-qr-close:hover {
		color: #000;
	}
	.qr-image {
		width: 100%;
		max-width: 300px;
		margin: 20px auto;
		display: block;
	}
	@media (max-width: 768px) {
		.checkout-form-wrapper {
			grid-template-columns: 1fr;
		}
	}
</style>

<div class="checkout-page margin-section">
	<div class="container">
		<h1 class="title text-center mb-4">Thanh toán</h1>
		
		<div class="checkout-form-wrapper">
			<div class="checkout-form">
				<div class="alert alert-danger d-none" id="checkout-error" style="padding: 12px; margin-bottom: 20px; background: #f8d7da; color: #721c24; border-radius: 4px;"></div>
				<form id="checkout-form">
					<?php wp_nonce_field('checkout_nonce', 'checkout_nonce'); ?>
					<input type="hidden" name="product_id" id="product_id" value="<?= esc_attr($product_id) ?>">
					<div class="form-group">
						<label for="customer_name">Họ và tên *</label>
						<input type="text" id="customer_name" name="customer_name" required>
					</div>
					<div class="form-group">
						<label for="customer_email">Email *</label>
						<input type="email" id="customer_email" name="customer_email" required>
					</div>
					<button type="submit" id="checkout-submit-btn">Thanh toán</button>
				</form>
			</div>
			
			<div class="checkout-product-info">
				<?= $product_image ?>
				<h3><?= esc_html($product_name) ?></h3>
				<div class="price"><?= $product_price ?></div>
				<?php if ($cf_product['include']): ?>
					<h4>Bạn nhận được gì?</h4>
					<div class="description">
						<?= nl2br($cf_product['include']) ?>
					</div>
				<?php endif ?>
			</div>
		</div>
	</div>
</div>

<!-- Modal QR Code -->
<div class="modal-qr" id="qr-modal">
	<div class="modal-qr-content">
		<span class="modal-qr-close" onclick="closeQRModal()">&times;</span>
		<h2 style="margin-bottom: 20px;">Thanh toán qua chuyển khoản ngân hàng</h2>
		<div id="qr-modal-content">
			<!-- Content will be inserted here via AJAX -->
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	$('#checkout-form').on('submit', function(e) {
		e.preventDefault();
		
		var form = $(this);
		var submitBtn = $('#checkout-submit-btn');
		var errorDiv = $('#checkout-error');
		var btnHtml = submitBtn.html();
		
		// Reset error
		errorDiv.addClass('d-none').html('');
		
		// Validate form
		var customerName = $('#customer_name').val().trim();
		var customerEmail = $('#customer_email').val().trim();
		
		if (!customerName || !customerEmail) {
			errorDiv.removeClass('d-none').html('Vui lòng điền đầy đủ thông tin.');
			return false;
		}
		
		if (!isValidEmail(customerEmail)) {
			errorDiv.removeClass('d-none').html('Email không hợp lệ.');
			return false;
		}
		
		// Disable button and show loading
		submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-pulse"></i> Đang xử lý...');
		
		// Prepare data
		var formData = {
			action: 'create_checkout_order',
			nonce: $('#checkout_nonce').val(),
			product_id: $('#product_id').val(),
			customer_name: customerName,
			customer_email: customerEmail
		};
		
		// Send AJAX request
		$.ajax({
			url: adminAjaxUrl,
			type: 'POST',
			data: formData,
			success: function(response) {
				submitBtn.prop('disabled', false).html(btnHtml);
				
				if (response.success && response.data) {
					// Show modal with QR code
					showQRModal(response.data);
					// Reset form
					form[0].reset();
				} else {
					var errorMsg = response.data && response.data.message ? response.data.message : 'Có lỗi xảy ra. Vui lòng thử lại.';
					errorDiv.removeClass('d-none').html(errorMsg);
				}
			},
			error: function(xhr, status, error) {
				submitBtn.prop('disabled', false).html(btnHtml);
				errorDiv.removeClass('d-none').html('Có lỗi xảy ra khi kết nối server. Vui lòng thử lại.');
			}
		});
		
		return false;
	});
	
	function isValidEmail(email) {
		var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		return re.test(email);
	}
	
	function showQRModal(data) {
		var modal = $('#qr-modal');
		var content = $('#qr-modal-content');
		
		var bankLogoHtml = '';
		if (data.bank_logo_url) {
			bankLogoHtml = '<img src="' + escapeHtml(data.bank_logo_url) + '" alt="Bank Logo" style="max-width: 150px; margin-bottom: 20px;">';
		}
		
		var html = '<div class="qr-box" style="text-align: center; margin-bottom: 30px;">' +
			'<p style="margin-bottom: 15px; font-weight: 600;">Cách 1: Mở app ngân hàng/ Ví và <b>quét mã QR</b></p>' +
			'<img src="' + escapeHtml(data.qr_code_url) + '" alt="QR Code" class="qr-image">' +
			'<div style="margin-top: 15px;">' +
			'<a href="' + escapeHtml(data.qr_code_url) + '&download=yes" download="" style="display: inline-block; padding: 10px 20px; background: #007bff; color: #fff; text-decoration: none; border-radius: 4px;">Tải ảnh QR</a>' +
			'</div>' +
			'</div>' +
			'<div class="manual-box" style="border-top: 1px solid #ddd; padding-top: 20px;">' +
			'<p style="margin-bottom: 15px; font-weight: 600;">Cách 2: Chuyển khoản <b>thủ công</b> theo thông tin</p>' +
			bankLogoHtml +
			'<table style="width: 100%; border-collapse: collapse;">' +
			'<tr style="border-bottom: 1px solid #eee;">' +
			'<td style="padding: 10px; font-weight: 600;">Ngân hàng</td>' +
			'<td style="padding: 10px; text-align: right; font-weight: 600;">' + escapeHtml(data.displayed_bank_name) + '</td>' +
			'</tr>' +
			'<tr style="border-bottom: 1px solid #eee;">' +
			'<td style="padding: 10px; font-weight: 600;">Thụ hưởng</td>' +
			'<td style="padding: 10px; text-align: right; font-weight: 600;">' + escapeHtml(data.account_holder_name) + '</td>' +
			'</tr>' +
			'<tr style="border-bottom: 1px solid #eee;">' +
			'<td style="padding: 10px; font-weight: 600;">Số tài khoản</td>' +
			'<td style="padding: 10px; text-align: right; font-weight: 600;">' + escapeHtml(data.account_number) + '</td>' +
			'</tr>' +
			'<tr style="border-bottom: 1px solid #eee;">' +
			'<td style="padding: 10px; font-weight: 600;">Số tiền</td>' +
			'<td style="padding: 10px; text-align: right; font-weight: 600;">' + data.amount_formatted + '</td>' +
			'</tr>' +
			'<tr>' +
			'<td style="padding: 10px; font-weight: 600;">Nội dung CK</td>' +
			'<td style="padding: 10px; text-align: right; font-weight: 600;">' + escapeHtml(data.remark) + '</td>' +
			'</tr>' +
			'</table>' +
			'<div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 4px; color: #856404;">' +
			'<strong>Lưu ý:</strong> Vui lòng giữ nguyên nội dung chuyển khoản <b>' + escapeHtml(data.remark) + '</b> để xác nhận thanh toán tự động.' +
			'</div>' +
			'</div>';
		
		content.html(html);
		modal.addClass('active');
	}
	
	function closeQRModal() {
		$('#qr-modal').removeClass('active');
	}
	
	function escapeHtml(text) {
		var map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};
		return text ? text.replace(/[&<>"']/g, function(m) { return map[m]; }) : '';
	}
	
	// Close modal when clicking outside
	$('#qr-modal').on('click', function(e) {
		if (e.target === this) {
			closeQRModal();
		}
	});
	
	// Close modal with Escape key
	$(document).on('keydown', function(e) {
		if (e.key === 'Escape') {
			closeQRModal();
		}
	});
});
</script>

<?php get_footer(); ?>

