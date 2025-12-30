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
<link rel="stylesheet" href="<?= get_template_directory_uri(); ?>/assets/css/checkout.css">
<div class="checkout-page margin-section">
	<div class="container">
		<h1 class="title text-center mb-4">Thanh toán</h1>
		
		<!-- Checkout Steps -->
		<div class="checkout-steps">
			<div class="checkout-step active" id="step-1">
				<div class="step-number">1</div>
				<div class="step-label">Nhập thông tin</div>
			</div>
			<div class="checkout-step" id="step-2">
				<div class="step-number">2</div>
				<div class="step-label">Thanh toán</div>
			</div>
			<div class="checkout-step" id="step-3">
				<div class="step-number">3</div>
				<div class="step-label">Thành công</div>
			</div>
		</div>
		
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
		
		<!-- Payment Info Section -->
		<div class="payment-info-section" id="payment-info-section">
			<div class="payment-info-box">
				<h2>Thông tin chuyển khoản</h2>
				<div id="payment-info-content">
					<!-- Content will be inserted here via AJAX -->
				</div>
				<button type="button" id="confirm-payment-btn" class="confirm-payment-btn">
					Xác nhận đã thanh toán
				</button>
			</div>
		</div>
		
		<!-- Thank You Section -->
		<div class="thank-you-section" id="thank-you-section">
			<h2>Cảm ơn bạn đã thanh toán!</h2>
			<p style="font-size: 18px; margin-top: 20px;">Đơn hàng của bạn đã được xác nhận thành công.</p>
			<p style="margin-top: 15px;" id="download-status">Các file tải xuống sẽ được tự động tải về trong giây lát...</p>
			<div class="download-files-list" id="download-files-list" style="display: none;">
				<h3 style="margin-bottom: 20px; color: #155724;">Tải xuống file:</h3>
				<div id="download-files-content"></div>
			</div>
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
					// Hide form and show payment info
					$('.checkout-form-wrapper').hide();
					showPaymentInfo(response.data);
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
	
	var currentOrderId = null;
	var currentDownloads = [];
	
	function updateStep(stepNumber) {
		// Remove active from all steps
		$('.checkout-step').removeClass('active completed');
		
		// Mark previous steps as completed
		for (var i = 1; i < stepNumber; i++) {
			$('#step-' + i).addClass('completed');
		}
		
		// Mark current step as active
		$('#step-' + stepNumber).addClass('active');
	}
	
	function showPaymentInfo(data) {
		var section = $('#payment-info-section');
		var content = $('#payment-info-content');
		
		currentOrderId = data.order_id;
		
		// Update step to 2
		updateStep(2);
		
		var bankLogoHtml = '';
		if (data.bank_logo_url) {
			bankLogoHtml = '<img src="' + escapeHtml(data.bank_logo_url) + '" alt="Bank Logo" style="max-width: 150px; margin-bottom: 20px;">';
		}
		
		var html = '<div class="qr-box">' +
			'<p style="margin-bottom: 15px; font-weight: 600;">Cách 1: Mở app ngân hàng/ Ví và <b>quét mã QR</b></p>' +
			'<img src="' + escapeHtml(data.qr_code_url) + '" alt="QR Code" class="qr-image">' +
			'<div style="margin-top: 15px;">' +
			'<a href="' + escapeHtml(data.qr_code_url) + '&download=yes" download="" style="display: inline-block; padding: 10px 20px; background: #007bff; color: #fff; text-decoration: none; border-radius: 4px;">Tải ảnh QR</a>' +
			'</div>' +
			'</div>' +
			'<div class="manual-box">' +
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
		section.addClass('active');
		
		// Scroll to payment info
		$('html, body').animate({
			scrollTop: section.offset().top - 100
		}, 500);
	}
	
	// Handle confirm payment button
	$('#confirm-payment-btn').on('click', function() {
		if (!currentOrderId) return;
		
		var btn = $(this);
		var btnHtml = btn.html();
		
		btn.prop('disabled', true).html('<i class="fas fa-spinner fa-pulse"></i> Đang kiểm tra...');
		
		$.ajax({
			url: adminAjaxUrl,
			type: 'POST',
			data: {
				action: 'check_order_status',
				nonce: $('#checkout_nonce').val(),
				order_id: currentOrderId
			},
			success: function(response) {
				if (response.success && response.data) {
					if (response.data.status === 'completed') {
						// Order is completed, show thank you and download files
						$('#payment-info-section').removeClass('active');
						$('#thank-you-section').addClass('active');
						
						// Update step to 3
						updateStep(3);
						
						// Store downloads
						currentDownloads = response.data.downloads || [];
						
						// Download files automatically
						if (currentDownloads.length > 0) {
							var downloadAttempted = false;
							setTimeout(function() {
								currentDownloads.forEach(function(download, index) {
									setTimeout(function() {
										try {
											var link = document.createElement('a');
											link.href = download.url;
											link.download = download.name || 'download';
											link.target = '_blank';
											document.body.appendChild(link);
											link.click();
											document.body.removeChild(link);
											downloadAttempted = true;
										} catch (e) {
											console.error('Auto download failed:', e);
										}
									}, index * 500); // Delay each download by 500ms
								});
								
								// Show download buttons after auto download attempt
								setTimeout(function() {
									showDownloadButtons();
								}, (currentDownloads.length * 500) + 2000);
							}, 1000);
						} else {
							$('#download-status').html('Không có file tải xuống.');
						}
						
						// Scroll to thank you section
						$('html, body').animate({
							scrollTop: $('#thank-you-section').offset().top - 100
						}, 500);
					} else {
						// Order not completed yet
						btn.prop('disabled', false).html(btnHtml);
						alert('Đơn hàng chưa được xác nhận thanh toán. Vui lòng đợi thêm một chút và thử lại.');
					}
				} else {
					btn.prop('disabled', false).html(btnHtml);
					var errorMsg = response.data && response.data.message ? response.data.message : 'Có lỗi xảy ra. Vui lòng thử lại.';
					alert(errorMsg);
				}
			},
			error: function() {
				btn.prop('disabled', false).html(btnHtml);
				alert('Có lỗi xảy ra khi kết nối server. Vui lòng thử lại.');
			}
		});
	});
	
	function showDownloadButtons() {
		if (currentDownloads.length > 0) {
			var filesContent = $('#download-files-content');
			var html = '';
			
			currentDownloads.forEach(function(download) {
				html += '<div class="download-file-item">' +
					'<span class="file-name">' + escapeHtml(download.name || 'File tải xuống') + '</span>' +
					'<a href="' + escapeHtml(download.url) + '" class="download-btn" target="_blank" download>' +
					'<i class="fas fa-download"></i> Tải xuống' +
					'</a>' +
					'</div>';
			});
			
			filesContent.html(html);
			$('#download-files-list').show();
			$('#download-status').html('Nếu file không tự động tải xuống, vui lòng sử dụng các nút bên dưới:');
		}
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
	
});
</script>

<?php get_footer(); ?>

