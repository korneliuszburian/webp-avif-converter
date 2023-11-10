<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://rekurencja.com/
 * @since      1.0.0
 *
 * @package    Web_Avif_Converter
 * @subpackage Web_Avif_Converter/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Web_Avif_Converter
 * @subpackage Web_Avif_Converter/includes
 * @author     Rekurencja <biuro@rekurencja.com>
 */
class Web_Avif_Converter_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'web-avif-converter',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
