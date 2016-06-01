<?php
/*
 * Plugin Name: WP Controllers
 * Plugin URI: https://github.com/JasonTheAdams/WP-Controllers
 * Description: Controllers to work in WordPress the OOP way
 * Version: 0.5.0
 * Author: Jason Adams
 * Author URI: https://github.com/JasonTheAdams/
 * License: MIT
 */

// Suffix with 'Plugin' to avoid possible name collision in the future
final class WP_Controllers_Plugin {
  private static $_directories;

  /**
   * _construct
   * Kicks everything off
   */
  public static function _construct() {
    add_action('init', array(__CLASS__, 'init'));
  }

  /**
   * init.
   * Loads all the controller classes upon the init event of WordPress
   */
  public static function init() {
    self::load_controller_directories();

    spl_autoload_register(array(__CLASS__, 'autoload_register'));

    foreach(self::$_directories as $directory) {
      self::auto_load_controllers($directory);
    }

    spl_autoload_unregister(array(__CLASS__, 'autoload_register'));

    require_once 'functions.php';
  }

  /**
   * autoload_register.
   * Used to autoload the classes in order of inheritance
   * @param  string $class the class name
   */
  public static function autoload_register($class) {
    $lower_class = function_exists('mb_strtolower') ? mb_strtolower($class) : strtolower($class);

    foreach(self::$_directories as $directory) {
      if ( file_exists("$directory/$class.php") ) {
        $include = "$directory/$class.php";
      } elseif ( file_exists("$directory/$lower_class.php") ) {
        $include = "$directory/$lower_class";
      } else {
        continue;
      }

      include $include;
      if ( class_exists($class, false) && method_exists($class, '_construct')) {
        call_user_func(array($class, '_construct'));
      }
    }
  }

  /**
   * load_controller_directories.
   * Goes through the theme and active plugins to check whether it has a wp-controllers directory
   * and adds this to the internal directories
   */
  private static function load_controller_directories() {
    self::$_directories = array(__DIR__ . '/controllers');

    $parent_theme = get_template_directory();
    $child_theme = get_stylesheet_directory();

    // Check & add child theme
    if ( $parent_theme !== $child_theme ) {
      $child_theme = apply_filters('wp_controllers_child_theme_directory', "$child_theme/wp-controllers");
      if ( is_dir($child_theme) ) {
        self::$_directories[] = $child_theme;
      }
    }

    // Check & add main/parent theme
    $parent_theme = apply_filters('wp_controllers_theme_directory', "$parent_theme/wp-controllers");
    if ( is_dir($parent_theme) ) {
      self::$_directories[] = $parent_theme;
    }

    // Include necessary plugin functions if front-end
    if ( !function_exists('get_plugins') ) {
      include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    }

    // Check & add active plugins
    $plugins = get_plugins();
    $plugins_path = WP_PLUGIN_DIR;
    foreach($plugins as $path => $data) {
      if ( is_plugin_active($path) && basename($path) !== basename(__FILE__) ) {
        $path = strstr($path, DIRECTORY_SEPARATOR, true);
        $directory = apply_filters('wp_controllers_plugin_directory', "$plugins_path/$path/wp-controllers", $path, $data);
        if ( is_dir($directory) ) {
          self::$_directories[] = $directory;
        }
      }
    }
  }

  /**
   * auto_load_controllers.
   * Loads the controller classes for the given directory
   * @param  string $directory absolute path to directory
   */
  private static function auto_load_controllers($directory) {
    $files = scandir($directory);
    if ( empty($files) ) return;

    foreach($files as $file) {
      if ( ( $class = strstr($file, '.php', true) ) ) {
        if ( !class_exists($class) ) {
          trigger_error("$file expected to load the $class class but $class was not found", E_USER_WARNING);
        }
      }
    }
  }
}

WP_Controllers_Plugin::_construct();
