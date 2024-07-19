<?php

namespace WebpAvifConverter;

class Plugin
{
    private $webpConverter;
    private $avifConverter;
    private $fileDeleter;
    private $utils;
    private $admin;

    public function __construct(
        WebpImageConverter $webpConverter,
        AvifImageConverter $avifConverter,
        ImageDeleter $fileDeleter
    ) {
        $this->webpConverter = $webpConverter;
        $this->avifConverter = $avifConverter;
        $this->fileDeleter = $fileDeleter;
        $this->utils = new Utils();
        $this->admin = new Admin($webpConverter, $avifConverter, $fileDeleter, $this->utils);
    }
    
    public function run()
    {
        add_filter('wp_generate_attachment_metadata', [$this, 'convertImagesOnGenerateAttachmentMetadata'], 10, 2);
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('delete_attachment', [$this->fileDeleter, 'deleteAttachmentFiles']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
        add_action('wp_ajax_convert_images', [$this->admin, 'handleAjaxConversion']);
    }

    public function convertImagesOnGenerateAttachmentMetadata($metadata, $attachment_id)
    {
        $converter = new ImageConverter($this->webpConverter, $this->avifConverter);
        return $converter->convertImagesOnGenerateAttachmentMetadata($metadata, $attachment_id);
    }

    public function addAdminMenu()
    {
        add_submenu_page(
            'options-general.php',
            'WebP & Avif Converter',
            'WebP & Avif Converter',
            'manage_options',
            'webp_avif_bulk_convert',
            [$this->admin, 'render']
        );
    }

    public function enqueueAdminScripts($hook)
    {
        if ($hook != 'settings_page_webp_avif_bulk_convert') {
            return;
        }

        wp_enqueue_style('webp-avif-converter-admin', plugin_dir_url(__FILE__) . 'css/admin.css', [], '1.0.0');
    }
}