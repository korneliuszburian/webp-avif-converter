<?php

namespace WebpAvifConverter;

class Plugin
{
    private $webpConverter;
    private $avifConverter;
    private $fileDeleter;
    private $utils;
    private $logger;

    public function __construct()
    {
        $this->webpConverter = new WebpImageConverter();
        $this->avifConverter = new AvifImageConverter();
        $this->fileDeleter = new ImageDeleter();
        $this->utils = new Utils();
        $this->logger = new Logger();
    }

    public function run()
    {
        $this->logger->log("Running Plugin");
        add_filter('wp_generate_attachment_metadata', [$this, 'convertImagesOnGenerateAttachmentMetadata'], 10, 2);
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('delete_attachment', [$this->fileDeleter, 'deleteAttachmentFiles']);
        add_action('wp_ajax_convert_batch', [$this, 'convertBatch']);
        add_action('wp_ajax_get_total_attachments', [$this, 'getTotalAttachments']);
    }

    public function convertImagesOnGenerateAttachmentMetadata($metadata, $attachment_id)
    {
        $this->logger->log("convertImagesOnGenerateAttachmentMetadata called with attachment ID: $attachment_id");
        $converter = new ImageConverter($this->webpConverter, $this->avifConverter);
        return $converter->convertImagesOnGenerateAttachmentMetadata($metadata, $attachment_id);
    }

    public function addAdminMenu()
    {
        $this->logger->log("Adding admin menu");
        add_submenu_page(
            'options-general.php',
            'WebP & Avif Converter',
            'WebP & Avif Converter',
            'manage_options',
            'webp_avif_bulk_convert',
            [$this, 'renderAdminPage']
        );
    }

    public function renderAdminPage()
    {
        $this->logger->log("Rendering admin page");

        $admin = new Admin($this->webpConverter, $this->avifConverter, $this->fileDeleter, $this->utils);
        $admin->render();
    }

    public function convertBatch()
    {
        check_ajax_referer('convert_batch_nonce', 'security');
    
        $quality_webp = intval($_POST['quality_webp']);
        $quality_avif = intval($_POST['quality_avif']);
        $offset = intval($_POST['offset']);
        $batch_size = intval($_POST['batch_size']);
    
        $total_attachments = wp_count_posts('attachment')->publish;
        $complete = false;
    
        $this->updateAllAttachmentsWebpAvifQuality($quality_webp, $quality_avif, $offset, $batch_size);
    
        if (($offset + $batch_size) >= $total_attachments) {
            $complete = true;
        }
    
        wp_send_json_success(['complete' => $complete, 'total' => $total_attachments]);
    }

    private function updateAllAttachmentsWebpAvifQuality($quality_webp, $quality_avif, $offset = 0, $batch_size = 10)
    {
        $attachments = get_posts([
            'post_type' => 'attachment',
            'numberposts' => $batch_size,
            'offset' => $offset,
            'post_status' => null,
            'post_parent' => null,
            'exclude' => get_post_thumbnail_id()
        ]);

        foreach ($attachments as $attachment) {
            $this->logger->log("Converting attachment ID: {$attachment->ID}");
            $converter = new ImageConverter($this->webpConverter, $this->avifConverter);
            $converter->convertImagesOnGenerateAttachmentMetadata(wp_get_attachment_metadata($attachment->ID), $attachment->ID, $quality_webp, $quality_avif);
        }
    }

    public function getTotalAttachments()
    {
        check_ajax_referer('convert_batch_nonce', 'security');

        $total_attachments = wp_count_posts('attachment')->publish;

        wp_send_json_success(['total_attachments' => $total_attachments]);
    }
}