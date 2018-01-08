<?php

namespace WPControllers;

final class Plugin implements Service {
  /**
   * All the directories to look in when autoloading
   * @var array<string>
   */
  private $_directories = [];

  /**
   * Main file of the WP Controllers plugin
   * @var string
   */
  private $plugin_file;

  /**
   * Main directory of the WP Controllers plugin
   * @var string
   */
  private $plugin_directory;

  /**
   * Constructor for plugin which sets the plugin file and directory
   * @param string $file      Main plugin file
   * @param string $directory Main plugin directory
   */
  public function __construct($file, $directory) {
    $this->plugin_file = $file;
    $this->plugin_directory = $directory;
  }

  /**
   * Kicks everything off
   */
  public function register() {
    add_action('init', [$this, 'init']);
    add_filter('register_post_type_args', [$this, 'register_post_type_args'], 10, 2);

    $this->registerCacheServices();

    require_once "{$this->plugin_directory}/functions.php";
  }

  /**
   * Loads all the controller classes upon the init event of WordPress
   */
  public function init() {
    $this->load_controller_directories();

    spl_autoload_register([$this, 'autoload_register']);
  }

  public function registerCacheServices() {
    $services = [
      CacheHandler\PostCache::class,
      CacheHandler\TermCache::class,
      CacheHandler\UserCache::class
    ];

    foreach($services as $service) {
      $service = new $service;
      $service->register();
    }
  }

  /**
   * Sets the controllers classes for the default post types
   * @param  array  $args      Post type registration arguments
   * @param  string $post_type Post type being registered
   * @return array             Modified registration arguments
   */
  public function register_post_type_args($args, $post_type) {
    switch($post_type) {
      case 'post':
        $args['wp_controller_class'] = apply_filters("wp_controllers_default_{$post_type}_class", 'Post', $args);
        break;

      case 'attachment':
        $args['wp_controller_class'] = apply_filters("wp_controllers_default_{$post_type}_class", 'Attachment', $args);
        break;

      case 'page':
        $args['wp_controller_class'] = apply_filters("wp_controllers_default_{$post_type}_class", 'Page', $args);
        break;
    }

    return $args;
  }

  /**
   * autoload_register.
   * Used to autoload the classes in order of inheritance
   * @param  string $class the class name
   */
  public function autoload_register($class) {
    // Checks for file in lowerclass
    $lower_class = function_exists('mb_strtolower') ? mb_strtolower($class) : strtolower($class);

    // Checks for class with namespacing stripped
    $namespace_position = strrpos($class, '\\');
    $base_class = $namespace_position ? substr($class, -1 * ( strlen($class) - $namespace_position - 1 ) ) : $class;

    foreach($this->_directories as $directory) {
      if ( file_exists("$directory/$base_class.php") ) {
        $include = "$directory/$base_class.php";
      } elseif ( file_exists("$directory/$lower_class.php") ) {
        $include = "$directory/$lower_class";
      } else {
        continue;
      }

      include $include;
      if ( class_exists($class, false) && method_exists($class, '_construct')) {
        call_user_func([$class, '_construct']);
      }

      break;
    }
  }

  /**
   * Goes through the theme and active plugins to check whether it has a wp-controllers directory
   * and adds this to the internal directories
   */
  private function load_controller_directories() {
    $this->_directories = ["{$this->plugin_directory}/controllers"];

    $parent_theme = get_template_directory();
    $child_theme = get_stylesheet_directory();

    // Check & add child theme
    if ( $parent_theme !== $child_theme ) {
      $child_theme = apply_filters('wp_controllers_child_theme_directory', "$child_theme/wp-controllers");
      if ( is_dir($child_theme) ) {
        $this->_directories[] = $child_theme;
      }
    }

    // Check & add main/parent theme
    $parent_theme = apply_filters('wp_controllers_theme_directory', "$parent_theme/wp-controllers");
    if ( is_dir($parent_theme) ) {
      $this->_directories[] = $parent_theme;
    }

    // Include necessary plugin functions if front-end
    if ( !function_exists('get_plugins') ) {
      include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    }

    // Check & add active plugins
    $plugins = get_plugins();
    $plugins_path = WP_PLUGIN_DIR;
    foreach($plugins as $path => $data) {
      if ( is_plugin_active($path) && basename($path) !== basename($this->plugin_file) ) {
        $path = strstr($path, DIRECTORY_SEPARATOR, true);
        $directory = apply_filters('wp_controllers_plugin_directory', "$plugins_path/$path/wp-controllers", $path, $data);
        if ( is_dir($directory) ) {
          $this->_directories[] = $directory;
        }
      }
    }
  }
}
