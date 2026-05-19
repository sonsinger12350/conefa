<?php
	get_header();

	$websiteName = get_bloginfo('name');
	$product_id = get_queried_object_id();
	$product = wc_get_product( $product_id );
	$images = [];
	$gallery_image_ids = $product->get_gallery_image_ids();

	if (!empty($gallery_image_ids)) {
		foreach ( $gallery_image_ids as $image_id ) {
			$images[] = wp_get_attachment_url( $image_id );
		}
	}

	$category_ids = $product->get_category_ids();
	$category = !empty($category_ids[0]) ? get_term( $category_ids[0], 'product_cat' ) : [];
	$tag_ids = $product->get_tag_ids();

	// Xây đường dẫn danh mục: tìm danh mục sâu nhất rồi trace ngược lên gốc
	$category_trail = [];
	if (!empty($category_ids)) {
		$deepest_id  = null;
		$max_depth   = -1;
		foreach ($category_ids as $cat_id) {
			$depth = count(get_ancestors($cat_id, 'product_cat'));
			if ($depth > $max_depth) {
				$max_depth  = $depth;
				$deepest_id = $cat_id;
			}
		}
		if ($deepest_id) {
			$ancestors = array_reverse(get_ancestors($deepest_id, 'product_cat'));
			foreach ($ancestors as $anc_id) {
				$category_trail[] = get_term($anc_id, 'product_cat');
			}
			$category_trail[] = get_term($deepest_id, 'product_cat');
		}
	}

	$cf_data = get_post_meta($product_id);
	$cf_product = array_map(function($value) {
		return $value[0];
	}, $cf_data);

	$promotions = [];
	for ($i = 1; $i <= 10; $i++) {
		$promo_text = $cf_product['promo_' . $i] ?? '';
		$promo_link = $cf_product['promo_link_' . $i] ?? '';
		if (!empty($promo_text)) {
			$promotions[] = ['text' => $promo_text, 'link' => $promo_link];
		}
	}

	$args = array(
        'post_type' => 'product',
        'posts_per_page' => 4,
        'post__not_in' => array( $product_id ),
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'id',
                'terms'    => $category_ids,
                'operator' => 'IN',
            ),
        ),
    );
	$related_products = new WP_Query( $args );
	$description = wc_format_content($product->get_description());

	$gallery_video_url = $cf_product['gallery_video'] ?? '';
	$gallery_video_id  = get_youtube_id($gallery_video_url);

	$_custom_sold   = get_post_meta($product_id, '_akixa_purchase_count', true);
	$_custom_rating = get_post_meta($product_id, '_akixa_star_rating', true);

	$total_sales  = $_custom_sold !== '' ? (int) $_custom_sold : (int) get_post_meta($product_id, 'total_sales', true);
	$avg_rating   = $_custom_rating !== '' ? (float) $_custom_rating : (float) $product->get_average_rating();
	$review_count = (int) $product->get_review_count();

	function format_sold_count($n) {
		if ($n >= 1000) return number_format($n / 1000, 1, ',', '.') . 'k';
		return $n;
	}

	$argsHot = array(
		'post_type'  => 'product',
		'post_status'  => 'publish',
		'meta_query' => array(
			array(
				'key'   => 'hot',
				'value' => '1',
				'compare' => '='
			)
			),
		'posts_per_page' => 4
	);
	$product_hot = new WP_Query($argsHot);
	$product_hot = !empty($product_hot->posts) ? $product_hot->posts : [];

	$product_prev = get_product_near($product_id, 'prev');
	$product_next = get_product_near($product_id, 'next');
