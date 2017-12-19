<?php
/*
 * Plugin Name: WP Controllers
 * Plugin URI: https://github.com/JasonTheAdams/WP-Controllers
 * Description: Controllers to work in WordPress the OOP way
 * Version: 0.7.0
 *
 * Requires at least: 4.7
 * Tested up to: 4.9.1
 * Requires PHP: 5.6
 *
 * Author: Jason Adams
 * Author URI: https://github.com/JasonTheAdams/
 * License: MIT
 */


// Setup the PSR-4 Autoloader
require_once 'vendor/autoload.php';

// Load the plugin!
$inventory_plugin = WPControllers\PluginFactory::create(__FILE__, __DIR__);
$inventory_plugin->register();
