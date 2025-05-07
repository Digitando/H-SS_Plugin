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
        wp_enqueue_style($this->plugin_name, plugin_dir_url(dirname(__FILE__)) . 'assets/css/school-sports-api-public.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(dirname(__FILE__)) . 'assets/js/school-sports-api-public.js', array('jquery'), $this->version, true);
        
        // Get options
        $options = get_option('school_sports_api_options');
        $refresh_interval = isset($options['refresh_interval']) ? absint($options['refresh_interval']) : 60;
        
        // Localize script
        wp_localize_script($this->plugin_name, 'school_sports_api', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('school-sports-api-nonce'),
            'refresh_interval' => $refresh_interval,
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
        
        // Generate shortcode output
        $atts = array(
            'sport' => $sport,
            'school_type' => $school_type,
            'school_year' => $school_year,
        );
        
        $html = $this->shortcodes->results_shortcode($atts);
        
        // Add debug comment at the end
        $html .= '<!-- Debug: Refreshed at ' . gmdate('H:i:s') . ' -->';
        
        echo wp_kses_post($html);
        wp_die();
    }

    /**
     * Add shortcode classes to container.
     *
     * @since    1.0.0
     * @param    string    $content    The content.
     * @return   string                The modified content.
     */
    public function add_shortcode_classes($content) {
        // Check if content contains our shortcode
        if (strpos($content, 'school-sports-api-container') === false) {
            return $content;
        }
        
        // Use regex to find shortcode attributes
        $pattern = '/\[school_sports_api_results([^\]]*)\]/';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $shortcode = $match[0];
            $atts_string = isset($match[1]) ? $match[1] : '';
            
            // Parse attributes
            $atts = array();
            $pattern = '/(\w+)=[\'"]([^\'"]*)[\'"]|(\w+)=([^\s\'"]+)/';
            preg_match_all($pattern, $atts_string, $att_matches, PREG_SET_ORDER);
            
            foreach ($att_matches as $att_match) {
                $key = !empty($att_match[1]) ? $att_match[1] : $att_match[3];
                $value = !empty($att_match[2]) ? $att_match[2] : $att_match[4];
                $atts[$key] = $value;
            }
            
            // Generate classes
            $classes = array();
            if (isset($atts['sport'])) {
                $classes[] = 'sport-' . sanitize_html_class($atts['sport']);
            }
            
            if (isset($atts['school_type'])) {
                $classes[] = 'school-type-' . sanitize_html_class($atts['school_type']);
            }
            
            if (isset($atts['school_year'])) {
                $classes[] = 'school-year-' . sanitize_html_class($atts['school_year']);
            }
            
            // Replace the first div with one that has our classes
            $class_string = implode(' ', $classes);
            $replacement = '<div class="school-sports-api-container ' . $class_string . '"';
            $content = preg_replace('/<div class="school-sports-api-container"/', $replacement, $content, 1);
        }
        
        return $content;
    }

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