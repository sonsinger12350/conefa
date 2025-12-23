$(document).ready(function () {
	let slide = $('.slide .owl-carousel');

	slide.on('initialized.owl.carousel', function(event) {
		var getDotWidthInterval = setInterval(function() {
			if ($('.body .owl-carousel .owl-dots').length) {
				let dotWidth = $('.body .owl-carousel .owl-dots').width();
				let navWidth = Number(dotWidth) + 65;

				$('.body .owl-carousel .owl-nav').css('width', navWidth + 'px');
				clearInterval(getDotWidthInterval);
			}

		}, 100);
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

	$('.seek-tab-menu .item .menu-title').on('click', function() {
		if (!$(this).closest('.seek-tab').hasClass('active')) {
			let tab = $(this).parent().attr('data-tab');

			$('.seek-tab').removeClass('active');
			$('.seek-tab-menu .item').removeClass('active');
			$(`.seek-tab.tab-${tab}`).addClass('active');
			$(`.seek-tab-menu .item[data-tab="${tab}"]`).addClass('active');

			$('.seek-tab-menu .item').each(function() {
				if (Number($(this).attr('data-tab')) > Number(tab)+1) {
					$(this).addClass('hide');
				}
				else {
					$(this).removeClass('hide');
				}
			});
		}
	});

	$('.seek-tab-menu .item .dot').on('click', function() {
		$(this).parent().find('.menu-title').click();
	});

	var $contents = $('.seek-tab');
    var $window = $(window);

    $window.on('scroll', function() {
        $contents.each(function(index) {
            let $currentContent = $(this);
			let currentTab = $currentContent.attr('data-tab');
            let $nextContent = $contents.eq(index + 1);
			let $menu = $(`.seek-tab-menu .item[data-tab="${currentTab}"]`);
			let $lastContent = $contents.last();
			let headerHeight = 90;

			if ($window.scrollTop() >= $lastContent.offset().top + $lastContent.outerHeight() - $window.height() + headerHeight) {
                $contents.removeClass('sticky');
				$('.seek-tab-menu .item').removeClass('sticky');
            } else {
                if ($nextContent.length) {
					let nextContentTop = $nextContent.offset().top;

					if ($window.scrollTop() >= nextContentTop - $currentContent.outerHeight() - headerHeight) {
						$currentContent.addClass('sticky');
						$menu.addClass('sticky');
					} else {
						$currentContent.removeClass('sticky');
						$menu.removeClass('sticky');
					}
				}

				if (currentTab == 3) {
					let height = calculateVerticalDistance($('.seek-tab-menu .item[data-tab="3"]'), $('.seek-tab-menu .item[data-tab="4"]'));

					$('.seek-tab-menu .item .process-line').css('height', height+'px');
				}
            }
        });
    });

	function calculateVerticalDistance($element1, $element2) {
		// Lấy tọa độ của hai phần tử
		const offset1 = $element1.offset();
		const offset2 = $element2.offset();
	
		// Tính khoảng cách theo chiều dọc (top)
		const verticalDistance = Math.abs(offset2.top - offset1.top);
	
		return verticalDistance;
	}

	// Services Slide Carousel with Thumbnail Navigation
	$('.services-slide').each(function() {
		var $servicesSlide = $(this);
		var $carousel = $servicesSlide.find('.main-carousel');
		var $thumbnails = $servicesSlide.find('.navigation-thumbnails .thumbnail-item');
		var totalSlides = $thumbnails.length;
		console.log($carousel);
		

		if ($carousel.length && totalSlides > 0) {
			$carousel.owlCarousel({
				items: 1,
				loop: false,
				margin: 0,
				nav: true,
				navText: ['<i class="fa-solid fa-angle-left"></i>', '<i class="fa-solid fa-angle-right"></i>'],
				dots: false,
				autoplay: false,
				autoplayTimeout: 5000,
				autoplayHoverPause: true,
				onInitialized: function(event) {
					var currentIndex = event.item.index;
					var realIndex = currentIndex % totalSlides;
					updateActiveThumbnail($servicesSlide, realIndex);
				},
				onChanged: function(event) {
					var currentIndex = event.item.index;
					var realIndex = currentIndex % totalSlides;
					updateActiveThumbnail($servicesSlide, realIndex);
				}
			});

			// Click thumbnail to navigate
			$thumbnails.on('click', function() {
				var slideIndex = parseInt($(this).data('slide'));
				$carousel.trigger('to.owl.carousel', [slideIndex, 300]);
			});

			function updateActiveThumbnail($container, activeIndex) {
				// Normalize index to handle loop mode
				var normalizedIndex = activeIndex % totalSlides;
				if (normalizedIndex < 0) {
					normalizedIndex = totalSlides + normalizedIndex;
				}
				// Ensure index is within bounds
				if (normalizedIndex < 0) normalizedIndex = 0;
				if (normalizedIndex >= totalSlides) normalizedIndex = totalSlides - 1;
				
				$container.find('.thumbnail-item').removeClass('active');
				$container.find('.thumbnail-item[data-slide="' + normalizedIndex + '"]').addClass('active');
			}
		}
	});

	$('.projects-slide .main-carousel').owlCarousel({
		items: 1,
		slideBy: 1,
		loop: false,
		margin: 0,
		nav: true,
		navText: ['<i class="fa-solid fa-angle-left"></i>', '<i class="fa-solid fa-angle-right"></i>'],
		dots: true,
		dotsEach: 1,  // Mỗi dot đại diện cho 1 item
		autoplay: false,
		autoplayTimeout: 5000,
		autoplayHoverPause: true,
		margin: 24,
		responsive: {
			0: {
				items: 1,
			},
			400: {
				items: 2,
			},
			576: {
				items: 3,
			},
			991: {
				items: 4,
			},
			1199: {
				items: 5,
			},
			1300: {
				items: 6,
			},
			1500: {
				items: 7,
			}
		}
	});

	// Testimonials Slide Carousel
	$('.testimonials-slide').each(function() {
		var $testimonialsSlide = $(this);
		var $carousel = $testimonialsSlide.find('.testimonials-carousel');
		var slidesToShow = parseInt($testimonialsSlide.data('slides')) || 1;
		var autoplay = $testimonialsSlide.data('autoplay') === 'true' || $testimonialsSlide.data('autoplay') === true;
		var autoplayTimeout = parseInt($testimonialsSlide.data('timeout')) || 5000;

		if ($carousel.length) {
			$carousel.owlCarousel({
				items: slidesToShow,
				loop: false,
				margin: 36,
				nav: true,
				navText: ['<i class="fa-solid fa-angle-left"></i>', '<i class="fa-solid fa-angle-right"></i>'],
				dots: true,
				dotsEach: 1,
				autoplay: autoplay,
				autoplayTimeout: autoplayTimeout,
				autoplayHoverPause: true,
				responsive: {
					0: {
						items: 1,
						margin: 12
					},
					768: {
						items: slidesToShow >= 2 ? 2 : 1,
						margin: 16
					},
					992: {
						items: slidesToShow >= 3 ? 3 : slidesToShow,
						margin: 24
					},
					1200: {
						items: slidesToShow,
						margin: 36
					}
				}
			});
		}
	});
});