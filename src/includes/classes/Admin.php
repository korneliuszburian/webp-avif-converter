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
        echo '<div class="wrap">';

        $this->renderForm();

        if (isset($_POST['submit'])) {
            if ($_POST['submit'] === 'Delete') {
                $this->logger->log("User initiated Delete action");
                $this->deleteAllAttachmentsAvifAndWebp();
            } elseif ($_POST['submit'] === 'Print Uploads') {
                $this->logger->log("User initiated Print Uploads action");
                echo '<div class="item"><h2>Upload Folder Contents</h2>';
                $this->utils->printUploadFolderContents();
                echo '</div>';
            }
        }

        echo '</div>';
    }

    public function imageSupportInfo()
    {
        $info = '';
        $hasImagick = extension_loaded('imagick');
        $supportsWebP = function_exists('imagewebp');
        $supportsAvif = function_exists('imageavif');

        if ($supportsWebP && $supportsAvif) {
            $info .= '<p>Native PHP support for both WebP and AVIF is available.</p>';
        } else {
            if ($hasImagick) {
                $info .= '<p>Imagick extension is available for converting images.</p>';
                if (!$supportsWebP) $info .= '<p>Using Imagick for WebP conversion.</p>';
                if (!$supportsAvif) $info .= '<p>Using Imagick for AVIF conversion.</p>';
            } else {
                $info .= '<p><strong>Warning:</strong> Neither native PHP functions (imageavif, imagewebp) nor Imagick are available. Image conversion cannot be performed.</p>';
            }
        }

        return $info;
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
            <div class="item">
                <h1 class="text-1">WebP & Avif Converter</h1>
                <fieldset <?php echo PHP_VERSION_OK ? '' : 'disabled' ?>>
                    <p class="conversion-description text-1">Tool for WebP and Avif format conversion of all images in the uploads/media library directory. <br>
                        <b>Choose quality</b> - choose the quality of the converted images (the lower the quality, the smaller the size) <br>
                        <b>Delete function</b> - deletes all WebP and Avif images from the uploads/media library directory.
                        <b>Convert function</b> - converts all images in the uploads/media library directory to WebP and Avif formats. <br>
                        <b>Print function</b> - prints the contents of the uploads/media library directory. <br>
                        <b>Note: </b> This tool will not convert images that are not in the uploads/media library directory.<br>
                        <?php echo $this->getPhpVersionInfo(); ?>
                        <?php echo $this->imageSupportInfo(); ?>
                    </p>
                </fieldset>
            </div>
            <div class="item">
                <form id="convert-form" method="post">
                    <div class="conversion-options">
                        <div>
                            <label class="option-label text-1">Quality of WEBP: (0 - 100%)</label>
                            <input class="quality-input" type="number" name="quality_webp" value="80" min="0" max="100">
                        </div>
                        <div>
                            <label class="option-label text-1">Quality of AVIF: (0 - 100%)</label>
                            <input type="number" name="quality_avif" value="80" min="0" max="100">
                        </div>
                        <div class="conversion-buttons">
                            <input type="submit" name="submit" value="Convert" class="custom-button convert-button text-2">
                            <input type="submit" name="submit" value="Delete" class="custom-button delete-button text-2">
                            <input type="submit" name="submit" value="Print Uploads" class="custom-button print-button text-2">
                        </div>
                    </div>
                </form>
            </div>
            <div class="item">
                <h2 class="text-1">Choose your theme</h2>
                <form id="theme-switcher">
                    <div>
                        <input checked type="radio" id="auto" name="theme" value="auto">
                        <label class="text-1" for="auto">Auto</label>
                    </div>
                    <div>
                        <input type="radio" id="light" name="theme" value="light">
                        <label class="text-1" for="light">Light</label>
                    </div>
                    <div>
                        <input type="radio" id="dark" name="theme" value="dark">
                        <label class="text-1" for="dark">Dark</label>
                    </div>
                    <div>
                        <input type="radio" id="dim" name="theme" value="dim">
                        <label class="text-1" for="dim">Dim</label>
                    </div>
                    <div>
                        <input type="radio" id="grape" name="theme" value="grape">
                        <label class="text-1" for="grape">Grape</label>
                    </div>
                    <div>
                        <input type="radio" id="choco" name="theme" value="choco">
                        <label class="text-1" for="choco">Choco</label>
                    </div>
                </form>
            </div>
            <div class="item progress-bar-item">
                <div id="progress-bar-container" class="circular-progress-bar">
                    <svg viewBox="0 0 36 36" class="circular-chart">
                        <path class="circle-bg" d="M18 2.0845
                    a 15.9155 15.9155 0 0 1 0 31.831
                    a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="var(--surface-4)" stroke-width="4" />
                        <path class="circle" stroke-linecap="round" d="M18 2.0845
                    a 15.9155 15.9155 0 0 1 0 31.831
                    a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="var(--surface-3)" stroke-width="4" stroke-dasharray="0, 100" />
                        <text x="18" y="20.35" class="percentage" fill="var(--text-1)" font-size="4" text-anchor="middle"></text>
                    </svg>
                </div>
            </div>
            <!-- <div id="conversion-status"></div> -->
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
