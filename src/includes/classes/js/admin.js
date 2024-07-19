jQuery(document).ready(function ($) {
    var progressBar = $('#progress-bar');
    var progressBarContainer = $('#progress-bar-container');
    var conversionStatus = $('#conversion-status');

    $('#convert-form').on('submit', function (e) {
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
            success: function (response) {
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
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                conversionStatus.html('An error occurred. Please check the browser console for more details.');
                $('input[type=submit]', '#convert-form').prop('disabled', false);
            }
        });
    }

    function updateProgressBar(progress) {
        const circle = $('#progress-bar-container .circle');
        const percentageText = $('#progress-bar-container .percentage');
        const radius = 15.9155; // Radius of the circle from the SVG
        const circumference = 2 * Math.PI * radius;

        const strokeDasharray = circumference;
        const strokeDashoffset = ((100 - progress) / 100) * circumference;

        circle.css('stroke-dasharray', strokeDasharray + ' ' + strokeDasharray);
        circle.css('stroke-dashoffset', strokeDashoffset);
        percentageText.text(Math.round(progress) + '%');
    }
});

const switcher = document.querySelector('#theme-switcher')
const doc = document.firstElementChild

switcher.addEventListener('input', e =>
    setTheme(e.target.value))

const setTheme = theme =>
    doc.setAttribute('color-scheme', theme)