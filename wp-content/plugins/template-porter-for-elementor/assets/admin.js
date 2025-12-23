jQuery(document).ready(function($){
	// ========== EXPORT ==========
	$('#etp-export-form').on('submit', function(e){
		e.preventDefault();

		var templateId = $('#etp-template-select').val();
		if (!templateId) {
			alert('Please select a template');
			return;
		}

		var $btn = $('#etp-export-btn');
		var $spinner = $btn.next('.spinner');
		var $result = $('#etp-export-result');

		$btn.prop('disabled', true);
		$spinner.addClass('is-active');
		$result.empty();

		$.ajax({
			url: temppofoData.ajax_url,
			type: 'POST',
			data: {
				action: 'temppofo_export_template',
				nonce: temppofoData.nonce,
				template_id: templateId
			},
			success: function(response){
				$btn.prop('disabled', false);
				$spinner.removeClass('is-active');

				if (response.success) {
					$result.html('<div class="etp-success">Export ready! <a href="' + response.data.download_url + '" download class="button button-small">Download ZIP</a></div>');
				} else {
					$result.html('<div class="etp-error">Error: ' + response.data.message + '</div>');
				}
			},
			error: function(){
				$btn.prop('disabled', false);
				$spinner.removeClass('is-active');
				$result.html('<div class="etp-error">AJAX error. Please try again.</div>');
			}
		});
	});

	// ========== IMPORT ==========
	$('#etp-import-form').on('submit', function(e){
		e.preventDefault();

		var file = $('#etp-import-file')[0].files[0];
		if (!file) {
			alert('Please select a ZIP file');
			return;
		}

		var formData = new FormData();
		formData.append('action', 'temppofo_import_template');
		formData.append('nonce', temppofoData.nonce);
		formData.append('import_file', file);

		var $btn = $('#etp-import-btn');
		var $spinner = $btn.next('.spinner');
		var $result = $('#etp-import-result');

		$btn.prop('disabled', true);
		$spinner.addClass('is-active');
		$result.empty();

		$.ajax({
			url: temppofoData.ajax_url,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function(response){
				$btn.prop('disabled', false);
				$spinner.removeClass('is-active');

				if (response.success) {
					$result.html('<div class="etp-success">' + response.data.message + ' <a href="' + response.data.edit_url + '" target="_blank" class="button button-small">Edit in Elementor</a></div>');
					$('#etp-import-file').val('');
				} else {
					$result.html('<div class="etp-error">Error: ' + response.data.message + '</div>');
				}
			},
			error: function(){
				$btn.prop('disabled', false);
				$spinner.removeClass('is-active');
				$result.html('<div class="etp-error">AJAX error. Please try again.</div>');
			}
		});
	});
});