<?php

/**
 * @package  WooCommerce MeMailPlatform
 */
/*
  Plugin Name: eMailPlatform for WooCommerce
  Plugin URI: https://emailplatform.com
  Description: Add subscribe to eMailPlatform on WooCommerce checkout page
  Version: 1.0.2
  WC tested up to: 5.1
  Author: Kasper Bang
  Author URI: https://github.com/kmbdk
  License: GPLv2 or later
  Text Domain: emailplatform-woocommerce
 */
/*
  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.
  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
  Copyright 2005-2015 Automattic, Inc.
 */

/** If this file is called directly, abort. */
defined('ABSPATH') or die('Go away silly human!');

/** Constants */
define('WC_Emailplatform_FILE', __FILE__);

/**
 * The main plugin class
 */
require_once( __DIR__ . '/includes/class-wc-emailplatform-plugin.php' );

function EMPWC() {
    return WC_Emailplatform_Plugin::get_instance();
}

// Get WC Emailplatform running.
add_action('plugins_loaded', 'EMPWC', 11);
