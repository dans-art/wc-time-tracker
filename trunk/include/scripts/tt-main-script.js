jQuery(document).ready(function($){

    /**
     * Runs if customer is selected
     * Shows products / positions of customer
     */
    $('#tt_customer_select').change(function(){
         if($(this).find(':selected').val() !== "null"){
            jQuery('#tt_customer_products_select').html("");
            hide_time_tracker();
            hide_product_dropdown();

            get_products($(this).val());
        }
    });

    jQuery('#tt_action_button').click(function(btn){
        time_tracker_click(this);
    });
    //Starts Push Service
    const swRegistration = null;
    init_push_notifications();
});

function get_products(orderid){
    var data={
        action:'get_products',
        orderid: orderid
   };
   jQuery.post(da_ajaxurl, data, function(response){
       var items = JSON.parse(response);
       if(items.error.length > 0){
           tt_log(items.error);
           hide_product_dropdown();
       }
       else{
           jQuery('#tt_customer_products_select').html(items.success);
           show_product_dropdown();

           jQuery('#tt_customer_products_select').change(function(e){
            if(jQuery(this).find(':selected').val() === "link"){
                var url = wp_backend_url + jQuery('#tt_customer_select').find(':selected').val(); //http://wpsite/wp-admin/post.php?action=edit&post=ID
                var win = window.open(url, '_blank');
                hide_product_dropdown();
                jQuery('#tt_customer_select > option:eq(0)').attr('selected', true);;
                win.focus();
            }
            else if(jQuery(this).find(':selected').val() !== "null"){
                var qty = jQuery(this).find(':selected').attr('data-current_qty');
                show_time_tracker(qty);
            }else{
                hide_time_tracker();
            }
           });
       }
   });
}

function save_product(qty){
    var order_item_id = jQuery('#tt_customer_products_select').find(':selected').val();
    var data={
        action:'save_qty',
        order_item_id: order_item_id,
        qty : qty
   };
   jQuery.post(da_ajaxurl, data, function(response){
       var items = JSON.parse(response);
       if(items.error.length > 0){
            tt_log(items.error, 'error');
       }
       else{
            tt_log(items.success, 'success');
            //reload the products
            var orderid = jQuery('#tt_customer_select').find(':selected').val();
            get_products(orderid);
            hide_time_tracker();
       }
   });

}

function show_time_tracker(qty){
    jQuery('#tt_timetracker').show();
    jQuery('#tt_start_value .value').html(qty);
}
function hide_time_tracker(){
    jQuery('#tt_timetracker').hide();
}
function show_product_dropdown(qty){
    jQuery('#tt_customer_products').show();
}
function hide_product_dropdown(){
    jQuery('#tt_customer_products').hide();
}

function time_tracker_click(btn){
    var ctext = jQuery(btn).text();
    if(ctext === 'Start'){
        jQuery(btn).text('Stop');
        run_time_tracker();
    }else{
        jQuery(btn).text('Start');
        stop_time_tracker();
    }
}

function run_time_tracker(){
    jQuery('#tt_customer_select').attr('disabled', true);
    jQuery('#tt_customer_products_select').attr('disabled', true);
    var date = new Date().toLocaleDateString("de-DE");
    var time = new Date().toLocaleTimeString("de-DE");
    var time_in_seconds = new Date().getTime();
    jQuery('#tt_start_time').attr('stime',time_in_seconds);
    jQuery('#tt_start_time .value').html(date+' - '+time);
    jQuery('#tt_stop_time .value').html('');

    //Show the notification
    showLocalNotification('Time Tracker', 'Tracking started for '+get_current_customer_name(), swRegistration);

    document.tt_timer = window.setInterval(function(){
           time_tracker_current();
      }, 1000);

}
function stop_time_tracker(){
    jQuery('#tt_customer_select').attr('disabled', false);
    jQuery('#tt_customer_products_select').attr('disabled', false);
    var date = new Date().toLocaleDateString("de-DE");
    var time = new Date().toLocaleTimeString("de-DE");
    jQuery('#tt_stop_time .value').html(date+' - '+time);

    var otime = jQuery('#tt_start_time').attr('stime');
    var ctime = new Date().getTime();

    //stop the current counter
    clearInterval(document.tt_timer);

    //compare
    var calcTime = (ctime - otime); //Time in Milliseconds 
    time_tracker_current();
    var hrs = parseFloat(ms_to_hours(calcTime));
    var oqty = parseFloat(jQuery('#tt_start_value .value').html());
    if(hrs === 0){
        tt_log('Nothing changed, nothing saved!');
        return false;
    }
    var qty_to_save = hrs + oqty;
    save_product(qty_to_save);
}

