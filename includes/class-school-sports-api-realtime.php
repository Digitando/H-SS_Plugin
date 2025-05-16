<?php
/**
 * The real-time updates functionality of the plugin.
 *
 * @since      1.0.0
 * @package    School_Sports_API
 */

class School_Sports_API_Realtime {

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
     * The API instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      School_Sports_API_API    $api    The API instance.
     */
    private $api;

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
        $this->api = new School_Sports_API_API($plugin_name, $version);
        
        // Register custom cron schedules
        add_filter('cron_schedules', array($this, 'add_cron_schedules'));
    }
    
    /**
     * Add custom cron schedules.
     *
     * @since    1.0.0
     * @param    array    $schedules    The existing schedules.
     * @return   array                  The modified schedules.
     */
    public function add_cron_schedules($schedules) {
        $schedules['minute'] = array(
            'interval' => 60,
            'display'  => esc_html__('Every Minute', 'school-sports-api'),
        );
        return $schedules;
    }

    /**
     * AJAX handler for fetching live results.
     *
     * @since    1.0.0
     */
    public function fetch_live_results() {
        // Verify nonce if provided
        if (isset($_POST['nonce']) && !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'school-sports-api-nonce')) {
            wp_send_json_error('Invalid nonce');
            exit;
        }
        
        // Get school type from request if provided
        $school_type = isset($_POST['school_type']) ? sanitize_text_field(wp_unslash($_POST['school_type'])) : '';
        $testing = isset($_POST['testing']) ? sanitize_text_field(wp_unslash($_POST['testing'])) : '';

        $is_testing = ($testing === 'test');
        if ($is_testing) {
            // Note: The School_Sports_API_Shortcodes class has the use_test_api_url method.
            // To access it here, we'd need an instance of it, or make that method static,
            // or duplicate the filter callback logic.
            // For simplicity in this AJAX context, we'll assume the shortcodes class is available
            // or we might need to adjust how the filter is added if this class doesn't have direct access.
            // A cleaner way would be to pass the shortcodes object or make the callback universally accessible.
            // For now, let's assume we can access it or a similar static/global helper.
            // This might require an instance of School_Sports_API_Shortcodes if not static.
            // Let's assume $this->shortcodes is available if this class is instantiated by the main plugin class.
            // However, $this->shortcodes is not a property of School_Sports_API_Realtime.
            // We will need to add the filter directly or use a helper.
            // For now, we'll add a temporary local callback.
            add_filter('school_sports_api_base_url', array($this, 'use_test_api_url_realtime_callback'), 10, 1);
        }
        
        // Get live results from API
        $data = $this->api->get_live_results(true); // Use cache with short expiration
        
        if ($is_testing) {
            remove_filter('school_sports_api_base_url', array($this, 'use_test_api_url_realtime_callback'), 10);
        }
        
        // Check for errors
        if (is_wp_error($data)) {
            echo '<div class="school-sports-api-error">' . esc_html($data->get_error_message()) . '</div>';
            wp_die();
        }
        
        // Filter by school type if specified
        if (!empty($school_type)) {
            $data = $this->filter_live_results_by_school_type($data, $school_type);
        }
        
        // Generate HTML
        $html = $this->generate_live_results_html($data);
        
        // Add debug information
        $html .= '<!-- Debug: HTML generated, length: ' . strlen($html) . ' -->';
        
        // Check if filter is present
        if (strpos($html, 'school-sports-api-filter') !== false) {
            $html .= '<!-- Debug: Filter found in HTML -->';
        } else {
            $html .= '<!-- Debug: Filter NOT found in HTML -->';
            }
            
            // Inline styles for filter visibility removed. CSS will handle this.
            
            // Use a less restrictive kses filter to allow inline styles (if any remain for other purposes)
            // Or, ideally, use a more specific kses setup if all inline styles are removed.
            echo wp_kses($html, array(
                'div' => array(
                    'class' => array(),
                    'id' => array(),
                    // 'style' => array(), // Style attribute removed for divs
                    'data-group' => array(),
                    'data-gender' => array(),
                    'data-school-type' => array()
                ),
                'select' => array(
                    // 'style' => array() // Style attribute removed for selects
                ),
                'option' => array(
                'value' => array(),
                'selected' => array()
            ),
            'h2' => array(),
            'h3' => array(),
            'h4' => array(),
            'h5' => array(),
            'table' => array(
                'class' => array(),
                'id' => array()
            ),
            'thead' => array(),
            'tbody' => array(),
            'tr' => array(),
            'th' => array(),
            'td' => array(),
            'span' => array(
                'class' => array(),
                'style' => array()
            ),
            'strong' => array(),
            'b' => array(),
            'em' => array(),
            'i' => array(),
            '!--' => array() // Allow HTML comments
        ));
        wp_die();
    }

    /**
     * Filter live results by school type.
     *
     * @since    1.0.0
     * @param    array     $data         The live results data.
     * @param    string    $school_type  The school type (ss or os).
     * @return   array                   The filtered data.
     */
    private function filter_live_results_by_school_type($data, $school_type) {
        if (empty($data) || empty($school_type)) {
            return $data;
        }
        
        $filtered_data = array();
        
        // Define which genders to include based on school type
        $included_genders = array();
        if ($school_type === 'ss') {
            $included_genders = array('Mladići', 'Djevojke');
        } elseif ($school_type === 'os') {
            $included_genders = array('Dječaci', 'Djevojčice');
        }
        
        // Filter the data
        foreach ($data as $sport) {
            $filtered_sport = array(
                'naziv' => $sport['naziv'],
                'natjecanja' => array(),
            );
            
            foreach ($sport['natjecanja'] as $natjecanje) {
                if (in_array($natjecanje['spol'], $included_genders)) {
                    $filtered_sport['natjecanja'][] = $natjecanje;
                }
            }
            
            if (!empty($filtered_sport['natjecanja'])) {
                $filtered_data[] = $filtered_sport;
            }
        }
        
        return $filtered_data;
    }

    /**
     * Generate HTML for live results.
     *
     * @since    1.0.0
     * @param    array     $data    The live results data.
     * @return   string             The generated HTML.
     */
    private function generate_live_results_html($data) {
        if (empty($data)) {
            return '<div class="school-sports-api-no-results">' . __('Trenutno nema dostupnih rezultata uživo.', 'school-sports-api') . '</div>';
        }
        
        // Add debug information
        $html = '<!-- Debug: Live results data received -->';
        
        // We're not generating the filter here anymore - it will be handled by JavaScript
        // This prevents filter duplication on refresh
        
        // Generate content for each sport
        foreach ($data as $sport) {
            $html .= '<h3>' . esc_html($sport['naziv']) . '</h3>';
            
            foreach ($sport['natjecanja'] as $natjecanje) {
                $gender = $natjecanje['spol'];
                $gender_key = sanitize_title($gender);
                
                foreach ($natjecanje['faza'] as $faza) {
                    foreach ($faza['grupa'] as $grupa) {
                        // Ensure consistent group ID format
                        $group_name = sanitize_title($grupa['naziv']);
                        $group_id = 'group-' . $gender_key . '-' . $group_name;
                        $html .= '<div class="school-sports-api-group" data-group="' . esc_attr($group_id) . '" data-gender="' . esc_attr($gender_key) . '">';
                        $html .= '<h4>' . esc_html($grupa['naziv']) . ' - ' . esc_html($gender) . '</h4>';
                        
                        // Matches table
                        if (!empty($grupa['utakmice'])) {
                            $html .= '<table class="school-sports-api-table">';
                            $html .= '<thead><tr>';
                            $html .= '<th>' . __('Br.', 'school-sports-api') . '</th>';
                            
                            // Check if we need a "Name" column
                            $has_name_column = false;
                            foreach ($grupa['utakmice'] as $utakmica) {
                                if (!empty($utakmica['naziv'])) {
                                    $has_name_column = true;
                                    break;
                                }
                            }
                            
                            if ($has_name_column) {
                                $html .= '<th>' . __('Naziv', 'school-sports-api') . '</th>';
                            }
                            
                            $html .= '<th>' . __('Vrijeme', 'school-sports-api') . '</th>';
                            $html .= '<th>' . __('Lokacija', 'school-sports-api') . '</th>';
                            $html .= '<th>' . __('Ekipa 1', 'school-sports-api') . '</th>';
                            $html .= '<th>' . __('Ekipa 2', 'school-sports-api') . '</th>';
                            $html .= '<th>' . __('Rezultat', 'school-sports-api') . '</th>';
                            $html .= '</tr></thead>';
                            $html .= '<tbody>';
                            
                            foreach ($grupa['utakmice'] as $utakmica) {
                                // Highlight live matches
                                $is_live = !empty($utakmica['uzivo']) && $utakmica['uzivo'] === true;
                                $row_class = $is_live ? ' class="school-sports-api-live-match"' : '';
                                
                                $html .= '<tr' . $row_class . '>';
                                $html .= '<td>' . esc_html($utakmica['brojUtakmice']) . '</td>';
                                
                                if ($has_name_column) {
                                    $html .= '<td>' . esc_html($utakmica['naziv']) . '</td>';
                                }
                                
                                $html .= '<td>' . esc_html($utakmica['vrijeme']) . '</td>';
                                $html .= '<td>' . esc_html($utakmica['lokacija']) . '</td>';
                                $html .= '<td>' . esc_html($utakmica['sudionici'][0]['naziv']) . '</td>';
                                $html .= '<td>' . esc_html($utakmica['sudionici'][1]['naziv']) . '</td>';
                                
                                // Allow specific HTML tags in the result for styling
                                $allowed_html = array(
                                    'span' => array(
                                        'class' => array(),
                                        'style' => array(),
                                    ),
                                    'div' => array(
                                        'class' => array(),
                                        'style' => array(),
                                    ),
                                    'strong' => array(),
                                    'b' => array(),
                                    'em' => array(),
                                    'i' => array(),
                                );
                                
                                // Keep the HTML styling but sanitize it
                                $result = wp_kses($utakmica['rezultatPrikaz'], $allowed_html);
                                
                                // Add live indicator to result if match is live
                                if ($is_live) {
                                    $html .= '<td><span class="school-sports-api-live-indicator"></span> ' . $result . '</td>';
                                } else {
                                    $html .= '<td>' . $result . '</td>';
                                }
                                
                                $html .= '</tr>';
                            }
                            
                            $html .= '</tbody></table>';
                        }
                        
                        $html .= '</div>'; // End group
                    }
                }
            }
        }
        
        return $html;
    }

    /**
     * Initialize WebSocket server if enabled.
     *
     * @since    1.0.0
     */
    public function init_websocket_server() {
        // Check if WebSocket server is enabled in settings
        $options = get_option('school_sports_api_options');
        $websocket_enabled = isset($options['websocket_enabled']) ? (bool) $options['websocket_enabled'] : false;
        
        if (!$websocket_enabled) {
            return;
        }
        
        // WebSocket server implementation would go here
        // This would typically be a separate process or service
        // For WordPress integration, we would use a background process or external service
        
        // For now, we'll just add the WebSocket URL to the script localization
        add_filter('school_sports_api_script_data', array($this, 'add_websocket_url'));
    }

    /**
     * Add WebSocket URL to script data.
     *
     * @since    1.0.0
     * @param    array    $data    The script data.
     * @return   array             The modified script data.
     */
    public function add_websocket_url($data) {
        $options = get_option('school_sports_api_options');
        $websocket_url = isset($options['websocket_url']) ? $options['websocket_url'] : '';
        
        if (!empty($websocket_url)) {
            $data['ws_url'] = $websocket_url;
        }
        
        return $data;
    }

    /**
     * Schedule periodic API checks for live updates.
     *
     * @since    1.0.0
     */
    public function schedule_live_updates() {
        if (!wp_next_scheduled('school_sports_api_check_live_updates')) {
            wp_schedule_event(time(), 'minute', 'school_sports_api_check_live_updates');
        }
    }

    /**
     * Check for live updates and notify WebSocket clients.
     *
     * @since    1.0.0
     */
    public function check_live_updates() {
        // Get live results from API
        $data = $this->api->get_live_results(false); // Don't use cache
        
        // Check for errors
        if (is_wp_error($data)) {
            return;
        }
        
        // Store the data in a transient for comparison on next check
        $previous_data = get_transient('school_sports_api_live_data');
        set_transient('school_sports_api_live_data', $data, 5 * MINUTE_IN_SECONDS);
        
        // If there's no previous data, we can't compare
        if (empty($previous_data)) {
            return;
        }
        
        // Compare data to detect changes
        $changes = $this->detect_changes($previous_data, $data);
        
        // If there are changes, notify WebSocket clients
        if (!empty($changes)) {
            $this->notify_websocket_clients($changes);
        }
    }

    /**
     * Detect changes between two sets of live data.
     *
     * @since    1.0.0
     * @param    array    $old_data    The old data.
     * @param    array    $new_data    The new data.
     * @return   array                 The detected changes.
     */
    private function detect_changes($old_data, $new_data) {
        $changes = array();
        
        // This is a simplified implementation
        // In a real-world scenario, you would need a more sophisticated comparison
        
        // For now, we'll just check if the number of matches or results have changed
        foreach ($new_data as $sport_index => $sport) {
            foreach ($sport['natjecanja'] as $natjecanje_index => $natjecanje) {
                foreach ($natjecanje['faza'] as $faza_index => $faza) {
                    foreach ($faza['grupa'] as $grupa_index => $grupa) {
                        // Check if this group exists in old data
                        if (!isset($old_data[$sport_index]['natjecanja'][$natjecanje_index]['faza'][$faza_index]['grupa'][$grupa_index])) {
                            $changes[] = array(
                                'type' => 'new_group',
                                'sport' => $sport['naziv'],
                                'natjecanje' => $natjecanje['naziv'],
                                'grupa' => $grupa['naziv'],
                            );
                            continue;
                        }
                        
                        $old_grupa = $old_data[$sport_index]['natjecanja'][$natjecanje_index]['faza'][$faza_index]['grupa'][$grupa_index];
                        
                        // Check if number of matches has changed
                        if (count($grupa['utakmice']) !== count($old_grupa['utakmice'])) {
                            $changes[] = array(
                                'type' => 'matches_changed',
                                'sport' => $sport['naziv'],
                                'natjecanje' => $natjecanje['naziv'],
                                'grupa' => $grupa['naziv'],
                            );
                            continue;
                        }
                        
                        // Check if any match results have changed
                        foreach ($grupa['utakmice'] as $utakmica_index => $utakmica) {
                            if (!isset($old_grupa['utakmice'][$utakmica_index])) {
                                $changes[] = array(
                                    'type' => 'new_match',
                                    'sport' => $sport['naziv'],
                                    'natjecanje' => $natjecanje['naziv'],
                                    'grupa' => $grupa['naziv'],
                                    'match' => $utakmica['brojUtakmice'],
                                );
                                continue;
                            }
                            
                            $old_utakmica = $old_grupa['utakmice'][$utakmica_index];
                            
                            // Check if result has changed
                            if ($utakmica['rezultatPrikaz'] !== $old_utakmica['rezultatPrikaz']) {
                                $changes[] = array(
                                    'type' => 'result_changed',
                                    'sport' => $sport['naziv'],
                                    'natjecanje' => $natjecanje['naziv'],
                                    'grupa' => $grupa['naziv'],
                                    'match' => $utakmica['brojUtakmice'],
                                    'old_result' => wp_strip_all_tags($old_utakmica['rezultatPrikaz']),
                                    'new_result' => wp_strip_all_tags($utakmica['rezultatPrikaz']),
                                );
                            }
                        }
                    }
                }
            }
        }
        
        return $changes;
    }

    /**
     * Notify WebSocket clients of changes.
     *
     * @since    1.0.0
     * @param    array    $changes    The detected changes.
     */
    private function notify_websocket_clients($changes) {
        // This would typically send a message to the WebSocket server
        // Log changes only when WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
            error_log('School Sports API: Otkrivena ažuriranja uživo: ' . print_r($changes, true));
        }
        
        // In a real implementation, you would send a message to the WebSocket server
        // For example:
        // $websocket_url = get_option('school_sports_api_websocket_url');
        // wp_remote_post($websocket_url, array(
        //     'body' => array(
        //         'action' => 'notify',
        //         'changes' => $changes,
        //     ),
        // ));
    }

    /**
     * Callback function to use the test API URL for realtime AJAX.
     *
     * @since 1.0.1
     * @param string $url The current API URL (unused in this override).
     * @return string The test API URL.
     */
    public function use_test_api_url_realtime_callback($url) {
        return 'https://test.skolski-sport.hr/api/';
    }
}