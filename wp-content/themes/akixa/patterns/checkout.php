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

<div class="checkout-page margin-section">
	<div class="container">
		<h1 class="title text-center mb-0">Thanh toán</h1>
		
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

<?php get_footer(); ?>

