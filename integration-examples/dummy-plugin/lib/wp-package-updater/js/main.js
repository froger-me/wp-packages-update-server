/* version 1.4.0 */
/* global WP_PackageUpdater */
jQuery(document).ready(function ($) {

	$('body').on('click', '.wrap-license .activate-license', function(e) {
		e.preventDefault();

		var licenseContainer = $(this).parent().parent(),
			data             = {
			'nonce' : licenseContainer.data('nonce'),
			'license_key' : licenseContainer.find('.license').val(),
			'package_slug' : licenseContainer.data('package_slug'),
			'action' : WP_PackageUpdater.action_prefix + '_activate_license'
		};

		$.ajax({
			url: WP_PackageUpdater.ajax_url,
			data: data,
			type: 'POST',
			success: function(response) {

				if (response.success) {
					licenseContainer.find('.current-license').html(licenseContainer.find('.license').val());
					licenseContainer.find('.current-license-error').addClass('hidden');
					licenseContainer.find('.license-message').removeClass('hidden');
					$( '.license-error-' + licenseContainer.data('package_slug') + '.notice' ).addClass('hidden');
				} else {
					var errorContainer = licenseContainer.find('.current-license-error');

					errorContainer.html(response.data[0].message + '<br/>');
					errorContainer.removeClass('hidden');
					licenseContainer.find('.license-message').removeClass('hidden');
				}

				if ('' === licenseContainer.find('.current-license').html()) {
					licenseContainer.find('.current-license-label').addClass('hidden');
					licenseContainer.find('.current-license').addClass('hidden');
				} else {
					licenseContainer.find('.current-license-label').removeClass('hidden');
					licenseContainer.find('.current-license').removeClass('hidden');
				}
			}
		});
	});

	$('body').on('click', '.wrap-license .deactivate-license', function(e) {
		e.preventDefault();

		var licenseContainer = $(this).parent().parent(),
			data             = {
			'nonce' : licenseContainer.data('nonce'),
			'license_key' : licenseContainer.find('.license').val(),
			'package_slug' : licenseContainer.data('package_slug'),
			'action' : WP_PackageUpdater.action_prefix + '_deactivate_license'
		};

		$.ajax({
			url: WP_PackageUpdater.ajax_url,
			data: data,
			type: 'POST',
			success: function(response) {

				if (response.success) {
					licenseContainer.find('.current-license').html('');
					licenseContainer.find('.current-license-error').addClass('hidden');
					licenseContainer.find('.license-message').addClass('hidden');
				} else {
					var errorContainer = licenseContainer.find('.current-license-error');

					errorContainer.html(response.data[0].message + '<br/>');
					errorContainer.removeClass('hidden');
					licenseContainer.find('.license-message').removeClass('hidden');
				}

				if ('' === licenseContainer.find('.current-license').html()) {
					licenseContainer.find('.current-license-label').addClass('hidden');
					licenseContainer.find('.current-license').addClass('hidden');
				} else {
					licenseContainer.find('.current-license-label').removeClass('hidden');
					licenseContainer.find('.current-license').removeClass('hidden');
				}
			}
		});
	});
});