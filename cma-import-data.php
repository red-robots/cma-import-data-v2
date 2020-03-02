<?php

/**
 * Plugin Name: CMA Search Import Data
 * Plugin URI: https://bellaworksweb.com/cmasearch-import
 * Description: This plugin will import the csv into properties post type
 * Version: 1.1
 * Author: Hermie
 * Author URI: https://bellaworksweb.com/
 */

if( !defined('ABSPATH') ){
    exit;
}

// Load scripts
require_once( plugin_dir_path( __FILE__ ) . '/includes/cma-scripts.php' );
