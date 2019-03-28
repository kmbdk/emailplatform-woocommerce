<?php

/**
 * Main Plugin Class
 *
 * @package WooCommerce eMailPlatform
 */

/**
 * WooCommerce eMailplatform plugin main class
 */
final class WC_Emailplatform_Plugin {

    /**
     * Plugin version
     *
     * @var string
     */
    private static $version = '1.0.3';

    /**
     * Plugin singleton instance
     *
     * @var WC_Emailplatform_Plugin
     */
    private static $instance;

    /**
     * Plugin namespace
     *
     * @var string
     */
    private $namespace = 'wc_emailplatform';

    /**
     * Plugin settings
     *
     * @var array
     */
    private $settings;

    /**
     * Plugin Emailplatform helper instance
     *
     * @var WC_Emailplatform
     */
    private $emailplatform;

    /**
     * Plugin compatibility checker
     *
     * @var WC_Emailplatform_Compatibility
     */
    public $compatibility;

    /**
     * Returns the plugin version
     *
     * @return string
     */
    public static function version() {
        return self::$version;
    }

    /**
     * Singleton instance
     *
     * @return WC_Emailplatform_Plugin   WC_Emailplatform_Plugin object
     */
    public static function get_instance() {

        if (empty(self::$instance) && !( self::$instance instanceof WC_Emailplatform_Plugin )) {

            self::$instance = new WC_Emailplatform_Plugin();
            self::$instance->define_constants();

            self::$instance->save_settings();
            self::$instance->settings();
            self::$instance->includes();
            self::$instance->emailplatform();
            self::$instance->handler = WC_Emailplatform_Handler::get_instance();
            self::$instance->compatibility = WC_Emailplatform_Compatibility::get_instance();
            self::$instance->admin_notices = new WC_Emailplatform_Admin_Notices();
            self::$instance->load_plugin_textdomain();

            self::update();
            self::$instance->add_hooks();
            do_action('wc_emailplatform_loaded');
        }

        return self::$instance;
    }

//end function instance

    /**
     * Gets the plugin db settings
     *
     * @param  boolean $refresh refresh the settings from DB.
     * @return array  The plugin db settings
     */
    public function settings($refresh = false) {

        if (empty($this->settings) || true === $refresh) {

            $defaults = require( WC_Emailplatform_DIR . 'config/default-settings.php' );
            $defaults = apply_filters('wc_emailplatform_default_settings', $defaults);
            $settings = array();

            foreach ($defaults as $key => $default_value) {

                $setting_value = get_option($this->namespace_prefixed($key));

                $settings[$key] = $setting_value ? $setting_value : $default_value;
            }

            $merged_settings = apply_filters('wc_emailplatform_settings', array_merge($defaults, $settings));

            $this->settings = $merged_settings;

            $this->emailplatform($settings['api_token'], $settings['api_username']);
            
        }

        return $this->settings;
    }

    /**
     * Returns the api token.
     *
     * @return string Emailplatform API Token
     */
    public function api_token() {
        return $this->settings['api_token'];
    }

    /**
     * Returns the api username.
     *
     * @return string Emailplatform API Username
     */
    public function api_username() {
        return $this->settings['api_username'];
    }

    /**
     * Whether or not the plugin functionality is enabled.
     *
     * @access public
     * @return boolean
     */
    public function is_enabled() {
        return 'yes' === $this->settings['enabled'];
    }

    /**
     * Whether or not a main list has been selected.
     *
     * @access public
     * @return boolean
     */
    public function has_list() {
        $has_list = false;

        if ($this->get_list()) {
            $has_list = true;
        }
        return apply_filters('wc_emailplatform_has_list', $has_list);
    }

    /**
     * When the subscription should be triggered.
     *
     * @return string
     */
    public function occurs() {
        return $this->settings['occurs'];
    }

    /**
     * Returns the selected list.
     *
     * @access public
     * @return string eMailPlatform list ID
     */
    public function get_list() {
        return $this->settings['list'];
    }
    
    /**
     * Returns the selected list.
     *
     * @access public
     * @return string eMailPlatform list ID
     */
    public function get_firstname() {
        return $this->settings['firstname'];
    }
    
    /**
     * Returns the selected list.
     *
     * @access public
     * @return string eMailPlatform list ID
     */
    public function get_lastname() {
        return $this->settings['lastname'];
    }

    /**
     * Whether or not double opt-in is selected.
     *
     * @access public
     * @return boolean
     */
    public function double_opt_in() {
        return 'yes' === $this->settings['double_opt_in'];
    }

    /**
     * Whether or not to display opt-in checkbox to user.
     *
     * @access public
     * @return boolean
     */
    public function display_opt_in() {
        return 'yes' === $this->settings['display_opt_in'];
    }

    /**
     * Opt-in label.
     *
     * @access public
     * @return string
     */
    public function opt_in_label() {
        return $this->settings['opt_in_label'];
    }

    /**
     * Opt-in checkbox default status.
     *
     * @access public
     * @return string
     */
    public function opt_in_checkbox_default_status() {
        return $this->settings['opt_in_checkbox_default_status'];
    }

