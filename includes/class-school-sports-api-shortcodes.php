<?php
/**
 * The shortcodes functionality of the plugin.
 *
 * @since      1.0.0
 * @package    School_Sports_API
 */

class School_Sports_API_Shortcodes {

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
    }

    /**
     * Register all shortcodes.
     *
     * @since    1.0.0
     */
    public function register_shortcodes() {
        add_shortcode('school_sports_api_results', array($this, 'results_shortcode'));
        add_shortcode('school_sports_api_live', array($this, 'live_shortcode'));
    }

    /**
     * Shortcode for displaying sports results.
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode attributes.
     * @return   string             The shortcode output.
     */
    public function results_shortcode($atts) {
        // Extract shortcode attributes
        $atts = shortcode_atts(array(
            'sport' => 'odbojka',
            'school_type' => 'ss', // ss = high school, os = elementary school
            'gender' => '', // Kept for backward compatibility but not used for filtering
            'school_year' => '2024',
        ), $atts, 'school_sports_api_results');
        
        // Get data from API
        $data = $this->api->get_sports_data($atts['sport'], $atts['school_year']);
        
        // Check for errors
        if (is_wp_error($data)) {
            return '<div class="school-sports-api-error">' . esc_html($data->get_error_message()) . '</div>';
        }
        
        // Filter data by school type
        $data = $this->filter_by_school_type($data, $atts['school_type']);
        
        // Generate HTML
        $html = $this->generate_results_html($data, $atts);
        
        return $html;
    }

    /**
     * Filter data by school type.
     *
     * @since    1.0.0
     * @param    array     $data         The sports data.
     * @param    string    $school_type  The school type (ss or os).
     * @return   array                   The filtered data.
     */
    private function filter_by_school_type($data, $school_type) {
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
        foreach ($data as $item) {
            if (in_array($item['natjecanje']['spol'], $included_genders)) {
                $filtered_data[] = $item;
            }
        }
        
        return $filtered_data;
    }

    /**
     * Shortcode for displaying live results.
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode attributes.
     * @return   string             The shortcode output.
     */
    public function live_shortcode($atts) {
        // Extract shortcode attributes
        $atts = shortcode_atts(array(
            'refresh_interval' => '', // Override default refresh interval
            'school_type' => '', // Optional school type filter for live results
        ), $atts, 'school_sports_api_live');
        
        // Generate HTML container for AJAX loading
        $html = '<div class="school-sports-api-container">';
        $html .= '<div class="school-sports-api-live-header">';
        $html .= '<h2>' . __('Rezultati Uživo', 'school-sports-api') . ' <span class="school-sports-api-live-indicator"></span></h2>';
        $html .= '</div>';
        
        // Add school type as a data attribute for AJAX filtering
        $school_type_attr = !empty($atts['school_type']) ? ' data-school-type="' . esc_attr($atts['school_type']) . '"' : '';
        $html .= '<div class="school-sports-api-live"' . $school_type_attr . '></div>';
        $html .= '</div>';
        
        // Add custom refresh interval if specified
        if (!empty($atts['refresh_interval'])) {
            $html .= '<script>var school_sports_api_custom_refresh = ' . absint($atts['refresh_interval']) . ';</script>';
        }
        
        return $html;
    }

    /**
     * Generate HTML for sports results.
     *
     * @since    1.0.0
     * @param    array     $data    The sports data.
     * @param    array     $atts    The shortcode attributes.
     * @return   string             The generated HTML.
     */
    private function generate_results_html($data, $atts) {
        if (empty($data)) {
            return '<div class="school-sports-api-container"><p>' . __('Nema pronađenih rezultata.', 'school-sports-api') . '</p></div>';
        }
        
        $html = '<div class="school-sports-api-container">';
        
        // Add tabs if multiple genders are present
        $genders = array();
        foreach ($data as $item) {
            $gender = $item['natjecanje']['spol'];
            if (!in_array($gender, $genders)) {
                $genders[] = $gender;
            }
        }
        
        if (count($genders) > 1) {
            $html .= '<div class="school-sports-api-tabs">';
            foreach ($genders as $index => $gender) {
                $active = $index === 0 ? ' active' : '';
                $tab_id = 'school-sports-api-tab-' . sanitize_title($gender);
                $html .= '<div class="school-sports-api-tab' . $active . '" data-tab="' . $tab_id . '">' . esc_html($gender) . '</div>';
            }
            $html .= '</div>';
        }
        
        // Process each gender
        foreach ($genders as $index => $gender) {
            $tab_id = 'school-sports-api-tab-' . sanitize_title($gender);
            $display = $index === 0 || count($genders) === 1 ? '' : ' style="display:none;"';
            
            $html .= '<div id="' . $tab_id . '" class="school-sports-api-tab-content"' . $display . '>';
            
            // Filter data for this gender
            $gender_data = array();
            foreach ($data as $item) {
                if ($item['natjecanje']['spol'] === $gender) {
                    $gender_data[] = $item;
                }
            }
            
            // Generate filter dropdown if there are multiple groups
            $groups = array();
            foreach ($gender_data as $item) {
                foreach ($item['faza'] as $faza) {
                    foreach ($faza['grupa'] as $grupa) {
                        $group_id = sanitize_title($grupa['naziv'] . '-' . $gender);
                        $groups[$group_id] = $grupa['naziv'];
                    }
                }
            }
            
            if (count($groups) > 1) {
                $html .= '<div class="school-sports-api-filter">';
                $html .= '<select>';
                $html .= '<option value="all">' . __('Sve Grupe', 'school-sports-api') . '</option>';
                
                foreach ($groups as $group_id => $group_name) {
                    $html .= '<option value="' . $group_id . '">' . esc_html($group_name) . '</option>';
                }
                
                $html .= '</select>';
                $html .= '</div>';
            }
            
            // Generate content for each competition
            foreach ($gender_data as $competition) {
                $html .= '<h3>' . esc_html($competition['natjecanje']['naziv']) . '</h3>';
                
                foreach ($competition['faza'] as $faza) {
                    foreach ($faza['grupa'] as $grupa) {
                        $group_id = sanitize_title($grupa['naziv'] . '-' . $gender);
                        $html .= '<div class="school-sports-api-group" data-group="' . $group_id . '" data-gender="' . sanitize_title($gender) . '">';
                        $html .= '<h4>' . esc_html($grupa['naziv']) . '</h4>';
                        
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
                                $html .= '<tr>';
                                $html .= '<td>' . esc_html($utakmica['brojUtakmice']) . '</td>';
                                
                                if ($has_name_column) {
                                    $html .= '<td>' . esc_html($utakmica['naziv']) . '</td>';
                                }
                                
                                $html .= '<td>' . esc_html($utakmica['vrijeme']) . '</td>';
                                $html .= '<td>' . esc_html($utakmica['lokacija']) . '</td>';
                                $html .= '<td>' . esc_html($utakmica['sudionici'][0]['naziv']) . '</td>';
                                $html .= '<td>' . esc_html($utakmica['sudionici'][1]['naziv']) . '</td>';
                                
                                // Strip HTML tags from the result
                                $result = wp_strip_all_tags($utakmica['rezultatPrikaz']);
                                $html .= '<td>' . esc_html($result) . '</td>';
                                
                                $html .= '</tr>';
                            }
                            
                            $html .= '</tbody></table>';
                        }
                        
                        // Rankings table
                        if (!empty($grupa['tablica'])) {
                            $html .= '<h5>' . __('Poredak', 'school-sports-api') . ' - ' . esc_html($grupa['naziv']) . '</h5>';
                            $html .= $this->generate_rankings_table($grupa['tablica'], $atts['sport']);
                        }
                        
                        $html .= '</div>'; // End group
                    }
                }
                
                // Final rankings
                if (!empty($competition['poredak'])) {
                    $html .= '<div class="school-sports-api-group" data-group="final-' . sanitize_title($gender) . '" data-gender="' . sanitize_title($gender) . '">';
                    $html .= '<h4>' . __('Konačni Poredak', 'school-sports-api') . ' - ' . esc_html($gender) . '</h4>';
                    $html .= '<table class="school-sports-api-table">';
                    $html .= '<thead><tr>';
                    $html .= '<th>' . __('Plasman', 'school-sports-api') . '</th>';
                    $html .= '<th>' . __('Naziv', 'school-sports-api') . '</th>';
                    $html .= '<th>' . __('Škola', 'school-sports-api') . '</th>';
                    $html .= '<th>' . __('Mjesto', 'school-sports-api') . '</th>';
                    $html .= '</tr></thead>';
                    $html .= '<tbody>';
                    
                    foreach ($competition['poredak'] as $poredak) {
                        $html .= '<tr>';
                        $html .= '<td>' . esc_html($poredak['plasman']) . '</td>';
                        $html .= '<td>' . esc_html($poredak['naziv']) . '</td>';
                        $html .= '<td>' . esc_html($poredak['skola']) . '</td>';
                        $html .= '<td>' . esc_html($poredak['mjesto']) . '</td>';
                        $html .= '</tr>';
                    }
                    
                    $html .= '</tbody></table>';
                    $html .= '</div>'; // End group
                }
            }
            
            $html .= '</div>'; // End tab content
        }
        
        $html .= '</div>'; // End container
        
        return $html;
    }

    /**
     * Generate rankings table HTML based on sport type.
     *
     * @since    1.0.0
     * @param    array     $tablica    The rankings data.
     * @param    string    $sport      The sport type.
     * @return   string                The generated HTML.
     */
    private function generate_rankings_table($tablica, $sport) {
        $html = '<table class="school-sports-api-table">';
        
        // Different table headers based on sport
        switch ($sport) {
            case 'odbojka':
                $html .= '<thead><tr>';
                $html .= '<th rowspan="2">' . __('Mjesto', 'school-sports-api') . '</th>';
                $html .= '<th rowspan="2">' . __('Ekipa', 'school-sports-api') . '</th>';
                $html .= '<th colspan="3">' . __('Utakmice', 'school-sports-api') . '</th>';
                $html .= '<th colspan="3">' . __('Setovi', 'school-sports-api') . '</th>';
                $html .= '<th colspan="3">' . __('Poeni', 'school-sports-api') . '</th>';
                $html .= '<th rowspan="2">' . __('Bodovi', 'school-sports-api') . '</th>';
                $html .= '</tr><tr>';
                $html .= '<th>' . __('Odigrano', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Pobjeda', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Poraza', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Dobiveno', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Izgubljeno', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Omjer', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Dobiveno', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Izgubljeno', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Omjer', 'school-sports-api') . '</th>';
                $html .= '</tr></thead>';
                break;
                
            case 'futsal':
            case 'kosarka':
            case 'kosarka3x3':
            case 'rukomet':
                $html .= '<thead><tr>';
                $html .= '<th rowspan="2">' . __('Mjesto', 'school-sports-api') . '</th>';
                $html .= '<th rowspan="2">' . __('Ekipa', 'school-sports-api') . '</th>';
                $html .= '<th colspan="4">' . __('Utakmice', 'school-sports-api') . '</th>';
                $html .= '<th colspan="3">' . __('Golovi', 'school-sports-api') . '</th>';
                $html .= '<th rowspan="2">' . __('Bodovi', 'school-sports-api') . '</th>';
                $html .= '</tr><tr>';
                $html .= '<th>' . __('Odigrano', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Pobjeda', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Neriješeno', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Poraza', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Zabijeno', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Primljeno', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Razlika', 'school-sports-api') . '</th>';
                $html .= '</tr></thead>';
                break;
                
            case 'badminton':
            case 'stolnitenis':
                $html .= '<thead><tr>';
                $html .= '<th rowspan="2">' . __('Mjesto', 'school-sports-api') . '</th>';
                $html .= '<th rowspan="2">' . __('Ekipa', 'school-sports-api') . '</th>';
                $html .= '<th colspan="3">' . __('Utakmice', 'school-sports-api') . '</th>';
                $html .= '<th colspan="3">' . __('Gemovi', 'school-sports-api') . '</th>';
                $html .= '<th colspan="3">' . __('Poeni', 'school-sports-api') . '</th>';
                $html .= '<th rowspan="2">' . __('Bodovi', 'school-sports-api') . '</th>';
                $html .= '</tr><tr>';
                $html .= '<th>' . __('Odigrano', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Pobjeda', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Poraza', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Dobiveno', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Izgubljeno', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Omjer', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Dobiveno', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Izgubljeno', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Omjer', 'school-sports-api') . '</th>';
                $html .= '</tr></thead>';
                break;
                
            default:
                // Generic table header
                $html .= '<thead><tr>';
                $html .= '<th>' . __('Mjesto', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Ekipa', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Utakmice', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Pobjeda', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Poraza', 'school-sports-api') . '</th>';
                $html .= '<th>' . __('Bodovi', 'school-sports-api') . '</th>';
                $html .= '</tr></thead>';
                break;
        }
        
        $html .= '<tbody>';
        
        foreach ($tablica as $item) {
            $html .= '<tr>';
            $html .= '<td>' . esc_html($item['mjesto']) . '</td>';
            $html .= '<td>' . esc_html($item['ekipa']) . '</td>';
            
            // Different table rows based on sport
            switch ($sport) {
                case 'odbojka':
                    $matches_played = $item['pobjeda'] + $item['poraza'];
                    $html .= '<td>' . esc_html($matches_played) . '</td>';
                    $html .= '<td>' . esc_html($item['pobjeda']) . '</td>';
                    $html .= '<td>' . esc_html($item['poraza']) . '</td>';
                    $html .= '<td>' . esc_html($item['periodDobiveno']) . '</td>';
                    $html .= '<td>' . esc_html($item['periodIzgubljeno']) . '</td>';
                    $html .= '<td>' . esc_html($item['periodKolicnik']) . '</td>';
                    $html .= '<td>' . esc_html($item['poenDobiveno']) . '</td>';
                    $html .= '<td>' . esc_html($item['poenIzgubljeno']) . '</td>';
                    $html .= '<td>' . esc_html($item['poenKolicnik']) . '</td>';
                    break;
                    
                case 'futsal':
                case 'kosarka':
                case 'kosarka3x3':
                case 'rukomet':
                    $matches_played = $item['pobjeda'] + $item['poraza'] + (isset($item['neodlucenih']) ? $item['neodlucenih'] : 0);
                    $html .= '<td>' . esc_html($matches_played) . '</td>';
                    $html .= '<td>' . esc_html($item['pobjeda']) . '</td>';
                    $html .= '<td>' . esc_html(isset($item['neodlucenih']) ? $item['neodlucenih'] : 0) . '</td>';
                    $html .= '<td>' . esc_html($item['poraza']) . '</td>';
                    $html .= '<td>' . esc_html($item['poenDobiveno']) . '</td>';
                    $html .= '<td>' . esc_html($item['poenIzgubljeno']) . '</td>';
                    $html .= '<td>' . esc_html($item['poenDobiveno'] - $item['poenIzgubljeno']) . '</td>';
                    break;
                    
                case 'badminton':
                case 'stolnitenis':
                    $matches_played = $item['pobjeda'] + $item['poraza'];
                    $html .= '<td>' . esc_html($matches_played) . '</td>';
                    $html .= '<td>' . esc_html($item['pobjeda']) . '</td>';
                    $html .= '<td>' . esc_html($item['poraza']) . '</td>';
                    $html .= '<td>' . esc_html($item['periodDobiveno']) . '</td>';
                    $html .= '<td>' . esc_html($item['periodIzgubljeno']) . '</td>';
                    $html .= '<td>' . esc_html($item['periodKolicnik']) . '</td>';
                    $html .= '<td>' . esc_html($item['poenDobiveno']) . '</td>';
                    $html .= '<td>' . esc_html($item['poenIzgubljeno']) . '</td>';
                    $html .= '<td>' . esc_html($item['poenKolicnik']) . '</td>';
                    break;
                    
                default:
                    // Generic table row
                    $matches_played = $item['pobjeda'] + $item['poraza'];
                    $html .= '<td>' . esc_html($matches_played) . '</td>';
                    $html .= '<td>' . esc_html($item['pobjeda']) . '</td>';
                    $html .= '<td>' . esc_html($item['poraza']) . '</td>';
                    break;
            }
            
            $html .= '<td>' . esc_html($item['bodovi']) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        
        return $html;
    }
}