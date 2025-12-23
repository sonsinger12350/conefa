$(document).ready(function () {
	let slideBigImage = $('.slide .big-image');

	slideBigImage.owlCarousel({
		loop: false,
		margin: 0,
		nav: true,
		navText: ['<i class="fa-solid fa-angle-left"></i>', '<i class="fa-solid fa-angle-right"></i>'],
		dots: true,
		autoplay: false,
		autoplayTimeout: 5000,
		onInitialized: updateCurrentSlide,
		onChanged: updateCurrentSlide,
		responsive: {
			0: {
				items: 1
			}
		}
	});

	$('.slide .list-image').owlCarousel({
		loop: false,
		margin: 10,
		nav: true,
		navText: ['<i class="fa-solid fa-angle-left"></i>', '<i class="fa-solid fa-angle-right"></i>'],
		dots: false,
		autoplay: false,
		autoplayTimeout: 5000,
		responsive: {
			0:{
				items:2
			},
			480:{
				items:3
			},
			600:{
				items:3
			},
			1000:{
				items:4
			}
		}
	});

	$('.product-related .list').owlCarousel({
		loop: false,
		margin: 20,
		nav: false,
		dots: true,
		autoplay: true,
		autoplayTimeout: 5000,
		responsive: {
			0:{
				items:1
			},
			767:{
				items:2
			},
			991:{
				items:3
			},
			1199:{
				items:4
			}
		}
	});

	$("body").on('click', '.list-image .owl-item .item', function() {
		var slideIndex = $(this).data('slide');

		slideBigImage.trigger('to.owl.carousel', [slideIndex, 300]);
	});
});

function updateCurrentSlide(event) {
	let totalItems = event.item.count;
	let clonesLength = event.relatedTarget._clones.length / 2;
	let currentItem = (event.item.index - clonesLength + totalItems) % totalItems + 1;
	let galleryImage = $(`.list-image .owl-item .item.gallery-${currentItem}`);

	$('.list-image .owl-item .item').removeClass('selected');

	if (!galleryImage.hasClass('selected')) galleryImage.addClass('selected');

	$('.slide .list-image').trigger('to.owl.carousel', [currentItem-1, 300]);

}