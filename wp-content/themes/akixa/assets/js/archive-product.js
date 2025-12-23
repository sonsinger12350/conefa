$(document).ready(function () {
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
			data: {
				isAjax: 1,
				action: "load_more",
				offset
			},
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

	$('body').on('click','.submit-search', function() {
		$('#search-form').submit();
	});

	$('body').on('click','.toggle-category', function() {
		let parent = $(this).closest('.cat-item');
		let ul = $(this).hasClass('parent') ? parent.find('.children-categories') : parent.find('.grandchildren-categories');

		parent.toggleClass('active');

		if (parent.hasClass('active')) ul.slideDown();
		else ul.slideUp();
	});

	$('.product-categories .cat-parent').each(function() {
		if ($(this).hasClass('active')) {
			$(this).find('.children-categories').slideDown();
		}
	});

	$('.product-categories .cat-children').each(function() {
		if ($(this).hasClass('active')) {
			$(this).find('.grandchildren-categories').slideDown();
		}
	});

	let minPrice = Number(priceRange.min);
	let maxPrice = Number(priceRange.max);

	$( "#slider-range" ).slider({
		range: true,
		min: minPrice,
		max: maxPrice,
		values: [ current_min_price, current_max_price ],
		slide: function( event, ui ) {
			$('.filter-price-widget [name="min-size"]').val(ui.values[0]);
			$('.filter-price-widget [name="max-size"]').val(ui.values[1]);

			$('.filter-price-widget .min-size').html(ui.values[0]);
			$('.filter-price-widget .max-size').html(ui.values[1]);
		}
	});

	$('.filter-price-widget [name="min-size"]').val(current_min_price);
	$('.filter-price-widget [name="max-size"]').val(current_max_price);

	$('.filter-price-widget .min-size').html(current_min_price);
	$('.filter-price-widget .max-size').html(current_max_price);
});