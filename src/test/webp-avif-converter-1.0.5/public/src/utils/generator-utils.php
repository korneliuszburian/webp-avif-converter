<?php

namespace WebpAvifConverter\GeneratorUtils;

function p($a, $die = 0) {
    echo '<pre>';
    echo print_r($a);
    echo '</pre>';
    if ($die) { wp_die(); }
}

function log_message($message) {
    $log_file = __DIR__ . '/conversion.log';
    $timestamp = date("Y-m-d H:i:s");
    $log_entry = "[$timestamp] $message\n";

    if (!file_put_contents($log_file, $log_entry, FILE_APPEND)) {
        error_log("Failed to write to log file: $log_file");
        error_log($log_entry);
    }
}

function generate_jpeg(string $path, int $quality = 80) {
    log_message("Starting JPEG generation for: $path");

    $output_path = dirname($path);
    $extension = pathinfo($path, PATHINFO_EXTENSION);
    $filename = basename($path);

    $image = null;
    if ($extension === 'jpeg' || $extension === 'jpg') {
        $image = imagecreatefromjpeg($path);
    } elseif ($extension === 'png') {
        $image = imagecreatefrompng($path);
    } elseif ($extension === 'gif') {
        $image = imagecreatefromgif($path);
    } else {
        log_message("Unsupported format: $path");
        return;
    }

    if (!$image) {
        log_message("Unable to create image resource: $path");
        return;
    }

    imagejpeg($image, $output_path . '/' . $filename, $quality);
    imagedestroy($image);

    $directories = explode('/', dirname($path));
    $upload_subdir = implode('/', array_slice($directories, -2));
    log_message("JPEG generated: $path");
    return $upload_subdir . '/' . $filename;
}

function generate_webp(string $path, int $quality = 80) {
    log_message("Starting WebP generation for: $path");

    $output_path = dirname($path);
    $extension = pathinfo($path, PATHINFO_EXTENSION);
    $filename = basename($path, '.' . $extension);

    $image = null;
    if ($extension === 'jpeg' || $extension === 'jpg') {
        $image = imagecreatefromjpeg($path);
    } elseif ($extension === 'png') {
        $image = imagecreatefrompng($path);
    } elseif ($extension === 'gif') {
        $image = imagecreatefromgif($path);
    } else {
        log_message("Unsupported format: $path");
        return; // Unsupported format.
    }

    if (!$image) {
        log_message("Unable to create image resource: $path");
        return; // Unable to create image resource.
    }

    if (!imageistruecolor($image)) {
        imagepalettetotruecolor($image);
    }

    imagewebp($image, $output_path . '/' . $filename . '.webp', $quality);
    imagedestroy($image);

    $directories = explode('/', dirname($path));
    $upload_subdir = implode('/', array_slice($directories, -2));
    log_message("WebP generated: $path");
    return $upload_subdir . '/' . $filename . '.webp';
}

/**
 * Convert image to AVIF format
 *
 * @param string $path
 * @param int $quality
 * @return string|void
 */
function generate_avif(string $path, int $quality = 80) {
    log_message("Starting AVIF generation for: $path");

    $output_path = dirname($path);
    $extension = pathinfo($path, PATHINFO_EXTENSION);
    $filename = basename($path, '.' . $extension);

    $image = null;
    if ($extension === 'jpeg' || $extension === 'jpg') {
        $image = imagecreatefromjpeg($path);
    } elseif ($extension === 'png') {
        $image = imagecreatefrompng($path);
    } elseif ($extension === 'gif') {
        $image = imagecreatefromgif($path);
    } else {
        log_message("Unsupported format: $path");
        return;
    }

    if (!$image) {
        log_message("Unable to create image resource: $path");
        return;
    }

    if (!imageistruecolor($image)) {
        imagepalettetotruecolor($image);
    }

    if (function_exists('imageavif')) {
        if (imageavif($image, $output_path . '/' . $filename . '.avif', $quality)) {
            log_message("AVIF generated using GD: $path");
        } else {
            log_message("Failed to generate AVIF using GD: $path");
        }
    } else {
        try {
            $imagick = new \Imagick();
            $imagick->readImage($path);
            $imagick->setImageFormat('avif');
            $imagick->setImageCompressionQuality($quality);
            $imagick->writeImage($output_path . '/' . $filename . '.avif');
            $imagick->clear();
            $imagick->destroy();
            log_message("AVIF generated using Imagick: $path");
        } catch (\Exception $e) {
            log_message("Imagick error: " . $e->getMessage());
            return;
        }
    }

    imagedestroy($image);

    $directories = explode('/', dirname($path));
    $upload_subdir = implode('/', array_slice($directories, -2));
    log_message("AVIF generated: $path");
    return $upload_subdir . '/' . $filename . '.avif';
}

