jQuery(document).ready(function ($) {
    const $document = $(document); // Cache document lookup

    // init events listeners
    $document.on('click', '#submit-c411-credentials', submit_c411_credentials);

    function submit_c411_credentials(e) {
        e.preventDefault();
        allI1d.requestWPApi(
            allI1d_c411.api.routes.credentials,
            {
                c411_api_key: $('#c411_api_key').val(),
            },
            function (response, data) {
                allI1d.showToast('Saved', 'success');
                setTimeout(function () { location.reload(); }, 1000);
            },
            'POST',
            function (request, error) {
                var message = (request.responseJSON && request.responseJSON.message)
                    ? request.responseJSON.message
                    : error;
                allI1d.showToast(message, 'error');
            }
        );
    }
});
