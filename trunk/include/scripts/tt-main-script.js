/** 
 * Plugin Name: Time Tracker for WooCommerce
 * Description: Javascript functions for the plugin. Requires jQuery. 
 * Version: 1.0
 * Author: DansArt
 * Author URI: http://dans-art.ch
 * License: GPLv2 or later
 * @todo: Minify the JS
 */

//Starts Push Service
var swRegistration = null;

/**
 * Adds Eventlistener for customer_select - change
 * Adds Eventlistener for Starting the Timetracker - click
 * Initialising the Service Worker for Notifications
 */
jQuery(document).ready(function ($) {

    /**
     * Runs if customer is selected
     * Shows products / positions of customer
     */
    $('#tt_customer_select').change(function () {
        if ($(this).find(':selected').val() !== "null") {
            jQuery('#tt_customer_products_select').html("");
            hide_time_tracker();
            hide_product_dropdown();
            get_products($(this).val());
        }
    });

    jQuery('#tt_action_button').click(function (btn) {
        time_tracker_click(this);
    });
    init_push_notifications();
});

/**
 * Loads the products / Services via ajax call
 * Adds options to the dropdown for adding a new product to the order 
 * @todo: Preload the Data after Page is loaded.
 *        Allow to add a new product to the order without the need to go into the backend.
 * @param {integer} orderid The ID of the Order
 */
function get_products(orderid) {
    var data = {
        action: 'get_products',
        orderid: orderid
    };
    jQuery.post(da_ajaxurl, data, function (response) {
        var items = JSON.parse(response);
        if (items.error.length > 0) {
            tt_log(items.error);
            hide_product_dropdown();
        }
        else {
            jQuery('#tt_customer_products_select').html(items.success);
            show_product_dropdown();

            jQuery('#tt_customer_products_select').change(function (e) {
                if (jQuery(this).find(':selected').val() === "link") {
                    var url = wp_backend_url + jQuery('#tt_customer_select').find(':selected').val(); //http://wpsite/wp-admin/post.php?action=edit&post=ID
                    var win = window.open(url, '_blank');
                    hide_product_dropdown();
                    jQuery('#tt_customer_select > option:eq(0)').attr('selected', true);;
                    win.focus();
                }
                else if (jQuery(this).find(':selected').val() !== "null") {
                    var qty = jQuery(this).find(':selected').attr('data-current_qty');
                    show_time_tracker(qty);
                } else {
                    hide_time_tracker();
                }
            });
        }
    });
}

/**
 * Saves the amount to the database via ajax
 * It takes the existing one and adds the new amount to it
 * Sends the description of the job with the current date. It will add the description, if
 * there was a time already tracked for this day. 
 * @param {float} qty The quantity for the selected product
 */
function save_product(qty) {
    var order_item_id = jQuery('#tt_customer_products_select').find(':selected').val();
    var meta_date = jQuery('#tt_start_time').attr('stime');
    var meta_description = jQuery('#tt_meta_description').val();
    var data = {
        action: 'save_qty',
        order_item_id: order_item_id,
        qty: qty,
        meta_date: meta_date,
        meta_description: meta_description
    };
    jQuery.post(da_ajaxurl, data, function (response) {
        var items = JSON.parse(response);
        if (items.error.length > 0) {
            tt_log(items.error, 'error');
        }
        else {
            tt_log(items.success, 'success');
            //reload the products
            var orderid = jQuery('#tt_customer_select').find(':selected').val();
            get_products(orderid);
            hide_time_tracker();
        }
    });

}
/**
 * Shows the Time Tracker
 * @param {float} qty The quantity for the selected product
 */
function show_time_tracker(qty) {
    jQuery('#tt_timetracker').show();
    jQuery('#tt_start_value .value').html(qty);
}

/**
 * Hides the Time Tracker
 * 
 */
function hide_time_tracker() {
    jQuery('#tt_timetracker').hide();
}
/**
 * Shows the "Products" dropdown
 */
function show_product_dropdown() {
    jQuery('#tt_customer_products').show();
}

/**
 * Hides the "Products" dropdown
 */
function hide_product_dropdown() {
    jQuery('#tt_customer_products').hide();
}

/**
 * Runs on click event. Changes the Name of the button and starts or stops the tracker
 * @param {object} btn The Button object 
 */
function time_tracker_click(btn) {
    var ctext = jQuery(btn).text();
    if (ctext === 'Start') {
        jQuery(btn).text('Stop');
        run_time_tracker();
    } else {
        jQuery(btn).text('Start');
        stop_time_tracker();
    }
}

/**
 * Starts the tracking.
 * Shows the Notification that the tracking has started
 * Shows every 30 Minutes a Notification as a reminder
 */
function run_time_tracker() {
    jQuery('#tt_customer_select').attr('disabled', true);
    jQuery('#tt_customer_products_select').attr('disabled', true);
    var date = new Date().toLocaleDateString("de-DE");
    var time = new Date().toLocaleTimeString("de-DE");
    var time_in_seconds = new Date().getTime();
    jQuery('#tt_start_time').attr('stime', time_in_seconds);
    jQuery('#tt_start_time .value').html(date + ' - ' + time);
    jQuery('#tt_stop_time .value').html('');

    //Show the notification
    showLocalNotification('Time Tracker', 'Tracking started for ' + get_current_customer_name(), 'starttt');
    var ct = null;
    document.tt_timer = window.setInterval(function () {
        ct = time_tracker_current();
    }, 1000);

    document.tt_timer_notification = window.setInterval(function () {
        showLocalNotification('Time Tracker', 'Tracking still running for ' + get_current_customer_name() + '\r\nCurrent time: ' + ct, 'starttt');
    }, 1000 * 60 * 30); //Send a notification every 30 Min

}

