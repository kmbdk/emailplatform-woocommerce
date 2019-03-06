<?php
/**
 * WooCommerce eMailPlatform Handler
 *
 * @author              eMailPlatform
 * @package             WooCommerce eMailPlatform
 * 
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('WC_Emailplatform_Handler')) {

    /**
     * @class WC_Emailplatform_Handler
     */
    final class WC_Emailplatform_Handler {

        /**
         * Plugin singleton instance
         * @var WC_Emailplatform_Handler
         */
        private static $instance = null;

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
            $this->empwc = EMPWC();
            $this->register_hooks();
        }

//end function __construct

        /**
         * @return WC_Emailplatform_Handler
         */
        public static function get_instance() {

            if (empty(self::$instance)) {
                self::$instance = new self;
            }

            return self::$instance;
        }

        /**
         * order_status_changed function.
         *
         * @access public
         * @return void
         */
        public function order_status_changed($id, $status = 'new', $new_status = 'pending') {
            if ($this->empwc->is_valid() && $new_status === $this->empwc->occurs()) {
                // Get WC order
                $order = $this->wc_get_order($id);

                // get the wc_emailplatform_opt_in value from the post meta. "order_custom_fields" was removed with WooCommerce 2.1
                $subscribe_customer = get_post_meta($id, $this->namespace_prefixed('opt_in'), true);

                $order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;
                $order_billing_email = method_exists($order, 'get_billing_email') ? $order->get_billing_email() : $order->billing_email;
                $order_billing_first_name = method_exists($order, 'get_billing_first_name') ? $order->get_billing_first_name() : $order->billing_first_name;
                $order_billing_last_name = method_exists($order, 'get_billing_last_name') ? $order->get_billing_last_name() : $order->billing_last_name;

                // If the 'wc_emailplatform_opt_in' meta value isn't set
                // (because 'display_opt_in' wasn't enabled at the time the order was placed)
                // or the 'wc_emailplatform_opt_in' is yes, subscriber the customer
                if (!$subscribe_customer || empty($subscribe_customer) || 'yes' === $subscribe_customer) {
                    // log
                    $this->log(sprintf(__(__METHOD__ . '(): Subscribing customer (%s) to list %s', 'emailplatform-woocommerce'), $order_billing_email, $this->empwc->get_list()));

                    // subscribe
                    $this->subscribe($order_id, $order_billing_first_name, $order_billing_last_name, $order_billing_email, $this->empwc->get_list());
                }
            }
        }

        /**
         * Register plugin hooks
         *
         * @access public
         * @return void
         */
        public function register_hooks() {

            // We would use the 'woocommerce_new_order' action but first name, last name and email address (order meta) is not yet available,
            // so instead we use the 'woocommerce_checkout_update_order_meta' action hook which fires after the checkout process on the "thank you" page
            add_action('woocommerce_checkout_update_order_meta', array($this, 'order_status_changed'), 1000, 1);

            // hook into woocommerce order status changed hook to handle the desired subscription event trigger
            add_action('woocommerce_order_status_changed', array($this, 'order_status_changed'), 10, 3);

            $opt_in_checkbox_display_location = $this->empwc->opt_in_checkbox_display_location();

            // Maybe add an "opt-in" field to the checkout
            $opt_in_checkbox_display_location = !empty($opt_in_checkbox_display_location) ? $opt_in_checkbox_display_location : 'woocommerce_review_order_before_submit';

            // Old opt-in checkbox display locations
            $old_opt_in_checkbox_display_locations = array(
                'billing' => 'woocommerce_after_checkout_billing_form',
                'order' => 'woocommerce_review_order_before_submit',
            );

            // Map old billing/order checkbox display locations to new format
            if (array_key_exists($opt_in_checkbox_display_location, $old_opt_in_checkbox_display_locations)) {
                $opt_in_checkbox_display_location = $old_opt_in_checkbox_display_locations[$opt_in_checkbox_display_location];
            }

            add_action($opt_in_checkbox_display_location, array($this, 'maybe_add_checkout_fields'));

            // Maybe save the "opt-in" field on the checkout
            add_action('woocommerce_checkout_update_order_meta', array($this, 'maybe_save_checkout_fields'));

            add_action('wp_ajax_wc_emailplatform_test_emailplatform', array($this, 'ajax_test_emailplatform'));

            add_action('wp_ajax_wc_emailplatform_get_lists', array($this, 'ajax_get_lists'));

            add_action('wp_ajax_wc_emailplatform_get_fields', array($this, 'ajax_get_fields'));
            
        }

