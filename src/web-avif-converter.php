<?php

/**
 * The plugin bootstrap file
 *
 * @link              https://rekurencja.com/
 * @since             1.0.0
 * @package           WebpAvifConverter
 *
 * @wordpress-plugin
 * Plugin Name:       WebP & Avif Converter
 * Plugin URI:        https://images.rekurencja.com
 * Description:       Fast and simple plugin to automatically convert and serve WebP & AVIF images.
 * Version:           1.0.7
 * Author:            Rekurencja
 * Author URI:        https://rekurencja.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       web-avif-converter
 * Domain Path:       /languages
 */

if (!defined('WPINC')) {
    die;
}

add_action('wp_head', function() {
    $savedTheme = isset($_COOKIE['webp_avif_color_scheme']) ? $_COOKIE['webp_avif_color_scheme'] : 'auto';
    ?>
    <script>
        document.documentElement.classList.add('<?php echo esc_js($savedTheme); ?>');
        document.documentElement.setAttribute('color-scheme', '<?php echo esc_js($savedTheme); ?>');
    </script>
    <?php
}, 0);

define('PHP_REQUIRED_VERSION', '8.1.0');
define('PHP_VERSION_OK', version_compare(phpversion(), PHP_REQUIRED_VERSION, '>='));

register_activation_hook(__FILE__, ['WebpAvifConverter\Activator', 'activate']);
register_deactivation_hook(__FILE__, ['WebpAvifConverter\Deactivator', 'deactivate']);

require plugin_dir_path(__FILE__) . 'includes/autoloader.php';

use WebpAvifConverter\Plugin;
use WebpAvifConverter\WebpImageConverter;
use WebpAvifConverter\AvifImageConverter;
use WebpAvifConverter\ImageDeleter;
use WebpAvifConverter\AjaxHandler;

$webpConverter = new WebpImageConverter();
$avifConverter = new AvifImageConverter();
$fileDeleter = new ImageDeleter();
$ajaxHandler = new AjaxHandler();

$plugin = new Plugin($webpConverter, $avifConverter, $fileDeleter);
$plugin->run();

$ajaxHandler->init();