<?php
	$type = !empty($args['type']) ? $args['type'] : '';
	if (!empty($type)):
	$websiteName = get_bloginfo('name');

	if ($type == 'explore') {
		$title = 'KHÁM PHÁ '.$websiteName.' <i class="fa-solid fa-angle-right"></i>';
		$href = home_url('ve-connest');
	}
	else if ($type == 'product') {
		$title = 'xem thêm <i class="fa-solid fa-angle-right"></i>';
		$href = home_url('du-an');
	}
	else if ($type == 'register') {
		$title = 'Đăng ký <i class="fa-solid fa-angle-right"></i>';
		$href = home_url('lien-he');
	}
	else if ($type == 'contact') {
		$title = 'Liên hệ tư vấn <i class="fa-solid fa-angle-right"></i>';
		$href = home_url('lien-he');
	}
?>
<a class="btn btn-success btn-sm btn-explore" href="<?= !empty($href) ? $href : 'javascript:void(0)' ?>"><?= $title ?></a>
<?php endif ?>