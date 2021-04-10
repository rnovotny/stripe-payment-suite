<?php
/*
	Plugin Name: Payment Suite
	Description: Start taking payments with Stripe in 2 minutes
	Author: Ryan Novotny
	Author URI: https://ryanmnovotny.com
	License: GPLv2
	Requires PHP: 5.6
	Version: 0.0.1
*/

//DEFINE SOME USEFUL CONSTANTS
define( 'RN_SPS_PLUGIN_VER', '0.0.5' );
define( 'RN_SPS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RN_SPS_PLUGINS_URL', plugins_url( '', __FILE__ ) );
define( 'RN_SPS_PLUGIN_FILE', __FILE__ );
define( 'RN_SPS_PLUGINS_BASENAME', plugin_basename(__FILE__) );
	
//LOAD CORE
include_once( RN_SPS_PLUGIN_DIR . '/includes/functions.php' );
include_once( RN_SPS_PLUGIN_DIR . '/includes/admin/admin.php' );
include_once( RN_SPS_PLUGIN_DIR . '/includes/checkout/checkout.php' );