//end function ensure_tab

        /**
         * Return all lists from Emailplatform to be used in select fields
         *
         * @access public
         * @return array
         */
        public function ajax_test_emailplatform() {

            try {

                if (!isset($_POST['data'])) {
                    throw new Exception(__(__METHOD__ . ': $_POST[\'data\'] not provided.', 'emailplatform-woocommerce'));
                }

                if (!$_POST['data']['api_token'] || empty($_POST['data']['api_token'])) {

                    throw new Exception(__('Please enter an api token.', 'emailplatform-woocommerce'));
                }
                
                if (!$_POST['data']['api_username'] || empty($_POST['data']['api_username'])) {

                    throw new Exception(__('Please enter an api username.', 'emailplatform-woocommerce'));
                }

                $api_token = $_POST['data']['api_token'];
                $api_username = $_POST['data']['api_username'];

                $account = $this->empwc->emailplatform()->test_emailplatform($api_token, $api_username);

                $results = $account;
            } catch (Exception $e) {

                return $this->toJSON(array('error' => $e->getMessage()));
            }

            return $this->toJSON($results);
        }
        //end function ajax_test_emailplatform

        /**
         * Return all lists from Emailplatform to be used in select fields
         *
         * @access public
         * @return array
         */
        public function ajax_get_lists() {
            
            try {

                    if ( !$_POST['data']['api_token'] || empty( $_POST['data']['api_token'] ) || !$_POST['data']['api_username'] || empty( $_POST['data']['api_username'] ) ) {

                            return $this->toJSON( array( 0 => __( 'Enter your api token and username above to see your lists', 'emailplatform-woocommerce' ) ) );

                    }

                    $api_token = $_POST['data']['api_token'];
                    $api_username = $_POST['data']['api_username'];

                    $lists = $this->empwc->emailplatform($api_token, $api_username)->get_lists();

                    $results = array(0 => 'Select a list...') + $lists;

            }
            catch ( Exception $e ) {

                    return $this->toJSON( array( 'error' => $e->getMessage() ) );

            }

            return $this->toJSON( $results );

        }

//end function ajax_get_lists
        
        /**
         * Return merge fields (a.k.a. merge tags) for the passed Emailplatform List
         *
         * @access public
         * @return array
         */
        public function ajax_get_fields() {

            try {

                    if ( !$_POST['data']['api_token'] || empty( $_POST['data']['api_token'] ) || !$_POST['data']['api_username'] || empty( $_POST['data']['api_username'] || !$_POST['data']['list_id'] || empty( $_POST['data']['list_id'] ) ) ) {

                            return $this->toJSON( array( 0 => __( 'Please select list above before choosing fields', 'emailplatform-woocommerce' ) ) );

                    }

                    $api_token = $_POST['data']['api_token'];
                    $api_username = $_POST['data']['api_username'];
                    $list_id = $_POST['data']['list_id'];

                    $lists = $this->empwc->emailplatform($api_token, $api_username)->get_fields($list_id);

                    $results = array(0 => 'Select a field...') + $lists;

            }
            catch ( Exception $e ) {

                    return $this->toJSON( array( 'error' => $e->getMessage() ) );

            }

            return $this->toJSON( $results );
            
        }

