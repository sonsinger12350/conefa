$(document).ready(function() {
	$('body').on('input', '.input-custom input', function() {
		let val = $(this).val();
		let parent = $(this).parent();

		if (val) {
			if (!parent.hasClass('active')) parent.addClass('active');
		}
		else {
			if (parent.hasClass('active')) parent.removeClass('active');
		}
	});

	$('body').on('click', '.input.number .action', function() {
		let action = $(this).attr('data-action');
		let input = $(this).parent().find('input');
		let val = Number(input.val());
		let min = Number(input.attr('min'));

		if (action == 'minus') {
			if (val <= min) return;
			input.val(val-1);
		}

		if (action == 'plus') input.val(val+1);
	});

	$('body').on('input', 'input[type="number"]', function() {
		let input = $(this);
		let val = Number(input.val());
		let max = Number(input.attr('max'));

		if (val > max) input.val(max);
	});

	$('body').on('input', '.day input[type="number"], .month input[type="number"]', function() {
		let input = $(this);
		let val = input.val();

		if (val.length > 0) {
			if (val.length < 2) input.val('0' + val);
			if (val.length > 2) input.val(val.substring(1));
		}
	});

	var filesArray = [];

	$('body').on('change', '.upload-image [name="image[]"]', function(e) {
		uploadFile(e.target.files);
	});

	$('#dropFile').on('drop', function(e) {
		e.preventDefault();
		e.stopPropagation();
		console.log(123);
		uploadFile(e.originalEvent.dataTransfer.files);
	});

	function uploadFile(files) {
		let parent = $('.upload-image');
		$.each(files, function(index, file) {
			if (!filesArray.includes(file)) {
				filesArray.push(file);
				let item = parent.find('.list-image-sample .item').clone();

				item.find('.image').attr('src', URL.createObjectURL(file));
				item.find('.image').attr('alt', file.name);
				item.find('.name').html(file.name);

				item.find('.delete').on('click', function() {
					filesArray = filesArray.filter(f => f !== file);
					item.remove();
				});

				parent.find('.list-image').append(item);
			}
		});
	}

	$('body').on('submit', '.form-contact', function(e) {
		e.preventDefault();
		saveCustomForm($(this));

		return false;
	});

	let now = new Date();
	let day = String(now.getDate()).padStart(2,'0');
	let month = String(now.getMonth()+1).padStart(2,'0');

	$('[name="ngay-gui"]').val(`${day}-${month}-${now.getFullYear()}`);
});