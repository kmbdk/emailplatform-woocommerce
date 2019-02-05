<?php

if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * Minimal Emailplatform API v1.1 wrapper
 *
 * @class       WC_Emailplatform_API
 * @version     v1.1
 * @package     WooCommerce eMailPlatform
 * @author      eMailPlatform
 * 
 */
class WC_Emailplatform_API {

    /**
     * @var string
     */
    public $api_token;

    /**
     * @var string
     */
    public $api_username;

    /**
     * @var string
     */
    private $api_root = 'https://api.mailmailmail.net/v1.1/';

    /**
     * @var boolean
     */
    private $debug = false;

    /**
     * @var array
     */
    private $last_response;

    /**
     * @var WP_Error
     */
    private $last_error;

    /**
     * @var WC_Logger
     */
    private $log;

    /**
     * Create a new instance
     * @param string $api_token eMailPlatform API key
     * @param boolean $debug  Whether or not to log API calls
     */
    function __construct($api_token, $api_username, $debug = false) {

        $this->debug = $debug;

        if ($this->debug === true) {
            $this->log = new WC_Logger();
        }

        $this->api_token = $api_token;
        $this->api_username = $api_username;
    }

//end function __construct

    /**
     * @param string $resource
     * @param array $args
     *
     * @return mixed
     */
    public function get($resource, $args = array()) {

        try {

            $url = $this->api_root . $resource;

            // open connection
            $ch = curl_init();
            if (!empty($args)) {
                $url .= "?" . http_build_query($args, '', '&');
            }

            // set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->api_header());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            // disable for security
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            // execute post
            $result = json_decode(curl_exec($ch), true);

            // close connection
            curl_close($ch);

            return $this->api_response_validation($result);
            
        } catch (Exception $error) {
            return $error->GetMessage();
        }
    }

//end function post

    /**
     * @param string $resource
     * @param array $args
     *
     * @return mixed
     */
    public function post($resource, $args = array()) {
        try {
            
            $url = $this->api_root . $resource;
            
            // open connection
            $ch = curl_init();

            // add the setting to the fields
            $encodedData = http_build_query($args, '', '&');

            // set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->api_header());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_POST, count($args));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedData);
            // disable for security
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            // execute post
            $result = json_decode(curl_exec($ch));

            // close connection
            curl_close($ch);
            return $this->api_response_validation($result);
            
        } catch (Exception $error) {
            return $error->GetMessage();
        }
    }

    private function api_response_validation($response) {

        if (isset($response[0]) AND $response[0] == false)
            return false;
        
        if ($response == false)
            return false;

        if (is_string($response))
            return false;
        
        return $response;
        
    }

    private function api_header() {
        return array(
            "Accept: application/json; charset=utf-8",
            "ApiUsername: " . $this->api_username,
            "ApiToken: " . $this->api_token
        );
    }

//end function post

    /**
     * Performs the underlying HTTP request.
     * @param  string $method HTTP method (GET|POST|PUT|PATCH|DELETE)
     * @param  string $resource Emailplatform API resource to be called
     * @param  array  $args   array of parameters to be passed
     * @return array          array of decoded result
     */
    private function api_request($method, $resource, $args = array()) {

        $this->reset();

        $url = $this->api_root . $resource;

        global $wp_version;

        $request_args = array(
            'method' => $method,
            'headers' => array(
                'Accept' => 'application/json; charset=utf-8',
                'ApiUsername' => $this->api_token,
                'ApiToken' => $this->api_username,
                'User-Agent' => 'emailplatform-woocommerce/' . WC_Emailplatform_VERSION . '; WordPress/' . $wp_version . '; ' . get_bloginfo('url'),
            ),
        );

        // attach arguments (in body or URL)
        if ($method === 'GET') {
            $url = add_query_arg($args, $url);
        } else {
            $request_args['body'] = http_build_query($args);
        }

        $raw_response = wp_remote_request($url, $request_args);

        return $raw_response['body'];

        $this->maybe_log($url, $method, $args, $raw_response);

        if (is_wp_error($raw_response)) {

            $this->last_error = new WP_Error('wc-emp-api-request-error', $raw_response->get_error_message(), $this->format_error($resource, $method, $raw_response));

            return false;
        } elseif (is_array($raw_response) && $raw_response['response']['code'] && floor($raw_response['response']['code']) / 100 >= 4) {

            $json = wp_remote_retrieve_body($raw_response);

            $error = json_decode($json, true);

            $this->last_error = new WP_Error('wc-emp-api-request-error', $error, $this->format_error($resource, $method, $raw_response));

            return false;
        } else {

            $json = wp_remote_retrieve_body($raw_response);

            $result = json_decode($json, true);

            return $result;
        }
    }

//end function api_request

    /**
     * Empties all data from previous response
     */
    private function reset() {
        $this->last_response = null;
        $this->last_error = null;
    }

    /**
     * Conditionally log Emailplatform API Call
     * @param  string $resource Emailplatform API Resource
     * @param  string $method   HTTP Method
     * @param  array $args      HTTP Request Body
     * @param  array $response  WP HTTP Response
     * @return void
     */
    private function maybe_log($resource, $method, $args, $response) {

        if ($this->debug === true) {
            $this->log->add('emailplatform-woocommerce', "Emailplatform API Call RESOURCE: $resource \n METHOD: $method \n BODY: " . print_r($args, true) . " \n RESPONSE: " . print_r($response, true));
        }
    }

    /**
     * Formats api_request info for inclusion in WP_Error $data
     * @param  [type] $resource [description]
     * @param  [type] $method   [description]
     * @param  [type] $response [description]
     * @return [type]           [description]
     */
    private function format_error($resource, $method, $response) {
        return array(
            'resource' => $resource,
            'method' => $method,
            'response' => json_encode($response),
        );
    }

    /**
     * has_api_token function.
     *
     * @access public
     * @return void
     */
    public function has_api_token() {

        return !empty($this->api_token);
    }

//end function has_api_token

    /**
     * @return array|WP_Error
     */
    public function get_last_response() {
        return $this->last_response;
    }

    /**
     * Returns error code from error property
     * @return string error code
     */
    public function get_error_code() {

        $last_error = $this->last_error;
        if (is_wp_error($last_error)) {
            return $last_error->get_error_code();
        }
        return null;
    }

//end get_error_code

    /**
     * Returns error message from error property
     * @return string error message
     */
    public function get_error_message() {

        $last_error = $this->last_error;
        if (is_wp_error($last_error)) {
            return $last_error->get_error_message();
        }
        return null;
    }

}
