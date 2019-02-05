<?php
/**
 * WooCommerce Emailplatform Settings
 *
 * @author      eMailPlatform
 * @package     WooCommerce eMailPlatform
 *
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('WC_Settings_Emailplatform')) {

    /**
     * @class   WC_Settings_Emailplatform
     * @extends WC_Settings_Page
     */
    class WC_Settings_Emailplatform extends WC_Settings_Page {

        private static $instance;

        /**
         * Singleton instance
         *
         * @return WC_Settings_Emailplatform   WC_Settings_Emailplatform object
         */
        public static function get_instance() {

            if (empty(self::$instance)) {
                self::$instance = new self;
            }

            return self::$instance;
        }

        /**
         * Constructor
         *
         * @access public
         * @return void
         */
        public function __construct() {

            $this->id = 'emailplatform';
            $this->namespace = 'wc_' . $this->id;
            $this->label = __('eMailPlatform', 'emailplatform-woocommerce');
            $this->init();

            $this->register_hooks();
        }
        //end function __construct

        /**
         * api_token function.
         * @return string Emailplatform API Token
         */
        public function api_token() {
            return $this->get_option('api_token');
        }

        /**
         * api_username function.
         * @return string Emailplatform API Username
         */
        public function api_username() {
            return $this->get_option('api_username');
        }

        /**
         * is_enabled function.
         *
         * @access public
         * @return boolean
         */
        public function is_enabled() {
            return 'yes' === $this->get_option('enabled');
        }

        /**
         * occurs function
         * @return string
         */
        public function occurs() {
            return $this->get_option('occurs');
        }

        /**
         * get_list function.
         *
         * @access public
         * @return string Emailplatform list ID
         */
        public function get_list() {
            return $this->get_option('list');
        }

        /**
         * get_firstname function.
         *
         * @access public
         * @return string Emailplatform list ID
         */
        public function get_firstname() {
            return $this->get_option('firstname');
        }

        /**
         * get_lastname function.
         *
         * @access public
         * @return string Emailplatform list ID
         */
        public function get_lastname() {
            return $this->get_option('lastname');
        }

        /**
         * double_opt_in function.
         *
         * @access public
         * @return boolean
         */
        public function double_opt_in() {
            return 'yes' === $this->get_option('double_opt_in');
        }

        /**
         * display_opt_in function.
         *
         * @access public
         * @return boolean
         */
        public function display_opt_in() {
            return 'yes' === $this->get_option('display_opt_in');
        }

        /**
         * opt_in_label function.
         *
         * @access public
         * @return string
         */
        public function opt_in_label() {
            return $this->get_option('opt_in_label');
        }

        /**
         * opt_in_checkbox_default_status function.
         *
         * @access public
         * @return string
         */
        public function opt_in_checkbox_default_status() {
            return $this->get_option('opt_in_checkbox_default_status');
        }

        /**
         * opt_in_checkbox_display_location function.
         *
         * @access public
         * @return string
         */
        public function opt_in_checkbox_display_location() {
            return $this->get_option('opt_in_checkbox_display_location');
        }

        /**
         * has_list function.
         *
         * @access public
         * @return boolean
         */
        public function has_list() {
            if ($this->get_list()) {
                return true;
            }
            return false;
        }

        /**
         * has_api_token function.
         *
         * @access public
         * @return boolean
         */
        public function has_api_token() {
            $api_token = $this->api_token();
            return !empty($api_token);
        }

        /**
         * is_valid function.
         *
         * @access public
         * @return boolean
         */
        public function is_valid() {
            return $this->is_enabled() && $this->has_api_token() && $this->has_list();
        }

        /**
         * debug_enabled function.
         *
         * @access public
         * @return boolean
         */
        public function debug_enabled() {
            return 'yes' === $this->get_option('debug');
        }

        /**
         * Check if the user has enabled the plugin functionality, but hasn't provided an api key
         * */
        function checks() {
            // Check required fields
            if ($this->is_enabled() && !$this->has_api_token()) {
                // Show notice
                echo $this->get_message(sprintf(__('WooCommerce eMailPlatform error: Plugin is enabled but no api token or username provided. Please enter your api token and username <a href="%s">here</a>.', 'emailplatform-woocommerce'), WC_Emailplatform_SETTINGS_URL)
                );
            }
        }

        public function init() {

            $this->api_token = $this->get_option('api_token');
            $this->api_username = $this->get_option('api_username');

            $this->enabled = $this->get_option('enabled');
        }

        public function get_option($option_suffix) {

            return get_option($this->namespace_prefixed($option_suffix));
        }

        /**
         * Register plugin hooks
         *
         * @access public
         * @return void
         */
        public function register_hooks() {

            // Hook in to add the Emailplatform tab
            add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_page'), 20);
            add_action('woocommerce_settings_' . $this->id, array($this, 'output'));
            add_action('woocommerce_settings_save_' . $this->id, array($this, 'save'));
            add_action('woocommerce_sections_' . $this->id, array($this, 'output_sections'));
            add_action('woocommerce_settings_saved', array($this, 'init'));

            // Hooks
            add_action('admin_notices', array($this, 'checks'));
            add_action('woocommerce_admin_field_sysinfo', array($this, 'sysinfo_field'), 10, 1);
        }

//end function ensure_tab
        // /**
        //  * Get sections
        //  *
        //  * @return array
        //  */
        public function get_sections() {

            $sections = array();

            $sections[''] = __('General', 'emailplatform-woocommerce');

            if ($this->has_api_token()) {
                $sections['troubleshooting'] = __('Troubleshooting', 'emailplatform-woocommerce');
            }

            return apply_filters('woocommerce_get_sections_' . $this->id, $sections);
        }

        /**
         * Output the settings
         */
        public function output() {

            global $current_section;

            $settings = $this->get_settings($current_section);

            WC_Admin_Settings::output_fields($settings);

            $this->wc_enqueue_js("
	 			(function($){

	 				$(document).ready(function() {
	 					WC_Emailplatform.init();
	 				});

	 			})(jQuery);
			");

            do_action('wc_emailplatform_after_settings_enqueue_js');
        }

        /**
         * Save settings
         */
        public function save() {
            global $current_section;

            $settings = $this->get_settings($current_section);

            WC_Admin_Settings::save_fields($settings);

            if (!isset($_POST['wc_emailplatform_api_token']) || empty($_POST['wc_emailplatform_api_token'])) {
                delete_transient('empwc_lists');
            }

            $wcemp = EMPWC();

            // Trigger reload of plugin settings
            $settings = $wcemp->settings(true);
        }

        /**
         * Get settings array
         *
         * @return array
         */
        public function get_settings($current_section = '') {

            $settings = array();

            if ('' === $current_section) {

                $settings = array(
                    array(
                        'title' => __('eMailPlatform', 'emailplatform-woocommerce'),
                        'type' => 'title',
                        'desc' => __('Enter your eMailPlatform settings below to control how WooCommerce integrates with your eMailPlatform account.', 'emailplatform-woocommerce'),
                        'id' => 'general_options',
                    ),
                );

                $settings[] = array(
                    'id' => $this->namespace_prefixed('api_username'),
                    'title' => __('API Username', 'emailplatform-woocommerce'),
                    'type' => 'text',
                    'desc' => 'Get your API Username from eMailPlatform support at support@emailplatform.com',
                    'placeholder' => __('Paste your eMailPlatform Username here', 'emailplatform-woocommerce'),
                    'default' => '',
                    'css' => 'min-width:350px;',
                    'desc_tip' => 'Your API Username is required for the plugin to communicate with your eMailplatform account.',
                );

                $settings[] = array(
                    'id' => $this->namespace_prefixed('api_token'),
                    'title' => __('API Token', 'emailplatform-woocommerce'),
                    'type' => 'text',
                    'desc' => 'Get your API Token from eMailPlatform support at support@emailplatform.com',
                    'placeholder' => __('Paste your eMailPlatform Token here', 'emailplatform-woocommerce'),
                    'default' => '',
                    'css' => 'min-width:350px;',
                    'desc_tip' => 'Your API Token is required for the plugin to communicate with your eMailplatform account.',
                );

                $settings[] = array(
                    'id' => $this->namespace_prefixed('enabled'),
                    'title' => __('Enable/Disable', 'emailplatform-woocommerce'),
                    'label' => __('Enable eMailPlatform Integration', 'emailplatform-woocommerce'),
                    'type' => 'checkbox',
                    'desc' => __('Enable/disable the plugin functionality.', 'emailplatform-woocommerce'),
                    'default' => 'yes',
                );

                $emailplatform_lists = array();
                if (!isset($this->emailplatform()->test_emailplatform()['error']))
                    $emailplatform_lists = $this->get_lists();

                $settings[] = array(
                    'id' => $this->namespace_prefixed('list'),
                    'title' => __('Main List', 'emailplatform-woocommerce'),
                    'type' => 'select',
                    'desc' => __('All subscribers will be added to this list.', 'emailplatform-woocommerce'),
                    'default' => '',
                    'options' => $emailplatform_lists,
                    'class' => 'wc-enhanced-select',
                    'css' => 'min-width: 350px;',
                    'desc_tip' => true,
                );

                //$settings = apply_filters($this->namespace_prefixed('settings_general_after_interest_groups'), $settings);

                $emailplatform_fields = array();
                if ((int) $this->get_list() > 0)
                    $emailplatform_fields = $this->get_fields($this->get_list());

                $settings[] = array(
                    'id' => $this->namespace_prefixed('firstname'),
                    'title' => __('Firstname', 'emailplatform-woocommerce'),
                    'type' => 'select',
                    'desc' => __('Firstname will be added to this field', 'emailplatform-woocommerce'),
                    'default' => '',
                    'options' => $emailplatform_fields,
                    'class' => 'wc-enhanced-select',
                    'css' => 'min-width: 350px;',
                    'desc_tip' => true,
                );

                $settings[] = array(
                    'id' => $this->namespace_prefixed('lastname'),
                    'title' => __('Lastname', 'emailplatform-woocommerce'),
                    'type' => 'select',
                    'desc' => __('Lastname will be added to this field', 'emailplatform-woocommerce'),
                    'default' => '',
                    'options' => $emailplatform_fields,
                    'class' => 'wc-enhanced-select',
                    'css' => 'min-width: 350px;',
                    'desc_tip' => true,
                );

                $settings[] = array(
                    'id' => $this->namespace_prefixed('occurs'),
                    'title' => __('Subscribe Event', 'emailplatform-woocommerce'),
                    'type' => 'select',
                    'desc' => __('Choose whether to subscribe customers as soon as an order is placed or after the order is processing or completed.', 'emailplatform-woocommerce'),
                    'class' => 'wc-enhanced-select',
                    'default' => 'pending',
                    'options' => array(
                        'pending' => __('Order Created', 'emailplatform-woocommerce'),
                        'processing' => __('Order Processing', 'emailplatform-woocommerce'),
                        'completed' => __('Order Completed', 'emailplatform-woocommerce'),
                    ),
                    'desc_tip' => true,
                );

                $settings[] = array(
                    'id' => $this->namespace_prefixed('double_opt_in'),
                    'title' => __('Double Opt-In', 'emailplatform-woocommerce'),
                    'desc' => __('Enable Double Opt-In', 'emailplatform-woocommerce'),
                    'type' => 'checkbox',
                    'default' => 'no',
                    'desc_tip' => __('Recomended to be enabled -> Subscribers will receive an opt-in email to confirm their subscription.', 'emailplatform-woocommerce'),
                );

                $settings[] = array(
                    'id' => $this->namespace_prefixed('opt_in_label'),
                    'title' => __('Checkbox Label', 'emailplatform-woocommerce'),
                    'type' => 'text',
                    'desc' => __('Text to be displayed on checkbox label', 'emailplatform-woocommerce'),
                    'default' => __('Subscribe to our newsletter', 'emailplatform-woocommerce'),
                    'css' => 'min-width:350px;',
                    'desc_tip' => true,
                );

                $settings[] = array(
                    'id' => $this->namespace_prefixed('opt_in_checkbox_default_status'),
                    'title' => __('Checkbox: default', 'emailplatform-woocommerce'),
                    'type' => 'select',
                    'desc' => __('The default value for checkbox. (may not be legal to set checked as default in your country)', 'emailplatform-woocommerce'),
                    'class' => 'wc-enhanced-select',
                    'default' => 'checked',
                    'options' => array(
                        'checked' => __('Checked', 'emailplatform-woocommerce'),
                        'unchecked' => __('Unchecked', 'emailplatform-woocommerce')
                    ),
                    'desc_tip' => true,
                );

                $settings[] = array(
                    'id' => $this->namespace_prefixed('opt_in_checkbox_display_location'),
                    'title' => __('Checkbox Location', 'emailplatform-woocommerce'),
                    'type' => 'select',
                    'desc' => __('Where to display the checkbox.', 'emailplatform-woocommerce'),
                    'class' => 'wc-enhanced-select',
                    'default' => 'woocommerce_review_order_before_submit',
                    'options' => array(
                        'woocommerce_checkout_before_customer_details' => __('Above customer details', 'emailplatform-woocommerce'),
                        'woocommerce_checkout_after_customer_details' => __('Below customer details', 'emailplatform-woocommerce'),
                        'woocommerce_checkout_before_order_review' => __('Order review above cart/product table.', 'emailplatform-woocommerce'),
                        'woocommerce_review_order_before_submit' => __('Order review above submit', 'emailplatform-woocommerce'),
                        'woocommerce_review_order_after_submit' => __('Order review below submit', 'emailplatform-woocommerce'),
                        'woocommerce_review_order_before_order_total' => __('Order review above total', 'emailplatform-woocommerce'),
                        'woocommerce_checkout_billing' => __('Above billing details', 'emailplatform-woocommerce'),
                        'woocommerce_checkout_shipping' => __('Above shipping details', 'emailplatform-woocommerce'),
                        'woocommerce_after_checkout_billing_form' => __('Below Checkout billing form', 'emailplatform-woocommerce'),
                        'woocommerce_checkout_before_terms_and_conditions' => __('Above Checkout Terms and Conditions', 'emailplatform-woocommerce'),
                        'woocommerce_checkout_after_terms_and_conditions' => __('Below Checkout Terms and Conditions', 'emailplatform-woocommerce'),
                    ),
                    'desc_tip' => true,
                );

                $settings = apply_filters($this->namespace_prefixed('settings_general'), $settings);

                $settings[] = array('type' => 'sectionend', 'id' => 'general_options');
            } elseif ('troubleshooting' === $current_section) {

                $label = __('Enable Logging', 'emailplatform-woocommerce');

                if (defined('WC_LOG_DIR')) {
                    $debug_log_url = add_query_arg('tab', 'logs', add_query_arg('page', 'wc-status', admin_url('admin.php')));
                    $debug_log_key = 'emailplatform-woocommerce-' . sanitize_file_name(wp_hash('emailplatform-woocommerce')) . '-log';
                    $debug_log_url = add_query_arg('log_file', $debug_log_key, $debug_log_url);

                    $label .= ' | ' . sprintf(__('%1$sView Log%2$s', 'emailplatform-woocommerce'), '<a href="' . esc_url($debug_log_url) . '">', '</a>');
                }

                $settings[] = array(
                    'title' => __('Troubleshooting', 'emailplatform-woocommerce'),
                    'type' => 'title',
                    'desc' => '',
                    'id' => 'troubleshooting_settings'
                );

                $settings[] = array(
                    'id' => $this->namespace_prefixed('debug'),
                    'title' => __('Debug Log', 'emailplatform-woocommerce'),
                    'desc' => $label,
                    'type' => 'checkbox',
                    'default' => 'no',
                    'desc_tip' => __('Enable logging eMailPlatform API calls. Only enable for troubleshooting purposes.', 'emailplatform-woocommerce'),
                );

                $settings[] = array(
                    'id' => 'sysinfo',
                    'title' => __('System Info', 'emailplatform-woocommerce'),
                    'type' => 'sysinfo',
                    'desc' => __('Copy the information below and send it to us when reporting an issue with the plugin.', 'emailplatform-woocommerce') . '<p/>',
                    'desc_tip' => '',
                );

                $settings[] = array('type' => 'sectionend', 'id' => 'troubleshooting_settings');

                $settings = apply_filters($this->namespace_prefixed('settings_troubleshooting'), $settings);
            }

            return apply_filters('woocommerce_get_settings_' . $this->id, $settings, $current_section);
        }

//end function get_settings

        private function namespace_prefixed($value) {
            return $this->namespace . '_' . $value;
        }

        /**
         * WooCommerce 2.1 support for wc_enqueue_js
         *
         * @since 1.2.1
         *
         * @access private
         * @param string $code
         * @return void
         */
        private function wc_enqueue_js($code) {
            if (function_exists('wc_enqueue_js')) {
                wc_enqueue_js($code);
            } else {
                global $woocommerce;
                $woocommerce->add_inline_js($code);
            }
        }

        /**
         * Get message
         * @return string Error
         */
        private function get_message($message, $type = 'error') {
            ob_start();
            ?>
            <div class="<?php echo $type ?>">
                <p><?php echo $message ?></p>
            </div>
            <?php
            return ob_get_clean();
        }

        /**
         * API Instance Singleton
         * @return Object
         */
        public function emailplatform($api_token = null, $api_username = null) {

            $empwc = EMPWC();

            return $empwc->emailplatform($api_token, $api_username);
        }

        /**
         * get_lists function.
         *
         * @access public
         * @return void
         */
        public function get_lists() {

            if ($this->emailplatform()) {
                $emailplatform_lists = $this->emailplatform()->get_lists();
            } else {
                return false;
            }

            if ($emailplatform_lists === false) {

                add_action('admin_notices', array($this, 'emailplatform_api_error_msg'));
                add_action('network_admin_notices', array($this, 'emailplatform_api_error_msg'));

                return false;
            }

            if (count($emailplatform_lists) === 0) {
                $default = array(
                    'no_lists' => __('Oops! No lists in your eMailPlatform account...', 'emailplatform-woocommerce'),
                );
                add_action('admin_notices', array($this, 'emailplatform_no_lists_found'));
            } else {
                $default = array(
                    0 => __('Select a list...', 'emailplatform-woocommerce'),
                );
            }
            $emailplatform_lists = $default + $emailplatform_lists;

            return $emailplatform_lists;
        }

        /**
         * Inform the user they don't have any Emailplatform lists
         */
        public function emailplatform_no_lists_found() {
            echo $this->get_message(sprintf(__('Oops! There are no lists in your eMailPlatform account. %sClick here%s to create one.', 'emailplatform-woocommerce'), '<a href="https://client3.mailmailmail.net/" target="_blank">', '</a>'));
        }

        /**
         * get_fields function.
         *
         * @access public
         * @return void
         */
        public function get_fields($listid) {

            if ($this->emailplatform()) {
                $emailplatform_fields = $this->emailplatform()->get_fields($listid);
            } else {
                return false;
            }

            if ($emailplatform_fields === false) {

                add_action('admin_notices', array($this, 'emailplatform_api_error_msg'));
                add_action('network_admin_notices', array($this, 'emailplatform_api_error_msg'));

                return false;
            }

            if (count($emailplatform_fields) === 0) {
                $default = array(
                    'no_lists' => __('Oops! No fields in your eMailPlatform account...', 'emailplatform-woocommerce'),
                );
                add_action('admin_notices', array($this, 'emailplatform_no_fields_found'));
            } else {
                $default = array(
                    0 => __('Select a field...', 'emailplatform-woocommerce'),
                );
            }
            $emailplatform_fields = $default + $emailplatform_fields;

            return $emailplatform_fields;
        }

        /**
         * Inform the user they don't have any Emailplatform lists
         */
        public function emailplatform_no_fields_found() {
            echo $this->get_message(sprintf(__('Oops! There are no fields in your eMailPlatform account. %sClick here%s to create one.', 'emailplatform-woocommerce'), '<a href="https://client3.mailmailmail.net/" target="_blank">', '</a>'));
        }

        /**
         * Display message to user if there is an issue with the Emailplatform API call
         *
         * @since 1.0
         * @param void
         * @return html the message for the user
         */
        public function emailplatform_api_error_msg() {
            echo $this->get_message(
                    sprintf(__('Unable to load lists from eMailPlatform: (%s) %s. ', 'emailplatform-woocommerce'), $this->emailplatform()->get_error_code(), $this->emailplatform()->get_error_message()) .
                    sprintf(__('Please check your Settings %ssettings%s.', 'emailplatform-woocommerce'), '<a href="' . WC_Emailplatform_SETTINGS_URL . '">', '</a>')
            );
        }

//end function emailplatform_api_error_msg

        /**
         * Helper log function for debugging
         *
         * @since 1.2.2
         */
        private function log($message) {
            if ($this->debug_enabled()) {
                $logger = new WC_Logger();

                if (is_array($message) || is_object($message)) {
                    $logger->add('emailplatform-woocommerce', print_r($message, true));
                } else {
                    $logger->add('emailplatform-woocommerce', $message);
                }
            }
        }

        public function sysinfo_field($value) {

            // $option_value = self::get_option( $value['id'], $value['default'] );
            $option_value = EMP_System_Info::get_system_info();
            // Description handling
            $field_description = WC_Admin_Settings::get_field_description($value);
            extract($field_description);
            ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr($value['id']); ?>"><?php echo esc_html($value['title']); ?></label>
            <?php echo $tooltip_html; ?>
                </th>
                <td class="forminp forminp-<?php echo sanitize_title($value['type']) ?>">
            <?php echo $description; ?>

                    <textarea
                        name="<?php echo esc_attr($value['id']); ?>"
                        id="<?php echo esc_attr($value['id']); ?>"
                        style="font-family: Menlo,Monaco,monospace;display: block; overflow: auto; white-space: pre; width: 800px; height: 400px;<?php echo esc_attr($value['css']); ?>"
                        class="<?php echo esc_attr($value['class']); ?>"
                        placeholder="<?php echo esc_attr($value['placeholder']); ?>"
                        readonly="readonly" onclick="this.focus(); this.select()"
            <?php //echo implode( ' ', $custom_attributes );  ?>
                        ><?php echo esc_textarea($option_value); ?></textarea>
                </td>
            </tr>
                        <?php
        }

    }

    //end class WC_Emailplatform

    return WC_Settings_Emailplatform::get_instance();
}
