<?php

class wc_time_tracker extends wc_time_tracker_helper
{

    public function __construct()
    {
        add_shortcode('time_tracker', [$this, 'tt_render_frontend']);

        //register Ajax
        //add_action('wp_ajax_tt-ajax', [$this, 'tt_ajax']);
        //add_action('wp_ajax_nopriv_tt-ajax', [$this, 'tt_ajax']);
        add_action('da_ajax_tt-ajax', [$this, 'tt_ajax']);
        add_action('da_ajax_nopriv_tt-ajax', [$this, 'tt_ajax']);

        add_action( 'wp_loaded',[$this, 'tt_allow_wc_decimals'] );
        
    }



    public function tt_render_frontend()
    {
        $data = array();
        $data['orders'] = $this -> tt_get_orders();
        return $this->tt_load_template('templates/frontend.php', $data);
    }
    /**
     * Gets the Orders form the Database
     * @todo Improve the query
     *
     * @return Array With database Results as object
     */
    public function tt_get_orders(){
        global $wpdb;
        $query = "SELECT `posts`.`post_date`,
        `posts`.`ID`, 
        `m1`.`meta_value` AS 'order_total',
        `m2`.`meta_value` AS 'currency',
        `m3`.`meta_value` AS '_billing_company',
        `m4`.`meta_value` AS '_billing_first_name',
        `m5`.`meta_value` AS '_billing_last_name'
        FROM `wploc_posts` `posts`
        LEFT JOIN `wploc_postmeta` `m1`
            ON `posts`.`ID` = `m1`.`post_id`
        LEFT JOIN `wploc_postmeta` `m2`
            ON `posts`.`ID` = `m2`.`post_id`
        LEFT JOIN `wploc_postmeta` `m3`
            ON `posts`.`ID` = `m3`.`post_id`
        LEFT JOIN `wploc_postmeta` `m4`
            ON `posts`.`ID` = `m4`.`post_id`
        LEFT JOIN `wploc_postmeta` `m5`
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
        if(empty($db_result)){
            return __("No Orders found!","wctt");
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
        $ajax = new wc_time_tracker_ajax();
        $action =  $_REQUEST['action'];
        $orderid =  $_REQUEST['orderid'];
        $order_item_id =  $_REQUEST['order_item_id'];
        $qty =  $_REQUEST['qty'];
        switch ($action) {
            case 'get_products':
                echo $ajax->get_products($orderid);
                break;
            case 'save_qty':
                echo $ajax->save_qty($order_item_id,$qty);
                break;
            default:
                break;
        }
        exit();
    }
}
