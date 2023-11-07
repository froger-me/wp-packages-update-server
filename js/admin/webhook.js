jQuery(document).ready(function ($) {

	$('#wppus_remote_repository_use_webhooks').on('change', function (e) {

		if ($(this).prop('checked')) {
			$('.check-frequency').addClass('hidden');
			$('.webhooks').removeClass('hidden');
        } else {
            console.log($('.webhooks'));
            $('.webhooks').addClass('hidden');
			$('.check-frequency').removeClass('hidden');
		}
	});

});