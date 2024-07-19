<?php

namespace WebpAvifConverter;

class Admin
{
    private $webpConverter;
    private $avifConverter;
    private $fileDeleter;
    private $utils;
    private $logger;

    public function __construct(
        ImageConverterInterface $webpConverter,
        ImageConverterInterface $avifConverter,
        ImageDeleterInterface $fileDeleter,
        Utils $utils
    ) {
        $this->webpConverter = $webpConverter;
        $this->avifConverter = $avifConverter;
        $this->fileDeleter = $fileDeleter;
        $this->utils = $utils;
        $this->logger = new Logger();
    }

    public function render()
    {
        if (isset($_POST['submit'])) {
            if ($_POST['submit'] === 'Delete') {
                $this->logger->log("User initiated Delete action");
                $this->deleteAllAttachmentsAvifAndWebp();
            } else if ($_POST['submit'] === 'Print Uploads') {
                $this->logger->log("User initiated Print Uploads action");
                echo '<h2>Upload Folder Contents</h2>';
                $this->utils->printUploadFolderContents();
            }
        }

        $this->renderForm();
    }

    public function handleAjaxConversion()
    {
        $this->logger->log("AJAX conversion started");

        if (!check_ajax_referer('convert_images_nonce', 'security', false)) {
            $this->logger->log("Nonce check failed");
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        $quality_webp = isset($_POST['quality_webp']) ? intval($_POST['quality_webp']) : 80;
        $quality_avif = isset($_POST['quality_avif']) ? intval($_POST['quality_avif']) : 80;

        $this->logger->log("Quality settings - WebP: $quality_webp, AVIF: $quality_avif");

        $attachments = get_posts([
            'post_type' => 'attachment',
            'numberposts' => -1,
            'post_status' => null,
            'post_parent' => null,
            'exclude' => get_post_thumbnail_id()
        ]);

        $total = count($attachments);
        $this->logger->log("Total attachments found: $total");

        $current = isset($_POST['current']) ? intval($_POST['current']) : 0;

        if ($current >= $total) {
            $this->logger->log("All images processed");
            wp_send_json_success(['progress' => 100, 'message' => 'All images have been converted to WebP and Avif formats.']);
            return;
        }

        $attachment = $attachments[$current];
        $this->logger->log("Processing attachment ID: {$attachment->ID}");

        try {
            $converter = new ImageConverter($this->webpConverter, $this->avifConverter);
            $result = $converter->convertImagesOnGenerateAttachmentMetadata(wp_get_attachment_metadata($attachment->ID), $attachment->ID, $quality_webp, $quality_avif);
            
            $this->logger->log("Conversion result: " . print_r($result, true));

            $current++;
            $progress = ($current / $total) * 100;
            
            $this->logger->log("Progress: $progress%");

            wp_send_json_success([
                'progress' => $progress,
                'current' => $current,
                'total' => $total
            ]);
        } catch (\Exception $e) {
            $this->logger->log("Error during conversion: " . $e->getMessage());
            wp_send_json_error(['message' => 'Error during conversion: ' . $e->getMessage()]);
        }
    }

    private function deleteAllAttachmentsAvifAndWebp()
    {
        $deleted = $this->fileDeleter->deleteAllAvifAndWebpFiles();

        if ($deleted) {
            echo '<p class="notification notification-bad">All WebP and Avif images have been deleted.</p>';
        } else {
            echo '<p class="notification notification-info">There were no images to delete.</p>';
        }
    }

    private function renderForm()
    {
        wp_enqueue_script('webp-avif-converter-admin', plugin_dir_url(__FILE__) . 'js/admin.js', ['jquery'], '1.0.0', true);
        wp_localize_script('webp-avif-converter-admin', 'webp_avif_converter', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('convert_images_nonce')
        ]);
        
        ?>
        <div class="wrap">
            <h1>WebP & Avif Converter</h1>
            <form id="convert-form" method="post">
                <fieldset <?php echo PHP_VERSION_OK ? '' : 'disabled' ?>>
                    <p class="conversion-description">Tool for WebP and Avif format conversion of all images in the uploads/media library directory. <br>
                        <b>Choose quality</b> - choose the quality of the converted images (the lower the quality, the smaller the size) <br>
                        <b>Delete function</b> - deletes all WebP and Avif images from the uploads/media library directory.
                        <b>Convert function</b> - converts all images in the uploads/media library directory to WebP and Avif formats. <br>
                        <b>Print function</b> - prints the contents of the uploads/media library directory. <br>
                        <b>Note: </b> This tool will not convert images that are not in the uploads/media library directory.<br>
                        <?php echo $this->getPhpVersionInfo(); ?>
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
            <div id="progress-bar-container" style="display: none;">
                <div id="progress-bar"></div>
            </div>
            <div id="conversion-status"></div>
        </div>
        <?php
    }

    private function getPhpVersionInfo()
    {
        if (version_compare(phpversion(), PHP_REQUIRED_VERSION, '>=')) {
            return '<span class="php-version-good">PHP Version is: ' . phpversion() . ' <b>version >= ' . PHP_REQUIRED_VERSION . '</b> is required.</span>';
        } else {
            return '<span class="php-version-bad">PHP Version: ' . phpversion() . ' is too low <b>version >= ' . PHP_REQUIRED_VERSION . ' is required</b>.</span>';
        }
    }
}