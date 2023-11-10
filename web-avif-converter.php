<?php

/**
 *
 * @link              https://rekurencja.com/
 * @since             1.0.0
 * @package           Web_Avif_Converter
 *
 * @wordpress-plugin
 * Plugin Name:       WebP & Avif Converter
 * Plugin URI:        https://rekurencja.com/
 * Description:       Fast and simple plugin to automatically convert and serve WebP & AVIF images.
 * Version:           1.0.1
 * Author:            Rekurencja.com
 * Author URI:        https://rekurencja.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       web-avif-converter
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

define('PHP_REQUIRED_VERSION', '8.1.0');
define('PHP_VERSION_OK', version_compare(phpversion(), PHP_REQUIRED_VERSION, '>='));
define('DEFAULT_QUALITY', 82);

require plugin_dir_path(__FILE__) . 'public/src/utils/utils.php';
require plugin_dir_path(__FILE__) . 'public/src/hooks/activation_hooks.php';
require plugin_dir_path(__FILE__) . 'public/src/hooks/attachment_hooks.php';
require plugin_dir_path(__FILE__) . 'public/src/utils/generator-utils.php';
require plugin_dir_path(__FILE__) . 'public/src/exceptions/exceptions.php';


if (is_admin()) {
	require plugin_dir_path(__FILE__) . 'public/src/admin.php';
}

register_activation_hook(__FILE__, 'WebAvifConverter\Hooks\activate_web_avif_converter');
register_deactivation_hook(__FILE__, 'WebAvifConverter\Hooks\deactivate_web_avif_converter');

add_filter('attachment_fields_to_edit', 'add_image_quality_field', 10, 2);
add_filter('manage_media_columns', 'add_image_quality_column');
add_action('manage_media_custom_column', 'display_image_quality_column', 10, 2);
add_filter('jpeg_quality', 'get_jpeg_quality');

use WebAvifConverter\Hooks;
use WebAvifConverter\Utils;

function add_image_quality_column($columns)
{
	$columns['quality_webp'] = 'WebP Quality';
	$columns['quality_avif'] = 'Avif Quality';
	$columns['quality_jpeg'] = 'Jpeg Quality';

	return $columns;
}

function get_jpeg_quality($quality)
{
	return get_option('wac_quality_jpeg', 82);
}

function add_image_quality_field($form_fields, $post)
{
	$form_fields['quality_webp'] = generate_quality_field($post->ID, 'quality_webp', 'Quality of WEBP');
	$form_fields['quality_avif'] = generate_quality_field($post->ID, 'quality_avif', 'Quality of AVIF');
	$form_fields['quality_jpeg'] = generate_quality_field($post->ID, 'quality_jpeg', 'Quality of JPEG');
	return $form_fields;
}

function determine_image_extension($image_url)
{
    $extension = pathinfo($image_url, PATHINFO_EXTENSION);
    return strtolower($extension);
}

function generate_quality_field($post_id, $field_name, $field_label)
{
	// get post metadata
	$metadata = wp_get_attachment_metadata($post_id);
	// echo $post_id;
	// wp_die(print_r($metadata));
	if (isset(wp_get_attachment_metadata($post_id)[$field_name])) {
		$field_value = wp_get_attachment_metadata($post_id)[$field_name];
		$placeholder_value = $field_value;
	} else {
		$field_value = '';
		$placeholder_value = 'Quality is not set';
	}

	$input_html = "<input type='number' min='0' max='100' step='1' ";
	$input_html .= "name='attachments[$post_id][$field_name]' ";
	$input_html .= "value='" . esc_attr($field_value ? $field_value : '') . "' ";
	$input_html .= "placeholder='" . esc_attr($placeholder_value) . "'/>";

	$field = array(
		'label' => $field_label,
		'input' => 'html',
		'helps' => 'Enter a value between 1 and 100 for image quality (100 = best quality, 1 = worst quality)',
		'html' => $input_html,
	);

	return $field;
}


function display_image_quality_column($column_name, $attachment_id)
{
	$allowed_columns = ['quality_avif', 'quality_webp', 'quality_jpeg'];

	if (in_array($column_name, $allowed_columns)) {
		$attachment_metadata = wp_get_attachment_metadata($attachment_id);

		if (isset($attachment_metadata[$column_name])) {
			echo $attachment_metadata[$column_name];
		} else {
			echo 'N/A';
		}
	}
}

add_action('admin_menu', 'webp_avif_bulk_convert_menu');
function webp_avif_bulk_convert_menu()
{
	add_submenu_page(
		'options-general.php',
		'WebP & Avif Converter',
		'WebP & Avif Converter',
		'manage_options',
		'webp_avif_bulk_convert',
		'webp_avif_bulk_convert_page'
	);
}

function get_images_size_info()
{
    // loop through all images
    $images = get_posts(
        array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'numberposts' => -1,
        )
    );

    $images_size_info = [
        "total_original_size" => 0,
        "total_avif_size" => 0,
        "total_webp_size" => 0,
    ];

    echo "<div class='summary-wrapper'>";

    foreach ($images as $image) {
        try {
            // Determine the extension
            $extension = determine_image_extension($image->guid);

            // Handle unsupported extensions
            if ($extension === 'svg' || $extension === 'gif') {
                throw new FilesizeUnavailableException($extension, 'Unsupported extension: ' . $extension);
            }

            $metadata = wp_get_attachment_metadata($image->ID);

            $cur_image_size_info = [
                "original_size" => isset($metadata['filesize']) ? $metadata['filesize'] : 0,
                "avif_size" => 0,
                "webp_size" => 0,
            ];

            if (isset($metadata['sizes'])) {
                foreach ($metadata['sizes'] as $size_name => $size) {
                    $cur_image_size_info["original_size"] += isset($size['filesize']) ? $size['filesize'] : 0;
                }
            }

            if (isset($metadata['sizes_avif'])) {
                foreach ($metadata['sizes_avif'] as $size_name => $size) {
                    $cur_image_size_info["avif_size"] += isset($size['filesize']) ? $size['filesize'] : 0;
                }
            }

            if (isset($metadata['sizes_webp'])) {
                foreach ($metadata['sizes_webp'] as $size_name => $size) {
                    $cur_image_size_info["webp_size"] += isset($size['filesize']) ? $size['filesize'] : 0;
                }
            }

            if (!isset($metadata['sizes'])) {
                throw new FilesizeUnavailableException('Unknown', 'Filesize information not available for this file.');
            }

            $images_size_info['total_original_size'] += $cur_image_size_info['original_size'];
            $images_size_info['total_avif_size'] += $cur_image_size_info['avif_size'];
            $images_size_info['total_webp_size'] += $cur_image_size_info['webp_size'];

            echo "<div class='summary-item'>";
            echo "<img src='" . $image->guid . "' alt='Image ID: " . $image->ID . "' width='150px'>";
            echo "<h4>Image ID: " . $image->ID . "</h4>";
            echo "<p>Image Title: <b>" . $image->post_title . "</b></p>";
            echo "<p>Original size: " . size_format($cur_image_size_info['original_size']) . " </p>";
            echo "<p>Avif size: " . size_format($cur_image_size_info['avif_size']) . "</p>";
            echo "<p>Webp size: " . size_format($cur_image_size_info['webp_size']) . "</p>";
            echo "</div>";
        } catch (FilesizeUnavailableException $e) {
            // log error
            error_log($e->getMessage());
        } catch (Exception $e) {
            // log error
            error_log($e->getMessage());
        }
    }
    
    echo "</div>";
    echo "<div class='total-summary-item'>";
    echo "<h4>Total Summary:</h4>";
    echo "<p>Total Original Image Size: " . size_format($images_size_info['total_original_size']) . "</p>";
    echo "<p>Total AVIF Image Size: " . size_format($images_size_info['total_avif_size']) . "</p>";
    echo "<p>Total WebP Image Size: " . size_format($images_size_info['total_webp_size']) . "</p>";
    echo "</div>";
    return $images_size_info;
}

function webp_avif_bulk_convert_page()
{
	if (isset($_POST['submit'])) {

		$quality_webp = isset($_POST['quality_webp']) ? intval($_POST['quality_webp']) : DEFAULT_QUALITY;
		$quality_avif = isset($_POST['quality_avif']) ? intval($_POST['quality_avif']) : DEFAULT_QUALITY;
		$quality_jpeg = isset($_POST['quality_jpeg']) ? intval($_POST['quality_jpeg']) : DEFAULT_QUALITY;

		if ($_POST['submit'] === 'Set') {
			echo '<div class="success alert conversion-wrapper" id="alert" [class.visible]="isVisible">';
			echo '<div class="content">';
			update_option('wac_quality_webp', $quality_webp);
			update_option('wac_quality_avif', $quality_avif);
			update_option('wac_quality_jpeg', $quality_jpeg);

			if (isset($_POST['regenerate']) && $_POST['regenerate'] === 'on') {
				echo '<span class="closebtn icon" onclick="this.parentElement.style.display=\'none\';"></span>';
				echo '<div class="progress-bar" id="progress-bar" style="width: 0%">0%;
				</div>';
				Hooks\update_all_attachments_quality($quality_webp, $quality_avif, $quality_jpeg);

				echo '<p class="notification notification-good">
                    All images have been converted to WebP and Avif formats.
                </p>';
			}
		} else if ($_POST['submit'] === 'Delete') {
			echo '<div class="success alert conversion-wrapper" id="alert" [class.visible]="isVisible">';
			echo '<div class="content">';
			Hooks\delete_all_attachments_avif_and_webp();
			echo '<p class="notification notification-bad">
					All WebP and Avif images have been deleted.
				</p>';
		}

		echo '</div>';
		echo '</div>';
	}

	?>

<div class="image-conversion-wrapper">
    <h1 class="conversion-heading">WebP & Avif Converter </h1>
    <form method="post">
        <?php $isPhpVersionOk = true; ?>
        <fieldset <?php echo $isPhpVersionOk ? '' : 'disabled' ?>>
            <p class="conversion-description">Tool for WebP and Avif format conversion of all images in the
                uploads/media library directory. <br>
                <b>Choose quality</b> - choose the quality of the converted images (the lower the quality, the smaller
                the size) <br>
                <b>Delete function</b> - deletes all WebP and Avif images from the uploads/media library directory.
                <br>
                <b>Convert function</b> - converts all images in the uploads/media library directory to WebP and Avif
                formats. <br>
                <b>Note: </b> This tool will not convert images that are not in the uploads/media library directory.<br>
                <?php echo Utils\get_php_version_info(); ?>
            </p>
            <div class="conversion-options">
                <label class="option-label">Quality of WEBP: (0 - 100%)</label>
                <input type="number" name="quality_webp" value="<?php echo get_option('wac_quality_webp', DEFAULT_QUALITY); ?>" min="0" max="100">

                <label class="option-label">Quality of AVIF: (0 - 100%)</label>
                <input type="number" name="quality_avif" value="<?php echo get_option('wac_quality_avif', DEFAULT_QUALITY); ?>" min="0" max="100">

                <label class="option-label">Quality of JPEG: (0 - 100%)</label>
                <input type="number" name="quality_jpeg" value="<?php echo get_option('wac_quality_jpeg', DEFAULT_QUALITY); ?>" min="0" max="100">

                <input type="checkbox" name="regenerate" id="regenerate" checked>
                <label for="regenerate" class="option-label">
                    Update qualities of existing images
                </label>
                
                <div class="toggle-summary_w">
                <button id="toggle-summary">Show / Hide Podsumowanie</button>
                </div>
                <div class="conversion-summary-toggle">
                    <?php
                    get_images_size_info();
                    ?>
                </div>
            </div>
            
            <div class="conversion-buttons">
                <input type="submit" name="submit" value="Set" class="convert-button">
                <input type="submit" name="submit" value="Delete" class="delete-button">
            </div>
        </fieldset>
    </form>
</div>

<?php
}

/**
 * Runs the main functionality of the plugin.
 */
function run_web_avif_converter()
{
    // The 'Web_Avif_Converter' class code is included in the 'includes/class-web-avif-converter.php' file.
    require plugin_dir_path(__FILE__) . 'includes/class-web-avif-converter.php';
    // echo print_r
    $plugin = new Web_Avif_Converter();
    $plugin->run();
}
run_web_avif_converter();