    /**
     * Opt-in checkbox display location.
     *
     * @access public
     * @return string
     */
    public function opt_in_checkbox_display_location() {
        return $this->settings['opt_in_checkbox_display_location'];
    }

    /**
     * Whether or not an api key has been set.
     *
     * @access public
     * @return boolean
     */
    public function has_api_token() {
        $api_token = $this->api_token();
        return !empty($api_token);
    }

    /**
     * Whether or not the configuration is valid.
     *
     * @access public
     * @return boolean
     */
    public function is_valid() {
        return $this->is_enabled() && $this->has_api_token() && $this->has_list();
    }

    /**
     * Whether or not debug is enabled.
     *
     * @access public
     * @return boolean
     */
    public function debug_enabled() {
        return 'yes' === $this->settings['debug'];
    }

    /**
     * Saves the settings back to the DB
     *
     * @return void
     */
    public function save_settings() {

        $settings = $this->settings();

        foreach ($settings as $key => $value) {
            update_option($this->namespace_prefixed($key), $value);
        }
    }

//end function save_settings

    /**
     * Gets the Emailplatform Helper
     *
     * @param  string  $api_token Emailplatform API Token.
     * @param string $api_username Emailplatform API Username
     * @param  boolean $debug   Debug mode enabled/disabled.
     * @return WC_Emailplatform  Emailplatform Helper class
     */
    public function emailplatform($api_token = null, $api_username = null, $debug = false) {

        $settings = $this->settings();

        if (empty($this->emailplatform) || !is_null($api_token) || !is_null($api_username)) {

            $api_token = $api_token ? $api_token : $settings['api_token'];
            $api_username = $api_username ? $api_username : $settings['api_username'];
            $debug = $debug ? $debug : $settings['debug'];

            require_once( WC_Emailplatform_DIR . 'includes/class-wc-emailplatform.php' );
            $this->emailplatform = new WC_Emailplatform($api_token, $api_username, $debug);

            //delete_transient('wcemailplatform_lists');
        }

        return $this->emailplatform;
    }

//end function emailplatform

    /**
     * Define Plugin Constants.
     */
    private function define_constants() {

        // Minimum supported version of WordPress.
        $this->define('WC_Emailplatform_MIN_WP_VERSION', '3.5.1');

        // Minimum supported version of WooCommerce.
        $this->define('WC_Emailplatform_MIN_WC_VERSION', '2.2.0');

        // Minimum supported version of PHP.
        $this->define('WC_Emailplatform_MIN_PHP_VERSION', '5.4.0');

        // Plugin version.
        $this->define('WC_Emailplatform_VERSION', self::version());

        // Plugin Folder Path.
        $this->define('WC_Emailplatform_DIR', plugin_dir_path(WC_Emailplatform_FILE));
        
        // Plugin Folder URL.
        $this->define('WC_Emailplatform_URL', plugin_dir_url(WC_Emailplatform_FILE));

        $settings_url = admin_url('admin.php?page=wc-settings&tab=emailplatform');

        $this->define('WC_Emailplatform_SETTINGS_URL', $settings_url);
    }

//function define_constants

    /**
     * Define constant if not already set.
     *
     * @param  string      $name  Constant name.
     * @param  string|bool $value Constant value.
     * @return void
     */
    private function define($name, $value) {
        if (!defined($name)) {
            define($name, $value);
        }
    }

//function define

    /**
     * Include required core plugin files
     *
     * @return void
     */
    public function includes() {

        require_once( WC_Emailplatform_DIR . 'includes/lib/class-emp-system-info.php' );

        require_once( WC_Emailplatform_DIR . 'includes/helper-functions.php' );

        require_once( WC_Emailplatform_DIR . 'includes/class-wc-emailplatform-compatibility.php' );

        require_once( WC_Emailplatform_DIR . 'includes/class-wc-emailplatform-admin-notices.php' );

        require_once( WC_Emailplatform_DIR . 'includes/class-wc-emailplatform-api.php' );

        require_once( WC_Emailplatform_DIR . 'includes/class-wc-emailplatform.php' );

        require_once( WC_Emailplatform_DIR . 'includes/class-wc-emailplatform-handler.php' );
    }

//end function includes

    /**
     * Add plugin hooks
     *
     * @return void
     */
    private function add_hooks() {

        /** Register hooks that are fired when the plugin is activated and deactivated. */
        register_activation_hook(WC_Emailplatform_FILE, array(__CLASS__, 'activate'));
        register_deactivation_hook(WC_Emailplatform_FILE, array(__CLASS__, 'deactivate'));

        // Add the "Settings" links on the Plugins administration screen.
        if (is_admin()) {

            add_filter('plugin_action_links_' . plugin_basename(WC_Emailplatform_FILE), array($this, 'action_links'));

            add_filter('woocommerce_get_settings_pages', array($this, 'add_emailplatform_settings'));

            add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        }
    }

//end function add_hooks

