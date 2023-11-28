/* global Wppus, console, WppusAdminMain_l10n */
jQuery(document).ready(function ($) {

	function htmlDecode(input) {
		var doc = new DOMParser().parseFromString(input, "text/html");
		return doc.documentElement.textContent;
	}

	if (-1 !== location.href.indexOf('action=')) {
		var hrefParts = window.location.href.split('?');

		hrefParts[1] = hrefParts[1].replace('action=', 'action_done=');

		history.pushState(null, '', hrefParts.join('?') );
	}

	$('input[type="password"].secret').on('focus', function () {
		$(this).attr('type', 'text');
	});

	$('input[type="password"].secret').on('blur', function () {
		$(this).attr('type', 'password');
	});

	$('.wppus-wrap .wp-list-table .delete a').on('click', function(e) {
		var r = window.confirm(WppusAdminMain_l10n.deleteRecord);

		if (!r) {
			e.preventDefault();
		}
	});

	$('.wppus-delete-all-packages').on('click', function(e) {
		var r = window.confirm(WppusAdminMain_l10n.deletePackagesConfirm);

		if (!r) {
			e.preventDefault();
		}
	});

	$('.ajax-trigger').on('click', function(e) {
		e.preventDefault();

		var button = $(this),
			type   = button.data('type'),
			data   = {
				type: type,
				nonce: $('#wppus_plugin_options_handler_nonce').val(),
				action: 'wppus_' + button.data('action'),
				data: button.data('selector') ? $(button.data('selector')).get().reduce(function (obj, el) {
					obj[el.id] = el.type === 'checkbox' || el.type === 'radio' ? el.checked : el.value;

					return obj;
				}, {}) : {}
			};

		button.attr('disabled', 'disabled');

		$.ajax({
			url: WppusAdminMain.ajax_url,
			data: data,
			type: 'POST',
			success: function(response) {

				if (!response.success) {
					var message = '';

					/* jshint ignore:start */
					$.each(response.data, function(idx, value) {
						message += htmlDecode(value.message) + "\n";
					});
					/* jshint ignore:end */

					window.alert(message);
				} else if (response.data) {
					var message = '';

					/* jshint ignore:start */
					$.each(response.data, function (idx, value) {

						if ('btnVal' !== idx) {
							message += htmlDecode(value) + "\n";
						}
					});
					/* jshint ignore:end */

					if (message.length) {
						window.alert(message);
					}
				}

				button.removeAttr('disabled');

				if (response.data && response.data.btnVal) {
					button.val(response.data.btnVal);
				}
			},
			error: function (jqXHR, textStatus) {
				WppusAdminMain.debug && console.log(textStatus);
			}
		});

	});

	var primeLocked = false;

	$('#wppus_prime_package_slug').on('input', function() {

		if (0 < $(this).val().length && !primeLocked) {
			$('#wppus_prime_package_trigger').removeAttr('disabled');
		} else if (!primeLocked) {
			$('#wppus_prime_package_trigger').attr('disabled', 'disabled');
		}
	});

	$('#wppus_prime_package_trigger').on('click', function(e) {
		e.preventDefault();

		var button = $(this),
			data   = {
				slug :   $('#wppus_prime_package_slug').val(),
				nonce :  $('#wppus_plugin_options_handler_nonce').val(),
				action : 'wppus_prime_package_from_remote'
			};

		button.attr('disabled', 'disabled');
		button.next().css('visibility', 'visible');

		primeLocked = true;

		$.ajax({
			url: WppusAdminMain.ajax_url,
			data: data,
			type: 'POST',
			success: function(response) {

				if (!response.success) {
					var message = '';

					/* jshint ignore:start */
					$.each(response.data, function(idx, value) {
						message += value.message + "\n";
					});
					/* jshint ignore:end */

					primeLocked = false;

					button.removeAttr('disabled');
					button.next().css('visibility', 'hidden');
					window.alert(message);
				} else {
					window.location.reload(true);
				}
			},
			error: function (jqXHR, textStatus) {
				WppusAdminMain.debug && console.log(textStatus);

				primeLocked = false;

				button.removeAttr('disabled');
				button.next().css('visibility', 'hidden');
			}
		});

	});

	$('#wppus_manual_package_upload').on('change', function() {
		var fileinput = $(this);

		if (0 < fileinput.prop('files').length) {
			$('#wppus_manual_package_upload_filename').val(fileinput.prop('files')[0].name);
			$('#wppus_manual_package_upload_trigger').removeAttr('disabled');
		} else {
			$('#wppus_manual_package_upload_filename').val('');
			$('#wppus_manual_package_upload_trigger').attr('disabled', 'disabled');
		}
	});

	$('#wppus_manual_package_upload_dropzone').on('drag dragstart dragend dragover dragenter dragleave drop', function(e) {
		e.preventDefault();
		e.stopPropagation();
	}).on('drop', function(e) {
		var fileinput = $('#wppus_manual_package_upload');

		fileinput.prop('files', e.originalEvent.dataTransfer.files);
		fileinput.trigger('change');
	});

	$('.manual-package-upload-trigger').on('click', function(e) {
		e.preventDefault();

		var button           = $(this),
			data             = new FormData(),
			valid            = true,
			file             = $('#wppus_manual_package_upload').prop('files')[0],
			regex            = /^([a-zA-Z0-9\-\_]*)\.zip$/gm,
			validFileFormats = [
				'multipart/x-zip',
				'application/zip',
				'application/zip-compressed',
				'application/x-zip-compressed'
			];

		button.attr('disabled', 'disabled');
		button.next().css('visibility', 'visible');

		if (typeof file !== 'undefined' &&
			typeof file.type !== 'undefined' &&
			typeof file.size !== 'undefined' &&
			typeof file.name !==  'undefined'
		) {

			if ($.inArray(file.type, validFileFormats) === -1) {
				window.alert(WppusAdminMain_l10n.invalidFileFormat);

				valid = false;
			}

			if (0 === file.size) {
				window.alert(WppusAdminMain_l10n.invalidFileSize);

				valid = false;
			}

			if (!regex.test(file.name)) {
				window.alert(WppusAdminMain_l10n.invalidFileName);

				valid = false;
			}

		} else {
			window.alert(WppusAdminMain_l10n.invalidFile);

			valid = false;
		}

		if (valid) {
			data.append('action','wppus_manual_package_upload');
			data.append('package', file);
			data.append('nonce', $('#wppus_plugin_options_handler_nonce').val());

			$.ajax({
				url: WppusAdminMain.ajax_url,
				data: data,
				type: 'POST',
				cache: false,
				contentType: false,
				processData: false,
				success: function(response) {

					if (!response.success) {
						var message = '';

						/* jshint ignore:start */
						$.each(response.data, function(idx, value) {
							message += value.message + "\n";
						});
						/* jshint ignore:end */

						button.removeAttr('disabled');
						window.alert(message);
					} else {
						window.location.reload(true);
					}
				},
				error: function (jqXHR, textStatus) {
					WppusAdminMain.debug && console.log(textStatus);
				}
			});
		} else {
			button.next().css('visibility', 'hidden');
			button.removeAttr('disabled');
		}
	});
});