<?php
/**
 * Autoload classes and functions
 */

$directory = __DIR__;
spl_autoload_register(function($class) use ($directory) {
  if ( !file_exists("$directory/$class.php") ) return;

  include "$directory/$class.php";
  if ( class_exists($class) && method_exists($class, '_construct') ) {
    call_user_func(array($class, '_construct'));
  }
});

if ( !function_exists('get_post_controller') ) {
  /**
   * Global function to call Post::get_controller
   * @see Post::get_controller
   */
  function get_post_controller($key = null, $options = array()) { return Post::get_controller($key, $options); }
}

if ( !function_exists('get_post_controllers') ) {
  /**
   * Global function to call Post::get_controllers
   * @see Post::get_controllers
   */
  function get_post_controllers($args) { return Post::get_controllers($args); }
}

if ( !function_exists('get_page_controller') ) {
  /**
   * Global function to call Page::get_controller
   * @see Post::get_controller
   */
  function get_page_controller($key = null, $options = array()) { return Page::get_controller($key, $options); }
}

if ( !function_exists('get_page_controllers') ) {
  /**
   * Global function to call Page::get_controllers
   * @see Post::get_controllers
   */
  function get_page_controllers($args) { return Page::get_controllers($args); }
}

if ( !function_exists('get_picture_controller') ) {
  /**
   * Global function to call Picture::get_controllers
   * @see Post::get_controller
   */
  function get_picture_controller($key = null, $options = array()) { return Picture::get_controller($key, $options); }
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

if ( !function_exists('get_user_controller') ) {
  /**
   * Global function to calll User::get_controller
   * @see User::get_controller
   */
  function get_user_controller($key = null, $field = 'id', $options = array()) { return User::get_controller($key, $field, $options); }
}

if ( !function_exists('get_user_controllers') ) {
  /**
   * Global function to calll User::get_controllers
   * @see User::get_controllers
   */
  function get_user_controllers($args) { return User::get_controllers($args); }
}

?>