function generate_jpeg_sizes($attachment_id, $quality) {
    log_message("Starting JPEG size generation for attachment ID: $attachment_id");

    $metadata = wp_get_attachment_metadata($attachment_id);
    if (empty($metadata)) {
        log_message("No metadata found for attachment ID: $attachment_id");
        return;
    }

    $original_image_path = wp_get_upload_dir()['basedir'] . '/' . $metadata['file'];
    $image_subdir_path = wp_get_upload_dir()['basedir'] . '/' . dirname($metadata['file']);

    $metadata['quality_jpeg'] = $quality;

    if (pathinfo($original_image_path, PATHINFO_EXTENSION) === 'svg') {
        return;
    }

    foreach ($metadata['sizes'] as $size_name => $size) {
        $subsize_path = $image_subdir_path . '/' . $size['file'];
        $image = wp_get_image_editor($subsize_path);
        $image->set_quality($quality);
        $saved = $image->save($subsize_path);

        if (is_wp_error($saved)) {
            log_message("Error generating JPEG size for $subsize_path: " . $saved->get_error_message());
        } else {
            $metadata['sizes'][$size_name]['filesize'] = filesize($subsize_path);
        }
    }

    wp_update_attachment_metadata($attachment_id, $metadata);
    log_message("JPEG sizes generated for attachment ID: $attachment_id");
}

function generate_webp_sizes($attachment_id, $quality) {
    log_message("Starting WebP size generation for attachment ID: $attachment_id");

    $metadata = wp_get_attachment_metadata($attachment_id);
    if (empty($metadata)) {
        log_message("No metadata found for attachment ID: $attachment_id");
        return;
    }

    $image_subdir_path = wp_get_upload_dir()['basedir'] . '/' . dirname($metadata['file']);

    $metadata['quality_webp'] = $quality;
    $metadata['sizes_webp'] = [];

    $metadata['file_webp'] = generate_webp(
        wp_get_upload_dir()['basedir'] . '/' . $metadata['file'],
        $quality
    );
    $metadata['webp_filesize'] = filesize(wp_get_upload_dir()['basedir'] . '/' . $metadata['file_webp']);

    $original_image_path = wp_get_upload_dir()['basedir'] . '/' . $metadata['file'];

    if (pathinfo($original_image_path, PATHINFO_EXTENSION) === 'svg') {
        return;
    }

    foreach ($metadata['sizes'] as $size_name => $size) {
        $original_subsize_path = $image_subdir_path . '/' . $size['file'];

        $subsize_subpath = generate_webp($original_subsize_path, $quality);

        if ($subsize_subpath) {
            $metadata['sizes_webp'][$size_name] = [
                'file' => basename($subsize_subpath),
                'width' => $size['width'],
                'height' => $size['height'],
                'mime-type' => 'image/webp',
                'filesize' => filesize(wp_get_upload_dir()['basedir'] . '/' . $subsize_subpath)
            ];
        } else {
            log_message("Failed to generate WebP size for $original_subsize_path");
        }
    }

    wp_update_attachment_metadata($attachment_id, $metadata);
    log_message("WebP sizes generated for attachment ID: $attachment_id");
}

function generate_avif_sizes($attachment_id, $quality) {
    log_message("Starting AVIF size generation for attachment ID: $attachment_id");

    $metadata = wp_get_attachment_metadata($attachment_id);
    if (empty($metadata)) {
        log_message("No metadata found for attachment ID: $attachment_id");
        return;
    }

    $image_subdir_path = wp_get_upload_dir()['basedir'] . '/' . dirname($metadata['file']);

    $metadata['quality_avif'] = $quality;
    $metadata['sizes_avif'] = [];

    $metadata['file_avif'] = generate_avif(
        wp_get_upload_dir()['basedir'] . '/' . $metadata['file'],
        $quality
    );
    $metadata['avif_filesize'] = filesize(wp_get_upload_dir()['basedir'] . '/' . $metadata['file_avif']);

    $original_image_path = wp_get_upload_dir()['basedir'] . '/' . $metadata['file'];

    if (pathinfo($original_image_path, PATHINFO_EXTENSION) === 'svg') {
        return;
    }

    foreach ($metadata['sizes'] as $size_name => $size) {
        $original_subsize_path = $image_subdir_path . '/' . $size['file'];

        $subsize_subpath = generate_avif($original_subsize_path, $quality);

        if ($subsize_subpath) {
            $metadata['sizes_avif'][$size_name] = [
                'file' => basename($subsize_subpath),
                'width' => $size['width'],
                'height' => $size['height'],
                'mime-type' => 'image/avif',
                'filesize' => filesize(wp_get_upload_dir()['basedir'] . '/' . $subsize_subpath)
            ];
        } else {
            log_message("Failed to generate AVIF size for $original_subsize_path");
        }
    }

    wp_update_attachment_metadata($attachment_id, $metadata);
    log_message("AVIF sizes generated for attachment ID: $attachment_id");
}
