<?php

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * Minimal Emailplatform helper
 *
 * @class       WC_Emailplatform
 * @version     1.0
 * @package     WooCommerce eMailPlatform
 * @author      eMailPlatform
 * 
 */
class WC_Emailplatform {

    /**
     * @var WC_Emailplatform_API
     */
    public $api;
    public $api_token;
    public $api_username;
    public $debug;

    /**
     * Create a new instance
     * @param string $api_token Emaiplatform API Token
     * @param string $api_username Emailplatform API Username
     */
    function __construct($api_token, $api_username, $debug = false) {

        $this->api_token = $api_token;

        $this->api_username = $api_username;

        $this->debug = $debug;

        require_once( WC_Emailplatform_DIR . 'includes/class-wc-emailplatform-api.php' );
        $this->api = new WC_Emailplatform_API($api_token, $api_username, $debug);
    }

//end function __construct

    /**
     * Test account
     *
     * @access public
     * @return mixed
     */
    public function test_emailplatform($api_token = null, $api_username = null) {

        $resource = 'Test/TestUserToken';

        $api = $this->api;

        if (!empty($api_token) && !empty($api_username)) {
            $api = new WC_Emailplatform_API($api_token, $api_username, $this->debug);
        }

        $test = json_decode($api->post($resource));

        if ($test == false) {
            return array(
                'error' => 'Incorrect user credentials'
            );
        }
        return true;
    }

//end function test_emailplatform

    /**
     * Get list
     *
     * @access public
     * @return mixed
     */
    public function get_lists($args = array()) {


        $resource = 'Users/GetLists';

        $response = $this->api->get($resource, $args);

        if (!$response) {
            return false;
        }

        $lists = $response;

        $results = array();

        foreach ($lists as $list) {
            $results[(string) $list['listid']] = $list['name'];
        }


        return $results;
    }

//end function get_lists

    /**
     * Subscribe the user to the list
     * @param  string $list_id         The Emailplatform list ID
     * @param  string $email_address   The user's email address
     * @param  string $email_type      html|text
     * @param  array $merge_fields     Array of Emailplatform Merge Tags
     * @param  boolean $double_opt_in  Whether to send a double opt-in email to confirm subscription
     * @return mixed $response         The Emailplatform API response
     */
    public function subscribe($list_id, $email_address, $contactFields, $confirmed) {
        
        $args = array(
            'listid' => $list_id,
            'emailaddress' => $email_address,
            'mobile' => false,
            'mobilePrefix' => false,
            'contactFields' => $contactFields,
            'add_to_autoresponders' => 1,
            'skip_listcheck' => false,
            'confirmed' => $confirmed
        );

        $resource = 'Subscribers/AddSubscriberToList';

        $response = $this->api->post($resource, $args);

        if (!$response) {
            return false;
        }

        return $response;
    }

//end function subscribe

    /**
     * Unsubscribe the user from the list
     * @param  string $list_id         The Emailplatform list ID
     * @param  string $email_address   The user's email address
     * @return mixed $response         The Emailplatform API response
     */
    public function unsubscribe($list_id, $email_address) {

        $args = array(
            'listid' => $list_id,
            'emailaddress' => $email_address
        );

        $resource = 'Subscribers/UnsubscribeSubscriberEmail';

        $response = $this->api->post($resource, $args);

        if (!$response) {
            return false;
        }

        return $response;
    }

//end function subscribe

    /**
     * Get merge fields
     *
     * @access public
     * @param string $list_id
     * @return mixed
     */
    public function get_fields($list_id) {
        
        $resource = 'Lists/GetCustomFields';

        $args = array(
            'listids' => $list_id
        );

        $response = $this->api->get($resource, $args);
        
        if (!$response) {
            return false;
        }
        
        $contactFields = $response;

        $results = array();

        foreach ($contactFields as $field) {
            // only allow text fields
            if($field['fieldtype'] !== 'text'){
                continue;
            }
            
            $results[(string) $field['fieldid']] = $field['fieldname'];
        }
        
        return $results;
        
    }

//end function get_merge_fields

    /**
     * Returns error code from error property
     * @return string error code
     */
    public function get_error_code() {

        return $this->api->get_error_code();
    }

//end get_error_code

    /**
     * Returns error message from error property
     * @return string error message
     */
    public function get_error_message() {

        return $this->api->get_error_message();
    }

//end get_error_message
}

//end class WC_Emailplatform
