<div id="wc_time_tracker">
    <script type="text/javascript">
        const da_ajaxurl = "<?php echo site_url(); ?>/wp-content/plugins/wc-time-tracker/include/php/da-ajax.php";
        const wp_backend_url = "<?php echo get_admin_url(); ?>post.php?action=edit&post=";
        const wp_frontend_url = "<?php echo site_url(); ?>/wp-content/plugins/wc-time-tracker/";
    </script>

    <?php
    $orders = (is_array($data['orders'])) ? $data['orders'] : array();
    ?>
    <div id="tt_customers">
        <select id="tt_customer_select">
            <option value='null'><?php echo __("Select a Customer", "wctt"); ?></option>;
            <?php
            foreach ($orders as $order) {
                $date = date('d.m.Y', strtotime($order->post_date));
                echo "<option value='" . $order->ID . "' data-company='" . htmlspecialchars($order->_billing_company, ENT_QUOTES) .
                    "' data-name='" . htmlspecialchars($order->_billing_first_name, ENT_QUOTES) . " " . htmlspecialchars($order->_billing_last_name, ENT_QUOTES) . "'>" .
                    $date . " - " . $order->_billing_company . " | " . $order->_billing_first_name . " " . $order->_billing_last_name . " | " . $order->order_total . " " . $order->currency .
                    "</option>";
            }
            ?>
        </select>
    </div>
    <div id="tt_customer_products" style="display: none;">
        <select id="tt_customer_products_select"></select>
    </div>

    <div id="tt_timetracker" style="display: none;">
        <button id="tt_action_button" class="button">Start</button>
        <div id="tt_start_value" style="display: none;">
            <span class='value'></span>
        </div>
        <div id="tt_description">
            <span class='label'><?php echo __("Description", "wctt"); ?></span>
            <span class='value'>
                <textarea id="tt_meta_description"></textarea>
            </span>
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

    </div>
    <div id="tt_log">
    </div>

</div>