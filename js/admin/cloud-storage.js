jQuery(document).ready(function ($) {

	$('#wppus_use_cloud_storage').on('change', function (e) {

		if ($(this).prop('checked')) {
			$('.hide-if-no-cloud-storage').removeClass('hidden');
		} else {
			$('.hide-if-no-cloud-storage').addClass('hidden');
		}
	});

});