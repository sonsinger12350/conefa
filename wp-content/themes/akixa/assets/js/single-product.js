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
				items:3
			}
		}
	});

	$("body").on('click', '.list-image .owl-item', function(e) {
		var $item = $(e.target).closest('.item');
		if (!$item.length) {
			$item = $(this).find('.item').first();
		}

		var slideIndex = $item.data('slide');
		if (typeof slideIndex === 'undefined') {
			return;
		}

		slideBigImage.trigger('to.owl.carousel', [slideIndex, 300]);
	});

	// Click vào video placeholder → ẩn thumbnail, hiện iframe embed
	$('body').on('click', '.video-slide .video-placeholder', function() {
		var $slide  = $(this).closest('.video-slide');
		var $iframe = $slide.find('iframe');
		$iframe.attr('src', $iframe.data('src'));
		$(this).hide();
		$slide.find('.video-embed').show();
	});

	// Khi chuyển slide khác → dừng video (reset iframe src)
	slideBigImage.on('changed.owl.carousel', function() {
		$('.video-slide iframe').attr('src', '');
		$('.video-slide .video-placeholder').show();
		$('.video-slide .video-embed').hide();
	});
});

function updateCurrentSlide(event) {
	let totalItems = event.item.count;
	let clonesLength = event.relatedTarget._clones.length / 2;
	let currentItem = (event.item.index - clonesLength + totalItems) % totalItems + 1;

	// Thử tìm theo class gallery-N trước, fallback sang data-slide khi có video slide
	let galleryImage = $(`.list-image .owl-item .item.gallery-${currentItem}`);
	if (!galleryImage.length) {
		galleryImage = $(`.list-image .owl-item .item[data-slide="${currentItem - 1}"]`);
	}

	$('.list-image .owl-item .item').removeClass('selected');

	if (!galleryImage.hasClass('selected')) galleryImage.addClass('selected');

	$('.slide .list-image').trigger('to.owl.carousel', [currentItem-1, 300]);

}

// Toggle form viết đánh giá
$(document).on('click', '#btn-write-review', function() {
	var $form = $('#review-form-box');
	$form.toggle(300);
	$(this).text($form.is(':visible') ? 'Ẩn form' : 'Viết đánh giá');
});

// Star rating click (form đánh giá sản phẩm)
$(document).on('click', '.stars a', function(e) {
	e.preventDefault();
	var m = ($(this).attr('class') || '').match(/star-(\d)/);
	if (!m) return;
	var val = m[1];
	var $stars = $(this).closest('.stars');
	var $form = $stars.closest('form');
	$stars.find('a').removeClass('active');
	$(this).addClass('active').prevAll('a').addClass('active');
	var $hidden = $form.find('input[name="rating"]');
	if ($hidden.length) {
		$hidden.val(val);
	} else {
		$stars.after('<input type="hidden" name="rating" value="' + val + '">');
	}
});

// Expand / collapse Tab Mô tả
$(document).on('click', '.btn-expand-tab', function() {
	var $content    = $(this).siblings('.tab-desc-content');
	var isCollapsed = $content.hasClass('collapsed');
	if (isCollapsed) {
		$content.removeClass('collapsed');
		$(this).html('Thu gọn <i class="fa-solid fa-chevron-up"></i>');
	} else {
		$content.addClass('collapsed');
		$(this).html('Xem thêm đặc điểm nổi bật <i class="fa-solid fa-chevron-down"></i>');
	}
});

// Smooth scroll cho info bar anchors
document.querySelectorAll('.info-rating, .info-specs').forEach(function(link) {
	link.addEventListener('click', function(e) {
		var target = document.querySelector(this.getAttribute('href'));
		if (target) {
			e.preventDefault();
			target.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}
	});
});

// Block CTA inline -> mo thang popup lop 2 (form tu van)
document.addEventListener('click', function (e) {
	var trigger = e.target.closest('#btn-inline-open-consult');
	if (!trigger) {
		return;
	}

	var overlay = document.getElementById('exit-overlay');
	var popup1 = document.getElementById('exit-popup-1');
	var popup2 = document.getElementById('exit-popup-2');

	if (!overlay || !popup2) {
		return;
	}

	overlay.classList.add('active');
	if (popup1) {
		popup1.classList.remove('active');
	}
	popup2.style.display = 'block';
	window.setTimeout(function () {
		popup2.classList.add('active');
	}, 10);

	if (window.sessionStorage) {
		sessionStorage.setItem('acone_exit_shown', '1');
	}
});

