<?php
/**
 * Plugins Main File
 * 
 * PHP version 5.6.x | 7.x | 8.x
 * 
 * @category  PLugins
 * @package   Wordpress
 * @author    Pejman Kheyri <pejmankheyri@gmail.com>
 * @copyright 2021 All rights reserved.
 */

/*
Plugin Name: send sms via sms.ir
Version: 1.0.0
Description: send sms via sms.ir
Author: pejmankheyri@gmail.com
Contributors: pejmankheyri
WC requires at least: 3.0.0
WC tested up to: 5.0.0
*/

if (!defined('ABSPATH')) { 
    header('Location: https://github.com/pejmankheyri/SMSIR-Woocommerce');exit; 
}

if (!defined('PS_WOO_SMS_VERSION'))
    define('PS_WOO_SMS_VERSION', '1.0.0');

if (!defined('PS_WOO_SMS_PLUGIN_PATH'))
    define('PS_WOO_SMS_PLUGIN_PATH', plugins_url('', __FILE__));

if (!defined('PS_WOO_SMS_PLUGIN_LIB_PATH'))
    define('PS_WOO_SMS_PLUGIN_LIB_PATH', dirname(__FILE__). '/includes');

/**
 * Uninstall Function
 *
 * @return void
 */
function woocommerceIRSMSProUninstall()
{
    update_option('redirect_to_woo_sms_about_page', 'no');
    update_option('redirect_to_woo_sms_about_page_check', 'no');
}
register_activation_hook(__FILE__, 'woocommerceIRSMSProUninstall');
register_deactivation_hook(__FILE__, 'woocommerceIRSMSProUninstall');

/**
 * Adding File Resources
 *
 * @return void
 */
function woocommerceIRSMSIr()
{
    global $persianwoosms;
    include_once PS_WOO_SMS_PLUGIN_LIB_PATH. '/requirement.php';
    include_once PS_WOO_SMS_PLUGIN_LIB_PATH. '/class.settings.php';
    include_once PS_WOO_SMS_PLUGIN_LIB_PATH. '/class.gateways.php';
    include_once PS_WOO_SMS_PLUGIN_LIB_PATH. '/class.bulk.send.php';
    $persianwoosms = WoocommerceIR_Settings_SMS::init();
}

add_action('plugins_loaded', 'woocommerceIRSMSIr');