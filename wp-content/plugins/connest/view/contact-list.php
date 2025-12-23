<style>
	/* Các kiểu chung cho phân trang */
	.tablenav-pages {
		display: flex;
		justify-content: center;
		align-items: center;
		margin-top: 20px;
	}

	/* Kiểu cho mỗi liên kết */
	.tablenav-pages .page-numbers {
		display: inline-block;
		padding: 8px 12px;
		margin: 0 4px;
		text-decoration: none;
		background-color: #f2f2f2;
		color: #333;
		border: 1px solid #ddd;
		border-radius: 4px;
	}

	/* Kiểu cho liên kết trang hiện tại */
	.tablenav-pages .current {
		background-color: #4CAF50;
		color: white;
		border: 1px solid #4CAF50;
	}

	/* Kiểu cho liên kết "Previous" và "Next" */
	.tablenav-pages .prev, .tablenav-pages .next {
		background-color: #333;
		color: white;
	}
	
	.row-title {
		color: #2271b1;
	}

	.wp-list-table .list-image {
		display: flex;
		gap: 8px;
		flex-wrap: wrap;
	}

	.wp-list-table .list-image a {
		width: 50px;
		height: 50px;
	}

	.wp-list-table .list-image a img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		object-position: center;
	}

	input[type="search"] {
		width: 300px;
	}
</style>
<!-- FancyBox -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js"></script>
<div class="wrap">
	<h1 class="wp-heading-inline">Danh sách liên hệ</h1>
	<hr class="wp-header-end">

	<form id="posts-filter" method="POST" action="">
		<div class="tablenav top">
			<p class="search-box" style="float: left">
				<label class="screen-reader-text" for="post-search-input">Tìm kiếm:</label>
				<input type="search" name="post-search-input" value="<?=$search_input?>" placeholder="Tìm tên hoặc số điện thoại">
				<input type="submit" id="search-submit" class="button" value="Tìm kiếm">
			</p>
			<h2 class="screen-reader-text">Điều hướng danh sách các trang</h2>
			<div class="tablenav-pages">
				<span class="displaying-num"><?=$pagination['total']?> mục</span>
				<?=paginate_links($pagination)?>
			</div>
			<br class="clear">
		</div>
		<h2 class="screen-reader-text">Danh sách liên hệ</h2>
		<table class="wp-list-table widefat fixed striped table-view-list pages">
			<thead>
				<tr>
					<td>Ngày gửi</td>
					<td>Họ và tên</td>
					<td>Số điện thoại</td>
					<td>Địa chỉ</td>
					<td>Ghi chú</td>
					<td width="300px">Thông tin công trình</td>
				</tr>
			</thead>

			<tbody id="the-list">
				<?php if (!empty($data)):?>
					<?php 
						$form_type = [
							'contact' => 'Liên hệ',
							'register-news' => 'Đăng ký nhận tin tức mới',
							'get-advice' => 'Nhận tư vấn',
						];
						foreach($data as $v):
							$extraData = [];
							if ($v->type == 'contact') {
								$extraData = json_decode($v->extra_data);
							}
					?>
						<tr id="<?=$v->id?>">
							<td><span class="row-title"><?= $v->created_at ?></span></td>
							<td><span class="row-title"><?= $v->full_name ?></span></td>
							<td><span class="row-title"><?= $v->phone ?></span></td>
							<td><span class="row-title"><?= $v->address ?></span></td>
							<td><span class="row-title"><?= $form_type[$v->type] ?></span></td>
							<td>
								<?php if (!empty($extraData)): ?>
									<div class="extra-data">
										<?php if (!empty($extraData->area_address)): ?>
											<p><b>Địa điểm khu đất:</b> <?= $extraData->area_address ?></p>
										<?php endif ?>
										<?php if (!empty($extraData->land_area)): ?>
											<p><b>Diện tích khu đất:</b> <?= $extraData->land_area ?></p>
										<?php endif ?>
										<?php if (!empty($extraData->number_bedrooms)): ?>
											<p><b>Số phòng ngủ:</b> <?= $extraData->number_bedrooms ?></p>
										<?php endif ?>
										<?php if (!empty($extraData->date)): ?>
											<p><b>Thời gian xây dựng dự kiến:</b> <?= $extraData->date ?></p>
										<?php endif ?>
										<?php if (!empty($extraData->other)): ?>
											<p><b>Yêu cầu khác:</b> <?= $extraData->other ?></p>
										<?php endif ?>
										<?php if (!empty($extraData->image)): ?>
											<div class="list-image">
												<?php foreach ($extraData->image as $k => $img): ?>
													<a href="<?= wp_upload_dir()['baseurl'].$img ?>" data-fancybox="gallery-<?= $v->id ?>"><img src="<?= wp_upload_dir()['baseurl'].$img ?>" alt="<?= $v->full_name.'-'.$v->id.'-'.$k ?>"></a>
												<?php endforeach ?>
											</div>
										<?php endif ?>
									</div>
								<?php endif?>
							</td>
						</tr>
					<?php endforeach?>
				<?php else:?>
					<tr>
						<td colspan="8" align="center">Không có dữ liệu</td>
					</tr>
				<?php endif?>
			</tbody>

			<tfoot>
				<tr>
					<td>Ngày gửi</td>
					<td>Họ và tên</td>
					<td>Số điện thoại</td>
					<td>Địa chỉ</td>
					<td>Ghi chú</td>
					<td width="300px">Thông tin công trình</td>
				</tr>
			</tfoot>
		</table>
		<div class="tablenav bottom">
			<div class="alignleft actions">
			</div>
			<div class="tablenav-pages">
				<span class="displaying-num"><?= $pagination['total'] ?> mục</span>
				<?= paginate_links($pagination) ?>
			</div>
			<br class="clear">
		</div>
	</form>

	<div id="ajax-response"></div>
	<div class="clear"></div>
</div>