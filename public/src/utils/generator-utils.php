<?php

namespace WebAvifConverter\GeneratorUtils;

/**
 * Convert image to WebP format
 *
 * @param string $path Path to the image file.
 * @param int $quality Image quality (0 to 100, default is 80).
 *
 * @return string|void Returns the relative path to the converted WebP image or void if unable to create an image resource.
 */

 function p($a, $die = 0){
	echo '<pre>';
	echo print_r($a);
	echo '</pre>';
	if ($die) { wp_die(); }
}

function generate_jpeg(string $path, int $quality = 80){
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
        return; // Unsupported format.
    }

    if (!$image) {
        return; // Unable to create image resource, possibly unsupported format.
    }

    imagejpeg($image, $output_path . '/' . $filename , $quality);
    imagedestroy($image);

    $directories = explode('/', dirname($path));
    $upload_subdir = implode('/', array_slice($directories, -2));
    return $upload_subdir . '/' . $filename;
}

function generate_webp(string $path, int $quality = 80)
{
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
    }

    if (!$image) {
        return; // Unable to create image resource, possibly unsupported format.
    }

    if(!imageistruecolor($image)){
        imagepalettetotruecolor($image);
    }
    
    imagewebp($image, $output_path . '/' . $filename . '.webp', $quality);
    imagedestroy($image);

    $directories = explode('/', dirname($path));
    $upload_subdir = implode('/', array_slice($directories, -2));
    return $upload_subdir . '/' . $filename . '.webp';
}

/**
 * Convert image to AVIF format
 *
 * @param string $path Path to the image file.
 * @param int $quality Image quality (0 to 100, default is 80).
 *
 * @return string|void Returns the relative path to the converted AVIF image or void if unable to create an image resource.
 */
function generate_avif(string $path, int $quality = 80)
{
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
        return; // Unsupported format.
    }

    if (!$image) {
        return; // Unable to create image resource, possibly unsupported format.
    }

    if(!imageistruecolor($image)){
        imagepalettetotruecolor($image);
    }

    imageavif($image, $output_path . '/' . $filename . '.avif', $quality);
    imagedestroy($image);

    $directories = explode('/', dirname($path));
    $upload_subdir = implode('/', array_slice($directories, -2));
    return $upload_subdir . '/' . $filename . '.avif';
}



function generate_jpeg_sizes($attachment_id, $quality){

	$metadata = wp_get_attachment_metadata($attachment_id);
	if (empty($metadata)) { return; }
	
	$original_image_path = wp_get_upload_dir()['basedir'] . '/' . $metadata['file'];
	$image_subdir_path = wp_get_upload_dir()['basedir'] . '/' . dirname($metadata['file']);

	$metadata['quality_jpeg'] = $quality;

    // skip svg files path info
    if (pathinfo($original_image_path, PATHINFO_EXTENSION) === 'svg') {
        return;
    }

	// p($metadata['sizes'], 1);
	foreach($metadata['sizes'] as $size_name => $size){
        $subsize_path  = $image_subdir_path . '/' . $size['file'];
        $image = wp_get_image_editor($subsize_path);
        $image->set_quality( $quality );
        $saved = $image->save($subsize_path);

		$metadata['sizes'][$size_name]['filesize'] = filesize($subsize_path);
	}

	wp_update_attachment_metadata($attachment_id, $metadata);
};


function generate_webp_sizes($attachment_id, $quality){
	$metadata = wp_get_attachment_metadata($attachment_id);
	if (empty($metadata)) { return; }
	
	$image_subdir_path = wp_get_upload_dir()['basedir'] . '/' . dirname($metadata['file']);

	$metadata['quality_webp'] = $quality;
	$metadata['sizes_webp'] = [];

	$metadata['file_webp'] = generate_webp(
		wp_get_upload_dir()['basedir'] . '/' . $metadata['file'],
		$quality
	);
	$metadata['webp_filesize'] = filesize(wp_get_upload_dir()['basedir'] . '/'. $metadata['file_webp']);

    $original_image_path = wp_get_upload_dir()['basedir'] . '/' . $metadata['file'];

    // skip svg files path info
    if (pathinfo($original_image_path, PATHINFO_EXTENSION) === 'svg') {
        return;
    }

	foreach($metadata['sizes'] as $size_name => $size){
		$original_subsize_path = $image_subdir_path . '/' . $size['file'];
		
		//create or overwrite the subsize image
		$subsize_subpath = generate_webp($original_subsize_path, $quality);

		$metadata['sizes_webp'][$size_name] = [
			'file' => basename($subsize_subpath),
			'width' => $size['width'],
			'height' => $size['height'],
			'mime-type' => 'image/webp',
			'filesize' => filesize(wp_get_upload_dir()['basedir'] . '/' . $subsize_subpath)
		];
	}

	wp_update_attachment_metadata($attachment_id, $metadata);
};


function generate_avif_sizes($attachment_id, $quality){
	$metadata = wp_get_attachment_metadata($attachment_id);
	if (empty($metadata)) { return; }
	
	$image_subdir_path = wp_get_upload_dir()['basedir'] . '/' . dirname($metadata['file']);

	$metadata['quality_avif'] = $quality;
	$metadata['sizes_avif'] = [];

	$metadata['file_avif'] = generate_avif(
		wp_get_upload_dir()['basedir'] . '/' . $metadata['file'],
		$quality
	);
	$metadata['avif_filesize'] = filesize(wp_get_upload_dir()['basedir'] . '/'. $metadata['file_avif']);

    $original_image_path = wp_get_upload_dir()['basedir'] . '/' . $metadata['file'];

    // skip svg files path info
    if (pathinfo($original_image_path, PATHINFO_EXTENSION) === 'svg') {
        return;
    }

	foreach($metadata['sizes'] as $size_name => $size){
		$original_subsize_path = $image_subdir_path . '/' . $size['file'];

		//create or overwrite the subsize image
		$subsize_subpath = generate_avif($original_subsize_path, $quality);

		$metadata['sizes_avif'][$size_name] = [
			'file' => basename($subsize_subpath),
			'width' => $size['width'],
			'height' => $size['height'],
			'mime-type' => 'image/avif',
			'filesize' => filesize(wp_get_upload_dir()['basedir'] . '/' . $subsize_subpath)
		];
	}

	wp_update_attachment_metadata($attachment_id, $metadata);
};
