<?php

namespace WebpAvifConverter;

class ImageDeleter implements ImageDeleterInterface
{
    private $logger;

    public function __construct()
    {
        $this->logger = new Logger();
    }

    public function delete(string $path): void
    {
        $this->safeUnlink($path);
    }

    public function deleteAttachmentFiles($attachment_id): bool
    {
        $metadata = wp_get_attachment_metadata($attachment_id);
        $upload_dir = wp_upload_dir()['basedir'];
        $deleted = false;

        if (array_key_exists('file_avif', $metadata) && $metadata['file_avif']) {
            $this->safeUnlink($upload_dir . '/' . $metadata['file_avif']);
            $deleted = true;
        }

        if (array_key_exists('file_webp', $metadata) && $metadata['file_webp']) {
            $this->safeUnlink($upload_dir . '/' . $metadata['file_webp']);
            $deleted = true;
        }

        $attachment_subdir = dirname($metadata['file']);
        $attachment_files_dir = $upload_dir . '/' . $attachment_subdir . '/';

        if (array_key_exists('sizes_avif', $metadata)) {
            foreach ($metadata['sizes_avif'] as $size) {
                $this->safeUnlink($attachment_files_dir . $size['file']);
                $deleted = true;
            }
        }

        if (array_key_exists('sizes_webp', $metadata)) {
            foreach ($metadata['sizes_webp'] as $size) {
                $this->safeUnlink($attachment_files_dir . $size['file']);
                $deleted = true;
            }
        }

        return $deleted;
    }

    public function deleteAllAvifAndWebpFiles(): bool
    {
        $upload_dir = wp_upload_dir()['basedir'];
        $deleted = $this->scanAndDelete($upload_dir);
        return $deleted;
    }

    private function scanAndDelete($directory): bool
    {
        $deleted = false;
        $items = scandir($directory);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $deleted = $this->scanAndDelete($path) || $deleted;
            } else {
                $extension = pathinfo($path, PATHINFO_EXTENSION);
                if ($extension === 'avif' || $extension === 'webp') {
                    $this->safeUnlink($path);
                    $deleted = true;
                }
            }
        }

        return $deleted;
    }

    private function safeUnlink($path)
    {
        if (file_exists($path)) {
            unlink($path);
            $this->logger->log("Deleted file: $path");
        } else {
            $this->logger->log("File not found: $path");
        }
    }
}
