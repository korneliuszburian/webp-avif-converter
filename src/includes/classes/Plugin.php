<?php

namespace WebpAvifConverter;

class Plugin
{
    private $webpConverter;
    private $avifConverter;
    private $fileDeleter;
    private $imageConverter;
    private $utils;

    public function __construct()
    {
        $this->webpConverter = new WebpImageConverter();
        $this->avifConverter = new AvifImageConverter();
        $this->fileDeleter = new ImageDeleter();
        $this->utils = new Utils();
        $this->imageConverter = new ImageConverter($this->webpConverter, $this->avifConverter);
    }

    public function run()
    {
        add_filter('wp_generate_attachment_metadata', [$this, 'convertImagesOnGenerateAttachmentMetadata'], 10, 2);
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('delete_attachment', [$this->fileDeleter, 'deleteAttachmentFiles']);
    }

    public function convertImagesOnGenerateAttachmentMetadata($metadata, $attachment_id)
    {
        return $this->imageConverter->convertImagesOnGenerateAttachmentMetadata($metadata, $attachment_id);
    }

    public function addAdminMenu()
    {
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
        $admin = new Admin($this->webpConverter, $this->avifConverter, $this->fileDeleter, $this->utils);
        $admin->render();
    }
}