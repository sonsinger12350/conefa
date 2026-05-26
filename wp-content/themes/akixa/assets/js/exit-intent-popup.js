/**
 * IMPL-08: Popup – trigger khi người dùng rê chuột rời khỏi mép trên viewport.
 */
function akixaInitExitIntentPopup() {
	var SESSION_KEY = 'acone_exit_shown';

	if (!document.body) {
		return;
	}

	var isProductPage =
		document.body.classList.contains('single-product');
	var isCategoryPage = document.body.classList.contains('tax-product_cat');

	if (!isProductPage && !isCategoryPage) {
		return;
	}

	var overlay = document.getElementById('exit-overlay');
	var popup1 = document.getElementById('exit-popup-1');
	var popup2 = document.getElementById('exit-popup-2');
	var close1 = document.getElementById('exit-close-1');
	var close2 = document.getElementById('exit-close-2');
	var btnConsult = document.getElementById('btn-open-consult');
	var btnConsultInline = document.getElementById('btn-open-consult-inline');
	var form = document.getElementById('exit-consult-form');
	var pendingMessageTarget = null;

	if (!overlay || !popup2) {
		return;
	}

	function shouldSkipExit() {
		return window.sessionStorage && sessionStorage.getItem(SESSION_KEY);
	}

	function markExitConsumed() {
		if (window.sessionStorage) {
			sessionStorage.setItem(SESSION_KEY, '1');
		}
	}

	function openConsultForm() {
		overlay.classList.add('active');
		if (popup1) {
			popup1.classList.remove('active');
		}
		popup2.style.display = 'block';
		setTimeout(function () {
			popup2.classList.add('active');
		}, 10);
	}

	function showExitPopup() {
		if (!popup1) {
			return;
		}
		if (shouldSkipExit()) {
			return;
		}
		markExitConsumed();
		overlay.classList.add('active');
		popup1.classList.add('active');
	}

	function closeAll() {
		overlay.classList.remove('active');
		popup1.classList.remove('active');
		popup2.classList.remove('active');
		popup2.style.display = 'none';
		markExitConsumed();
	}

	function getHiddenCf7Form() {
		var holder = document.getElementById('exit-consult-cf7-holder');
		return holder ? holder.querySelector('form.wpcf7-form') : null;
	}

	function setCf7Field(cf7Form, name, value) {
		var field = cf7Form.querySelector('[name="' + name + '"]');
		if (field) {
			field.value = value || '';
		}
	}

	function setExitMessage(target, message, type) {
		if (!target) {
			return;
		}

		target.textContent = message;
		target.classList.toggle('is-success', type === 'success');
		target.classList.toggle('is-error', type === 'error');
	}

	function ensureCf7Ready(cf7Form) {
		var wrapper = cf7Form.closest('.wpcf7');

		if (window.wpcf7 && typeof window.wpcf7.init === 'function' && !cf7Form.wpcf7) {
			window.wpcf7.init(cf7Form);
		}

		if (wrapper) {
			wrapper.classList.remove('no-js');
			wrapper.classList.add('js');
		}
	}

	function submitExitCf7(exitForm) {
		var cf7Form = getHiddenCf7Form();
		pendingMessageTarget = exitForm.querySelector('.exit-consult-message');

		if (!cf7Form) {
			setExitMessage(
				pendingMessageTarget,
				'Chưa tìm thấy form lưu dữ liệu, vui lòng tải lại trang và thử lại.',
				'error'
			);
			return false;
		}

		ensureCf7Ready(cf7Form);

		var service = exitForm.querySelector('[name="dich_vu"]:checked');
		var serviceText = service ? service.closest('label').textContent.trim() : '';

		setCf7Field(cf7Form, 'consult-name', exitForm.querySelector('[name="ho_ten"]').value);
		setCf7Field(cf7Form, 'consult-phone', exitForm.querySelector('[name="sdt"]').value);
		setCf7Field(cf7Form, 'consult-land-area', exitForm.querySelector('[name="dien_tich_dat"]').value);
		setCf7Field(cf7Form, 'consult-build-area', exitForm.querySelector('[name="dien_tich_xd"]').value);
		setCf7Field(cf7Form, 'consult-function', exitForm.querySelector('[name="cong_nang"]').value);
		setCf7Field(cf7Form, 'consult-start-time', exitForm.querySelector('[name="thoi_gian"]').value);
		setCf7Field(cf7Form, 'consult-budget', exitForm.querySelector('[name="ngan_sach"]').value);
		setCf7Field(cf7Form, 'consult-service', serviceText);
		setCf7Field(cf7Form, 'consult-description', exitForm.querySelector('[name="mo_ta"]').value);
		setCf7Field(cf7Form, 'consult-source', 'Popup tư vấn chuyên sâu');
		setCf7Field(cf7Form, 'page-title', document.title);
		setCf7Field(cf7Form, 'page-url', window.location.href);

		setExitMessage(pendingMessageTarget, 'Đang gửi yêu cầu...', '');

		if (window.wpcf7 && typeof window.wpcf7.submit === 'function') {
			window.wpcf7.submit(cf7Form);
		} else if (typeof cf7Form.requestSubmit === 'function') {
			cf7Form.requestSubmit();
		} else {
			cf7Form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
		}

		return true;
	}

	document.addEventListener('mouseleave', function (e) {
		if (e.clientY < 10) {
			showExitPopup();
		}
	});

	if (close1) {
		close1.addEventListener('click', closeAll);
	}
	if (close2) {
		close2.addEventListener('click', closeAll);
	}
	overlay.addEventListener('click', closeAll);

	if (btnConsult) {
		btnConsult.addEventListener('click', openConsultForm);
	}

	if (btnConsultInline) {
		btnConsultInline.addEventListener('click', openConsultForm);
	}

	if (form) {
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			if (!form.reportValidity()) {
				return;
			}

			if (!submitExitCf7(form)) {
				return;
			}
		});
	}

	document.addEventListener('wpcf7mailsent', function (e) {
		if (!pendingMessageTarget || !e.target.closest('#exit-consult-cf7-holder')) {
			return;
		}

		setExitMessage(pendingMessageTarget, 'Cảm ơn bạn, Acone sẽ liên hệ lại sớm.', 'success');
		pendingMessageTarget = null;
		window.setTimeout(closeAll, 1500);
	});

	document.addEventListener('wpcf7invalid', function (e) {
		if (!pendingMessageTarget || !e.target.closest('#exit-consult-cf7-holder')) {
			return;
		}

		setExitMessage(pendingMessageTarget, 'Vui lòng kiểm tra lại các trường bắt buộc.', 'error');
	});

	document.addEventListener('wpcf7spam', function (e) {
		if (!pendingMessageTarget || !e.target.closest('#exit-consult-cf7-holder')) {
			return;
		}

		setExitMessage(pendingMessageTarget, 'Yêu cầu bị chặn tạm thời, vui lòng thử lại.', 'error');
		pendingMessageTarget = null;
	});

	document.addEventListener('wpcf7mailfailed', function (e) {
		if (!pendingMessageTarget || !e.target.closest('#exit-consult-cf7-holder')) {
			return;
		}

		setExitMessage(pendingMessageTarget, 'Chưa gửi được yêu cầu, vui lòng thử lại.', 'error');
		pendingMessageTarget = null;
	});
}

(function () {
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', akixaInitExitIntentPopup);
	} else {
		akixaInitExitIntentPopup();
	}
})();
