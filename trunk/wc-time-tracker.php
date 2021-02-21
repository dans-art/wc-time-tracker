<?php
/**
 * Plugin Name: Time Tracker for WooCommerce
 * Description: Allows you to track Time you spend on projects.
 * Version: 0.1
 * Author: DansArt
 * Author URI: http://dans-art.ch
 * Text Domain: wctt
 * Domain Path: /languages
 * License: GPLv2 or later
 *
 * 
 */
require_once('include/classes/tt-helper-class.php');
require_once('include/classes/tt-main-class.php');
require_once('include/classes/tt-ajax-class.php');

//Use this to get the right path...
//s(plugin_dir_path(__FILE__));

$tt = new WcTimeTracker;
?>