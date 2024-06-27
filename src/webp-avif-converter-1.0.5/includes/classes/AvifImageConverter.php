<?php

namespace WebpAvifConverter;

class AvifImageConverter implements ImageConverterInterface
{
    private $logger;

    public function __construct()
    {
        $this->logger = new Logger();
    }

    public function convert(string $path, int $quality): ?string
    {
        $this->logger->log("Starting AVIF conversion for $path with quality $quality");
        if (function_exists('imageavif')) {
            return $this->convertImage($path, $quality, 'avif', 'imageavif');
        } else {
            return $this->convertImageUsingFallback($path, $quality);
        }
    }

    private function convertImage(string $path, int $quality, string $format, callable $convertFunction): ?string
    {
        $output_path = dirname($path);
        $filename_with_extension = basename($path);
        $extension = pathinfo($filename_with_extension, PATHINFO_EXTENSION);
        $filename = substr($filename_with_extension, 0, -(strlen($extension) + 1));

        $this->logger->log("Parsing filename: $filename_with_extension, extension: $extension, base filename: $filename");

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

    private function convertImageUsingFallback(string $path, int $quality): ?string
    {
        $this->logger->log("Using fallback conversion for $path");
        $output_path = dirname($path);
        $filename_with_extension = basename($path);
        $extension = pathinfo($filename_with_extension, PATHINFO_EXTENSION);
        $filename = substr($filename_with_extension, 0, -(strlen($extension) + 1));
        $destination = $output_path . '/' . $filename . '.avif';

        $this->logger->log("Fallback conversion details - output path: $output_path, filename: $filename, destination: $destination");

        if (class_exists('Imagick')) {
            try {
                \IMagick::setResourceLimit(\IMagick::RESOURCETYPE_THREAD, 1);
                $imagick = new \IMagick($path);
                $imagick->setImageFormat('avif');
                $imagick->setImageCompressionQuality($quality);
                $imagick->writeImage($destination);
                $imagick->clear();

                $upload_subdir = implode('/', array_slice(explode('/', dirname($path)), -2));
                $this->logger->log("Successfully converted $path to $destination using Imagick");
                return $upload_subdir . '/' . $filename . '.avif';
            } catch (\Exception $e) {
                $this->logger->log("Imagick conversion failed for $path: " . $e->getMessage());
                return null;
            }
        } else {
            $this->logger->log("Imagick is not available for fallback conversion of $path");
            return null;
        }
    }

    private function createImageResource($path, $extension)
    {
        switch ($extension) {
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
