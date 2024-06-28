jQuery(document).ready(function ($) {
    let batch_size = ajax_object.batch_size;
    let quality_webp = ajax_object.quality_webp;
    let quality_avif = ajax_object.quality_avif;
    let offset = 0;

    function processBatch() {
        $.ajax({
            url: ajax_object.ajax_url,
            method: 'POST',
            data: {
                action: 'convert_batch',
                security: ajax_object.nonce,
                quality_webp: quality_webp,
                quality_avif: quality_avif,
                offset: offset,
                batch_size: batch_size
            },
            success: function (response) {
                if (response.success) {
                    if (response.data.complete) {
                        $('#progress-text').text('Conversion completed');
                    } else {
                        offset += batch_size;
                        let progress = (offset / response.data.total) * 100;
                        $('#progress-bar').css('width', progress + '%');
                        $('#progress-text').text('Progress: ' + progress.toFixed(2) + '%');
                        processBatch();
                    }
                } else {
                    alert('Error during conversion: ' + response.data.message);
                }
            }
        });
    }

    processBatch();
});
