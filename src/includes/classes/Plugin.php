<?php

namespace WebpAvifConverter;

class Plugin
{
    private $webp_converter;
    private $avif_converter;
    private $file_deleter;
    private $utils;
    private $logger;

    public function __construct()
    {
        $this->webp_converter = new WebpImageConverter();
        $this->avif_converter = new AvifImageConverter();
        $this->file_deleter = new ImageDeleter();
        $this->utils = new Utils();
        $this->logger = new Logger();
    }

    public function run()
    {
        $this->logger->log("Running Plugin");
        add_filter('wp_generate_attachment_metadata', [$this, 'convert_images_on_generate_attachment_metadata'], 10, 2);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('delete_attachment', [$this->file_deleter, 'delete_attachment_files']);
        add_action('wp_ajax_convert_batch', [$this, 'convert_images_batch']);
        add_action('wp_ajax_get_total_attachments', [$this, 'get_total_attachments']);
    }

    public function convert_images_on_generate_attachment_metadata($metadata, $attachment_id)
    {
        $this->logger->log("convert_images_on_generate_attachment_metadata called with attachment ID: $attachment_id");
        $converter = new ImageConverter($this->webp_converter, $this->avif_converter);
        return $converter->convertImagesOnGenerateAttachmentMetadata($metadata, $attachment_id);
    }

    public function add_admin_menu()
    {
        $this->logger->log("Adding admin menu");
        add_submenu_page(
            'options-general.php',
            'WebP & Avif Converter',
            'WebP & Avif Converter',
            'manage_options',
            'webp_avif_bulk_convert',
            [$this, 'render_admin_page']
        );
    }

    public function render_admin_page()
    {
        $this->logger->log("Rendering admin page");

        $admin = new Admin($this->webp_converter, $this->avif_converter, $this->file_deleter, $this->utils);
        $admin->render();
    }

    public function convert_images_batch()
    {
        check_ajax_referer('convert_batch_nonce', 'security');
    
        $quality_webp = intval($_POST['quality_webp']);
        $quality_avif = intval($_POST['quality_avif']);
        $offset = intval($_POST['offset']);
    
        $total_attachments = wp_count_posts('attachment')->publish;
        $complete = false;
    
        $this->update_all_attachments_webp_avif_quality($quality_webp, $quality_avif, $offset);
    
        wp_send_json_success(['complete' => $complete, 'total' => $total_attachments]);
    }

    private function update_all_attachments_webp_avif_quality($quality_webp, $quality_avif, $offset = 0)
    {
        $attachments = get_posts([
            'post_type' => 'attachment',
            'offset' => $offset,
            'post_status' => null,
            'post_parent' => null,
            'exclude' => get_post_thumbnail_id()
        ]);

        foreach ($attachments as $attachment) {
            $this->logger->log("Converting attachment ID: {$attachment->ID}");
            $converter = new ImageConverter($this->webp_converter, $this->avif_converter);
            $converter->convertImagesOnGenerateAttachmentMetadata(wp_get_attachment_metadata($attachment->ID), $attachment->ID, $quality_webp, $quality_avif);
        }
    }

    public function get_total_attachments()
    {
        check_ajax_referer('convert_batch_nonce', 'security');

        $total_attachments = wp_count_posts('attachment')->publish;

        wp_send_json_success(['total_attachments' => $total_attachments]);
    }
}