    /**
     * Load Localization files.
     *
     * Note: the first-loaded translation file overrides any following ones if the same translation is present.
     *
     * Locales found in:
     *      - WP_LANG_DIR/plugins/emailplatform-woocommerce/emailplatform-woocommerce-{lang}_{country}.mo
     *      - WP_CONTENT_DIR/plugins/emailplatform-woocommerce/languages/emailplatform-woocommerce-{lang}_{country}.mo
     *
     * @return void
     */
    public function load_plugin_textdomain() {

        // Set filter for plugin's languages directory.
        $woocommerce_emailplatform_lang_dir = dirname(plugin_basename(WC_Emailplatform_FILE)) . '/languages/';

        // Traditional WordPress plugin locale filter.
        // get locale in {lang}_{country} format (e.g. en_US).
        $locale = apply_filters('plugin_locale', get_locale(), 'emailplatform-woocommerce');

        $mofile = sprintf('%1$s-%2$s.mo', 'emailplatform-woocommerce', $locale);

        // Look for wp-content/languages/emailplatform-woocommerce/emailplatform-woocommerce-{lang}_{country}.mo
        $mofile_global1 = WP_LANG_DIR . '/emailplatform-woocommerce/' . $mofile;

        // Look in wp-content/languages/plugins/emailplatform-woocommerce
        $mofile_global2 = WP_LANG_DIR . '/plugins/emailplatform-woocommerce/' . $mofile;

        if (file_exists($mofile_global1)) {

            load_textdomain('emailplatform-woocommerce', $mofile_global1);
        } elseif (file_exists($mofile_global2)) {

            load_textdomain('emailplatform-woocommerce', $mofile_global2);
        } else {

            // Load the default language files.
            load_plugin_textdomain('emailplatform-woocommerce', false, $woocommerce_emailplatform_lang_dir);
        }
    }

//end function load_plugin_textdomain

    /**
     * Add Settings link to plugins list
     *
     * @param  array $links Plugin links.
     * @return array       Modified plugin links
     */
    public function action_links($links) {
        $plugin_links = array(
            '<a href="' . WC_Emailplatform_SETTINGS_URL . '">' . __('Settings', 'emailplatform-woocommerce') . '</a>',
        );

        return array_merge($plugin_links, $links);
    }

//end function action_links

    /**
     * Add the Emailplatform settings tab to WooCommerce
     *
     * @param  array $settings  Emailplatform settings.
     * @return array Settings.
     */
    function add_emailplatform_settings($settings) {

        if (!is_array($settings)) {
            $settings = array();
        }

        $settings[] = require_once( WC_Emailplatform_DIR . 'includes/class-wc-settings-emailplatform.php' );

        return $settings;
    }

//end function add_emailplatform_settings

    /**
     * Load scripts required for admin
     *
     * @access public
     * @return void
     */
    public function enqueue_scripts() {

        // Plugin scripts and styles.
        wp_register_script('emailplatform-woocommerce-admin', WC_Emailplatform_URL . 'assets/js/emailplatform-woocommerce-admin.js', array('jquery'), self::version());
        wp_register_style('emailplatform-woocommerce', WC_Emailplatform_URL . 'assets/css/style.css', array(), self::version());

        // Localize javascript messages.
        $translation_array = array(
            'connecting_to_emailplatform' => __('Connecting to eMailPlatform', 'emailplatgform-woocommerce'),
            'error_loading_account' => __('Error. Please check your api token and user.', 'emailplatform-woocommerce')
        );
        wp_localize_script('emailplatform-woocommerce-admin', 'WC_Emailplatform_Messages', $translation_array);

        // Scripts.
        wp_enqueue_script('emailplatform-woocommerce-admin');

        // Styles.
        wp_enqueue_style('emailplatform-woocommerce');
    }

//end function enqueue_scripts

    /**
     * Handles running plugin upgrades if necessary
     *
     * @return void
     */
    public static function update() {

        require_once( 'class-wc-emailplatform-migrator.php' );

        WC_Emailplatform_Migrator::migrate(self::version());
    }

//end function update

    /**
     * Plugin activate function.
     *
     * @access public
     * @static
     * @param mixed $network_wide Network activate.
     * @return void
     */
    public static function activate($network_wide = false) {

        self::update();
    }

//end function activate

    /**
     * Plugin deactivate function.
     *
     * @access public
     * @static
     * @param mixed $network_wide Network activate.
     * @return void
     */
    public static function deactivate($network_wide) {

        // Placeholder.
    }

//end function deactivate

    /**
     * Check whether WooCommerce Emailplatform is network activated
     *
     * @since 1.0
     * @return bool
     */
    public static function is_network_activated() {
        return is_multisite() && ( function_exists('is_plugin_active_for_network') && is_plugin_active_for_network('emailplatform-woocommerce/emailplatform-woocommerce.php') );
    }

    /**
     * Returns namespace prefixed value
     *
     * @param  string $suffix  The suffix to prefix.
     * @return string
     */
    private function namespace_prefixed($suffix) {

        return $this->namespace . '_' . $suffix;
    }

// end function namespace_prefixed
}

//end final class WC_Emailplatform_Plugin