/**
 * Stops the tracking.
 * Clears the Timers
 * Calls function to save the amount
 */
function stop_time_tracker() {
    //Close the notification
    closeLocalNotification('starttt');

    jQuery('#tt_customer_select').attr('disabled', false);
    jQuery('#tt_customer_products_select').attr('disabled', false);
    var date = new Date().toLocaleDateString("de-DE");
    var time = new Date().toLocaleTimeString("de-DE");
    jQuery('#tt_stop_time .value').html(date + ' - ' + time);

    var otime = jQuery('#tt_start_time').attr('stime');
    var ctime = new Date().getTime();

    //stop the current counter
    clearInterval(document.tt_timer);
    clearInterval(document.tt_timer_notification);

    //compare
    var calcTime = (ctime - otime); //Time in Milliseconds 
    time_tracker_current();
    var hrs = parseFloat(ms_to_hours(calcTime));
    var oqty = parseFloat(jQuery('#tt_start_value .value').html());
    if (hrs === 0) {
        //Add 10 Minutes (0.1h) if time is shorter than 5 Minutes
        hrs = 0.1;
    }
    var qty_to_save = hrs + oqty;
    save_product(qty_to_save);
}

/**
 * Gets the time since the tracking has started.
 * Updates the "Current Time" field
 */
function time_tracker_current() {
    var otime = jQuery('#tt_start_time').attr('stime');
    var ctime = new Date().getTime();

    var calcTime = (ctime - otime);//Time in Milliseconds
    var hrs = parseFloat(ms_to_hours(calcTime));
    var ct = ms_to_time(calcTime) + " (" + hrs + "h)";
    jQuery('#tt_current_time .value').html(ct);
    return ct;
}

/**
 * Formats Milliseconds to hh:mm:ss
 * @param {int} s Milliseconds
 */
function ms_to_time(s) {
    var ms = s % 1000;
    s = (s - ms) / 1000;
    var secs = s % 60;
    s = (s - secs) / 60;
    var mins = s % 60;
    var hrs = (s - mins) / 60;

    return pad(hrs) + ':' + pad(mins) + ':' + pad(secs);

}

/**
 * Formats Milliseconds to hours with one decimal (1.5)
 * @param {int} s Milliseconds
 */
function ms_to_hours(s) {
    var ms = s % 1000;
    s = (s - ms) / 1000;
    var secs = s % 60;
    s = (s - secs) / 60;
    var mins = s % 60;
    var hrs = (s - mins) / 60;

    var calcT = hrs + (mins / 60);
    return (Math.round(calcT * 100) / 100).toFixed(1);

}

/**
 * Adds leading zeros
 * @param {int} n  Number to modify
 * @param {int} z Total length of the number
 */
function pad(n, z = 2) {
    return ('00' + n).slice(-z);
}

/**
 * Sends a Message to the Log.
 * @param {string} msg The Message to show
 * @param {string} type Should be "succes", "error" or "default"
 */
function tt_log(msg, type) {
    jQuery('#tt_log').append('<span class="' + type + '">' + msg + '</span><br/>');
}

/**
 * Gets the Name and Company Info of the current selectet Customer
 */
function get_current_customer_name() {
    var selector = jQuery('#tt_customer_select').find(':selected');
    var company = selector.attr('data-company');
    var name = selector.attr('data-name');
    return company + " - " + name;
}

/**
 * Displays the notification
 * @param {string} title The Title of the notification
 * @param {string} body The Body Text of the notification
 * @param {string} thetag The Tag of the notification
 */
const showLocalNotification = (title, body, thetag = "default") => {
    dataobj = new Object;
    dataobj['url'] = window.location.href;
    const options = {
        body: body,
        tag: thetag,
        icon: wp_frontend_url + "include/images/icon.ico",
        badge: wp_frontend_url + "include/images/icon.ico",
        vibrate: [10, 50, 50, 50, 10],
        data: dataobj
    };
    var not = swRegistration.showNotification(title, options);


    return not;
}

/**
 * Removes the notification when time tracker stops
 * @param {string} thetag The Tag of the notification. Set on showLocalNotification
 */
function closeLocalNotification(thetag) {
    swRegistration.getNotifications({ tag: thetag }).then(function (notifications) {
        notifications.forEach(notification => notification.close());
    });

}

/**
 * Inits the Push notifications
 * @todo Create a Button to ask for permission!?
 */
const init_push_notifications = async () => {
    check();
    swRegistration = await registerServiceWorker();
    const permission = await requestNotificationPermission();
}

//Push Notifications
const check = () => {
    if (!('serviceWorker' in navigator)) {
        throw new Error('No Service Worker support!')
    }
    if (!('PushManager' in window)) {
        throw new Error('No Push API Support!')
    }
}

//function to register a service worker.
const registerServiceWorker = async () => {
    const swRegistration = await navigator.serviceWorker.register(wp_frontend_url + 'include/scripts/tt-push-service.min.js');
    return swRegistration;
}
/**
 * Asks for the permission. If granted, nothing happen. Else it throws an error.
 */
const requestNotificationPermission = async () => {
    const permission = await window.Notification.requestPermission();
    if (permission !== 'granted') {
        throw new Error('Permission not granted for Notification');
    }
}
