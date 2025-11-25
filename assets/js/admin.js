/**
 * Admin JavaScript for media picker.
 *
 * @package VirtualPhotoBooth
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Handle frame image selection.
		$('.pbe-select-frame').on('click', function(e) {
			e.preventDefault();

			const button = $(this);
			const input = button.siblings('input[type="hidden"]');
			const preview = button.siblings('.pbe-frame-preview');
			const removeBtn = button.siblings('.pbe-remove-frame');
			const frameId = button.data('frame-id') || input.val();

			const frame = wp.media({
				title: 'Select Frame Image',
				button: {
					text: 'Use this frame'
				},
				multiple: false,
				library: {
					type: 'image'
				}
			});

			if (frameId) {
				frame.on('open', function() {
					const selection = frame.state().get('selection');
					selection.add(wp.media.attachment(frameId));
				});
			}

			frame.on('select', function() {
				const attachment = frame.state().get('selection').first().toJSON();
				input.val(attachment.id);
				preview.html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto;" />');
				removeBtn.show();
			});

			frame.open();
		});

		// Handle frame removal.
		$('.pbe-remove-frame').on('click', function(e) {
			e.preventDefault();

			const button = $(this);
			const input = button.siblings('input[type="hidden"]');
			const preview = button.siblings('.pbe-frame-preview');

			input.val('');
			preview.html('');
			button.hide();
		});
	});
})(jQuery);


