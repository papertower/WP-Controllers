<?php
/**
 * Autoload classes and functions
 */

/*
 * Register autoloader
 */
$directory = __DIR__;
spl_autoload_register(function($class) use ($directory) {
  if ( !file_exists("$directory/$class.php") ) return;

  include "$directory/$class.php";
  if ( class_exists($class) && method_exists($class, '_construct') ) {
    call_user_func(array($class, '_construct'));
  }
});

/*
 * Load all controller files
 */
$_files = scandir($directory);
$_autoload_file = basename(__FILE__);
foreach($_files as $_file) {
  if ( $_file !== $_autoload_file && ( $_class = strstr($_file, '.php', true) ) && "$_class.php" === $_file ) {
    if ( !class_exists($_class) ) {
      trigger_error("$_file failed to load a controller class", E_USER_WARNING);
    }
  }
}

if ( !function_exists('get_post_controller') ) {
  /**
   * Global function to call PostController::get_controller
   * @see PostController::get_controller
   *
   * @param string|null $key
   * @param array $options
   *
   * @return PostController
   */
  function get_post_controller($key = null, $options = array()) { return PostController::get_controller($key, $options); }
}

if ( !function_exists('get_post_controllers') ) {
  /**
   * Global function to call PostController::get_controllers
   * @see PostController::get_controllers
   *
   * @param array $args
   *
   * @return array
   */
  function get_post_controllers($args = null) { return PostController::get_controllers($args); }
}

if ( !function_exists('get_page_controllers') ) {
  /**
   * Global function to call Page::get_controllers
   * @see PostController::get_controllers
   *
   * @param array $args
   *
   * @return array
   */
  function get_page_controllers($args = null) { return Page::get_controllers($args); }
}

if ( !function_exists('get_term_controller') ) {
  /**
   * Global function to call Term::get_controller
   * @see Term::get_controller
   */
  function get_term_controller($key = null, $taxonomy = null, $field = 'id', $options = array()) {
    return Term::get_controller($key, $taxonomy, $field, $options);
  }
}

if ( !function_exists('get_term_controllers') ) {
  /**
   * Global function to call Term::get_controller
   * @see Term::get_controller
   *
   * @param string|null $key
   * @param string $field
   * @param array $options
   *
   * @return Term
   */
  function get_term_controllers($args) {
    return Term::get_controllers($args);
  }
}

if ( !function_exists('get_user_controller') ) {
  /**
   * Global function to calll User::get_controller
   * @see User::get_controller
   *
   * @param string|null $key
   * @param string $field
   * @param array $options
   *
   * @return User
   */
  function get_user_controller($key = null, $field = 'id', $options = array()) { return User::get_controller($key, $field, $options); }
}

if ( !function_exists('get_user_controllers') ) {
  /**
   * Global function to calll User::get_controllers
   * @see User::get_controllers
   *
   * @param array $args
   *
   * @return array
   */
  function get_user_controllers($args) { return User::get_controllers($args); }
}
