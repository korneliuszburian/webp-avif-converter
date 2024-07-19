jQuery(document).ready(function($) {
    var progressBar = $('#progress-bar');
    var progressBarContainer = $('#progress-bar-container');
    var conversionStatus = $('#conversion-status');

    $('#convert-form').on('submit', function(e) {
        var clickedButton = $('input[type=submit]:focus', this);

        if (clickedButton.val() === 'Convert') {
            e.preventDefault();

            $('input[type=submit]', this).prop('disabled', true);

            progressBarContainer.show();
            progressBar.css('width', '0%');
            conversionStatus.html('');

            convertImages(0);
        }
    });

    function convertImages(current) {
        var formData = $('#convert-form').serialize() + '&action=convert_images&security=' + webp_avif_converter.nonce + '&current=' + current;

        $.ajax({
            url: webp_avif_converter.ajax_url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('AJAX response:', response);
                if (response.success) {
                    updateProgressBar(response.data.progress);
                    conversionStatus.html('Converting image ' + response.data.current + ' of ' + response.data.total);
                    if (response.data.progress < 100) {
                        convertImages(response.data.current);
                    } else {
                        conversionStatus.html(response.data.message);
                        $('input[type=submit]', '#convert-form').prop('disabled', false);
                    }
                } else {
                    conversionStatus.html('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                    $('input[type=submit]', '#convert-form').prop('disabled', false);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                conversionStatus.html('An error occurred. Please check the browser console for more details.');
                $('input[type=submit]', '#convert-form').prop('disabled', false);
            }
        });
    }

    function updateProgressBar(progress) {
        progressBar.css('width', progress + '%');
        progressBar.text(Math.round(progress) + '%');
    }
});