//end function ajax_get_merge_fields

        private function toJSON($response) {

            // Commented out due to json_encode not preserving quotes around Emailplatform ids
            // header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }

//end function toJSON

        private function namespace_prefixed($suffix) {
            return $this->namespace . '_' . $suffix;
        }

        /**
         * WooCommerce 2.2 support for wc_get_order
         *
         * @since 1.2.1
         *
         * @access private
         * @param int $order_id
         * @return void
         */
        private function wc_get_order($order_id) {
            if (function_exists('wc_get_order')) {
                return wc_get_order($order_id);
            } else {
                return new WC_Order($order_id);
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
         * subscribe function.
         *
         * @access public
         * @param int $order_id
         * @param mixed $first_name
         * @param mixed $last_name
         * @param mixed $email
         * @param string $listid (default: 'false')
         * @return void
         */
        public function subscribe($order_id, $first_name, $last_name, $email, $list_id = 'false') {
            if (!$email) {
                return; // Email is required
            }

            if ('false' == $list_id) {
                $list_id = $this->empwc->get_list();
            }
            
            if($this->empwc->double_opt_in() ? $confirmed = 0 : $confirmed = 1);
            
            $contactFields = array();
            if($this->empwc->get_firstname() > 0){
                $contactFields[] = array(
                    'fieldid' => $this->empwc->get_firstname(),
                    'value' => $first_name
                );
            }
            if($this->empwc->get_lastname() > 0){
                $contactFields[] = array(
                    'fieldid' => $this->empwc->get_lastname(),
                    'value' => $last_name
                );
            }
            
            // Set subscription options
            $subscribe_options = array(
                'listid' => $list_id,
                'emailaddress' => $email,
                'mobile' => false,
                'mobilePrefix' => false,
                'contactFields' => $contactFields,
                'confirmed' => $confirmed,
                'add_to_autoresponders' => 1
            );

            // Allow hooking into subscription options
            $options = apply_filters($this->namespace_prefixed('subscribe_options'), $subscribe_options, $order_id);

            // Extract options into variables
            extract($options);

            // Log
            $this->log(sprintf(__(__METHOD__ . '(): Subscribing customer to eMailPlatform: %s', 'emailplatform-woocommerce'), print_r($options, true)));

            do_action($this->namespace_prefixed('before_subscribe'), $subscribe_options, $order_id);

            // Call API
            $api_response = $this->empwc->emailplatform()->subscribe($list_id, $email, $contactFields, $confirmed);

            do_action($this->namespace_prefixed('after_subscribe'), $subscribe_options, $order_id);

            // Log api response
            $this->log(sprintf(__(__METHOD__ . '(): eMailPlatform API response: %s', 'emailplatform-woocommerce'), print_r($api_response, true)));

            if ($api_response === false) {
                // Format error message
                $error_response = sprintf(__(__METHOD__ . '(): WooCommerce eMailPlatform subscription failed: %s (%s)', 'emailplatform-woocommerce'), $this->empwc->emailplatform()->get_error_message(), $this->empwc->emailplatform()->get_error_code());

                // Log
                $this->log($error_response);

                // New hook for failing operations
                do_action($this->namespace_prefixed('subscription_failed'), $email, array('list_id' => $list_id, 'order_id' => $order_id));

                // Email admin
                $admin_email = get_option('admin_email');
                $admin_email = apply_filters($this->namespace_prefixed('admin_email'), $admin_email);
                
                wp_mail($admin_email, __('WooCommerce eMailPlatform subscription failed', 'emailplatform-woocommerce'), $error_response);
                
            } else {
                // Hook on success
                do_action($this->namespace_prefixed('subscription_success'), $email, array('list_id' => $list_id, 'order_id' => $order_id));
            }
            
        }

        /**
         * Add the opt-in checkbox to the checkout fields (to be displayed on checkout).
         *
         * @since 1.1
         */
        function maybe_add_checkout_fields() {

            if ($this->empwc->is_valid()) {
                if ($this->empwc->display_opt_in()) {
                    do_action($this->namespace_prefixed('before_opt_in_checkbox'));

                    echo apply_filters($this->namespace_prefixed('opt_in_checkbox'), '<p class="form-row emailplatform-woocommerce-opt-in"><label class="checkbox" for="wc_emailplatform_opt_in"><input type="checkbox" name="wc_emailplatform_opt_in" id="wc_emailplatform_opt_in" class="input-checkbox" value="yes"' . ($this->empwc->opt_in_checkbox_default_status() == 'checked' ? ' checked="checked"' : '') . '/> ' . esc_html($this->empwc->opt_in_label()) . '</label></p>' . "\n", $this->empwc->opt_in_checkbox_default_status(), $this->empwc->opt_in_label(), $this->empwc->opt_in_checkbox_default_status(), $this->empwc->opt_in_label());
                    do_action($this->namespace_prefixed('after_opt_in_checkbox'));
                }
            }
        }

        /**
         * When the checkout form is submitted, save opt-in value.
         *
         * @version 1.1
         */
        function maybe_save_checkout_fields($order_id) {
            if ($this->empwc->display_opt_in()) {
                $opt_in = isset($_POST[$this->namespace_prefixed('opt_in')]) ? 'yes' : 'no';

                update_post_meta($order_id, $this->namespace_prefixed('opt_in'), $opt_in);
            }
        }

        /**
         * Helper log function for debugging
         *
         * @since 1.2.2
         */
        private function log($message) {
            if ($this->empwc->debug_enabled()) {
                $logger = new WC_Logger();

                if (is_array($message) || is_object($message)) {
                    $logger->add('emailplatform-woocommerce', print_r($message, true));
                } else {
                    $logger->add('emailplatform-woocommerce', $message);
                }
            }
        }

    }

}
