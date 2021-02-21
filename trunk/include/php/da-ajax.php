<?php
//mimic the actuall admin-ajax
define('DOING_AJAX', true);

if (!isset( $_POST['action']))
    die('Not allowed');

//make sure you update this line 
//to the relative location of the wp-load.php
require_once('../../../../../wp-load.php'); 

//Typical headers
header('Content-Type: text/html');
send_nosniff_header();

//Disable caching
header('Cache-Control: no-cache');
header('Pragma: no-cache');



do_action('da_ajax_tt-ajax');
do_action('da_ajax_nopriv_tt-ajax');

/*$action = esc_attr(trim($_POST['action']));

//A bit of security
$allowed_actions = array(
    'my_allow_action_1',
    'my_allow_action_2',
    'my_allow_action_3',
    'my_allow_action_4',
    'my_allow_action_5'
);

if(in_array($action, $allowed_actions)){
    if(is_user_logged_in())
        do_action('MY_AJAX_HANDLER_'.$action);
    else
        do_action('MY_AJAX_HANDLER_nopriv_'.$action);
}
else{
    die('-1');
} */