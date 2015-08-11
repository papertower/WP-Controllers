<?php
/**
 * Autload classes and functions
 */

$directory = __DIR__;
spl_autload_register(function($class) use ($directory) {
  include "$directory/$class";
});

if ( !function_exists('get_post_controller') ) {
  /**
   * Global function to call Post::get_controller
   * @param string|int|object $post id, slug, or WP_Post object
   * @param array $options
   * @return Controller
   */
  function get_post_controller($key = null, $options = array()) { return Post::get_controller($key, $options); }
}

if ( !function_exists('get_post_controllers') ) {
  /**
   * Global function to call Post::get_controllers
   * @param array $args Either an array of WP_Post objects or get_posts arguments
   * @return array
   */
  function get_post_controllers($args) { return Post::get_controllers($args); }
}

if ( !function_exists('get_page_controller') ) {
  /**
   * Global function to call Page::get_controller
   * @see get_post_controller
   */
  function get_page_controller($key = null, $options = array()) { return Page::get_controller($key, $options); }
}

if ( !function_exists('get_page_controllers') ) {
  /**
   * Global function to call Page::get_controllers
   * @see get_post_controllers
   */
  function get_page_controllers($args) { return Page::get_controllers($args); }
}

if ( !function_exists('get_picture_controller') ) {
  /**
   * Global function to call Picture::get_controllers
   * @see get_post_controllers
   */
  function get_picture_controller($key = null, $options = array()) { return Picture::get_controller($key, $options); }
}

if ( !function_exists('get_term_controller') ) {
  /**
   * Global function to call Term::get_controller
   * @see https://codex.wordpress.org/Function_Reference/get_term_by
   * @param int|string|object $key term value
   * @param string $taxonomy taxonomy name
   * @param string $field field to retrieve by
   * @param array $options controller options
   * @return Term
   */
  function get_term_controller($key = null, $taxonomy = null, $field = 'id', $options = array()) {
    return Term::get_controller($key, $taxonomy, $field, $options);
  }
}

if ( !function_exists('get_user_controller') ) {
  /**
   * Global function to calll User::get_controller
   * @see http://codex.wordpress.org/Function_Reference/get_user_by
   * @param int|string|object $key user value
   * @param string $field field to retrieve by
   * @param array $options controller options
   * @return User
   */
  function get_user_controller($key = null, $field = 'id', $options = array()) { return User::get_controller($key, $field, $options); }
}

if ( !function_exists('get_user_controllers') ) {
  /**
   * Global function to calll User::get_controllers
   * @param array $args array of WP_User objects or get_users args
   * @return array
   */
  function get_user_controllers($args) { return User::get_controllers($args); }
}

?>
