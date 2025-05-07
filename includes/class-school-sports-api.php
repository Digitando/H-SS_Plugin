<?php
/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    School_Sports_API
 */

class School_Sports_API {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      School_Sports_API_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * The public-facing functionality of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      School_Sports_API_Public    $public    The public-facing functionality.
     */
    protected $public;

    /**
     * The admin-specific functionality of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      School_Sports_API_Admin    $admin    The admin-specific functionality.
     */
    protected $admin;

    /**
     * The realtime functionality of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      School_Sports_API_Realtime    $realtime    The realtime functionality.
     */
    protected $realtime;

    /**
     * Define the core functionality of the plugin.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if (defined('SCHOOL_SPORTS_API_VERSION')) {
            $this->version = SCHOOL_SPORTS_API_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'school-sports-api';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        try {
            $plugin_path = plugin_dir_path(dirname(__FILE__));
            $files_to_include = array(
                'includes/class-school-sports-api-loader.php',
                'includes/class-school-sports-api-i18n.php',
                'admin/class-school-sports-api-admin.php',
                'includes/class-school-sports-api-public.php',
                'includes/class-school-sports-api-api.php',
                'includes/class-school-sports-api-cache.php',
                'includes/class-school-sports-api-shortcodes.php',
                'includes/class-school-sports-api-realtime.php'
            );
            
            // Include each file if it exists
            foreach ($files_to_include as $file) {
                $file_path = $plugin_path . $file;
                if (file_exists($file_path)) {
                    require_once $file_path;
                } else {
                    // Log missing file but continue
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                        error_log('School Sports API: Missing file - ' . $file_path);
                    }
                }
            }
            
            // Initialize loader if class exists
            if (class_exists('School_Sports_API_Loader')) {
                $this->loader = new School_Sports_API_Loader();
            } else {
                throw new Exception('Required class School_Sports_API_Loader not found');
            }
        } catch (Exception $e) {
            // Log error but don't crash
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('School Sports API Error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new School_Sports_API_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        try {
            // Initialize admin class
            $this->admin = new School_Sports_API_Admin($this->get_plugin_name(), $this->get_version());
    
            // Add basic hooks
            $this->loader->add_action('admin_enqueue_scripts', $this->admin, 'enqueue_styles');
            $this->loader->add_action('admin_enqueue_scripts', $this->admin, 'enqueue_scripts');
            
            // Add feature-specific hooks if methods exist
            if (method_exists($this->admin, 'add_options_page')) {
                $this->loader->add_action('admin_menu', $this->admin, 'add_options_page');
            }
            
            if (method_exists($this->admin, 'register_settings')) {
                $this->loader->add_action('admin_init', $this->admin, 'register_settings');
            }
            
            if (method_exists($this->admin, 'add_shortcode_popup')) {
                $this->loader->add_action('admin_footer', $this->admin, 'add_shortcode_popup');
            }
            
            if (method_exists($this->admin, 'register_shortcode_button')) {
                $this->loader->add_filter('mce_buttons', $this->admin, 'register_shortcode_button');
            }
            
            if (method_exists($this->admin, 'add_shortcode_button_script')) {
                $this->loader->add_filter('mce_external_plugins', $this->admin, 'add_shortcode_button_script');
            }
        } catch (Exception $e) {
            // Log error but don't crash
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('School Sports API Error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        try {
            // Initialize public class
            $this->public = new School_Sports_API_Public($this->get_plugin_name(), $this->get_version());
            
            // Get realtime instance from public class
            if (isset($this->public->realtime) && is_object($this->public->realtime)) {
                $this->realtime = $this->public->realtime;
            }
    
            // Add basic hooks
            $this->loader->add_action('wp_enqueue_scripts', $this->public, 'enqueue_styles');
            $this->loader->add_action('wp_enqueue_scripts', $this->public, 'enqueue_scripts');
            
            // Add feature-specific hooks if methods exist
            if (method_exists($this->public, 'register_shortcodes')) {
                $this->loader->add_action('init', $this->public, 'register_shortcodes');
            }
            
            if (method_exists($this->public, 'register_ajax_handlers')) {
                $this->loader->add_action('init', $this->public, 'register_ajax_handlers');
            }
            
            if (method_exists($this->public, 'init_websocket_server')) {
                $this->loader->add_action('init', $this->public, 'init_websocket_server');
            }
            
            if (method_exists($this->public, 'schedule_live_updates')) {
                $this->loader->add_action('init', $this->public, 'schedule_live_updates');
            }
            
            if (method_exists($this->public, 'add_shortcode_classes')) {
                $this->loader->add_filter('the_content', $this->public, 'add_shortcode_classes', 99);
            }
        } catch (Exception $e) {
            // Log error but don't crash
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('School Sports API Error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        if (isset($this->loader) && is_object($this->loader) && method_exists($this->loader, 'run')) {
            $this->loader->run();
        } else {
            // Log error but don't crash
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('School Sports API Error: Loader not properly initialized');
            }
        }
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    School_Sports_API_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Retrieve the public instance.
     *
     * @since     1.0.0
     * @return    School_Sports_API_Public    The public instance.
     */
    public function get_public() {
        return $this->public;
    }

    /**
     * Retrieve the realtime instance.
     *
     * @since     1.0.0
     * @return    School_Sports_API_Realtime    The realtime instance.
     */
    public function get_realtime() {
        return $this->realtime;
    }
}