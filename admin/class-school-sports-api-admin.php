<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    School_Sports_API
 */

class School_Sports_API_Admin {

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
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(dirname(__FILE__)) . 'assets/css/school-sports-api-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(dirname(__FILE__)) . 'assets/js/school-sports-api-admin.js', array('jquery'), $this->version, false);
    }

    /**
     * Add options page.
     *
     * @since    1.0.0
     */
    public function add_options_page() {
        add_options_page(
            __('HŠSS Rezultati', 'school-sports-api'),
            __('HŠSS Rezultati', 'school-sports-api'),
            'manage_options',
            $this->plugin_name,
            array($this, 'display_options_page')
        );
    }

    /**
     * Display options page.
     *
     * @since    1.0.0
     */
    public function display_options_page() {
        include_once 'partials/school-sports-api-admin-display.php';
    }

    /**
     * Register settings.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        register_setting(
            'school_sports_api_options',
            'school_sports_api_options',
            array($this, 'validate_options')
        );

        add_settings_section(
            'school_sports_api_section_api',
            __('API Postavke', 'school-sports-api'),
            array($this, 'section_api_callback'),
            $this->plugin_name
        );

        add_settings_field(
            'api_username',
            __('API Korisničko Ime', 'school-sports-api'),
            array($this, 'field_api_username_callback'),
            $this->plugin_name,
            'school_sports_api_section_api'
        );

        add_settings_field(
            'api_password',
            __('API Lozinka', 'school-sports-api'),
            array($this, 'field_api_password_callback'),
            $this->plugin_name,
            'school_sports_api_section_api'
        );

        add_settings_field(
            'api_url',
            __('API URL', 'school-sports-api'),
            array($this, 'field_api_url_callback'),
            $this->plugin_name,
            'school_sports_api_section_api'
        );

        add_settings_section(
            'school_sports_api_section_cache',
            __('Postavke Predmemorije', 'school-sports-api'),
            array($this, 'section_cache_callback'),
            $this->plugin_name
        );

        add_settings_field(
            'cache_duration',
            __('Trajanje Predmemorije (sekunde)', 'school-sports-api'),
            array($this, 'field_cache_duration_callback'),
            $this->plugin_name,
            'school_sports_api_section_cache'
        );

        add_settings_section(
            'school_sports_api_section_realtime',
            __('Postavke Ažuriranja u Stvarnom Vremenu', 'school-sports-api'),
            array($this, 'section_realtime_callback'),
            $this->plugin_name
        );

        add_settings_field(
            'refresh_interval',
            __('Interval Osvježavanja (sekunde)', 'school-sports-api'),
            array($this, 'field_refresh_interval_callback'),
            $this->plugin_name,
            'school_sports_api_section_realtime'
        );

        add_settings_field(
            'websocket_enabled',
            __('Omogući WebSocket', 'school-sports-api'),
            array($this, 'field_websocket_enabled_callback'),
            $this->plugin_name,
            'school_sports_api_section_realtime'
        );

        add_settings_field(
            'websocket_url',
            __('WebSocket URL', 'school-sports-api'),
            array($this, 'field_websocket_url_callback'),
            $this->plugin_name,
            'school_sports_api_section_realtime'
        );
    }

    /**
     * Validate options.
     *
     * @since    1.0.0
     * @param    array    $input    The options to validate.
     * @return   array              The validated options.
     */
    public function validate_options($input) {
        $valid = array();

        // API settings
        $valid['api_username'] = sanitize_text_field($input['api_username']);
        $valid['api_password'] = sanitize_text_field($input['api_password']);
        $valid['api_url'] = esc_url_raw($input['api_url']);

        // Cache settings
        $valid['cache_duration'] = absint($input['cache_duration']);
        if ($valid['cache_duration'] < 60) {
            $valid['cache_duration'] = 60; // Minimum 1 minute
        }

        // Realtime settings
        $valid['refresh_interval'] = absint($input['refresh_interval']);
        if ($valid['refresh_interval'] < 30) {
            $valid['refresh_interval'] = 30; // Minimum 30 seconds
        }

        $valid['websocket_enabled'] = isset($input['websocket_enabled']) ? (bool) $input['websocket_enabled'] : false;
        $valid['websocket_url'] = esc_url_raw($input['websocket_url']);

        return $valid;
    }

    /**
     * Section API callback.
     *
     * @since    1.0.0
     */
    public function section_api_callback() {
        echo '<p>' . esc_html__('Unesite svoje API podatke za pristup HŠSS API-ju.', 'school-sports-api') . '</p>';
    }

    /**
     * Field API username callback.
     *
     * @since    1.0.0
     */
    public function field_api_username_callback() {
        $options = get_option('school_sports_api_options');
        $value = isset($options['api_username']) ? $options['api_username'] : 'web';
        echo '<input type="text" name="school_sports_api_options[api_username]" value="' . esc_attr($value) . '" class="regular-text">';
    }

    /**
     * Field API password callback.
     *
     * @since    1.0.0
     */
    public function field_api_password_callback() {
        $options = get_option('school_sports_api_options');
        $value = isset($options['api_password']) ? $options['api_password'] : 'e51eo24nzyXDWRFkT7We7G5YR7KCM04u';
        echo '<input type="password" name="school_sports_api_options[api_password]" value="' . esc_attr($value) . '" class="regular-text">';
    }

    /**
     * Field API URL callback.
     *
     * @since    1.0.0
     */
    public function field_api_url_callback() {
        $options = get_option('school_sports_api_options');
        $value = isset($options['api_url']) ? $options['api_url'] : 'https://portal.skolski-sport.hr/api/';
        echo '<input type="url" name="school_sports_api_options[api_url]" value="' . esc_attr($value) . '" class="regular-text">';
    }

    /**
     * Section cache callback.
     *
     * @since    1.0.0
     */
    public function section_cache_callback() {
        echo '<p>' . esc_html__('Konfigurirajte postavke predmemorije za smanjenje opterećenja API-ja.', 'school-sports-api') . '</p>';
    }

    /**
     * Field cache duration callback.
     *
     * @since    1.0.0
     */
    public function field_cache_duration_callback() {
        $options = get_option('school_sports_api_options');
        $value = isset($options['cache_duration']) ? $options['cache_duration'] : 300;
        echo '<input type="number" name="school_sports_api_options[cache_duration]" value="' . esc_attr($value) . '" class="small-text" min="60"> ' . esc_html__('sekundi', 'school-sports-api');
        echo '<p class="description">' . esc_html__('Minimalno 60 sekundi. Preporučeno 300 sekundi (5 minuta).', 'school-sports-api') . '</p>';
    }

    /**
     * Section realtime callback.
     *
     * @since    1.0.0
     */
    public function section_realtime_callback() {
        echo '<p>' . esc_html__('Konfigurirajte postavke ažuriranja u stvarnom vremenu.', 'school-sports-api') . '</p>';
    }

    /**
     * Field refresh interval callback.
     *
     * @since    1.0.0
     */
    public function field_refresh_interval_callback() {
        $options = get_option('school_sports_api_options');
        $value = isset($options['refresh_interval']) ? $options['refresh_interval'] : 60;
        echo '<input type="number" name="school_sports_api_options[refresh_interval]" value="' . esc_attr($value) . '" class="small-text" min="30"> ' . esc_html__('sekundi', 'school-sports-api');
        echo '<p class="description">' . esc_html__('Minimalno 30 sekundi. Preporučeno 60 sekundi (1 minuta).', 'school-sports-api') . '</p>';
    }

    /**
     * Field WebSocket enabled callback.
     *
     * @since    1.0.0
     */
    public function field_websocket_enabled_callback() {
        $options = get_option('school_sports_api_options');
        $value = isset($options['websocket_enabled']) ? $options['websocket_enabled'] : false;
        echo '<input type="checkbox" name="school_sports_api_options[websocket_enabled]" value="1" ' . checked(1, $value, false) . '>';
        echo '<p class="description">' . esc_html__('Omogućite WebSocket za trenutna ažuriranja. Zahtijeva WebSocket poslužitelj.', 'school-sports-api') . '</p>';
    }

    /**
     * Field WebSocket URL callback.
     *
     * @since    1.0.0
     */
    public function field_websocket_url_callback() {
        $options = get_option('school_sports_api_options');
        $value = isset($options['websocket_url']) ? $options['websocket_url'] : '';
        echo '<input type="url" name="school_sports_api_options[websocket_url]" value="' . esc_attr($value) . '" class="regular-text">';
        echo '<p class="description">' . esc_html__('URL WebSocket poslužitelja (npr. wss://example.com/ws).', 'school-sports-api') . '</p>';
    }

    /**
     * Add shortcode popup.
     *
     * @since    1.0.0
     */
    public function add_shortcode_popup() {
        // Only add to admin screens
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->base, array('post', 'page'))) {
            return;
        }
        
        include_once 'partials/school-sports-api-shortcode-popup.php';
    }

    /**
     * Register shortcode button.
     *
     * @since    1.0.0
     * @param    array    $buttons    The buttons array.
     * @return   array                The modified buttons array.
     */
    public function register_shortcode_button($buttons) {
        array_push($buttons, 'school_sports_api_shortcode');
        return $buttons;
    }

    /**
     * Add shortcode button script.
     *
     * @since    1.0.0
     * @param    array    $plugin_array    The plugin array.
     * @return   array                     The modified plugin array.
     */
    public function add_shortcode_button_script($plugin_array) {
        $plugin_array['school_sports_api_shortcode'] = plugin_dir_url(dirname(__FILE__)) . 'assets/js/school-sports-api-admin.js';
        return $plugin_array;
    }
}