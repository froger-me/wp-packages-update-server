/* global Wppus, console */
jQuery(document).ready(function($) {

	if (-1 !==  location.href.indexOf('action=')) {
		var hrefParts = window.location.href.split('?');

		hrefParts[1] = hrefParts[1].replace('action=', 'action_done=');

		history.pushState(null, '', hrefParts.join('?') );
	}

	$('.wppus-wrap .wp-list-table .delete a').on('click', function(e) {
		var r = window.confirm(Wppus.deleteRecord);
			
		if (!r) {
			e.preventDefault();
		}
	});

	$('.wppus-delete-all-packages').on('click', function(e) {
		var r = window.confirm(Wppus.deletePackagesConfirm);
			
		if (!r) {
			e.preventDefault();
		}
	});

	$('.wppus-delete-all-licenses').on('click', function(e) {
		var r = window.confirm(Wppus.deleteLicensesConfirm);
			
		if (!r) {
			e.preventDefault();
		}
	});
	
	$('.ajax-trigger').on('click', function(e) {
		e.preventDefault();

		var button = $(this),
			type   = button.data('type'),
			data   = {
				type :   type,
				nonce :  $('#wppus_plugin_options_handler_nonce').val(),
				action : 'wppus_force_' + button.data('action'),
			};

		button.attr('disabled', 'disabled');

		$.ajax({
			url: Wppus.ajax_url,
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

					window.alert(message);
				}

				button.removeAttr('disabled');

				if (response.data && response.data.btnVal) {
					button.val(response.data.btnVal);
				}
			},
			error: function (jqXHR, textStatus) {
				Wppus.debug && console.log(textStatus);
			}
		});
		
	});

	$('#wppus_prime_package_slug').on('keyup', function() {
		var textinput = $(this);
		
		if (0 < textinput.val().length) {
			$('#wppus_prime_package_trigger').removeAttr('disabled');
		} else {
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

		$.ajax({
			url: Wppus.ajax_url,
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

					button.removeAttr('disabled');
					button.next().css('visibility', 'hidden');
					window.alert(message);
				} else {
					window.location.reload(true); 
				}
			},
			error: function (jqXHR, textStatus) {
				Wppus.debug && console.log(textStatus);
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
				window.alert(Wppus.invalidFileFormat);

				valid = false;
			}

			if (0 === file.size) {
				window.alert(Wppus.invalidFileSize);

				valid = false;
			}

			if (!regex.test(file.name)) {
				window.alert(Wppus.invalidFileName);

				valid = false;
			}
			
		} else {
			window.alert(Wppus.invalidFile);

			valid = false;
		}

		if (valid) {
			data.append('action','wppus_manual_package_upload');
			data.append('package', file);
			data.append('nonce', $('#wppus_plugin_options_handler_nonce').val());

			$.ajax({
				url: Wppus.ajax_url,
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
					Wppus.debug && console.log(textStatus);
				}
			});
		} else {
			button.next().css('visibility', 'hidden');
			button.removeAttr('disabled');
		}
	});

	$('#add_license_trigger').on('click', function() {
		showLicensePanel($('#wppus_license_panel'), function() {
			populateLicensePanel();
			$('#wppus_license_action').val('create');
			$('.wppus-edit-license-label').hide();
			$('.wppus-license-show-if-edit').hide();
			$('.wppus-add-license-label').show();
			$('.open-panel').attr('disabled', 'disabled');
			$('.wppus-licenses-table .open-panel').hide();
			$('html, body').animate({
                scrollTop: ($('#wppus_license_panel').offset().top - $('#wpadminbar').height() - 20)
            }, 500);
		});
	});
	$('.wppus-licenses-table .open-panel .edit a').on('click', function(e){
		e.preventDefault();

		var licenseData = JSON.parse($(this).closest('tr').find('input[name="license_data[]"]').val());

		showLicensePanel($('#wppus_license_panel'), function() {
			populateLicensePanel(licenseData);
			$('#wppus_license_action').val('update');
			$('.wppus-edit-license-label').show();
			$('.wppus-license-show-if-edit').show();
			$('.wppus-add-license-label').hide();
			$('.open-panel').attr('disabled', 'disabled');
			$('.wppus-licenses-table .open-panel').hide();
			$('html, body').animate({
                scrollTop: ($('#wppus_license_panel').offset().top - $('#wpadminbar').height() - 20)
            }, 500);
		});
	});

	$('#wppus_license_cancel, .close-panel.reset').on('click', function() {
		$('html, body').animate({
            scrollTop: ($('.wppus-wrap').offset().top - $('#wpadminbar').height() - 20)
        }, 150);
		hideLicensePanel($('#wppus_license_panel'), function() {
			resetLicensePanel();
		});
	});
	
	if ($.validator) {
		$.validator.methods.licenseDate = function( value, element ) {
			return this.optional( element ) || /[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])/.test( value );
		};
		$.validator.methods.slug = function( value, element ) {
			return this.optional( element ) || /[a-z0-9-]*/.test( value );
		};

		$('#wppus_license').validate({
			errorClass: 'wppus-license-error',
			rules: {
				wppus_license_key: { required: true },
				wppus_license_package_slug: { required: true, slug: true },
				wppus_license_registered_email: { required: true, email: true },
				wppus_license_date_created: { required: true, licenseDate: true },
				wppus_license_date_expiry: { licenseDate: true },
				wppus_license_date_renewed: { licenseDate: true },
				wppus_license_max_allowed_domains: { required: true }
			},
			submitHandler: function(form) {
				var domainElements = $('.wppus-domains-list li:not(.wppus-domain-template) .wppus-domain-value'),
					values = {
					'id': $('#wppus_license_id').html(),
					'license_key': $('#wppus_license_key').val(),
					'max_allowed_domains': $('#wppus_license_max_allowed_domains').val(),
					'allowed_domains': domainElements.map(function() { return $(this).text(); }).get(),
					'status': $('#wppus_license_status').val(),
					'owner_name': $('#wppus_license_owner_name').val(),
					'email': $('#wppus_license_registered_email').val(),
					'company_name': $('#wppus_license_owner_company').val(),
					'txn_id': $('#wppus_license_transaction_id').val(),
					'date_created': $('#wppus_license_date_created').val(),
					'date_renewed': $('#wppus_license_date_renewed').val(),
					'date_expiry': $('#wppus_license_date_expiry').val(),
					'package_slug': $('#wppus_license_package_slug').val(),
					'package_type': $('#wppus_license_package_type').val()
				};

				$('#wppus_license_values').val(JSON.stringify(values));
				$('.no-submit').removeAttr('name');
				form.submit();
			}
		});
	}
	
	$('#wppus_license_registered_domains').on('click', '.wppus-remove-domain', function(e) {
		e.preventDefault();
		$(this).parent().remove();

		if (1 <= $('.wppus-remove-domain').length) {
			$('.wppus-no-domain').show();
		}
	});

	if ($('.wppus-license-date').length > 0) {
		$('.wppus-license-date').datepicker({
			dateFormat : 'yy-mm-dd'
		});
	}

	function populateLicensePanel(licenseData) {

		if ($.isPlainObject(licenseData)) {
			$('#wppus_license_id').html(licenseData.id);
			$('#wppus_license_key').val(licenseData.license_key);
			$('#wppus_license_date_created').val(licenseData.date_created);
			$('#wppus_license_max_allowed_domains').val(licenseData.max_allowed_domains);
			$('#wppus_license_owner_name').val(licenseData.owner_name);
			$('#wppus_license_registered_email').val(licenseData.email);
			$('#wppus_license_owner_company').val(licenseData.company_name);
			$('#wppus_license_transaction_id').val(licenseData.txn_id);
			$('#wppus_license_package_slug').val(licenseData.package_slug);
			$('#wppus_license_status').val(licenseData.status);
			$('#wppus_license_package_type').val(licenseData.package_type);

			if ('0000-00-00' !== licenseData.date_expiry ) {
				$('#wppus_license_date_expiry').val(licenseData.date_expiry);
			}

			if ('0000-00-00' !== licenseData.date_renewed ) {
				$('#wppus_license_date_renewed').val(licenseData.date_renewed);
			}

			if (licenseData.allowed_domains.length > 0) {
				var list = $('.wppus-domains-list'),
					listItem = list.find('li').clone();

				listItem.removeClass('wppus-domain-template');

				$.each(licenseData.allowed_domains, function(idx, elem) {
					var item = listItem.clone();

					item.find('.wppus-domain-value').html(elem);
					list.append(item);
				});

				$('.wppus-no-domain').hide();
				list.show();
			}
		} else {
			$('#wppus_license_key').val($('#wppus_license_key').data('random_key'));
			$('#wppus_license_date_created').val(new Date().toISOString().slice(0, 10));
			$('#wppus_license_max_allowed_domains').val(1);
		}
	}

	function resetLicensePanel() {
		$('#wppus_license').trigger('reset');
		$('wppus_license_values').val('');
		$('wppus_license_action').val('');
		$('.open-panel').removeAttr('disabled');
		$('.wppus-licenses-table .open-panel').show();
		$('#wppus_license_id').html('');
		$('.wppus-domains-list li:not(.wppus-domain-template)').remove();
		$('.wppus-no-domain').show();
		$('label.wppus-license-error').hide();
		$('.wppus-license-error').removeClass('wppus-license-error');
	}

	function showLicensePanel( panel, callback ) {

		if (!panel.is(':visible')) {
			panel.slideDown(100, function() {
				callback(panel);
				panel.find('.inside').animate({ opacity: '1' }, 150 );
			});
		}
	}

	function hideLicensePanel( panel, callback ) {

		if (panel.is(':visible')) {
			panel.slideUp(100, function() {
				panel.find('.inside').css( { opacity: '0' } );
				callback(panel);
			});
		}
	}
});