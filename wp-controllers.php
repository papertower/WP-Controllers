<?php
/*
 * Plugin Name: WP Controllers
 * Plugin URI: https://github.com/JasonTheAdams/WP-Controllers
 * Description: Controllers to work in WordPress the OOP way
 * Version: 0.6.0
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
    add_filter('register_post_type_args', array(__CLASS__, 'register_post_type_args'), 10, 2);
  }

  /**
   * init.
   * Loads all the controller classes upon the init event of WordPress
   */
  public static function init() {
    self::load_controller_directories();

    spl_autoload_register(array(__CLASS__, 'autoload_register'));

    require_once 'functions.php';
  }

  public static function register_post_type_args($args, $post_type) {
    switch($post_type) {
      case 'post':
        $args['wp_controller_class'] = 'Post';
        break;

      case 'attachment':
        $args['wp_controller_class'] = 'Attachment';
        break;

      case 'page':
        $args['wp_controller_class'] = 'Page';
        break;
    }

    return $args;
  }

  /**
   * autoload_register.
   * Used to autoload the classes in order of inheritance
   * @param  string $class the class name
   */
  public static function autoload_register($class) {
    // Checks for file in lowerclass
    $lower_class = function_exists('mb_strtolower') ? mb_strtolower($class) : strtolower($class);

    // Checks for class with namespacing stripped
    $namespace_position = strrpos($class, '\\');
    $base_class = $namespace_position ? substr($class, -1 * ( strlen($class) - $namespace_position - 1 ) ) : $class;

    foreach(self::$_directories as $directory) {
      if ( file_exists("$directory/$base_class.php") ) {
        $include = "$directory/$base_class.php";
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
}

WP_Controllers_Plugin::_construct();
