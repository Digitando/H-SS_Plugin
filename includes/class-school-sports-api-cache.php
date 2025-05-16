<?php
/**
 * The caching functionality of the plugin.
 *
 * @since      1.0.0
 * @package    School_Sports_API
 */

class School_Sports_API_Cache {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Get cached data.
     *
     * @since    1.0.0
     * @param    string    $key       The cache key.
     * @return   mixed                The cached data or false if not found.
     */
    public function get($key) {
        $cache_key = $this->plugin_name . '_' . $key;
        $cached_data = get_transient($cache_key);
        
        if ($cached_data === false) {
            return false;
        }
        
        return $cached_data;
    }

    /**
     * Set cached data.
     *
     * @since    1.0.0
     * @param    string    $key       The cache key.
     * @param    mixed     $data      The data to cache.
     * @param    int       $expiration    The cache expiration in seconds.
     * @return   bool                 Whether the data was cached successfully.
     */
    public function set($key, $data, $expiration = null) {
        $cache_key = $this->plugin_name . '_' . $key;
        
        // Get cache duration from options if not provided
        if ($expiration === null) {
            $options = get_option('school_sports_api_options');
            $expiration = isset($options['cache_duration']) ? absint($options['cache_duration']) : 300;
        }
        
        return set_transient($cache_key, $data, $expiration);
    }

    /**
     * Delete cached data.
     *
     * @since    1.0.0
     * @param    string    $key       The cache key.
     * @return   bool                 Whether the data was deleted successfully.
     */
    public function delete($key) {
        $cache_key = $this->plugin_name . '_' . $key;
        return delete_transient($cache_key);
    }

    /**
     * Clean up expired cache.
     *
     * @since    1.0.0
     */
    public function cleanup() {
        global $wpdb;
        
        // Direct database query is necessary here for performance reasons
        // when cleaning up expired transients, as there's no WordPress API for this specific operation
        // @codingStandardsIgnoreStart
        
        // First, find expired transients by checking timeout entries
        $timeout_prefix = '_transient_timeout_' . $this->plugin_name . '_';
        $sql = $wpdb->prepare(
            "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s AND option_value < %d",
            $timeout_prefix . '%',
            time()
        );
        $expired_timeouts = $wpdb->get_results($sql);
        
        // Delete the expired transients and their timeouts
        if (!empty($expired_timeouts)) {
            foreach ($expired_timeouts as $timeout) {
                $transient_name = str_replace($timeout_prefix, '', $timeout->option_name);
                delete_transient($this->plugin_name . '_' . $transient_name);
            }
        }
        
        // @codingStandardsIgnoreEnd
    }

    /**
     * Clear all transients associated with this plugin.
     *
     * @since 1.0.0
     */
    public function clear_all_plugin_transients() {
        global $wpdb;
        $plugin_prefix = $this->plugin_name . '_';

        // Transients have two parts: the data and the timeout. Both need to be deleted.
        // Pattern for the data part
        $transient_pattern = '_transient_' . $plugin_prefix . '%';
        // Pattern for the timeout part
        $timeout_pattern = '_transient_timeout_' . $plugin_prefix . '%';

        // @codingStandardsIgnoreStart
        // Delete data transients
        $sql_data = $wpdb->prepare(
            "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
            $transient_pattern
        );
        $wpdb->query($sql_data);

        // Delete timeout transients
        $sql_timeout = $wpdb->prepare(
            "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
            $timeout_pattern
        );
        $wpdb->query($sql_timeout);
        // @codingStandardsIgnoreEnd

        if (defined('WP_DEBUG') && WP_DEBUG) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log('School Sports API: All plugin transients cleared.');
        }
    }
}