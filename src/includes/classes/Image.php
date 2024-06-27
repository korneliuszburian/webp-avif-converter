<?php

namespace WebpAvifConverter;

class ImageService
{
    private $webpConverter;
    private $avifConverter;
    private $fileDeleter;

    public function __construct(
        ImageConverterInterface $webpConverter,
        ImageConverterInterface $avifConverter,
        ImageDeleterInterface $fileDeleter
    ) {
        $this->webpConverter = $webpConverter;
        $this->avifConverter = $avifConverter;
        $this->fileDeleter = $fileDeleter;
    }

    public function updateAllAttachmentsWebpAvifQuality($quality_webp, $quality_avif)
    {
        $attachments = get_posts([
            'post_type' => 'attachment',
            'numberposts' => -1,
            'post_status' => null,
            'post_parent' => null,
            'exclude' => get_post_thumbnail_id()
        ]);

        $total_attachments = count($attachments);
        $progress = 0;

        $this->updateProgressBar(0);

        foreach ($attachments as $attachment) {
            $percent = intval($progress / $total_attachments * 100);
            $this->updateProgressBar($percent);

            $path = get_attached_file($attachment->ID);
            if (!file_exists($path . '.webp')) {
                $this->webpConverter->convert($path, $quality_webp);
            }
            if (!file_exists($path . '.avif')) {
                $this->avifConverter->convert($path, $quality_avif);
            }

            $metadata = wp_get_attachment_metadata($attachment->ID);
            if (!empty($metadata['sizes']) && is_array($metadata['sizes'])) {
                foreach ($metadata['sizes'] as $key => $size) {
                    $filename = pathinfo($size['file'], PATHINFO_FILENAME);
                    $thumbnail_path = dirname($path) . '/' . $size['file'];

                    if (!file_exists($thumbnail_path . '.webp')) {
                        $this->webpConverter->convert($thumbnail_path, $quality_webp);
                        $metadata['sizes_webp'][$key]['file'] = $filename . '.webp';
                    }

                    if (!file_exists($thumbnail_path . '.avif')) {
                        $this->avifConverter->convert($thumbnail_path, $quality_avif);
                        $metadata['sizes_avif'][$key]['file'] = $filename . '.avif';
                    }
                }
                wp_update_attachment_metadata($attachment->ID, $metadata);
            }

            $progress++;
        }

        $this->updateProgressBar(100);
    }

    public function deleteAllAttachmentsAvifAndWebp()
    {
        $attachments = get_posts([
            'post_type' => 'attachment',
            'numberposts' => -1,
            'post_status' => null,
            'post_parent' => null,
            'exclude' => get_post_thumbnail_id()
        ]);

        $deleted = false;
        foreach ($attachments as $attachment) {
            $deleted = $this->fileDeleter->deleteAttachmentAvifAndWebp($attachment->ID) || $deleted;
        }

        return $deleted;
    }

    private function updateProgressBar($percent)
    {
        $percent = strval($percent);
        echo "<script>
                var progressBar = document.getElementById('progress-bar');
                progressBar.style.width = '$percent%';
                progressBar.innerHTML = '$percent%';
            </script>";
        ob_flush();
        flush();
    }
}
