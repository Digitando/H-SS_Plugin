<?php
/**
 * Fired during plugin deactivation.
 *
 * @since      1.0.0
 * @package    School_Sports_API
 */

class School_Sports_API_Deactivator {

    /**
     * Deactivate the plugin.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Clear scheduled cron jobs
        wp_clear_scheduled_hook('school_sports_api_cleanup_cache');
        wp_clear_scheduled_hook('school_sports_api_check_live_updates');
    }
}