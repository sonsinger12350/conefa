<div class="left">
	<div class="filter filter-category">
		<p class="title">Danh mục</p>
		<ul class="product-categories">
			<?php if (!empty($categories_tree)): ?>
				<?php foreach ($categories_tree as $cat): ?>
					<?php
						$isActiveCat = '';

						if (
							$cat['id'] == $category || 
							(!empty($current_cat) && ($cat['id'] == $current_cat->parent || !empty($cat['children'][$current_cat->parent])))
						) {
							$isActiveCat = 'active';
						}
					?>
					<li class="cat-item cat-parent <?= $isActiveCat ?>">
						<div class="d-flex justify-content-between">
							<a href="<?= $cat['link'] ?>" class="link-parent"><?= $cat['name'] ?> (<?= $cat['count'] ?>)</a>
							<?php if (!empty($cat['children'])): ?>
								<span class="toggle-category parent"><i class="fa-solid fa-angle-down"></i></span>
							<?php endif ?>
						</div>
						<?php if (!empty($cat['children'])): ?>
							<ul class="children-categories">
								<?php foreach ($cat['children'] as $child): ?>
									<?php
										$isActiveChildCat = '';

										if ($child['id'] == $category || (!empty($current_cat) && $child['id'] == $current_cat->parent)) {
											$isActiveChildCat = 'active';
										}
									?>
									<li class="cat-item cat-children <?= $isActiveChildCat ?>">
										<div class="d-flex justify-content-between">
											<a href="<?= $child['link'] ?>" class="link-children"><?= $child['name'] ?> (<?= $child['count'] ?>)</a>
											<?php if (!empty($child['children'])): ?>
												<span class="toggle-category children"><i class="fa-solid fa-angle-down"></i></span>
											<?php endif ?>
										</div>
										<?php if (!empty($child['children'])): ?>
											<ul class="grandchildren-categories">
												<?php foreach ($child['children'] as $grandchild): ?>
													<li class="cat-item cat-grandchildren <?= $grandchild['id'] == $category ? 'active' : '' ?>">
														<a href="<?= $grandchild['link'] ?>" class="link-grandchildren"><?= $grandchild['name'] ?> (<?= $grandchild['count'] ?>)</a>
													</li>
												<?php endforeach ?>
											</ul>
										<?php endif ?>
									</li>
								<?php endforeach ?>
							</ul>
						<?php endif ?>
					</li>
				<?php endforeach ?>
			<?php endif ?>	
		</ul>
	</div>
	<form class="filter filter-price">
		<p class="title">Diện tích</p>
		<div class="widget-area filter-price-widget mb-3">
			<input type="hidden" name="min-size">
			<input type="hidden" name="max-size">
			<div id="slider-range"></div>
			<div class="range-price-show"><p>Diện tích (m):</p> <span class="min-size"><?= $priceRange['min'] ?></span> - <span class="max-size"><?= $priceRange['max'] ?></span></div>
		</div>
		<div class="text-end">
			<a class="btn btn-outline-dark me-3" href="<?= home_url('shop') ?>">Xóa bộ lọc</a>
			<button class="btn btn-dark" type="submit">Lọc</button>
		</div>
	</form>
</div>
<div class="right">
	<form id="search-form" method="GET">
		<div class="input-group">
			<input type="text" class="form-control" name="keyword" value="<?= $keyword ?>" placeholder="Nhập tên sản phẩm để tìm kiếm">
			<span class="submit-search"><i class="fa-solid fa-magnifying-glass"></i></span>
		</div>
	</form>
	<div class="list-product">
		<?php if (!empty($products)): ?>
			<div class="list row mt-4">
				<?php foreach ($products as $k => $product): ?>
					<?php
						get_template_part('template-parts/product', null, ['index' => $k, 'product' => $product]);
					?>
				<?php endforeach ?>
			</div>
			<?php if ($showBtnLoadMore): ?>
				<div class="text-center mt-4 mb-4">
					<button class="btn btn-load-more" value="0" data-url="<?= home_url('du-an') ?>" data-limit="<?= $limit ?>">XEM THÊM DỰ ÁN</button>
				</div>
			<?php endif ?>
		<?php else: ?>
			<?php if (!empty($keyword)): ?>
				<h4 class="text-center mb-0 mt-4">Không tìm thấy kết quả. Vui lòng sử dụng từ khóa khác</h4>
			<?php else: ?>
				<h4 class="text-center mb-0 mt-4">Dữ liệu đang cập nhật</h4>
			<?php endif ?>
		<?php endif ?>
	</div>
</div>