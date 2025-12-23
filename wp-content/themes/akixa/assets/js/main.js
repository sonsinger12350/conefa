$(document).ready(function () {
	$('.open-menu-mobile').on('click', function() {
		$(this).toggleClass('active');
		$('.menu-collapse-mobile').toggleClass('active');
		$('.menu-mobile-overlay').toggleClass('active');
	});

	$('.menu-mobile-overlay').on('click', function() {
		$('.open-menu-mobile').removeClass('active');
		$('.menu-collapse-mobile').removeClass('active');

		$('.category-tree-mobile').removeClass('active');

		$('.menu-mobile-overlay').removeClass('active');
	});

	$('.open-menu-desktop').on('click', function() {
		$(this).toggleClass('active');
		$('.main-menu').toggleClass('active');
		$('header').toggleClass('active-menu');

		if ($('header .content').hasClass('d-none')) {
			setTimeout(() => {
				$('header .content').toggleClass('d-none');
			}, 500);
		}
		else $('header .content').toggleClass('d-none');
		
		if ($('header').hasClass('scroll-down')) {
			if ($('header').hasClass('active-menu')) $('header .logo img').attr('src', $('header .logo img').attr('data-black'));
			else $('header .logo img').attr('src', $('header .logo img').attr('data-white'));
		}

		activeMenu();
	});

	var $window = $(window);

	if (screen.width > 576) {
		$window.on('scroll', function() {
			if ($(window).scrollTop() > 100) {
				$('header').addClass('scroll-down');

				if (!$('header').hasClass('active-menu')) {
					$('.open-menu-desktop').click();
				}
			}
			else {
				if ($('header').hasClass('scroll-down')) {
					$('header').removeClass('scroll-down');
				}

				if ($('header').hasClass('active-menu')) {
					$('.open-menu-desktop').click();
				}
			}
	
			activeMenu();
		});
	}
	else {
		$('header').addClass('scroll-down');
		activeMenu();
	}

	$('.main-menu .title-category, .main-menu .category-tree').hover(function() {
		let tree = $('.main-menu .category-tree');

		if (!tree.hasClass('active')) {
			tree.addClass('active');
			$('.box-overlay').show();
		}
	}, function() {
		let tree = $('.main-menu .category-tree');

		if (!$('.main-menu .title-category').is(':hover') && !tree.is(':hover')) {
            tree.removeClass('active');
			$('.box-overlay').hide();
        }
	});

	$('.main-menu .cat-parent').hover(function() {
		let element = $(this);
		let child = element.find('.children-categories');

		if (!child.hasClass('active')) {
			child.addClass('active');
		}

	}, function() {
		let element = $(this);
		let child = element.find('.children-categories');

		if (child.hasClass('active')) {
			child.removeClass('active');
		}
	});
});

function activeMenu() {
	if ($('header').hasClass('active-menu')) {
		$('header .logo img').attr('src', $('header .logo img').attr('data-black'));
	} 
	else {
		if ($('header').hasClass('header-2')) {
			$('header .logo img').attr('src', $('header .logo img').attr('data-white'));
		}
		else {
			if ($('header').hasClass('scroll-down')) {
				$('header .logo img').attr('src', $('header .logo img').attr('data-white'));
			}
			else {
				$('header .logo img').attr('src', $('header .logo img').attr('data-black'));
			}
		}
	}
}

function saveCustomForm(form) {
	if (!form) return false;

	let error = 0;
	let formType = form.find('[name="type"]').val();
	let formData = new FormData(form.get(0));
	let btn = form.parent().find('button[type="submit"]');
	let btnHtml = btn.html();

	if ($('.alert-danger').length > 0) $('.alert-danger').addClass('d-none');

	form.find(':input[required]').each(function() {
		let input = $(this);
		let val = input.val().trim();
		let type = input.attr('type');
		
		if (type == 'phone') {
			if (val == '' || val.length == 0) {
				error = 1;
				input.addClass('is-invalid');
			}
			else {
				if (!validatePhone(val)) {
					error = 2;
					input.addClass('is-invalid');
				}
				else input.removeClass('is-invalid');
			}
			
		}
		else {
			if (val == '' || val.length == 0) {
				error = 1;
				input.addClass('is-invalid');
			}
			else input.removeClass('is-invalid');
		}
	});

	if (error) {
		let message = error == 2 ? 'Vui lòng nhập số điện thoại hợp lệ' : 'Vui lòng nhập đầy đủ thông tin';
		let element = form.find(':input.is-invalid').first();
		if (formType == 'contact') scrollToDiv(element);
		element.focus();

		if ($('.alert-danger').length > 0) {
			$('.alert-danger').html(message);
			$('.alert-danger').removeClass('d-none');
		}
		else {
			alert(message);
		}
		
		return false;
	}

	btn.attr('disabled', true);
	btn.addClass('loading');
	btn.html('<i class="fas fa-spinner fa-pulse"></i>');

	$.ajax({
		url: adminAjaxUrl + '?action=save_form_custom',
		type: 'POST',
		contentType: false,
		processData: false,
		data: formData,
		success: function(response) {
			btn.attr('disabled', false);
			btn.removeClass('loading');
			btn.html(btnHtml);
			alert('Đã gửi thông tin!');
			if (formType == 'contact') location.reload();
			form.find(':input:not([name="type"])').val('');
			form.find('.list-image').html('');
		},
		error: function(error) {
			btn.attr('disabled', false);
			btn.removeClass('loading');
			btn.html(btnHtml);
			$('.alert-danger').html('Có lỗi xảy ra khi gửi!');
			$('.alert-danger').removeClass('d-none');
		}
	});
}

function validatePhone(phone) {
	if (!/(84|0[3|5|7|8|9])+([0-9]{8})\b/g.test(phone)) return false;
	if (phone.trim() === "") return false;
	return true;
}

function scrollToDiv(element, distance=0) {
	window.scrollTo({ top: Number(element.offset().top) + distance, behavior: 'smooth' });
}

function formatPrice(price) {
	return new Intl.NumberFormat('vi-VN', { maximumSignificantDigits: 3 }).format(Number(price)) + ' <span class="woocommerce-Price-currencySymbol">₫</span>';
}