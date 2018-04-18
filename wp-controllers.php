<?php
/*
 * Plugin Name: WP Controllers
 * Plugin URI: https://github.com/JasonTheAdams/WP-Controllers
 * Description: Controllers to work in WordPress the OOP way
 * Version: 0.8.0
 *
 * Requires at least: 4.7
 * Tested up to: 4.9.1
 * Requires PHP: 5.6
 *
 * Author: Paper Tower
 * Author URI: https://papertower.com
 * License: MIT
 */


// Setup the PSR-4 Autoloader
require_once 'vendor/autoload.php';

// Load the plugin!
$inventory_plugin = WPControllers\Plugin\PluginFactory::create(__FILE__, __DIR__);
$inventory_plugin->register();
