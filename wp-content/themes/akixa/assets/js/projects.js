$(document).ready(function () {
	let slide = $('.slide .owl-carousel');
	slide.on('initialized.owl.carousel', function(event) {
		$('.bg-blur-mobile').appendTo('.slide .owl-carousel');
	});
	slide.owlCarousel({
		loop: true,
		margin: 0,
		nav: true,
		navText: ['<i class="fa-solid fa-angle-left"></i>', '<i class="fa-solid fa-angle-right"></i>'],
		dots: true,
		autoplay: true,
		autoplayTimeout: 5000,
		responsive: {
			0: {
				items: 1
			}
		}
	});

	$('body').on('click', '.btn-load-more', function() {
		let btn = $(this);
		let limit = Number(btn.attr('data-limit'));
		let offset = Number(btn.val()) + limit;
		let url = btn.attr('data-url');
		let btnHtml = btn.html();

		btn.attr('disabled', true);
		btn.html('<i class="fas fa-spinner fa-pulse"></i>');

		$.ajax({
			url: url,
			type: "GET",
			data: {isAjax: 1,offset},
			success: function(rs) {
				btn.attr('disabled', false);
				btn.html(btnHtml);

				if (rs.success) {
					if (rs.data.content) $('.list-product .list').append(rs.data.content);
					if (rs.data.continue) btn.val(offset);
					else btn.hide();
				}
			}
		});
	});

	$('body').on('submit', '.form-contact form', function(e) {
		e.preventDefault();
		saveCustomForm($(this));

		return false;
	});
});