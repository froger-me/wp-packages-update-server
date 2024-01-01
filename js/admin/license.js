/* global Wppus, console */
jQuery(document).ready(function ($) {
	editor = wp.codeEditor.initialize($('#wppus_license_data'), WppusAdminLicense.cm_settings);

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
			ignore: '.CodeMirror *',
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
						'data': $('#wppus_license_data').val(),
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

		if (1 >= $('.wppus-remove-domain').length) {
			$('.wppus-no-domain').show();
		}
	});

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
			$('#wppus_license_data').val(licenseData.data ? JSON.stringify(JSON.parse(licenseData.data), null, '\t') : '');
			$('#wppus_license_package_type').val(licenseData.package_type);
			editor.codemirror.setValue($('#wppus_license_data').val());

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
		editor.codemirror.setValue('{}');
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