?>
<div class="page">
	<div class="breadcrumb">
		<?php get_breadcrumb(); ?>
		<?php if (!empty($category_trail)): ?>
			<div class="breadcrumb-cats">
				<?php foreach ($category_trail as $k => $term): ?>
					<?php if ($k > 0): ?><span class="bc-sep">›</span><?php endif ?>
					<a href="<?= esc_url(get_term_link($term)) ?>"><?= esc_html($term->name) ?></a>
				<?php endforeach ?>
			</div>
		<?php endif ?>
	</div>
	<hr>
	<!-- FULL WIDTH: Tên sản phẩm -->
	<div class="product-header">
		<h1 class="product-title"><?= $product->get_name() ?></h1>
		<div class="product-info-bar">
			<span class="info-sold">
				Đã bán <?= format_sold_count($total_sales) ?>
			</span>
			<span class="info-sep">·</span>
			<a class="info-rating" href="#danh-gia">
				<i class="fa-solid fa-star"></i>
				<?= $avg_rating > 0 ? number_format($avg_rating, 1) : '0' ?>
			</a>
			<span class="info-sep">·</span>
			<a class="info-specs" href="#thong-so-ky-thuat">
				<i class="fa-solid fa-table-list"></i> Thông số
			</a>
		</div>
	</div>

	<!-- 2 CỘT: Gallery | Sidebar -->
	<div class="row product-main-row">

		<!-- CỘT TRÁI: Gallery -->
		<div class="col-lg-6 slide">
			<?php if (!empty($images) || !empty($gallery_video_id)): ?>
				<div class="big-image owl-carousel owl-theme position-relative">

					<?php if (!empty($gallery_video_id)): ?>
						<div class="item video-slide" data-video-id="<?= esc_attr($gallery_video_id) ?>">
							<div class="video-placeholder" style="position:relative;padding-top:56.25%;background:#000;cursor:pointer">
								<img src="https://img.youtube.com/vi/<?= $gallery_video_id ?>/hqdefault.jpg"
								     alt="video thumbnail"
								     style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;opacity:0.8">
								<div class="play-btn" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%)">
									<i class="fa-brands fa-youtube" style="font-size:4rem;color:#ff0000"></i>
								</div>
							</div>
							<div class="video-embed" style="display:none;position:relative;padding-top:56.25%">
								<iframe style="position:absolute;top:0;left:0;width:100%;height:100%"
								        src=""
								        data-src="https://www.youtube.com/embed/<?= $gallery_video_id ?>?autoplay=1"
								        frameborder="0" allowfullscreen allow="autoplay"></iframe>
							</div>
						</div>
					<?php endif ?>

					<?php foreach ($images as $k => $image): ?>
						<a class="item d-block" data-fancybox="gallery" href="<?= $image ?>">
							<img src="<?= $image ?>" alt="slide-<?= $k ?>" loading="lazy">
						</a>
					<?php endforeach ?>

				</div>
				<div class="list-image owl-carousel owl-theme position-relative">

					<?php if (!empty($gallery_video_id)):
						$offset = 1;
					?>
						<div class="item gallery-video <?= empty($images) ? 'selected' : '' ?>" data-slide="0">
							<div style="position:relative">
								<img src="https://img.youtube.com/vi/<?= $gallery_video_id ?>/default.jpg"
								     alt="video" loading="lazy">
								<i class="fa-solid fa-circle-play"
								   style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:#fff;font-size:1.5rem"></i>
							</div>
						</div>
					<?php else:
						$offset = 0;
					endif ?>

					<?php foreach ($images as $k => $image): ?>
						<div class="item gallery-<?= $k + $offset + 1 ?> <?= $k == 0 && empty($gallery_video_id) ? 'selected' : '' ?>"
						     data-slide="<?= $k + $offset ?>">
							<img src="<?= $image ?>" alt="gallery-<?= $k ?>" loading="lazy">
						</div>
					<?php endforeach ?>

				</div>
			<?php endif ?>

			<!-- BOX QUÀ TẶNG MIỄN PHÍ -->
			<div class="gift-box">
				<div class="gift-box-header">
					<span>Quà tặng miễn phí</span>
				</div>
				<div class="gift-box-body">
					<div class="gift-copy">
						<h3 class="gift-title">Hỗ trợ điều chỉnh mặt bằng theo đúng khu đất của bạn</h3>
						<p class="gift-desc">Áp dụng cho khách hàng đặt mua bản vẽ mẫu, giúp phương án phù hợp thực tế hơn trước khi triển khai.</p>
					</div>
					<div class="gift-actions">
						<a href="<?= esc_url( home_url( 'thanh-toan?id=' . $product_id ) ) ?>"
						   class="gift-btn-buy">
							Mua bản vẽ mẫu
						</a>
						<span class="gift-note">Nhận tư vấn nhanh sau khi đặt hàng</span>
					</div>
				</div>
			</div>

			<!-- THÔNG SỐ KỸ THUẬT -->
			<div class="product-specs" id="thong-so-ky-thuat">
				<div class="product-specs__header">
					<span class="product-specs__eyebrow">Hồ sơ tham khảo</span>
					<h3 class="section-title">Thông số kỹ thuật</h3>
					<p class="product-specs__intro">Tổng hợp nhanh các thông tin quan trọng để bạn đánh giá mức độ phù hợp của mẫu nhà với nhu cầu thực tế.</p>
				</div>
				<?php
					$attrs     = $product->get_attributes();
					$has_specs = false;
				?>
				<div class="specs-grid">
					<?php foreach ($attrs as $attr):
						if (!$attr || !$attr->get_visible()) continue;

						if ($attr->is_taxonomy()) {
							$attr_name = $attr->get_name();
							$values = wc_get_product_terms($product_id, $attr_name, ['fields' => 'names']);
							$value  = implode(', ', $values);
						} else {
							$value = implode(', ', $attr->get_options());
						}

						if (empty($value)) continue;
						$has_specs = true;
					?>
						<div class="spec-item">
							<span class="spec-label"><?= esc_html(wc_attribute_label($attr->get_name(), $product)) ?></span>
							<span class="spec-value"><?= esc_html($value) ?></span>
						</div>
					<?php endforeach ?>
				</div>
					<?php if (!$has_specs): ?>
						<p class="product-specs__empty">Chưa có thông số kỹ thuật.</p>
					<?php endif ?>
				</div>

				<!-- ACONE CAM KẾT -->
				<div class="product-commitment">
					<div class="product-commitment__header">
						<span class="product-commitment__eyebrow">Cam kết dịch vụ</span>
						<h4><i class="fa-solid fa-shield-halved"></i> Acone cam kết đồng hành rõ ràng và đúng chuyên môn</h4>
					</div>
					<div class="commitment-grid">
						<div class="commitment-item">
							<i class="fa-solid fa-drafting-compass"></i>
							<p>Bản vẽ nhà đã <strong>tối ưu công năng</strong>, giao thông, thông gió, nắng... do chính kiến trúc sư kiểm duyệt.</p>
						</div>
						<div class="commitment-item">
							<i class="fa-solid fa-circle-check"></i>
							<p>Bảo hành <strong>1 năm</strong> – Chỉnh sửa <strong>miễn phí</strong> nếu sai sót trong bản vẽ trong vòng 1 năm.</p>
						</div>
						<div class="commitment-item">
							<i class="fa-solid fa-handshake"></i>
							<p>Nếu khách hàng vẫn <strong>chưa chọn được phương án phù hợp</strong>, Acone có dịch vụ thiết kế bản vẽ <strong>may đo chuẩn</strong> (200k/m²) giúp giải quyết vấn đề.</p>
						</div>
					</div>
				</div>

				<!-- 3 TABS: Mô tả / Bạn nhận được gì / Không bao gồm -->
				<div class="tabs product-tabs" id="product-tabs">
					<nav class="mb-4">
						<div class="nav nav-tabs" id="nav-tab" role="tablist">
						<button class="nav-link active" id="description-tab"
							data-bs-toggle="tab" data-bs-target="#description"
							type="button" role="tab">MÔ TẢ</button>
						<button class="nav-link" id="include-tab"
							data-bs-toggle="tab" data-bs-target="#include"
							type="button" role="tab">BẠN NHẬN ĐƯỢC GÌ</button>
						<button class="nav-link" id="not-include-tab"
							data-bs-toggle="tab" data-bs-target="#not-include"
							type="button" role="tab">KHÔNG BAO GỒM</button>
					</div>
				</nav>
				<div class="tab-content" id="nav-tabContent">
					<!-- Tab 1: Mô tả — có expand/collapse -->
					<div class="tab-pane fade show active" id="description" role="tabpanel">
						<div class="tab-desc-content collapsed">
							<?= $description ?>
						</div>
						<button class="btn-expand-tab" type="button">
							Xem thêm đặc điểm nổi bật <i class="fa-solid fa-chevron-down"></i>
						</button>
					</div>
					<!-- Tab 2 -->
					<div class="tab-pane fade" id="include" role="tabpanel">
						<?= nl2br($cf_product['include'] ?? '') ?>
					</div>
					<!-- Tab 3 -->
					<div class="tab-pane fade" id="not-include" role="tabpanel">
						<?= nl2br($cf_product['not_include'] ?? '') ?>
					</div>
					</div>
				</div>

				<!-- BLOCK CTA INLINE -->
				<div class="inline-cta-block">
				<div class="inline-cta-left">
					<span class="inline-cta-kicker">Dành cho khách muốn chốt nhanh</span>
					<p class="inline-product-name"><?= esc_html( $product->get_name() ) ?></p>
					<a href="<?= esc_url( home_url( 'thanh-toan?id=' . $product_id ) ) ?>" class="btn-inline-order">
						ĐẶT HÀNG BẢN VẼ
					</a>
					<p class="inline-order-note">(Bản giao bản vẽ và PDF trong vòng 7 ngày)</p>
				</div>

				<div class="inline-cta-right">
					<span class="inline-cta-kicker">Cần tư vấn riêng</span>
					<p class="inline-cta-headline">Bạn vẫn chưa chọn được mẫu nhà thực sự phù hợp?</p>
					<button class="btn-inline-consult" id="btn-inline-open-consult" type="button">
						ĐĂNG KÝ NHẬN TƯ VẤN CHUYÊN SÂU TỪ KTS
					</button>
					<p class="inline-consult-note">cả buổi tìm hiểu, không bằng 15 phút gọi điện cùng chuyên gia</p>
				</div>
			</div>

			<div class="hr"></div>
			<?php if ($related_products->have_posts()): ?>
				<div class="product-related margin-section">
					<h5 class="title text-uppercase mb-0">Sản phẩm liên quan</h5>
					<hr>
					<div class="list owl-carousel owl-theme">
						<?php while ($related_products->have_posts()): ?>
							<?php 
								$id = get_the_ID();
								$related_products->the_post();
								$data = wc_get_product( $id );
								$image = get_the_post_thumbnail_url( $id, 'full' );
							?>
							<div class="item product">
								<a class="image" href="<?= $data->get_permalink() ?>">
									<img src="<?= $image ?>" alt="<?= $data->get_name() ?>" loading="lazy">
								</a>
								<div class="content">
									<div class="categories">
										<?= wc_get_product_category_list($id) ?>
									</div>
									<p class="name"><?= $data->get_name() ?></p>
									<p class="price"><?= !empty($data->get_price()) ? wc_price($data->get_price()) : 'Liên hệ' ?></p>
									<!-- <div class="btn-buy">
										<?php if (!empty($product->get_price())): ?>
											<button class="btn btn-outline-dark btn-sm" type="button"><i class="fa-solid fa-cart-shopping"></i> Thêm vào giỏ hàng</button>
											<button class="btn btn-dark" type="button">Mua ngay</button>
										<?php endif ?>
									</div> -->
								</div>
							</div>
						<?php endwhile ?>
					</div>
				</div>
				<?php wp_reset_postdata(); ?>
			<?php endif ?>

			<!-- SECTION ĐÁNH GIÁ -->
			<?php wc_get_template( 'single-product-reviews.php', array( 'product' => $product ) ); ?>
		</div>

		<!-- CỘT PHẢI: Sidebar mới -->
		<div class="col-lg-6 product-sidebar">

			<!-- 3.1 — 3 Icon hành động nhanh -->
			<div class="sidebar-quick-icons">
				<div class="quick-icon-item quick-icon-item--pdf">
					<div class="quick-icon-badge">
						<img class="quick-icon-img"
						     src="<?= esc_url( get_template_directory_uri() . '/assets/images/product-icons/pdf-svgrepo-com.svg' ) ?>"
						     alt="Gửi file PDF" loading="lazy">
						<span class="quick-icon-check" aria-hidden="true">
							<svg viewBox="0 0 20 20" fill="none">
								<path d="M5 10.5L8.2 13.7L15 6.9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</span>
					</div>
					<span class="quick-icon-divider" aria-hidden="true"></span>
					<h3 class="quick-icon-title">NHANH GỬI FILE PDF CÓ NGAY</h3>
				</div>
				<div class="quick-icon-item quick-icon-item--construction">
					<div class="quick-icon-badge">
						<img class="quick-icon-img"
						     src="<?= esc_url( get_template_directory_uri() . '/assets/images/product-icons/construction-hammer-svgrepo-com.svg' ) ?>"
						     alt="Thi công được ngay" loading="lazy">
						<span class="quick-icon-check" aria-hidden="true">
							<svg viewBox="0 0 20 20" fill="none">
								<path d="M5 10.5L8.2 13.7L15 6.9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</span>
					</div>
					<span class="quick-icon-divider" aria-hidden="true"></span>
					<h3 class="quick-icon-title">CHI TIẾT, THI CÔNG ĐƯỢC NGAY</h3>
				</div>
				<div class="quick-icon-item quick-icon-item--budget">
					<div class="quick-icon-badge">
						<img class="quick-icon-img"
						     src="<?= esc_url( get_template_directory_uri() . '/assets/images/product-icons/book-open-svgrepo-com.svg' ) ?>"
						     alt="Tiết kiệm ngân sách" loading="lazy">
						<span class="quick-icon-check" aria-hidden="true">
							<svg viewBox="0 0 20 20" fill="none">
								<path d="M5 10.5L8.2 13.7L15 6.9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</span>
					</div>
					<span class="quick-icon-divider" aria-hidden="true"></span>
					<h3 class="quick-icon-title">BẰNG 1/10 NGÂN SÁCH BẢN VẼ MỚI</h3>
				</div>
			</div>

			<!-- 3.2 + 3.3 — Khối khuyến mãi + CTA -->
			<div class="sidebar-promo sidebar-offer-panel" id="sidebar-promo">
				<div class="offer-panel__hero">
					<div class="offer-panel__eyebrow">Ưu đãi trong tháng <?= date('n') ?></div>
					<h3 class="offer-panel__title">Không gian đẹp bắt đầu từ một bộ hồ sơ rõ ràng và chỉn chu.</h3>
					<p class="offer-panel__subtitle">Nhận bản vẽ nhanh, tư vấn dễ hiểu và các khuyến mãi được tối ưu riêng cho khách hàng trong tháng này.</p>
				</div>

				<?php if (!empty($promotions)): ?>
				<div class="offer-panel__list-wrap">
					<div class="promo-header">
						<span>KHUYẾN MẠI THÁNG <?= date('n') ?></span>
					</div>
					<p class="promo-subtitle">Giá và khuyến mãi dự kiến áp dụng trong tháng <?= date('n') ?></p>
					<ul class="promo-list">
						<?php foreach ($promotions as $index => $promo): ?>
							<li>
								<span class="promo-index">0<?= $index + 1 ?></span>
								<?php if (!empty($promo['link'])): ?>
									<a href="<?= esc_url($promo['link']) ?>" target="_blank"><?= esc_html($promo['text']) ?></a>
								<?php else: ?>
									<span><?= esc_html($promo['text']) ?></span>
								<?php endif ?>
							</li>
						<?php endforeach ?>
					</ul>
				</div>
				<?php endif ?>

				<div class="sidebar-cta">
					<div class="offer-panel__actions">
						<a class="btn-order-main" href="<?= home_url('thanh-toan?id='.$product_id) ?>">
							ĐẶT HÀNG BẢN VẼ
						</a>
						<p class="order-note">Bản giao PDF và hồ sơ triển khai dự kiến trong vòng 7 ngày.</p>
					</div>
					<form class="order-form" data-product-quick-call-form>
						<input type="text" name="request-name" class="form-control" placeholder="Họ và tên" autocomplete="name" required>
						<input type="tel" name="request-phone" class="form-control" placeholder="Số điện thoại" autocomplete="tel" required>
						<select name="request-service" class="form-select" required>
							<option value="">Chọn dịch vụ</option>
							<option>Bản vẽ mẫu</option>
							<option>Khảo sát đất và tư vấn bản vẽ 2D</option>
							<option>Làm bản vẽ mới</option>
							<option>Thi công trọn gói</option>
						</select>
						<button class="btn btn-success" type="submit">Gọi cho tôi</button>
						<p class="order-form-message" aria-live="polite"></p>
					</form>
				</div>
			</div>

			<!-- 3.4 — Thanh liên hệ -->
			<div class="sidebar-contact">
				<a class="contact-item phone" href="tel:0988870288">
					<img class="contact-icon-img"
					     src="<?= esc_url( get_template_directory_uri() . '/assets/images/product-icons/call-cell-communication-phone-ring-talk-svgrepo-com.svg' ) ?>"
					     alt="Gọi điện" loading="lazy">
					<div>
						<strong>09888.702.88</strong>
						<small>5 phút gọi hơn hàng buổi tìm hiểu</small>
					</div>
				</a>
				<a class="contact-item fanpage" href="https://www.facebook.com/messages/t/952138177978565" target="_blank" rel="noopener">
					<img class="contact-icon-img"
					     src="<?= esc_url( get_template_directory_uri() . '/assets/images/product-icons/facebook-messenger-svgrepo-com.svg' ) ?>"
					     alt="Messenger" loading="lazy">
					<div>
						<strong>Fanpage</strong>
						<small>aconenhavuon</small>
					</div>
				</a>
				<a href="#product-request-modal" class="contact-btn btn-edit" data-product-request-open data-request-type="Chỉnh sửa bản vẽ">
					<i class="fa-solid fa-pen"></i> Chỉnh sửa bản vẽ
				</a>
				<a href="#product-request-modal" class="contact-btn btn-new" data-product-request-open data-request-type="Yêu cầu bản vẽ mới">
					<i class="fa-solid fa-plus"></i> Yêu cầu bản vẽ mới
				</a>
			</div>

			<?php
				$product_request_form_id = function_exists( 'akixa_get_product_request_cf7_form_id' ) ? akixa_get_product_request_cf7_form_id() : 0;
			?>
			<?php if ( $product_request_form_id ): ?>
				<div class="product-request-modal" id="product-request-modal"
				     data-product-id="<?= esc_attr( $product_id ) ?>"
				     data-product-name="<?= esc_attr( $product->get_name() ) ?>"
				     data-product-url="<?= esc_url( get_permalink( $product_id ) ) ?>"
				     hidden aria-hidden="true">
					<div class="product-request-modal__backdrop" data-product-request-close></div>
					<div class="product-request-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="product-request-modal-title">
						<button class="product-request-modal__close" type="button" data-product-request-close aria-label="Đóng">×</button>
						<span class="product-request-modal__eyebrow">Gửi yêu cầu</span>
						<h3 class="product-request-modal__title" id="product-request-modal-title">Để lại thông tin, Acone sẽ liên hệ tư vấn cho bạn</h3>
						<p class="product-request-modal__desc">Thông tin được gửi kèm sản phẩm hiện tại để kiến trúc sư nắm đúng nhu cầu của bạn.</p>
						<?= do_shortcode( '[contact-form-7 id="' . absint( $product_request_form_id ) . '"]' ) ?>
					</div>
				</div>
			<?php endif ?>

			<!-- 3.5 — Thống kê -->
			<div class="sidebar-stats">
				<?php
					$rating = (float) $product->get_average_rating();
					$count  = (int) $product->get_review_count();
					$active_stars = (int) round($rating);
				?>
				<div class="cmt-top-star" aria-label="<?= esc_attr(sprintf('Đánh giá %.1f sao', $rating)) ?>">
					<?php for ($i = 1; $i <= 5; $i++): ?>
						<i class="iconcmt-starbuy fa-solid fa-star<?= $i <= $active_stars ? ' is-active' : '' ?>" aria-hidden="true"></i>
					<?php endfor ?>
				</div>
				<div class="sidebar-stats__text">
					<strong><?= number_format($rating, 1) ?></strong>
					<span>(<?= $count ?> Đánh giá)</span>
				</div>
			</div>

		</div><!-- end .product-sidebar -->

	</div><!-- end .product-main-row -->
</div>
	
<?php
	get_footer();
?>
