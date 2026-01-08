jQuery(document).ready(function($) {
	var progressInterval = null;
	var isPolling = false;
	var lastProgressUpdate = null;
	var triggerInterval = null;
	
	// Get batch stats
	function getBatchStats() {
		$.ajax({
			url: itwpcAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'itwpc_get_batch_stats',
				nonce: itwpcAdmin.nonce
			},
			success: function(response) {
				if (response.success) {
					var total = response.data.total;
					$('#itwpc-total-images').text(total);
				}
			}
		});
	}
	
	// Get batch progress
	function getBatchProgress() {
		if (!isPolling) {
			return;
		}
		
		$.ajax({
			url: itwpcAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'itwpc_get_batch_progress',
				nonce: itwpcAdmin.nonce
			},
			success: function(response) {
				if (response.success) {
					var data = response.data;
					
					if (data.status === 'idle' || !data.status) {
						// No batch running
						stopPolling();
						stopTriggerInterval();
						$('#itwpc-batch-progress').hide();
						$('#itwpc-start-batch').prop('disabled', false).text('Start Batch Conversion');
						return;
					}
					
					if (data.status === 'running') {
						// Check if progress has updated
						var currentOffset = data.offset || 0;
						var lastOffset = lastProgressUpdate ? lastProgressUpdate.offset : currentOffset;
						
						// Update last progress
						lastProgressUpdate = {
							offset: currentOffset,
							timestamp: Date.now()
						};
						
						// Update progress bar
						var progress = data.progress || 0;
						$('#itwpc-progress-bar').css('width', progress + '%').text(progress.toFixed(1) + '%');
						
						// Update progress text
						var offset = data.offset || 0;
						var total = data.total || 0;
						var processed = data.processed || 0;
						var skipped = data.skipped || 0;
						var errors = data.errors || 0;
						var averageTime = data.average_time_per_image || 0;
						var totalTime = data.total_processing_time || 0;
						
						var message = 'Processing: ' + offset + ' / ' + total + ' (' + progress.toFixed(1) + '%)';
						message += '<br>Processed: ' + processed + ' | Skipped: ' + skipped + ' | Errors: ' + errors;
						if ((data.deleted_attachments || 0) > 0) {
							message += ' | Deleted: ' + data.deleted_attachments;
						}
						
						if (data.started_at) {
							var startedTime = new Date(data.started_at);
							var now = new Date();
							var elapsed = Math.floor((now - startedTime) / 1000); // seconds
							var minutes = Math.floor(elapsed / 60);
							var seconds = elapsed % 60;
							message += '<br>Elapsed time: ' + minutes + 'm ' + seconds + 's';
						}
						
						// Display timing statistics
						if (averageTime > 0) {
							message += '<br><strong>Performance:</strong> Avg ' + averageTime.toFixed(2) + 's/image';
							if (totalTime > 0) {
								var totalMinutes = Math.floor(totalTime / 60);
								var totalSeconds = Math.floor(totalTime % 60);
								message += ' | Total processing: ' + totalMinutes + 'm ' + totalSeconds + 's';
							}
						}
						
						$('#itwpc-progress-text').html(message);
						
						// Show stop button and disable start button
						$('#itwpc-stop-batch').show();
						$('#itwpc-start-batch').prop('disabled', true);
						
						// Start trigger interval if not already started
						if (!triggerInterval) {
							startTriggerInterval();
						}
						
						// Also trigger immediately if progress hasn't updated recently
						// (WordPress cron only runs when there's traffic, so we need to trigger via AJAX)
						if (lastProgressUpdate) {
							var timeSinceUpdate = (Date.now() - lastProgressUpdate.timestamp) / 1000;
							if (timeSinceUpdate > 40) {
								// Progress hasn't updated in 40+ seconds, trigger now
								triggerBatch();
							}
						} else {
							// No progress update yet, trigger now
							triggerBatch();
						}
					} else if (data.status === 'completed') {
						// Finished
						stopPolling();
						stopTriggerInterval();
						$('#itwpc-progress-bar').css('width', '100%').text('100%');
						
						var completedMessage = '<strong style="color: green;">✓ All images processed successfully!</strong><br>' +
							'Total: ' + data.total + ' images | Processed: ' + data.processed + ' | Skipped: ' + data.skipped + ' | Errors: ' + data.errors;
						if (data.deleted_attachments && data.deleted_attachments > 0) {
							completedMessage += ' | Deleted: ' + data.deleted_attachments;
						}
						
						// Add timing statistics if available
						if (data.average_time_per_image && data.average_time_per_image > 0) {
							completedMessage += '<br><strong>Performance:</strong> Avg ' + parseFloat(data.average_time_per_image).toFixed(2) + 's/image';
							if (data.total_processing_time && data.total_processing_time > 0) {
								var totalTime = parseFloat(data.total_processing_time);
								var totalMinutes = Math.floor(totalTime / 60);
								var totalSeconds = Math.floor(totalTime % 60);
								completedMessage += ' | Total processing: ' + totalMinutes + 'm ' + totalSeconds + 's';
							}
						}
						
						$('#itwpc-progress-text').html(completedMessage);
						$('#itwpc-start-batch').prop('disabled', false).text('Start Batch Conversion');
						$('#itwpc-stop-batch').hide();
					} else if (data.status === 'stopped') {
						// Stopped - ensure polling and triggers are stopped
						stopPolling();
						stopTriggerInterval();
						$('#itwpc-batch-progress').show();
						var progress = data.progress || 0;
						$('#itwpc-progress-bar').css('width', progress + '%').text(progress.toFixed(1) + '%');
						$('#itwpc-progress-text').html(
							'<strong style="color: orange;">Batch processing stopped</strong><br>' +
							'Processed: ' + (data.processed || 0) + ' / ' + (data.total || 0) + ' images'
						);
						$('#itwpc-start-batch').prop('disabled', false).text('Start Batch Conversion');
						$('#itwpc-stop-batch').hide();
					} else {
						// Idle or unknown status
						stopPolling();
						stopTriggerInterval();
						$('#itwpc-start-batch').prop('disabled', false);
						$('#itwpc-stop-batch').hide();
					}
				}
			},
			error: function() {
				console.error('Error getting batch progress');
			}
		});
	}
	
	// Start polling progress
	function startPolling() {
		if (isPolling) {
			return;
		}
		isPolling = true;
		// Poll every 30 seconds (same as cron interval)
		progressInterval = setInterval(getBatchProgress, 30000);
		// Get progress immediately
		getBatchProgress();
		// Also trigger batch immediately to ensure it starts (WordPress cron needs traffic)
		setTimeout(function() {
			triggerBatch();
		}, 2000); // Wait 2 seconds after starting to trigger
	}
	
	// Stop polling progress
	function stopPolling() {
		isPolling = false;
		if (progressInterval) {
			clearInterval(progressInterval);
			progressInterval = null;
		}
		stopTriggerInterval();
	}
	
	// Trigger batch processing (fallback when cron doesn't run)
	function triggerBatch() {
		$.ajax({
			url: itwpcAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'itwpc_trigger_batch',
				nonce: itwpcAdmin.nonce
			},
			success: function(response) {
				if (response.success) {
					console.log('Batch triggered via AJAX');
				}
			},
			error: function() {
				console.error('Error triggering batch');
			}
		});
	}
	
	// Start trigger interval (trigger batch every 35 seconds when running to ensure cron continues)
	function startTriggerInterval() {
		if (triggerInterval) {
			return;
		}
		// Trigger batch every 35 seconds to ensure it continues running
		// This is slightly longer than cron interval (30s) to avoid conflicts
		triggerInterval = setInterval(function() {
			// Always trigger if batch is running (WordPress cron needs traffic to run)
			// Check if progress hasn't updated in last 45 seconds
			if (lastProgressUpdate) {
				var timeSinceUpdate = (Date.now() - lastProgressUpdate.timestamp) / 1000; // seconds
				if (timeSinceUpdate > 45) {
					// Progress hasn't updated recently, trigger batch
					triggerBatch();
				} else {
					// Progress is updating, but still trigger to ensure cron continues
					// (WordPress cron only runs when there's traffic)
					triggerBatch();
				}
			} else {
				// No progress update yet, trigger batch
				triggerBatch();
			}
		}, 35000); // Check every 35 seconds
	}
	
	// Stop trigger interval
	function stopTriggerInterval() {
		if (triggerInterval) {
			clearInterval(triggerInterval);
			triggerInterval = null;
		}
		lastProgressUpdate = null;
	}
	
	// Start batch button click
	$('#itwpc-start-batch').on('click', function() {
		if (!confirm('This will convert all existing images to WebP format using WordPress Cron. This may take a while. Continue?')) {
			return;
		}
		
		$(this).prop('disabled', true).text('Starting...');
		$('#itwpc-batch-progress').show();
		$('#itwpc-progress-bar').css('width', '0%');
		$('#itwpc-progress-text').text('Initializing...');
		
		$.ajax({
			url: itwpcAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'itwpc_start_batch',
				nonce: itwpcAdmin.nonce
			},
			success: function(response) {
				if (response.success) {
					$('#itwpc-start-batch').text('Processing...');
					$('#itwpc-stop-batch').show();
					// Start polling
					startPolling();
				} else {
					alert('Error: ' + (response.data.message || 'Unknown error'));
					$('#itwpc-start-batch').prop('disabled', false).text('Start Batch Conversion');
					$('#itwpc-batch-progress').hide();
				}
			},
			error: function() {
				alert('AJAX error occurred. Please try again.');
				$('#itwpc-start-batch').prop('disabled', false).text('Start Batch Conversion');
				$('#itwpc-batch-progress').hide();
			}
		});
	});
	
	// Stop batch button click
	$('#itwpc-stop-batch').on('click', function() {
		if (!confirm('Are you sure you want to stop the batch processing?')) {
			return;
		}
		
		$(this).prop('disabled', true).text('Stopping...');
		
		$.ajax({
			url: itwpcAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'itwpc_stop_batch',
				nonce: itwpcAdmin.nonce
			},
			success: function(response) {
				if (response.success) {
					stopPolling();
					$('#itwpc-stop-batch').prop('disabled', false).hide();
					$('#itwpc-start-batch').prop('disabled', false).text('Start Batch Conversion');
					$('#itwpc-progress-text').html('<strong style="color: orange;">Batch processing stopped by user</strong>');
				} else {
					alert('Error: ' + (response.data.message || 'Unknown error'));
					$('#itwpc-stop-batch').prop('disabled', false);
				}
			},
			error: function() {
				alert('AJAX error occurred. Please try again.');
				$('#itwpc-stop-batch').prop('disabled', false);
			}
		});
	});
	
	// Clear cache button click
	$('#itwpc-clear-cache').on('click', function() {
		var $button = $(this);
		var originalText = $button.text();
		
		$button.prop('disabled', true).text('Clearing...');
		
		$.ajax({
			url: itwpcAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'itwpc_clear_cache',
				nonce: itwpcAdmin.nonce
			},
			success: function(response) {
				if (response.success) {
					// Refresh total images count
					getBatchStats();
					$button.text('Cache Cleared!').css('color', 'green');
					setTimeout(function() {
						$button.prop('disabled', false).text(originalText).css('color', '');
					}, 2000);
				} else {
					alert('Error: ' + (response.data.message || 'Unknown error'));
					$button.prop('disabled', false).text(originalText);
				}
			},
			error: function() {
				alert('AJAX error occurred. Please try again.');
				$button.prop('disabled', false).text(originalText);
			}
		});
	});
	
	// Check for existing batch on page load
	getBatchStats();
	
	// Check if batch is already running on page load
	function checkInitialStatus() {
		$.ajax({
			url: itwpcAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'itwpc_get_batch_progress',
				nonce: itwpcAdmin.nonce
			},
			success: function(response) {
				if (response.success) {
					var data = response.data;
					
					if (data.status === 'running') {
						// Batch is running, show progress and disable start button
						$('#itwpc-start-batch').prop('disabled', true);
						$('#itwpc-stop-batch').show();
						$('#itwpc-batch-progress').show();
						
						// Update progress display
						var progress = data.progress || 0;
						$('#itwpc-progress-bar').css('width', progress + '%').text(progress.toFixed(1) + '%');
						
						var offset = data.offset || 0;
						var total = data.total || 0;
						var processed = data.processed || 0;
						var skipped = data.skipped || 0;
						var errors = data.errors || 0;
						var averageTime = data.average_time_per_image || 0;
						var totalTime = data.total_processing_time || 0;
						
						var message = 'Processing: ' + offset + ' / ' + total + ' (' + progress.toFixed(1) + '%)';
						message += '<br>Processed: ' + processed + ' | Skipped: ' + skipped + ' | Errors: ' + errors;
						if ((data.deleted_attachments || 0) > 0) {
							message += ' | Deleted: ' + data.deleted_attachments;
						}
						
						if (data.started_at) {
							var startedTime = new Date(data.started_at);
							var now = new Date();
							var elapsed = Math.floor((now - startedTime) / 1000);
							var minutes = Math.floor(elapsed / 60);
							var seconds = elapsed % 60;
							message += '<br>Elapsed time: ' + minutes + 'm ' + seconds + 's';
						}
						
						// Display timing statistics
						if (averageTime > 0) {
							message += '<br><strong>Performance:</strong> Avg ' + averageTime.toFixed(2) + 's/image';
							if (totalTime > 0) {
								var totalMinutes = Math.floor(totalTime / 60);
								var totalSeconds = Math.floor(totalTime % 60);
								message += ' | Total processing: ' + totalMinutes + 'm ' + totalSeconds + 's';
							}
						}
						
						$('#itwpc-progress-text').html(message);
						
						// Start polling
						startPolling();
					} else if (data.status === 'completed') {
						// Show completed status
						$('#itwpc-batch-progress').show();
						$('#itwpc-progress-bar').css('width', '100%').text('100%');
						
						var completedMessage = '<strong style="color: green;">✓ All images processed successfully!</strong><br>' +
							'Total: ' + (data.total || 0) + ' images | Processed: ' + (data.processed || 0) + ' | Skipped: ' + (data.skipped || 0) + ' | Errors: ' + (data.errors || 0);
						if (data.deleted_attachments && data.deleted_attachments > 0) {
							completedMessage += ' | Deleted: ' + data.deleted_attachments;
						}
						
						// Add timing statistics if available
						if (data.average_time_per_image && data.average_time_per_image > 0) {
							completedMessage += '<br><strong>Performance:</strong> Avg ' + parseFloat(data.average_time_per_image).toFixed(2) + 's/image';
							if (data.total_processing_time && data.total_processing_time > 0) {
								var totalTime = parseFloat(data.total_processing_time);
								var totalMinutes = Math.floor(totalTime / 60);
								var totalSeconds = Math.floor(totalTime % 60);
								completedMessage += ' | Total processing: ' + totalMinutes + 'm ' + totalSeconds + 's';
							}
						}
						
						$('#itwpc-progress-text').html(completedMessage);
						$('#itwpc-start-batch').prop('disabled', false);
					} else if (data.status === 'stopped') {
						// Show stopped status - ensure polling and triggers are stopped
						stopPolling();
						stopTriggerInterval();
						$('#itwpc-batch-progress').show();
						var progress = data.progress || 0;
						$('#itwpc-progress-bar').css('width', progress + '%').text(progress.toFixed(1) + '%');
						$('#itwpc-progress-text').html(
							'<strong style="color: orange;">Batch processing stopped</strong><br>' +
							'Processed: ' + (data.processed || 0) + ' / ' + (data.total || 0) + ' images'
						);
						$('#itwpc-start-batch').prop('disabled', false);
						$('#itwpc-stop-batch').hide();
					} else {
						// No batch running, enable start button
						$('#itwpc-start-batch').prop('disabled', false);
						$('#itwpc-batch-progress').hide();
					}
				}
			},
			error: function() {
				console.error('Error checking initial batch status');
			}
		});
	}
	
	// Check initial status on page load
	checkInitialStatus();
	
	// Clean up on page unload
	$(window).on('beforeunload', function() {
		stopPolling();
	});
});
