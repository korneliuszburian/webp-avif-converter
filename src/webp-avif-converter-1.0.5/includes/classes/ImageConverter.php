<?php

namespace WebpAvifConverter;

class ImageConverter
{
    private $webpConverter;
    private $avifConverter;
    private $logger;

    public function __construct(ImageConverterInterface $webpConverter, ImageConverterInterface $avifConverter)
    {
        $this->webpConverter = $webpConverter;
        $this->avifConverter = $avifConverter;
        $this->logger = new Logger();
    }

    public function convertImagesOnGenerateAttachmentMetadata($metadata, $attachment_id, $quality_webp = 80, $quality_avif = 80)
    {
        $path = get_attached_file($attachment_id);
        $this->logger->log("Converting images for attachment ID: $attachment_id");

        if (!file_exists($path . '.webp')) {
            $metadata['file_webp'] = $this->webpConverter->convert($path, $quality_webp);
        }

        if (!file_exists($path . '.avif')) {
            $metadata['file_avif'] = $this->avifConverter->convert($path, $quality_avif);
        }

        $metadata['sizes_avif'] = [];
        $metadata['sizes_webp'] = [];

        if (!empty($metadata['sizes']) && is_array($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $key => $size) {
                $filename_with_extension = $size['file'];
                $extension = pathinfo($filename_with_extension, PATHINFO_EXTENSION);
                $filename = substr($filename_with_extension, 0, -(strlen($extension) + 1));
                $thumbnail_path = dirname($path) . '/' . $size['file'];

                $this->logger->log("Processing size $key: $filename_with_extension, base filename: $filename, extension: $extension");

                if (!file_exists($thumbnail_path . '.webp')) {
                    $webp_path = $this->webpConverter->convert($thumbnail_path, $quality_webp);
                    if ($webp_path) {
                        $metadata['sizes_webp'][$key]['file'] = $filename . '.webp';
                    }
                }

                if (!file_exists($thumbnail_path . '.avif')) {
                    $avif_path = $this->avifConverter->convert($thumbnail_path, $quality_avif);
                    if ($avif_path) {
                        $metadata['sizes_avif'][$key]['file'] = $filename . '.avif';
                    }
                }
            }
        }

        $this->logger->log("Metadata after conversion: " . print_r($metadata, true));
        return $metadata;
    }
}