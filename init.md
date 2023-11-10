<!-- <?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://rekurencja.com/
 * @since             1.0.0
 * @package           Web_Avif_Converter
 *
 * @wordpress-plugin
 * Plugin Name:       WebP & Avif Converter
 * Plugin URI:        https://rekurencja.com/
 * Description:       Fast and simple plugin to automatically convert and serve WebP & AVIF images.
 * Version:           1.0.0
 * Author:            Rekurencja.com
 * Author URI:        https://rekurencja.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       web-avif-converter
 * Domain Path:       /languages
 */
use WebAvifConverter\Hooks;
use WebAvifConverter\Utils;

 class Web_Avif_Converter {

    /**
     * The single instance of the class.
     *
     * @var Web_Avif_Converter
     * @since 1.0.0
     */
    protected static $_instance = null;

    /**
     * Main Web_Avif_Converter Instance.
     *
     * Ensures only one instance of Web_Avif_Converter is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @see Web_Avif_Converter()
     * @return Web_Avif_Converter - Main instance.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Web_Avif_Converter Constructor.
     */
    public function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }


    /**
     * Define Web_Avif_Converter Constants.
     */
    private function define_constants() {
        $this->define( 'WAC_VERSION', '1.0.0' );
        $this->define( 'WAC_FILE', __FILE__ );
        $this->define( 'WAC_PATH', __DIR__ );
        $this->define( 'WAC_URL', plugins_url( '', WAC_FILE ) );
        $this->define( 'WAC_ASSETS', WAC_URL . '/assets' );
        $this->define( 'WAC_BASENAME', plugin_basename( WAC_FILE ) );
    }

    /**
     * Include required core files used in admin and on the frontend.
     */
    private function includes() {
        require_once WAC_PATH . '/public/src/utils/utils.php';
        require_once WAC_PATH . '/public/src/hooks/activation_hooks.php';
        require_once WAC_PATH . '/public/src/hooks/attachment_hooks.php';
        require_once WAC_PATH . '/public/src/utils/generator-utils.php';
        require_once WAC_PATH . '/public/src/exceptions/exceptions.php';
    }

    /**
     * Hook into actions and filters.
     */
    private function init_hooks() {
        register_activation_hook( WAC_FILE, 'WebAvifConverter\Hooks\activate_web_avif_converter' );
        register_deactivation_hook( WAC_FILE, 'WebAvifConverter\Hooks\deactivate_web_avif_converter' );
        add_filter( 'attachment_fields_to_edit', 'add_image_quality_field', 10, 2 );
        add_filter( 'manage_media_columns', 'add_image_quality_column' );
        add_action( 'manage_media_custom_column', 'display_image_quality_column', 10, 2 );
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
  }

run_web_avif_converter();


     -->
