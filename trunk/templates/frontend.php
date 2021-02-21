<script type="text/javascript">
    const da_ajaxurl = "<?php echo site_url(); ?>/wp-content/plugins/wc-time-tracker/include/php/da-ajax.php";
</script>
<script src="<?php echo site_url(); ?>/wp-content/plugins/wc-time-tracker/include/scripts/tt-main-scripts.js"></script>

<?php
$orders = (is_array($data['orders'])) ? $data['orders'] : array();


?>
<select id="tt_customer_select">
    <option value='null'><?php echo __("Select a Customer", "wctt"); ?></option>;
    <?php
    foreach ($orders as $order) {
        /*
         MAKE this as a loop?
         foreach ($order as $key -> $value)
        */
        $date = date('d.m.Y', strtotime($order->post_date));
        echo "<option value='" . $order->ID . "'>" . $date . " - " . $order->_billing_company . " | " . $order->_billing_first_name . " " . $order->_billing_last_name . " | " . $order->order_total . " " . $order->currency . "</option>";
    }

    ?>
</select>

<div id="tt_customer_products">
    <select id="tt_customer_products_select"></select>
    <div id="tt_customer_products_error"></div>
</div>

<div id="tt_timetracker" style="display: none;">
    <button id="tt_action_button">Start</button>
    <div id="tt_start_value">
        <span class='label'>Start Qty:</span>
        <span class='value'></span>
    </div>
    <div id="tt_start_time" data-stime="">
        <span class='label'>Start time:</span>
        <span class='value'></span>
    </div>
    <div id="tt_stop_time" data-etime="">
        <span class='label'>Stop time:</span>
        <span class='value'></span>
    </div>
    <div id="tt_current_time">
        <span class='label'>Current time:</span>
        <span class='value'></span>
    </div>
    <div id="tt_log">
    </div>
</div>

