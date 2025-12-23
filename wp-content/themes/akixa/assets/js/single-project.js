$(document).ready(function () {
    let slide = $('.slide .owl-carousel');
	slide.on('initialized.owl.carousel', function(event) {
		$('.owl-dot-number').appendTo('.slide .owl-carousel');
	});
	slide.owlCarousel({
		loop: false,
		margin: 0,
		nav: true,
		navText: ['<i class="fa-solid fa-angle-left"></i>', '<i class="fa-solid fa-angle-right"></i>'],
		dots: false,
		onInitialized: updateCurrentCount,
		onChanged: updateCurrentCount,
		autoplay: false,
		autoplayTimeout: 5000,
		responsive: {
			0: {
				items: 1
			}
		}
	});

	$(".product-info .list-image .item").on('click', function(){
		var slideIndex = $(this).data('slide');
		slide.trigger('to.owl.carousel', [slideIndex, 300]); // Chuyển tới slide tương ứng
	});

	$('body').on('submit', '.form-contact form', function(e) {
		e.preventDefault();
		saveCustomForm($(this));

		return false;
	});

	$('.big-image .item img').elevateZoom();
});

function updateCurrentCount(event) {
	let totalItems = event.item.count;
	let currentItem = (event.item.index + 1) - event.relatedTarget._clones.length / 2;
	let galleryImage = $(`.product-info .right .list-image .item.gallery-${currentItem}`);

	if (currentItem > totalItems) currentItem = currentItem - totalItems;
	$('.owl-dot-number').text(currentItem + '/' + totalItems);

	$('.product-info .right .list-image .item').removeClass('active');
	if (!galleryImage.hasClass('active')) galleryImage.addClass('active');
}