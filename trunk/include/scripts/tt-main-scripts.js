jQuery(document).ready(function($){

    $('#tt_customer_select').change(function(){
        if($(this).find(':selected').val() !== "null"){
            get_products($(this).val());
        }
    });

    jQuery('#tt_action_button').click(function(btn){
        time_tracker_click(this);
    });

});

function get_products(orderid){
    var data={
        action:'get_products',
        orderid: orderid
   };
   jQuery.post(da_ajaxurl, data, function(response){
       var items = JSON.parse(response);
       if(items.error.length > 0){
           jQuery('#tt_customer_products_error').html(items.error);
       }
       else{
           jQuery('#tt_customer_products_select').html(items.success);
           jQuery('#tt_customer_products_error').html("");
           jQuery('#tt_customer_products_select').change(function(e){
            if(jQuery(this).find(':selected').val() !== "null"){
                var qty = jQuery(this).find(':selected').attr('data-current_qty');
                show_time_tracker(qty);
            }else{
                hide_time_tracker();
            }
           });
       }
        console.log(response);
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
        console.log(response);
   });

}

function show_time_tracker(qty){
    jQuery('#tt_timetracker').show();
    jQuery('#tt_start_value .value').html(qty);
}
function hide_time_tracker(){
    jQuery('#tt_timetracker').hide();
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
    var date = new Date().toLocaleDateString("de-DE");
    var time = new Date().toLocaleTimeString("de-DE");
    var time_in_seconds = new Date().getTime();
    jQuery('#tt_start_time').attr('stime',time_in_seconds);
    jQuery('#tt_start_time .value').html(date+' '+time);
    jQuery('#tt_stop_time .value').html('');

    document.tt_timer = window.setInterval(function(){
           time_tracker_current();
      }, 1000);

}
function stop_time_tracker(){
    var date = new Date().toLocaleDateString("de-DE");
    var time = new Date().toLocaleTimeString("de-DE");
    jQuery('#tt_stop_time .value').html(date+' '+time);

    var otime = jQuery('#tt_start_time').attr('stime');
    var ctime = new Date().getTime();

    //stop the current counter
    clearInterval(document.tt_timer);

    //compare
    var calcTime = (ctime - otime); //Time in Milliseconds 
    jQuery('#tt_current_time .value').html(ms_to_time(calcTime));
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

    jQuery('#tt_current_time .value').html(ms_to_time(calcTime)+" ("+hrs+")");
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