<?php
/**
 * The plugin bootstrap file
 *
 * @since             1.0.0
 * @package           School_Sports_API
 *
 * @wordpress-plugin
 * Plugin Name:       HŠSS Rezultati
 * Plugin URI:        https://github.com/Digitando/H-SS_Plugin
 * Description:       Integrates with the School Sports API to display sports data, results, and schedules.
 * Version:           1.0.0
 * Author:            Digitando
 * Author URI:        https://www.digitando.net/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       school-sports-api
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 */
define('SCHOOL_SPORTS_API_VERSION', '1.0.0');

/**
 * The code that runs during plugin activation.
 */
function activate_school_sports_api() {
    // Create default options
    $default_options = array(
        'api_username' => 'web',
        'api_password' => 'e51eo24nzyXDWRFkT7We7G5YR7KCM04u',
        'api_url' => 'https://portal.skolski-sport.hr/api/',
        'cache_duration' => 300, // 5 minutes
        'refresh_interval' => 60, // 1 minute
        'websocket_enabled' => false,
        'websocket_url' => '',
        'desktop_button_visible' => true, // Default to visible
        'mobile_button_visible' => true, // Default to visible
    );
    
    // Add options if they don't exist
    if (!get_option('school_sports_api_options')) {
        add_option('school_sports_api_options', $default_options);
    }

    // Schedule cron job for cache cleanup
    if (!wp_next_scheduled('school_sports_api_cleanup_cache')) {
        wp_schedule_event(time(), 'daily', 'school_sports_api_cleanup_cache');
    }
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_school_sports_api() {
    // Clear scheduled cron jobs
    wp_clear_scheduled_hook('school_sports_api_cleanup_cache');
    wp_clear_scheduled_hook('school_sports_api_check_live_updates');
}

register_activation_hook(__FILE__, 'activate_school_sports_api');
register_deactivation_hook(__FILE__, 'deactivate_school_sports_api');

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function run_school_sports_api() {
    // Check if the core class file exists
    if (file_exists(plugin_dir_path(__FILE__) . 'includes/class-school-sports-api.php')) {
        // Include the core plugin class
        require_once plugin_dir_path(__FILE__) . 'includes/class-school-sports-api.php';
        
        // Initialize the plugin
        global $school_sports_api_plugin;
        $school_sports_api_plugin = new School_Sports_API();
        $school_sports_api_plugin->run();
    }
}

// Run the plugin after all plugins are loaded
add_action('plugins_loaded', 'run_school_sports_api');


/**
 * Handle cache cleanup.
 */
function school_sports_api_do_cleanup_cache() {
    if (file_exists(plugin_dir_path(__FILE__) . 'includes/class-school-sports-api-cache.php')) {
        require_once plugin_dir_path(__FILE__) . 'includes/class-school-sports-api-cache.php';
        $cache = new School_Sports_API_Cache('school-sports-api', SCHOOL_SPORTS_API_VERSION);
        $cache->cleanup();
    }
}
add_action('school_sports_api_cleanup_cache', 'school_sports_api_do_cleanup_cache');

/**
 * Handle live updates check.
 */
function school_sports_api_do_check_live_updates() {
    global $school_sports_api_plugin;
    
    // Make sure the plugin instance exists
    if (isset($school_sports_api_plugin) && is_object($school_sports_api_plugin) && method_exists($school_sports_api_plugin, 'get_realtime')) {
        $realtime = $school_sports_api_plugin->get_realtime();
        if (is_object($realtime) && method_exists($realtime, 'check_live_updates')) {
            $realtime->check_live_updates();
        }
    }
}
add_action('school_sports_api_check_live_updates', 'school_sports_api_do_check_live_updates');