function time_tracker_current(){
    var otime = jQuery('#tt_start_time').attr('stime');
    var ctime = new Date().getTime();

    var calcTime = (ctime - otime);//Time in Milliseconds
    var hrs = parseFloat(ms_to_hours(calcTime));

    jQuery('#tt_current_time .value').html(ms_to_time(calcTime)+" ("+hrs+"h)");
}

function ms_to_time(s){
        var ms = s % 1000;
        s = (s - ms) / 1000;
        var secs = s % 60;
        s = (s - secs) / 60;
        var mins = s % 60;
        var hrs = (s - mins) / 60;
      
        return pad(hrs) + ':' + pad(mins) + ':' + pad(secs);

}

function ms_to_hours(s){
        var ms = s % 1000;
        s = (s - ms) / 1000;
        var secs = s % 60;
        s = (s - secs) / 60;
        var mins = s % 60;
        var hrs = (s - mins) / 60;

        var calcT = hrs + (mins / 60);
        return (Math.round(calcT * 100) / 100).toFixed(1);

}

function pad(n, z = 2) {
    return ('00' + n).slice(-z);
  }

function tt_log(msg, type){
    jQuery('#tt_log').append('<span class="'+type+'">'+msg+'</span><br/>');
} 

function get_current_customer_name(){
    var selector = jQuery('#tt_customer_select').find(':selected');
    var company = selector.attr('data-company');
    var name = selector.attr('data-name');
    return company+" - "+name;
     
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
  
  // I added a function that can be used to register a service worker.
const registerServiceWorker = async () => {
    swRegistration = await navigator.serviceWorker.register(wp_frontend_url+'include/scripts/tt-push-service.js'); //notice the file name
    console.log(swRegistration);
    return swRegistration;
}
 
const requestNotificationPermission = async () => {
    const permission = await window.Notification.requestPermission();
    // value of permission can be 'granted', 'default', 'denied'
    // granted: user has accepted the request
    // default: user has dismissed the notification permission popup by clicking on x
    // denied: user has denied the request.
    if(permission !== 'granted'){
        throw new Error('Permission not granted for Notification');
    }
}

const showLocalNotification = (title, body, swRegistration) => {
    const options = {
        body,
        // here you can add more properties like icon, image, vibrate, etc.
        /**
         * const options = {
              "//": "Visual Options",
              "body": "<String>",
              "icon": "<URL String>",
              "image": "<URL String>",
              "badge": "<URL String>",
              "vibrate": "<Array of Integers>",
              "sound": "<URL String>",
              "dir": "<String of 'auto' | 'ltr' | 'rtl'>",

              "//": "Behavioural Options",
              "tag": "<String>",
              "data": "<Anything>",
              "requireInteraction": "<boolean>",
              "renotify": "<Boolean>",
              "silent": "<Boolean>",

              "//": "Both Visual & Behavioural Options",
              "actions": "<Array of Strings>",

              "//": "Information Option. No visual affect.",
              "timestamp": "<Long>"
            }

            https://developer.mozilla.org/en-US/docs/Web/API/notification
         */
    };
    swRegistration.showNotification(title, options);
}

const init_push_notifications = async () => {
    check();
    const swRegistration = await registerServiceWorker();

    //Add Button to ask for permission
    const permission =  await requestNotificationPermission();
    
    
  }
  


