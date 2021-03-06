<?php

/** 
 * Plugin Name: Time Tracker for WooCommerce
 * Description: Various helper methods, like loading templates, and allow decimals quantities inputs
 *              Loads the Debugger "Knit"
 * Version: 1.0
 * Author: DansArt
 * Author URI: http://dans-art.ch
 * License: GPLv2 or later
 */

include_once('kint.phar');

class WcTimeTrackerHelper
{
    protected $version = '0.1';

    protected $plugin_url = '';
    public $plugin_path = '';
    protected $plugin_path_absolute = ABSPATH . 'wp-content/plugins/wc-time-tracker/';
    protected $plugin_url_absolute = '/wp-content/plugins/wc-time-tracker/';

    /**
     * Sets the plugin absolute url
     */
    public function __construct()
    {
        $this->plugin_url_absolute = +site_url();
    }


    /**
     * Gets a template file and transport some data to use.
     *
     * @param string $file - Name of the template file
     * @param array $data - Data to give to the template file
     * @return string Templates contents
     */
    public function tt_load_template($file, $data = array())
    {
        ob_start();
        $path = $this->plugin_path_absolute;
        if (file_exists($path . $file)) {
            require($path . $file);
            return ob_get_clean();
        } else {
            return "File not Found! (" . $path . $file . ")";
        }
    }
    /**
     * Gets the Plugin Path. From the current Theme (/wc-time-tracker/templates/) or from the Plugin
     * Structure is the same for plugin an theme
     *
     * @param string $name - Name of the template file to load
     * @param string $path - Path to the templates files. Default: templates/theme/
     * @return false on error, path on success
     * @todo This function is not in use now. Maybe later to support custom templates
     */
    public function tt_get_template($name, $path = 'templates/theme/')
    {
        if (empty($this->plugin_path)) {
            $this->plugin_path = $this->nm_get_home_path() . $this->plugin_path_relative;
        }
        $loc = get_locale();
        $tmpThemeLoc = get_stylesheet_directory() . '/wc-time-tracker/' . $path . $name . '_' . $loc . '.php';
        $tmpTheme = get_stylesheet_directory() . '/wc-time-tracker/' . $path . $name . '.php';
        $tmpLoc = $this->plugin_path . $path . $name . '_' . $loc . '.php';
        $tmp = $this->plugin_path . $path . $name . '.php';
        //Check if localized version exists in Theme folder
        if (file_exists($tmpThemeLoc)) {
            return $tmpThemeLoc;
        }
        //Check if default version exists in Theme Folder
        if (file_exists($tmpTheme)) {
            return $tmpTheme;
        }
        //Check if localized version exists in plugin folder
        if (file_exists($tmpLoc)) {
            return $tmpLoc;
        }
        //Check if default version exists in Plugin Folder
        if (!file_exists($tmp)) {
            return false;
        } //Not found in Theme as well as in plugin folder
        return $tmp;
    }

    /**
     * Loads the Javascript and css into the head of the page
     *
     * @return void
     * @todo Add support for custom stylesheets in theme folder
     */
    public function tt_enqueue_frontend_scripts_n_styles()
    {
        wp_enqueue_style('timetracker-style', $this->plugin_url_absolute . 'include/css/tt-frontend-style.min.css');
        wp_enqueue_script('timetracker-main-script',  $this->plugin_url_absolute . 'include/scripts/tt-main-script.min.js', ['jquery']);

        return;
    }

    /**
     * The minimal decimal number
     * This is used to set the filter for "woocommerce_quantity_input_min" & "woocommerce_quantity_input_step"
     *
     * @return void
     */
    public function min_decimal()
    {
        return 0.1;
    }

    /**
     * Fix for the correct calculation of the order
     *
     * @param integer $price - The Price to show
     * @param integer $order - 
     * @param array $item - The current Item
     * @param boolean $inc_tax - if taxes are included
     * @param boolean $round - if it should round the result
     * @return void
     */
    public function tt_unit_price_fix($price, $order, $item, $inc_tax = false, $round = true)
    {
        $qty = (!empty($item['qty']) && $item['qty'] != 0) ? $item['qty'] : 1;
        if ($inc_tax) {
            $price = ($item['line_total'] + $item['line_tax']) / $qty;
        } else {
            $price = $item['line_total'] / $qty;
        }
        $price = $round ? round($price, 2) : $price;
        return $price;
    }

    /**
     * Multiple filters to allow for decimal quantities
     *
     * @return void
     */
    public function tt_allow_wc_decimals()
    {
        //Allow decimal quantity
        // Add min value and step value to the quantity field (default = 1)
        add_filter('woocommerce_quantity_input_min', [$this, 'min_decimal']);
        add_filter('woocommerce_quantity_input_step', [$this, 'min_decimal']);

        // Removes the WooCommerce filter, that is validating the quantity to be an int
        remove_filter('woocommerce_stock_amount', 'intval');

        // Add a filter, that validates the quantity to be a float
        add_filter('woocommerce_stock_amount', 'floatval');
        // Add unit price fix when showing the unit price on processed orders
        add_filter('woocommerce_order_amount_item_total', [$this, 'tt_unit_price_fix'], 10, 5);
    }
}
