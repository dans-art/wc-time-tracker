<?php

/** 
 * Plugin Name: Time Tracker for WooCommerce
 * Description: This class handels the Ajax requests. Gets Informations form the DB and saves new values.
 * Version: 1.0
 * Author: DansArt
 * Author URI: http://dans-art.ch
 * License: GPLv2 or later
 */

class WcTimeTrackerAjax
{

    /**
     * Get all Products / Items from a Order
     *
     * @param integer $orderid - The ID of a Order
     * @return string A Json String with the Information about the Products
     */
    public function get_products($orderid)
    {
        global $wpdb;
        $ret_array = array('error' => '', 'success' => '');

        $orderid = (int) $orderid;
        $query = "SELECT `orders`.`order_item_name`,
        `orders`.`order_item_id`,
                `m1`.`meta_value` AS '_qty'
                FROM `" . $wpdb->prefix . "woocommerce_order_items` `orders`
                LEFT JOIN `" . $wpdb->prefix . "woocommerce_order_itemmeta` `m1`
                    ON `orders`.`order_item_id` = `m1`.`order_item_id`
                WHERE `orders`.`order_id` LIKE (" . $orderid . ")
                AND `m1`.`meta_key` = '_qty'";

        $db_result = $wpdb->get_results($query);
        if (empty($db_result) or !is_array($db_result)) {
            $ret_array['error'] = sprintf(__("No items found for order #%s", "wctt"), $orderid);
        } else {
            $ret_array['success'] = $this->format_products_select($db_result);
        }
        return json_encode($ret_array);
    }

    /**
     * Saves the new quantity to the db
     *
     * @param integer $order_item_id - The ID of a Order
     * @param float $qty - Number of Items / Hours to save
     * @param string $meta_date - Date in the format: d-m-Y
     * @param string $meta_description - Description of the Task.
     * @return string A Json String with the Information about the success or errors of the saving
     */
    public function save_qty($order_item_id, $qty, $meta_date, $meta_description)
    {
        global $wpdb;
        $ret_array = array('error' => '', 'success' => '');
        $qty =  (float) $qty;
        $order_item_id =  (int) $order_item_id;

        $updateField = array('meta_value' => $qty);
        $where = array('order_item_id' => $order_item_id, 'meta_key' => '_qty');
        //Update the Time / Amount
        $db_result = $wpdb->update($wpdb->prefix . 'woocommerce_order_itemmeta', $updateField, $where);

        //Insert or Update the Meta
        $existing_meta =  $this->get_item_meta($order_item_id, $meta_date);
        $existing_meta_desc =  $existing_meta['meta_description'];
        $replace = array();
        $replace['meta_id'] = $existing_meta['meta_id'];
        $replace['order_item_id'] = $order_item_id;
        $replace['meta_key'] = $meta_date;
        $replace['meta_value'] = (!empty($existing_meta_desc)) ? $existing_meta_desc . PHP_EOL . $meta_description : $meta_description;
        $db_result_meta_desc = $wpdb->_insert_replace_helper($wpdb->prefix . 'woocommerce_order_itemmeta', $replace, null, 'REPLACE');

        if (!$db_result) {
            $ret_array['error'] = __("Failed to save amount for item", "wctt");
        } else if (!$db_result_meta_desc) {
            $ret_array['error'] = __("Failed to save description for item", "wctt");
        } else {
            $ret_array['success'] = __("Amount saved!", "wctt");;
        }
        return json_encode($ret_array);
    }

    /**
     * Prepares the products for output. Wraps a option tag around it.
     *
     * @param object $data - Database result as Object
     * @return string html Options
     */
    public function format_products_select($data)
    {
        $options = "<option value='null'>" . __("Select a item", "wctt") . "</option>";
        $options .= "<option value='link'>" . __("Add new item", "wctt") . "</option>";
        foreach ($data as $obj) {
            $options .= "<option value='" . $obj->order_item_id . "' data-current_qty='" . $obj->_qty . "'>" . $obj->order_item_name . " (" . $obj->_qty . "h)</option>";
        }
        return $options;
    }

    /**
     * Returns the Description and Meta_id of the Order Meta Item from the DB
     *
     * @param integer $order_item_id - The ID of the Item of the Order
     * @param integer $meta_key - Meta Key of the Item. Should be the current date (d-m-Y)
     * @return void
     */
    public function get_item_meta($order_item_id, $meta_key)
    {
        global $wpdb;
        $query = "SELECT `ordersmeta`.*
                FROM `" . $wpdb->prefix . "woocommerce_order_itemmeta` `ordersmeta`
                WHERE `ordersmeta`.`order_item_id` LIKE (" . $order_item_id . ")
                AND `ordersmeta`.`meta_key` = '" . $meta_key . "'";

        $db_result = $wpdb->get_results($query);
        $ret_array = array();
        $ret_array['meta_id'] = (isset($db_result[0]->meta_id)) ? $db_result[0]->meta_id : NULL;
        $ret_array['meta_description'] = (isset($db_result[0]->meta_value)) ? $db_result[0]->meta_value : "";
        return $ret_array;
    }

    /**
     * Gets the description from the $_POST / $_REQUEST Array
     *
     * @return string The given Description or "Various Tasks"
     */
    public function get_meta_description_from_post()
    {
        $meta_description =  (!empty($_REQUEST['meta_description'])) ? $_REQUEST['meta_description'] : __('Various Tasks', 'wctt');
        return $meta_description;
    }

    /**
     * Gets the date from the $_POST / $_REQUEST Array
     * Expects time in Milliseconds
     *      
     * @return string - Current or given Time formated as 'd-m-Y'
     */
    public function get_meta_date_from_post()
    {
        if (!empty($_REQUEST['meta_date'])) {
            $meta_date =  (int) $_REQUEST['meta_date'];
            return date('d-m-Y', $meta_date / 1000);
        } else {
            return date('d-m-Y');
        }
    }
}
