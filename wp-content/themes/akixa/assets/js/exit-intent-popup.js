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

	if (!overlay || !popup2) {
		return;
	}

	var fanpageUrl =
		typeof akixaExitIntent !== 'undefined' && akixaExitIntent.fanpageUrl
			? akixaExitIntent.fanpageUrl
			: 'https://www.facebook.com/aconenhavuon';

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
			if (document.body.classList.contains('single-product')) {
				return;
			}
			e.preventDefault();
			window.location.href = fanpageUrl;
		});
	}
}

(function () {
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', akixaInitExitIntentPopup);
	} else {
		akixaInitExitIntentPopup();
	}
})();
