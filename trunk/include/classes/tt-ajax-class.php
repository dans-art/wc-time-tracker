<?php

class WcTimeTrackerAjax
{

    /**
     * Get all Products from a Order
     * @todo Make query save!!, change table prefix
     *
     * @param [type] $orderid
     * @return void
     */
    public function get_products($orderid)
    {
        global $wpdb;
        $ret_array = array('error' => '','success' => '');

        $orderid = (int) $orderid;
        $query = "SELECT `orders`.`order_item_name`,
        `orders`.`order_item_id`,
                `m1`.`meta_value` AS '_qty'
                FROM `".$wpdb->prefix."woocommerce_order_items` `orders`
                LEFT JOIN `".$wpdb->prefix."woocommerce_order_itemmeta` `m1`
                    ON `orders`.`order_item_id` = `m1`.`order_item_id`
                WHERE `orders`.`order_id` LIKE (".$orderid.")
                AND `m1`.`meta_key` = '_qty'";

        $db_result = $wpdb->get_results($query);
        if (empty($db_result) OR !is_array($db_result)) {
            $ret_array['error'] = sprintf(__("No items found for order #%s", "wctt"),$orderid);
        }else{
            $ret_array['success'] = $this->format_products_select($db_result);
        }
        return json_encode($ret_array);
    }

    /**
     * Saves the new quantity to the db
     *
     * @param [type] $order_item_id
     * @param [type] $qty
     * @return void
     */
    public function save_qty($order_item_id, $qty){
        global $wpdb;
        $ret_array = array('error' => '','success' => '');
        $qty =  (float) $qty;
        $order_item_id =  (integer) $order_item_id;
        
        $updateField = array('meta_value' => $qty);
        $where = array('order_item_id' => $order_item_id, 'meta_key' => '_qty');

        $db_result = $wpdb->update($wpdb->prefix.'woocommerce_order_itemmeta',$updateField, $where);
        if (!$db_result) {
            $ret_array['error'] = __("Failed to save amount for item", "wctt");
        }else{
            $ret_array['success'] = __("Amount saved!", "wctt");;
        }
        return json_encode($ret_array);
    }

    /**
     * Prepares the products for output. Wraps a option around it.
     *
     * @param [type] $data
     * @return [string] 
     */
    public function format_products_select($data)
    {
        $options = "<option value='null'>" . __("Select a item", "wctt") . "</option>";
        $options .= "<option value='link'>" . __("Add new item", "wctt") . "</option>";
        foreach ($data as $obj) {
            $options .= "<option value='" . $obj->order_item_id . "' data-current_qty='".$obj -> _qty."'>" . $obj->order_item_name . " (".$obj -> _qty."h)</option>";
        }
        return $options;
    }
}
