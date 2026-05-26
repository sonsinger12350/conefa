<?php
/**
 * Single product reviews (custom layout) — IMPL-07
 *
 * @package Akixa
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $product ) || ! is_a( $product, 'WC_Product' ) ) {
	return;
}

$product_id       = $product->get_id();
$reviews_enabled = function_exists( 'wc_reviews_enabled' ) && wc_reviews_enabled();
$count      = $product->get_review_count();
$avg_rating = (float) $product->get_average_rating();

$star_counts = array();
for ( $s = 5; $s >= 1; $s-- ) {
	$star_counts[ $s ] = (int) get_comments(
		array(
			'post_id'    => $product_id,
			'status'     => 'approve',
			'type'       => 'review',
			'meta_query' => array(
				array(
					'key'   => 'rating',
					'value' => (string) $s,
				),
			),
			'count'      => true,
		)
	);
}
?>

<div class="product-reviews" id="danh-gia">
	<h3 class="section-title"><?php echo esc_html__( 'Đánh giá', 'akixa' ); ?> <?php echo esc_html( $product->get_name() ); ?></h3>

	<div class="reviews-summary">
		<div class="summary-score">
			<span class="score-big"><?php echo esc_html( number_format( $avg_rating, 1 ) ); ?></span>
			<div class="score-stars"><?php echo wc_get_star_rating_html( $avg_rating ); ?></div>
			<small><?php echo esc_html( (string) $count ); ?> <?php echo esc_html__( 'đánh giá', 'akixa' ); ?></small>
		</div>
		<div class="summary-bars">
			<?php
			for ( $s = 5; $s >= 1; $s-- ) :
				$pct = $count > 0 ? round( $star_counts[ $s ] / $count * 100, 1 ) : 0;
				?>
				<div class="bar-row">
					<span class="bar-label"><?php echo (int) $s; ?>★</span>
					<div class="bar-track">
						<div class="bar-fill" style="width:<?php echo esc_attr( (string) $pct ); ?>%"></div>
					</div>
					<span class="bar-pct"><?php echo esc_html( (string) $pct ); ?>%</span>
				</div>
			<?php endfor; ?>
		</div>
	</div>

	<?php
	$comments = get_comments(
		array(
			'post_id' => $product_id,
			'status'  => 'approve',
			'type'    => 'review',
			'orderby' => 'comment_date',
			'order'   => 'DESC',
		)
	);
	?>

	<?php if ( ! empty( $comments ) ) : ?>
	<ul class="reviews-list">
		<?php
		foreach ( $comments as $comment ) :
			$rating = (int) get_comment_meta( $comment->comment_ID, 'rating', true );
			?>
			<li id="r-<?php echo (int) $comment->comment_ID; ?>" class="review-item par">
				<div class="cmt-top">
					<p class="cmt-top-name"><?php echo esc_html( $comment->comment_author ); ?></p>
					<div class="confirm-buy">
						<i class="iconcmt-confirm fa-regular fa-circle-check" aria-hidden="true"></i>
						<?php echo esc_html__( 'Đã mua tại Acone', 'akixa' ); ?>
					</div>
				</div>

				<div class="cmt-intro">
					<div class="cmt-top-star" aria-label="<?php echo esc_attr( sprintf( __( 'Đánh giá %d sao', 'akixa' ), $rating ) ); ?>">
						<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
							<i class="iconcmt-starbuy fa-solid fa-star<?php echo $i <= $rating ? ' is-active' : ''; ?>" aria-hidden="true"></i>
						<?php endfor; ?>
					</div>
					<p class="txt-intro">
						<i class="iconcmt-heart fa-solid fa-heart" aria-hidden="true"></i>
						<?php echo esc_html__( 'Sẽ giới thiệu...', 'akixa' ); ?>
					</p>
				</div>

				<div class="cmt-content">
					<p class="cmt-txt review-content"><?php echo esc_html( $comment->comment_content ); ?></p>
				</div>

				<div class="cmt-command">
					<span class="cmtl dot-circle-ava">
						<i id="l-<?php echo (int) $comment->comment_ID; ?>" class="iconcmt-thumpup fa-regular fa-thumbs-up" aria-hidden="true"></i>
						<?php echo esc_html__( 'Hữu ích', 'akixa' ); ?>
					</span>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php else : ?>
		<p class="no-reviews"><?php echo esc_html__( 'Chưa có đánh giá nào. Hãy là người đầu tiên!', 'akixa' ); ?></p>
	<?php endif; ?>

	<?php if ( $reviews_enabled ) : ?>
		<div class="review-form-wrap">
			<button class="btn-write-review" id="btn-write-review" type="button">
				<?php echo esc_html__( 'Viết đánh giá', 'akixa' ); ?>
			</button>
			<div class="review-form-box" id="review-form-box" style="display:none">
				<?php
				$commenter = wp_get_current_commenter();
				comment_form(
					array(
						'title_reply'         => '',
						'title_reply_before'  => '',
						'title_reply_after'   => '',
						'comment_notes_after' => '',
						'label_submit'        => __( 'Gửi đánh giá', 'akixa' ),
						'class_submit'        => 'btn btn-success',
						'fields'              => array(
							'author' => '<p class="comment-form-author">
								<label for="author">' . esc_html__( 'Tên của bạn', 'akixa' ) . ' <span class="required">*</span></label>
								<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" required>
							</p>',
							'email'  => '<p class="comment-form-email">
								<label for="email">' . esc_html__( 'Email', 'akixa' ) . '</label>
								<input id="email" name="email" type="email" value="' . esc_attr( $commenter['comment_author_email'] ) . '">
							</p>',
						),
						'comment_field'       => '
							<p class="comment-form-rating">
								<label>' . esc_html__( 'Đánh giá', 'akixa' ) . ' <span class="required">*</span></label>
								<span class="stars">
									<a class="star-1" href="#" aria-label="1">1</a>
									<a class="star-2" href="#" aria-label="2">2</a>
									<a class="star-3" href="#" aria-label="3">3</a>
									<a class="star-4" href="#" aria-label="4">4</a>
									<a class="star-5" href="#" aria-label="5">5</a>
								</span>
							</p>
							<p class="comment-form-comment">
								<label for="comment">' . esc_html__( 'Nội dung', 'akixa' ) . ' <span class="required">*</span></label>
								<textarea id="comment" name="comment" rows="4" required></textarea>
							</p>',
					),
					$product_id
				);
				?>
			</div>
		</div>
	<?php else : ?>
		<p class="reviews-closed text-muted small"><?php esc_html_e( 'Đánh giá sản phẩm đang tắt trong cài đặt WooCommerce.', 'akixa' ); ?></p>
	<?php endif; ?>
</div>
