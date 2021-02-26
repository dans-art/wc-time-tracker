<?php

/** 
 * Plugin Name: Time Tracker for WooCommerce
 * Description: This class handels the main functions of the Plugin. Registering of actions and adding Shortcodes.
 * Version: 1.0
 * Author: DansArt
 * Author URI: http://dans-art.ch
 * License: GPLv2 or later
 */

class WcTimeTracker extends WcTimeTrackerHelper
{
    /**
     * Registers Shortcode and Actions for front- and backend
     */
    public function __construct()
    {
        if (!is_admin()) {
            add_shortcode('time_tracker', [$this, 'tt_render_frontend']);

            //register Ajax
            add_action('da_ajax_tt-ajax', [$this, 'tt_ajax']);
            add_action('da_ajax_nopriv_tt-ajax', [$this, 'tt_ajax']);
            add_action('wp_loaded', [$this, 'tt_enqueue_frontend_scripts_n_styles']);
        } else {
            //if admin page. Placeholder for later...?
            add_action('admin_init', [$this, 'tt_allow_wc_decimals']);
        }
    }

    /**
     * Loads the frontend template from /templates
     *
     * @return string The contents of the template file
     */
    public function tt_render_frontend()
    {
        $data = array();
        $data['orders'] = $this->tt_get_orders();
        return $this->tt_load_template('templates/frontend.php', $data);
    }
    /**
     * Gets the Orders form the Database
     *
     * @return array With database Results as object
     */
    public function tt_get_orders()
    {
        global $wpdb;
        $query = "SELECT `posts`.`post_date`,
        `posts`.`ID`, 
        `m1`.`meta_value` AS 'order_total',
        `m2`.`meta_value` AS 'currency',
        `m3`.`meta_value` AS '_billing_company',
        `m4`.`meta_value` AS '_billing_first_name',
        `m5`.`meta_value` AS '_billing_last_name'
        FROM `" . $wpdb->prefix . "posts` `posts`
        LEFT JOIN `" . $wpdb->prefix . "postmeta` `m1`
            ON `posts`.`ID` = `m1`.`post_id`
        LEFT JOIN `" . $wpdb->prefix . "postmeta` `m2`
            ON `posts`.`ID` = `m2`.`post_id`
        LEFT JOIN `" . $wpdb->prefix . "postmeta` `m3`
            ON `posts`.`ID` = `m3`.`post_id`
        LEFT JOIN `" . $wpdb->prefix . "postmeta` `m4`
            ON `posts`.`ID` = `m4`.`post_id`
        LEFT JOIN `" . $wpdb->prefix . "postmeta` `m5`
            ON `posts`.`ID` = `m5`.`post_id`
        WHERE `posts`.`post_type` = 'shop_order' 
        AND `posts`.`post_status` NOT LIKE 'wc-completed' 
        AND `m1`.`meta_key` = '_order_total'
        AND `m2`.`meta_key` = '_order_currency'
        AND `m3`.`meta_key` = '_billing_company'
        AND `m4`.`meta_key` = '_billing_first_name'
        AND `m5`.`meta_key` = '_billing_last_name'
        ORDER BY `posts`.`post_date` DESC";

        $db_result = $wpdb->get_results($query);
        if (empty($db_result)) {
            return __("No Orders found!", "wctt");
        }
        return $db_result;
    }

    /**
     * Main Method for handling the Ajax Calls
     *
     * @return string echoes the output of the ajax function
     */
    public function tt_ajax()
    {
        $ajax = new WcTimeTrackerAjax();
        $action =  (isset($_REQUEST['action'])) ? $_REQUEST['action'] : null;
        $orderid =  (isset($_REQUEST['orderid'])) ? $_REQUEST['orderid'] : null;
        $order_item_id =  (isset($_REQUEST['order_item_id'])) ? $_REQUEST['order_item_id'] : null;
        $qty =  (isset($_REQUEST['qty'])) ? $_REQUEST['qty'] : null;
        $meta_date =  $ajax->get_meta_date_from_post();
        $meta_description =  $ajax->get_meta_description_from_post();
        switch ($action) {
            case 'get_products':
                echo $ajax->get_products($orderid);
                break;
            case 'save_qty':
                echo $ajax->save_qty($order_item_id, $qty, $meta_date, $meta_description);
                break;
            default:
                break;
        }
        exit();
    }
}
