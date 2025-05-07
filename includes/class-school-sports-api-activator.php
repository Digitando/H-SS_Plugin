<?php
/**
 * Fired during plugin activation.
 *
 * @since      1.0.0
 * @package    School_Sports_API
 */

class School_Sports_API_Activator {

    /**
     * Activate the plugin.
     *
     * @since    1.0.0
     */
    public static function activate() {
        // Create default options
        $default_options = array(
            'api_username' => 'web',
            'api_password' => 'e51eo24nzyXDWRFkT7We7G5YR7KCM04u',
            'api_url' => 'https://portal.skolski-sport.hr/api/',
            'cache_duration' => 300, // 5 minutes
            'refresh_interval' => 60, // 1 minute
            'websocket_enabled' => false,
            'websocket_url' => '',
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
}