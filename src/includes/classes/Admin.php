<?php

namespace WebpAvifConverter;

class Admin
{
    private $file_deleter;
    private $utils;
    private $logger;

    public function __construct(
        ImageConverterInterface $webp_converter,
        ImageConverterInterface $avif_converter,
        ImageDeleterInterface $file_deleter,
        Utils $utils
    ) {
        $this->file_deleter = $file_deleter;
        $this->utils = $utils;
        $this->logger = new Logger();
    }

    public function render()
    {
        if (isset($_POST['submit'])) {
            $quality_webp = isset($_POST['quality_webp']) ? intval($_POST['quality_webp']) : 80;
            $quality_avif = isset($_POST['quality_avif']) ? intval($_POST['quality_avif']) : 80;

            if ($_POST['submit'] === 'Convert') {
                $this->logger->log("User initiated Convert action with quality_webp: $quality_webp and quality_avif: $quality_avif");
                $this->enqueue_conversion_script($quality_webp, $quality_avif);
            } else if ($_POST['submit'] === 'Delete') {
                $this->logger->log("User initiated Delete action");
                $this->delete_all_attachments_avif_and_webp();
            } else if ($_POST['submit'] === 'Print Uploads') {
                $this->logger->log("User initiated Print Uploads action");
                echo '<h2>Upload Folder Contents</h2>';
                $this->utils->print_upload_folder_contents();
            }
        }

        $this->render_form();
    }

    private function enqueue_conversion_script($quality_webp, $quality_avif)
    {
        wp_enqueue_script('ajax-conversion', plugin_dir_url(__FILE__) . '/../../../public/js/ajax-conversion.js', ['jquery'], null, true);
        wp_localize_script('ajax-conversion', 'ajax_object', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('convert_batch_nonce'),
            'quality_webp' => $quality_webp,
            'quality_avif' => $quality_avif,
        ]);
    }

    private function render_form()
    {
        ?>
        <form method="post">
            <fieldset <?php echo PHP_VERSION_OK ? '' : 'disabled' ?>>
                <p class="conversion-description">Tool for WebP and Avif format conversion of all images in the uploads/media library directory. <br>
                    <b>Choose quality</b> - choose the quality of the converted images (the lower the quality, the smaller the size) <br>
                    <b>Delete function</b> - deletes all WebP and Avif images from the uploads/media library directory.
                    <b>Convert function</b> - converts all images in the uploads/media library directory to WebP and Avif formats. <br>
                    <b>Print function</b> - prints the contents of the uploads/media library directory. <br>
                    <b>Note: </b> This tool will not convert images that are not in the uploads/media library directory.<br>
                    <?php echo $this->get_php_version_info(); ?>
                </p>
                <div class="conversion-options">
                    <label class="option-label">Quality of WEBP: (0 - 100%)</label>
                    <input class="quality-input" type="number" name="quality_webp" value="80" min="0" max="100">
                    <label class="option-label">Quality of AVIF: (0 - 100%)</label>
                    <input type="number" name="quality_avif" value="80" min="0" max="100">
                    <br><br>
                    <div class="conversion-buttons">
                        <input type="submit" name="submit" value="Convert" class="convert-button">
                        <input type="submit" name="submit" value="Delete" class="delete-button">
                        <input type="submit" name="submit" value="Print Uploads" class="print-button">
                    </div>
                </div>
            </fieldset>
        </form>
        <div id="progress-container" style="display: none;">
            <div id="progress-bar" style="width: 0%; height: 20px; background-color: green;"></div>
        </div>
        <div id="progress-text" style="margin-top: 10px;"></div>
        <?php
    }

    private function delete_all_attachments_avif_and_webp()
    {
        $deleted = $this->file_deleter->deleteAllAvifAndWebpFiles();

        if ($deleted) {
            echo '<p class="notification notification-bad">All WebP and Avif images have been deleted.</p>';
        } else {
            echo '<p class="notification notification-info">There were no images to delete.</p>';
        }
    }

    private function get_php_version_info()
    {
        if (version_compare(phpversion(), PHP_REQUIRED_VERSION, '>=')) {
            return '<span class="php-version-good">PHP Version is: ' . phpversion() . ' <b>version >= ' . PHP_REQUIRED_VERSION . '</b> is required.</span>';
        } else {
            return '<span class="php-version-bad">PHP Version: ' . phpversion() . ' is too low <b>version >= ' . PHP_REQUIRED_VERSION . ' is required</b>.</span>';
        }
    }
}
