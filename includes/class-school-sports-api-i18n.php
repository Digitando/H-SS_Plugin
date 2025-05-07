<?php
/**
 * Define the internationalization functionality.
 *
 * @since      1.0.0
 * @package    School_Sports_API
 */

class School_Sports_API_i18n {

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'school-sports-api',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}