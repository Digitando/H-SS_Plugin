<?php
/**
 * The API functionality of the plugin.
 *
 * @since      1.0.0
 * @package    School_Sports_API
 */

class School_Sports_API_API {

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
     * The cache instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      School_Sports_API_Cache    $cache    The cache instance.
     */
    private $cache;

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
        $this->cache = new School_Sports_API_Cache($plugin_name, $version);
    }

    /**
     * Get API credentials from options.
     *
     * @since    1.0.0
     * @return   array    The API credentials.
     */
    private function get_credentials() {
        $options = get_option('school_sports_api_options');
        
        return array(
            'username' => isset($options['api_username']) ? $options['api_username'] : 'web',
            'password' => isset($options['api_password']) ? $options['api_password'] : 'e51eo24nzyXDWRFkT7We7G5YR7KCM04u',
            'api_url' => isset($options['api_url']) ? $options['api_url'] : 'https://portal.skolski-sport.hr/api/',
        );
    }

    /**
     * Make API request.
     *
     * @since    1.0.0
     * @param    string    $endpoint    The API endpoint.
     * @param    array     $params      The request parameters.
     * @param    bool      $use_cache   Whether to use cache.
     * @return   mixed                  The API response or WP_Error.
     */
    private function make_request($endpoint, $params = array(), $use_cache = true) {
        // Start timing the request
        $start_time = microtime(true);
        
        // Get API credentials
        $credentials = $this->get_credentials();
        
        // Add credentials to params
        $params['username'] = $credentials['username'];
        $params['password'] = $credentials['password'];
        
        // Generate cache key
        $cache_key = md5($endpoint . serialize($params));
        
        // Check cache if enabled
        if ($use_cache) {
            $cached_data = $this->cache->get($cache_key);
            
            if ($cached_data !== false) {
                // Add debug info
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log('School Sports API: Using cached data for ' . $endpoint . ' (key: ' . $cache_key . ')');
                }
                return $cached_data;
            }
        }
        
        // Build API URL
        $api_url = trailingslashit($credentials['api_url']) . '?' . $endpoint;
        
        // Log the request
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log('School Sports API: Making request to ' . $api_url);
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log('School Sports API: Request params: ' . wp_json_encode($params));
        }
        
        // Make request
        $response = wp_remote_post($api_url, array(
            'body' => $params,
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Cache-Control' => 'no-cache',
            ),
        ));
        
        // Calculate request time
        $request_time = microtime(true) - $start_time;
        
        // Check for errors
        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('School Sports API Error: ' . $response->get_error_message());
            }
            return $response;
        }
        
        // Get response code
        $response_code = wp_remote_retrieve_response_code($response);
        
        // Log response info
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log('School Sports API: Response code ' . $response_code . ' (time: ' . round($request_time, 2) . 's)');
        }
        
        // Get response body
        $body = wp_remote_retrieve_body($response);
        
        // Log response body length
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log('School Sports API: Response body length ' . strlen($body));
        }
        
        // Decode JSON
        $data = json_decode($body, true);
        
        // Check if JSON is valid
        if ($data === null) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('School Sports API Error: Invalid JSON response');
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('School Sports API: Response body: ' . substr($body, 0, 1000));
            }
            return new WP_Error('invalid_json', __('Nevažeći JSON odgovor od API-ja.', 'school-sports-api'));
        }
        
        // Log data count
        if (defined('WP_DEBUG') && WP_DEBUG && is_array($data)) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log('School Sports API: Received ' . count($data) . ' items');
        }
        
        // Cache data if enabled
        if ($use_cache) {
            $this->cache->set($cache_key, $data);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('School Sports API: Cached data with key ' . $cache_key);
            }
        }
        
        return $data;
    }

    /**
     * Get sports data.
     *
     * @since    1.0.0
     * @param    string    $sport        The sport.
     * @param    string    $school_year  The school year.
     * @param    bool      $use_cache    Whether to use cache.
     * @return   mixed                   The sports data or WP_Error.
     */
    public function get_sports_data($sport = 'odbojka', $school_year = '2024', $use_cache = true) {
        // Set up parameters
        $params = array(
            'rubrika' => 'dohvatiNatjecanje',
            'sport' => $sport,
            'skolskaGodina' => $school_year,
        );
        
        // Make request
        $data = $this->make_request('rubrika=dohvatiNatjecanje', $params, $use_cache);
        
        return $data;
    }

    /**
     * Get live results.
     *
     * @since    1.0.0
     * @param    bool      $use_cache    Whether to use cache.
     * @return   mixed                   The live results or WP_Error.
     */
    public function get_live_results($use_cache = false) {
        // Set up parameters
        $params = array(
            'rubrika' => 'rezultatiUzivo',
        );
        
        // Make request with short cache duration for live results
        $data = $this->make_request('rubrika=rezultatiUzivo', $params, $use_cache);
        
        // If using cache, set a short expiration
        if ($use_cache && !is_wp_error($data)) {
            $this->cache->set('live_results', $data, 60); // 1 minute
        }
        
        return $data;
    }
}