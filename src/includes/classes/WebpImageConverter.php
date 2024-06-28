<?php

namespace WebpAvifConverter;

class WebpImageConverter implements ImageConverterInterface
{
    private $logger;

    public function __construct()
    {
        $this->logger = new Logger();
    }

    public function convert(string $path, int $quality): ?string
    {
        $this->logger->log("Starting WebP conversion for $path with quality $quality");
        return $this->convertImage($path, $quality, 'webp', 'imagewebp');
    }

    private function convertImage(string $path, int $quality, string $format, callable $convertFunction): ?string
    {
        $output_path = dirname($path);
        $filename_with_extension = basename($path);
        $extension = pathinfo($filename_with_extension, PATHINFO_EXTENSION);
        $filename = basename($filename_with_extension, '.' . $extension);

        $image = $this->createImageResource($path, $extension);

        if (!$image) {
            $this->logger->log("Failed to create image resource for $path");
            return null;
        }

        $convertFunction($image, $output_path . '/' . $filename . '.' . $format, $quality);
        imagedestroy($image);

        $upload_subdir = implode('/', array_slice(explode('/', dirname($path)), -2));
        $convertedPath = $upload_subdir . '/' . $filename . '.' . $format;
        $this->logger->log("Successfully converted $path to $convertedPath");
        return $convertedPath;
    }

    private function createImageResource($path, $extension)
    {
        switch (strtolower($extension)) {
            case 'jpeg':
            case 'jpg':
                return imagecreatefromjpeg($path);
            case 'png':
                return imagecreatefrompng($path);
            case 'gif':
                return imagecreatefromgif($path);
            default:
                return null;
        }
    }
}