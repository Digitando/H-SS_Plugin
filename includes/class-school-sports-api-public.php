<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    School_Sports_API
 */

class School_Sports_API_Public {

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
     * The shortcodes instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      School_Sports_API_Shortcodes    $shortcodes    The shortcodes instance.
     */
    private $shortcodes;

    /**
     * The realtime instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      School_Sports_API_Realtime    $realtime    The realtime instance.
     */
    private $realtime;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->shortcodes = new School_Sports_API_Shortcodes($plugin_name, $version);
        $this->realtime = new School_Sports_API_Realtime($plugin_name, $version);
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        // Enqueue the main plugin CSS
        wp_enqueue_style($this->plugin_name, plugin_dir_url(dirname(__FILE__)) . 'assets/css/school-sports-api-public.css', array(), $this->version, 'all');
        
        // Enqueue custom live results CSS with high priority
        wp_enqueue_style(
            $this->plugin_name . '-custom-live-results', 
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/custom-live-results.css', 
            array(), 
            $this->version . '.' . time(), // Add timestamp to prevent caching
            'all'
        );
        
        // Enqueue button visibility CSS with very high priority
        wp_enqueue_style(
            $this->plugin_name . '-button-visibility', 
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/button-visibility.css', 
            array(), 
            $this->version . '.' . time(), // Add timestamp to prevent caching
            'all'
        );
        
        // Set the highest priority possible for the CSS files
        global $wp_styles;
        if (isset($wp_styles->registered[$this->plugin_name . '-button-visibility'])) {
            $wp_styles->registered[$this->plugin_name . '-button-visibility']->extra['after'] = array('media="all"');
        }
        
        if (isset($wp_styles->registered[$this->plugin_name . '-custom-live-results'])) {
            $wp_styles->registered[$this->plugin_name . '-custom-live-results']->extra['after'] = array('media="all"');
        }
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(dirname(__FILE__)) . 'assets/js/school-sports-api-public.js', array('jquery'), $this->version . '.' . time(), true);
        
        // Get options
        $options = get_option('school_sports_api_options');
        $refresh_interval = isset($options['refresh_interval']) ? absint($options['refresh_interval']) : 60;
        
        // Get button visibility settings
        $desktop_button_visible = isset($options['desktop_button_visible']) ? (bool) $options['desktop_button_visible'] : false;
        $mobile_button_visible = isset($options['mobile_button_visible']) ? (bool) $options['mobile_button_visible'] : false;
        
        // Localize script
        wp_localize_script($this->plugin_name, 'school_sports_api', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('school-sports-api-nonce'),
            'refresh_interval' => $refresh_interval,
            'desktop_button_visible' => $desktop_button_visible,
            'mobile_button_visible' => $mobile_button_visible,
            // Add translations for JavaScript
            'translations' => array(
                'loading' => __('Učitavanje...', 'school-sports-api'),
                'lastUpdated' => __('Zadnje ažurirano', 'school-sports-api'),
                'error' => __('Greška pri učitavanju podataka. Molimo pokušajte ponovno.', 'school-sports-api'),
                'noResults' => __('Nema rezultata za prikaz.', 'school-sports-api'),
            ),
        ));
    }

    /**
     * Register shortcodes.
     *
     * @since    1.0.0
     */
    public function register_shortcodes() {
        $this->shortcodes->register_shortcodes();
    }
    
    /**
     * Add body classes for button visibility.
     *
     * @since    1.0.0
     */
    public function add_body_classes($classes) {
        $options = get_option('school_sports_api_options');
        
        // Add classes based on button visibility settings
        if (empty($options['desktop_button_visible'])) {
            $classes[] = 'desktop-button-hidden';
        }
        
        if (empty($options['mobile_button_visible'])) {
            $classes[] = 'mobile-button-hidden';
        }
        
        return $classes;
    }

    /**
     * Register AJAX handlers.
     *
     * @since    1.0.0
     */
    public function register_ajax_handlers() {
        add_action('wp_ajax_fetch_live_results', array($this->realtime, 'fetch_live_results'));
        add_action('wp_ajax_nopriv_fetch_live_results', array($this->realtime, 'fetch_live_results'));
        
        add_action('wp_ajax_fetch_sports_results', array($this, 'fetch_sports_results'));
        add_action('wp_ajax_nopriv_fetch_sports_results', array($this, 'fetch_sports_results'));
    }

    /**
     * AJAX handler for fetching sports results.
     *
     * @since    1.0.0
     */
    public function fetch_sports_results() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'school-sports-api-nonce')) {
            wp_send_json_error('Invalid nonce');
            exit;
        }
        
        // Get parameters
        $sport = isset($_POST['sport']) ? sanitize_text_field(wp_unslash($_POST['sport'])) : 'odbojka';
        $school_type = isset($_POST['school_type']) ? sanitize_text_field(wp_unslash($_POST['school_type'])) : 'ss';
        $school_year = isset($_POST['school_year']) ? sanitize_text_field(wp_unslash($_POST['school_year'])) : '2024';
        $testing = isset($_POST['testing']) ? sanitize_text_field(wp_unslash($_POST['testing'])) : '';

        // Generate shortcode output
        $atts = array(
            'sport' => $sport,
            'school_type' => $school_type,
            'school_year' => $school_year,
            'testing' => $testing, // Pass testing status to shortcode handler
        );
        
        // The results_shortcode method itself handles adding/removing the filter
        // based on the 'testing' attribute.
        $html = $this->shortcodes->results_shortcode($atts);
        
        // Add debug comment at the end
        $html .= '<!-- Debug: Refreshed at ' . gmdate('H:i:s') . ' -->';

        // Define allowed HTML for the AJAX response, specifically for the filter
        $allowed_html_for_filter = array(
            'div' => array(
                'class' => array(),
                'id' => array(),
                'style' => array(),
                'data-group' => array(),
                'data-gender' => array(),
                'data-tab' => array(), // For tabs
            ),
            'select' => array(
                'class' => array(),
                'id' => array(),
                'name' => array(),
                'style' => array(),
            ),
            'option' => array(
                'value' => array(),
                'selected' => array(),
            ),
            'h3' => array(),
            'h4' => array(),
            'h5' => array(),
            'p' => array(),
            'table' => array(
                'class' => array(),
            ),
            'thead' => array(),
            'tbody' => array(),
            'tr' => array(),
            'th' => array(
                'colspan' => array(),
                'rowspan' => array(),
            ),
            'td' => array(
                'colspan' => array(),
                'rowspan' => array(),
            ),
            'span' => array( // For results formatting
                'class' => array(),
                'style' => array(),
            ),
            'strong' => array(),
            'b' => array(),
            'em' => array(),
            'i' => array(),
            'br' => array(),
            '!--' => array(), // Allow comments
        );
        
        echo wp_kses($html, $allowed_html_for_filter);
        wp_die();
    }

    // The add_shortcode_classes method is removed as data attributes are now added directly in generate_results_html.

    /**
     * Initialize WebSocket server if enabled.
     *
     * @since    1.0.0
     */
    public function init_websocket_server() {
        $this->realtime->init_websocket_server();
    }

    /**
     * Schedule periodic API checks for live updates.
     *
     * @since    1.0.0
     */
    public function schedule_live_updates() {
        $this->realtime->schedule_live_updates();
    }

    /**
     * Check for live updates.
     *
     * @since    1.0.0
     */
    public function check_live_updates() {
        $this->realtime->check_live_updates();
    }
}