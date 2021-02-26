<?php
/**
 * Plugin Name: Time Tracker for WooCommerce
 * Description: Allows you to track Time you spend on projects.
 * Version: 1.0
 * Author: DansArt
 * Author URI: http://dans-art.ch
 * Text Domain: wctt
 * Domain Path: /languages
 * License: GPLv2 or later
 * @todo Optimize the paths (plugin dir)
 *       Add German language
 * 
 */
require_once('include/classes/tt-helper-class.php');
require_once('include/classes/tt-main-class.php');
require_once('include/classes/tt-ajax-class.php');

$tt = new WcTimeTracker;