// Popup yêu cầu sản phẩm: chỉnh sửa bản vẽ / yêu cầu bản vẽ mới
(function () {
	var modal = document.getElementById('product-request-modal');
	if (!modal) {
		return;
	}
	var pendingMessageTarget = null;

	function fillFields(data) {
		var fieldMap = {
			'product-id': modal.dataset.productId || '',
			'product-name': modal.dataset.productName || '',
			'product-url': modal.dataset.productUrl || window.location.href,
			'request-name': '',
			'request-phone': '',
			'request-type': '',
			'request-service': '',
			'request-source': '',
			'request-note': ''
		};

		Object.assign(fieldMap, data || {});

		Object.keys(fieldMap).forEach(function (name) {
			var input = modal.querySelector('[name="' + name + '"]');
			if (input) {
				input.value = fieldMap[name];
			}
		});
	}

	function setRequestType(type) {
		if (!type) {
			return;
		}

		var hidden = modal.querySelector('[name="request-type"]');
		if (hidden) {
			hidden.value = type;
		}

		var radio = modal.querySelector('input[name="request-type-choice"][value="' + type + '"]');
		if (radio) {
			radio.checked = true;
		}
	}

	function openModal(type) {
		fillFields({
			'request-type': type || 'Chỉnh sửa bản vẽ',
			'request-source': 'Popup sản phẩm'
		});
		setRequestType(type || 'Chỉnh sửa bản vẽ');
		modal.hidden = false;
		modal.setAttribute('aria-hidden', 'false');
		document.body.classList.add('product-request-modal-open');

		window.setTimeout(function () {
			var firstInput = modal.querySelector('input[name="request-name"]');
			if (firstInput) {
				firstInput.focus();
			}
		}, 60);
	}

	function closeModal() {
		modal.hidden = true;
		modal.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('product-request-modal-open');
	}

	function getCf7Form() {
		return modal.querySelector('form.wpcf7-form');
	}

	function submitSharedCf7(data, messageTarget) {
		var cf7Form = getCf7Form();
		if (!cf7Form) {
			return false;
		}

		pendingMessageTarget = messageTarget || null;
		fillFields(data);
		setRequestType(data && data['request-type'] ? data['request-type'] : '');

		if (pendingMessageTarget) {
			pendingMessageTarget.textContent = 'Đang gửi yêu cầu...';
			pendingMessageTarget.classList.remove('is-error', 'is-success');
		}

		if (window.wpcf7 && typeof window.wpcf7.submit === 'function') {
			window.wpcf7.submit(cf7Form);
		} else if (typeof cf7Form.requestSubmit === 'function') {
			cf7Form.requestSubmit();
		} else {
			cf7Form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
		}

		return true;
	}

	function collectExitNote(form) {
		var parts = [];
		[
			'dien_tich_dat',
			'dien_tich_xd',
			'cong_nang',
			'thoi_gian',
			'ngan_sach',
			'mo_ta'
		].forEach(function (name) {
			var field = form.querySelector('[name="' + name + '"]');
			if (field && field.value) {
				parts.push(name + ': ' + field.value);
			}
		});
		return parts.join(' | ');
	}

	document.addEventListener('click', function (e) {
		var trigger = e.target.closest('[data-product-request-open]');
		if (trigger) {
			e.preventDefault();
			openModal(trigger.dataset.requestType || '');
			return;
		}

		if (e.target.closest('[data-product-request-close]')) {
			e.preventDefault();
			closeModal();
		}
	});

	document.addEventListener('change', function (e) {
		if (e.target && e.target.name === 'request-type-choice') {
			setRequestType(e.target.value);
		}
	});

	document.addEventListener('submit', function (e) {
		var quickForm = e.target.closest('[data-product-quick-call-form]');
		if (quickForm) {
			e.preventDefault();
			if (!quickForm.reportValidity()) {
				return;
			}

			submitSharedCf7({
				'request-name': quickForm.querySelector('[name="request-name"]').value,
				'request-phone': quickForm.querySelector('[name="request-phone"]').value,
				'request-service': quickForm.querySelector('[name="request-service"]').value,
				'request-type': 'Gọi cho tôi',
				'request-source': 'Form Gọi cho tôi'
			}, quickForm.querySelector('.order-form-message'));
			return;
		}

		var exitForm = e.target.closest('#exit-consult-form');
		if (exitForm) {
			e.preventDefault();
			if (!exitForm.reportValidity()) {
				return;
			}

			var service = exitForm.querySelector('[name="dich_vu"]:checked');
			submitSharedCf7({
				'request-name': exitForm.querySelector('[name="ho_ten"]').value,
				'request-phone': exitForm.querySelector('[name="sdt"]').value,
				'request-service': service ? service.closest('label').textContent.trim() : '',
				'request-type': 'Tư vấn chuyên sâu',
				'request-source': 'Popup tư vấn chuyên sâu',
				'request-note': collectExitNote(exitForm)
			}, exitForm.querySelector('.exit-consult-message'));
		}
	}, true);

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && !modal.hidden) {
			closeModal();
		}
	});

	document.addEventListener('wpcf7mailsent', function (e) {
		if (pendingMessageTarget) {
			pendingMessageTarget.textContent = 'Cảm ơn bạn, Acone sẽ liên hệ lại sớm.';
			pendingMessageTarget.classList.add('is-success');
			pendingMessageTarget.classList.remove('is-error');
			pendingMessageTarget = null;
		}
		if (modal.contains(e.target)) {
			window.setTimeout(closeModal, 1500);
		}
	});

	document.addEventListener('wpcf7mailfailed', function () {
		if (pendingMessageTarget) {
			pendingMessageTarget.textContent = 'Chưa gửi được yêu cầu, vui lòng thử lại.';
			pendingMessageTarget.classList.add('is-error');
			pendingMessageTarget.classList.remove('is-success');
			pendingMessageTarget = null;
		}
	});
})();
