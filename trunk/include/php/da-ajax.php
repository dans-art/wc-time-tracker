<?php

/** 
 * Plugin Name: Time Tracker for WooCommerce
 * Description: Custom Ajax function. Optimized to be faster than the standard WP Ajax
 * Version: 1.0
 * Author: DansArt
 * Author URI: http://dans-art.ch
 * License: GPLv2 or later
 */

//mimic the actuall admin-ajax
define('DOING_AJAX', true);

if (!isset($_POST['action']))
    die('Not allowed');

//make sure you update this line 
//to the relative location of the wp-load.php
require_once('../../../../../wp-load.php');

if (!is_user_logged_in()) {
    $ret_array = array('error' => 'You are not logged in. Access denied', 'success' => '');
    echo json_encode($ret_array);
    die();
}

//only allow user to make canges if he can edit orders
if (!current_user_can('edit_shop_orders')) {
    die('Not allowed');
}

//Typical headers
header('Content-Type: text/html');
send_nosniff_header();

//Disable caching
header('Cache-Control: no-cache');
header('Pragma: no-cache');

//Do the Actions
do_action('da_ajax_tt-ajax');
do_action('da_ajax_nopriv_tt-ajax');
