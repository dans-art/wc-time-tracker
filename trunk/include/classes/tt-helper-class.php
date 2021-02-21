<?php

/**
 * Plugin Name: Notify Me!
 * Class description: Various helper methods. Like loading templates, check if valid mail, get version etc.
 * Author: DansArt.
 * Author URI: http://dans-art.ch
 *
 */
include_once('kint.phar');

class WcTimeTrackerHelper
{
    protected $version = '0.1';

    protected $scriptsLoaded = false;
    protected $plugin_url = '';
    public $plugin_path = '';
    protected $plugin_path_absolute = ABSPATH . 'wp-content/plugins/wc-time-tracker/';
    protected $plugin_url_absolute = '/wp-content/plugins/wc-time-tracker/';

    public function __construct()
    {
        $this -> plugin_url_absolute =+ site_url();
    }


    /**
     * Gets a file and transport some data to use.
     *
     * @param [type] $file
     * @param array $data
     * @return void
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
     */
    public function tt_get_template($name, $path = 'templates/theme/')
    {
        if (empty($this->plugin_path)) {
            $this->plugin_path = $this->nm_get_home_path() . $this->plugin_path_relative;
        }
        $loc = get_locale();
        $tmpThemeLoc = get_stylesheet_directory() . '/notify-me/' . $path . $name . '_' . $loc . '.php';
        $tmpTheme = get_stylesheet_directory() . '/notify-me/' . $path . $name . '.php';
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
     * Modifies the script type to "module"
     *
     * @return void
     * @todo Add support for custom stylesheets in theme folder
     */
    public function tt_enqueue_frontend_scripts_n_styles()
    {
        wp_enqueue_style('timetracker-style', $this -> plugin_url_absolute.'include/css/tt-frontend-style.css');
        wp_enqueue_script('timetracker-main-script',  $this -> plugin_url_absolute.'include/scripts/tt-main-script.js', ['jquery']);


        //Set script tag "Module"
        /*add_filter('script_loader_tag', function ($tag, $handle, $src) {
            if ($handle === 'notify-me-app') {
                return '<script type="module" src="' . esc_url($src) . '"></script>' . '<script>var notify_me_url = "' . esc_url($this->plugin_url) . '"; var wp_site_url = "' . esc_url(get_site_url()) . '";</script>';
            } else {
                return $tag;
            }
        }, 10, 3);
        */
        return;
    }
    public function min_decimal($val)
    {
        return 0.1;
    }

    public function unit_price_fix($price, $order, $item, $inc_tax = false, $round = true)
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
}
