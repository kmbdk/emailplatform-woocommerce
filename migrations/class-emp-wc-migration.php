<?php

abstract class WC_Emailplatform_Migration {

    protected $emailplatform;
    protected $settings;
    protected $namespace = 'wc_emailplatform';
    protected $current_version;
    protected $target_version;
    protected $setting_keys;
    protected $settings_key = 'emailplatform_woocommerce_settings';

    public function __construct($current_version, $target_version) {

        $this->setting_keys = array(
            'api_token',
            'api_username',
            'enabled',
            'list',
            'firstname',
            'lastname',
            'display_opt_in',
            'occurs',
            'double_opt_in',
            'opt_in_label',
            'opt_in_checkbox_default_status',
            'opt_in_checkbox_display_location',
        );

        $this->settings = array();

        $this->current_version = $current_version;
        $this->target_version = $target_version;

        if (!$this->load_settings())
            return;

        if (empty($this->settings['api_token']))
            return;
        
        if (empty($this->settings['api_username']))
            return;

        require_once( WC_Emailplatform_DIR . 'includes/class-wc-emailplatform.php' );
        $this->emailplatform = new WC_Emailplatform($this->settings['api_token'], $this->setting['api_username']);
    }

    /**
     * [save_settings description]
     * @return [type] [description]
     */
    public function load_settings() {

        if ($this->current_version === '1.0.X') {

            if (!$this->settings = get_option($this->settings_key)) {
                return false;
            }

            if (!is_array($this->settings)) {
                return false;
            }
        } else {

            foreach ($this->setting_keys as $key) {
                $this->settings[$key] = get_option($this->namespace_prefixed($key));
            }
        }

        return true;
    }

    /**
     * [save_settings description]
     * @return [type] [description]
     */
    public function save_settings() {

        if ($this->target_version === '1.0.X') {

            update_option($this->settings_key, $this->settings);
        } else {

            foreach ($this->settings as $key => $value) {
                update_option($this->namespace_prefixed($key), $value);
            }
        }
    }

    protected function namespace_prefixed($suffix) {
        return $this->namespace . '_' . $suffix;
    }

}
