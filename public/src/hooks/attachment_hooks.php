<?php

namespace WebAvifConverter\Hooks;

use WebAvifConverter\Utils;
use WebAvifConverter\GeneratorUtils;

// function p($a, $die = 0){
	// echo '<pre>';
	// echo print_r($a);
	// echo '</pre>';
	// if ($die) { wp_die(); }
// }


apply_filters('jepg_quality', get_option('wac_quality_jpeg', DEFAULT_QUALITY));

add_filter('wp_generate_attachment_metadata', 'WebAvifConverter\Hooks\convert_images_on_generate_attachment_metadata', 10, 2);
function convert_images_on_generate_attachment_metadata($metadata, $attachment_id)
{
	if (!isset($metadata['file'])) { return $metadata; }

	$extension = pathinfo($metadata['file'], PATHINFO_EXTENSION);
	if (!in_array($extension, ['jpg', 'jpeg', 'png'])) { return $metadata; }
	
	$metadata['quality_jpeg'] = get_option('wac_quality_jpeg', DEFAULT_QUALITY);

	wp_update_attachment_metadata($attachment_id, $metadata);

	// Generating JPEGs with adjusted quality is handled by jepg_quality filter
	GeneratorUtils\generate_webp_sizes($attachment_id, get_option('wac_quality_webp', DEFAULT_QUALITY));
	GeneratorUtils\generate_avif_sizes($attachment_id, get_option('wac_quality_avif', DEFAULT_QUALITY));

	return wp_get_attachment_metadata($attachment_id);
}



add_action('edit_attachment', 'WebAvifConverter\Hooks\save_image_quality_metadata');
function save_image_quality_metadata($attachment_id)
{	
	$metadata = wp_get_attachment_metadata($attachment_id);

	$filed_names = ['quality_jpeg', 'quality_avif', 'quality_webp' ];

	foreach ($filed_names as $filed_name) {
		if (empty($_REQUEST['attachments'][$attachment_id][$filed_name])) { continue; }

		$quality = intval($_REQUEST['attachments'][$attachment_id][$filed_name]);

		if ($quality < 1 || $quality > 100 || isset($metadata[$filed_name]) && $quality === $metadata[$filed_name]) { continue; }

		if ($filed_name === 'quality_jpeg') { GeneratorUtils\generate_jpeg_sizes($attachment_id, $quality); }
		if ($filed_name === 'quality_webp') { GeneratorUtils\generate_webp_sizes($attachment_id, $quality); }
		if ($filed_name === 'quality_avif') { GeneratorUtils\generate_avif_sizes($attachment_id, $quality); }
	}	
}

function update_attachment_quality($attachment_id, $quality_webp = DEFAULT_QUALITY, $quality_avif = DEFAULT_QUALITY, $quality_jpeg = DEFAULT_QUALITY)
{
	$metadata = wp_get_attachment_metadata($attachment_id);

	if (empty($metadata) || !isset($metadata['file']) || !isset($metadata['sizes'])) {
		return; // No metadata found for this attachment.
	}

	if(!isset($metadata['quality_jpeg']) || intval($metadata['quality_jpeg']) !== $quality_jpeg){
		
		GeneratorUtils\generate_jpeg_sizes($attachment_id, $quality_jpeg);
	}

	if(!isset($metadata['quality_webp']) || intval($metadata['quality_webp']) !== $quality_webp){
		

		
		GeneratorUtils\generate_webp_sizes($attachment_id, $quality_webp);
	}
	
	if(!isset($metadata['quality_avif']) || intval($metadata['quality_avif']) !== $quality_avif){
		GeneratorUtils\generate_avif_sizes($attachment_id, $quality_avif);
	}
}

function update_all_attachments_quality($quality_webp, $quality_avif, $quality_jpeg)
{
	Utils\update_progress_bar(0);

	$attachments = get_posts(array(
		'post_type' => 'attachment',
		'numberposts' => -1,
		'post_status' => null,
		'exclude' => get_post_thumbnail_id()
	));

	$total_attachments = count($attachments);
	$progress = 0;
	
	foreach ($attachments as $attachment) {
		$progress++;
		$percent = intval($progress / $total_attachments * 100);
		Utils\update_progress_bar($percent);
		update_attachment_quality($attachment->ID, $quality_webp, $quality_avif, $quality_jpeg);
	}
}

add_filter('delete_attachment', 'WebAvifConverter\Hooks\delete_attachment_files');
function delete_attachment_files($attachment_id)
{
	$metadata = wp_get_attachment_metadata($attachment_id);
	if (empty($metadata)) { return; }

	$upload_dir = wp_upload_dir()['basedir'];

	if (array_key_exists('file_webp', $metadata)) {
		Utils\safe_unlink($upload_dir . '/' . $metadata['file_webp']);
		unset($metadata['file_webp']);
	}

	if (array_key_exists('file_avif', $metadata)) {
		Utils\safe_unlink($upload_dir . '/' . $metadata['file_avif']);
		unset($metadata['file_avif']);
	}

	$attachment_subdir = dirname($metadata['file']);
	$attachment_files_dir = $upload_dir . '/' . $attachment_subdir . '/';

	if (array_key_exists('sizes_avif', $metadata)) {
		foreach ($metadata['sizes_avif'] as $size) {
			Utils\safe_unlink($attachment_files_dir . $size['file']);
		}
		unset($metadata['sizes_avif']);
	}
	
	if (array_key_exists('quality_webp', $metadata)){
		unset($metadata['quality_webp']);
	}
	
	if (array_key_exists('quality_avif', $metadata)){


		unset($metadata['quality_avif']);
	}

	if (array_key_exists('sizes_webp', $metadata)) {
		foreach ($metadata['sizes_webp'] as $size) {
			Utils\safe_unlink($attachment_files_dir . $size['file']);
		}
		unset($metadata['sizes_webp']);
	}wp_update_attachment_metadata($attachment_id, $metadata);
}

function delete_all_attachments_avif_and_webp()
{
	Utils\update_progress_bar(0);

	$attachments = get_posts(array(
		'post_type' => 'attachment',
		'numberposts' => -1,
		'post_mime_type' => 'image',
		'exclude' => get_post_thumbnail_id()
	));

	$total_attachments = count($attachments);
	$progress = 0;

	foreach ($attachments as $attachment) {
		delete_attachment_files($attachment->ID);		
		$progress++;
		$percent = intval($progress / $total_attachments * 100) . "%";
		Utils\update_progress_bar($percent);
	}
}