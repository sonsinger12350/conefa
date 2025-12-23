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
</style>
<div class="wrap">
	<h1 class="wp-heading-inline">Danh sách tin tuyển dụng</h1>
	<hr class="wp-header-end">

	<form id="posts-filter" method="POST" action="">
		<div class="tablenav top">
			<p class="search-box" style="float: left">
				<label class="screen-reader-text" for="post-search-input">Tìm kiếm:</label>
				<input type="search" name="post-search-input" value="<?=$search_input?>" placeholder="Tìm vị trí">
				<input type="submit" id="search-submit" class="button" value="Tìm kiếm">
			</p>
			<h2 class="screen-reader-text">Điều hướng danh sách các trang</h2>
			<div class="tablenav-pages">
				<span class="displaying-num"><?=$pagination['total']?> mục</span>
				<?=paginate_links($pagination)?>
			</div>
			<br class="clear">
		</div>
		<h2 class="screen-reader-text">Danh sách tin tuyển dụng</h2>
		<table class="wp-list-table widefat fixed striped table-view-list pages">
			<thead>
				<tr>
					<td>Vị trí</td>
					<td>Địa điểm làm việc</td>
					<td>Thời gian làm việc</td>
					<td>Mức lương</td>
					<td>Thời gian nộp hồ sơ</td>
					<td width="300px">Mô tả công việc</td>
					<td width="300px">Quyền lợi</td>
					<td>Hiển thị</td>
				</tr>
			</thead>

			<tbody id="the-list">
				<?php if (!empty($data)):?>
					<?php foreach($data as $v):?>
						<tr id="<?=$v->id?>">
							<td>
								<strong><span class="row-title"><?= $v->position ?></span></strong>
								<div class="row-actions">
									<span class="edit">
										<a href="<?=admin_url('admin.php?page=career-create&id='.$v->id)?>" class="edit-service" aria-label="Sửa “<?=$v->position?>”">Chỉnh sửa</a> | 
									</span>
									<span class="trash">
										<a href="javascript:void(0)" class="delete-item" data-id="<?=$v->id?>" data-table="<?=$table?>" aria-label="Xóa “<?=$v->name?>”">Xóa</a>
									</span>
								</div>
							</td>
							<td><span class="row-title"><?= $v->address ?></span></td>
							<td><span class="row-title"><?= $v->working_time ?></span></td>
							<td><span class="row-title"><?= $v->salary ?></span></td>
							<td><span class="row-title"><?= $v->application_deadline ?></span></td>
							<td><span class="row-title"><?= str_replace('\n', "<br>", $v->desc) ?></span></td>
							<td><span class="row-title"><?= str_replace('\n', "<br>", $v->benefits) ?></span></td>
							<td><span class="row-title"><?= $v->show == 1 ? 'Có' : 'Không' ?></span></td>
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
					<td>Vị trí</td>
					<td>Địa điểm làm việc</td>
					<td>Thời gian làm việc</td>
					<td>Mức lương</td>
					<td>Thời gian nộp hồ sơ</td>
					<td>Mô tả công việc</td>
					<td>Quyền lợi</td>
					<td>Hiển thị</td>
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
	$('body').on('click', '.delete-item', function(e) {
		let self = $(this);
		let table = self.attr('data-table');
		let id = self.attr('data-id');

		if (table != '' && id != '') {
			if (confirm('Bạn có chắc muốn tin này')) {
				$.ajax({
					url:"<?=admin_url('admin-ajax.php')?>?action=delete_data_connest",
					type:"POST",
					data:{table, id},
					success:function(rs) {
						if (rs.success) {
							self.closest('tr').remove();
						} else {
							alert(rs.data);
						}
					}
				});
			}
		}
	});
</script>