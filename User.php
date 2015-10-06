<?php

class User {
  const CACHE_GROUP = 'usercontroller';

  protected
    $user;

  public static function _construct() {
    if ( __CLASS__ === get_called_class() ) {
      add_action('profile_update', array(__CLASS__, 'profile_update'), 10, 2);
      add_action('delete_user', array(__CLASS__, 'delete_user'), 10, 2);
    }
  }

  /**
   * Retrieve User controller
   * @see http://codex.wordpress.org/Function_Reference/get_user_by
   * @param int|string|object $key user value
   * @param string $field field to retrieve by
   * @param array $options controller options
   * @return User
   */
  public static function get_controller($key = null, $field = 'id', $options = array()) {
    $options = wp_parse_args($options, array(
      'load_standard_meta'=> true
    ));

    if ( is_object($key) ) {
      $user = wp_cache_get($key->ID, self::CACHE_GROUP);
      if ( false !== $user ) return $controller;
      $user = $key;

    } elseif ( $key ) {
      // Retrieve user and check if cached
      if ( $field == 'id' ) {
        $user = wp_cache_get($key, self::CACHE_GROUP);
        if ( false !== $user ) return $user;

      } else {
        $user_id = wp_cache_get($key, self::CACHE_GROUP . '_' . $field);
        if ( false !== $user_id )
          return wp_cache_get($key, self::CACHE_GROUP);
      }

      $user = get_user_by($field, $key);

      if ( false === $user )
        return new WP_Error('user_not_found', 'No user was found with the provided parameters', array('field' => $field, 'value' => $key));

    } else {
      if ( !is_user_logged_in() )
        return new WP_Error('user_not_logged_in', 'No user is logged in to return');

      $user = wp_get_current_user();

      $controller = wp_cache_get($user->ID, self::CACHE_GROUP);
      if ( false !== $controller ) return $controller;
    }

    $controller = new self($user, $options['load_standard_meta']);

    wp_cache_set($controller->id, $controller, self::CACHE_GROUP, MINUTE_IN_SECONDS * 10);
    wp_cache_set($controller->email, $controller->id, self::CACHE_GROUP . '_email', MINUTE_IN_SECONDS * 10);
    wp_cache_set($controller->nice_name, $controller->id, self::CACHE_GROUP . '_slug', MINUTE_IN_SECONDS * 10);
    wp_cache_set($controller->login, $controller->id, self::CACHE_GROUP . '_login', MINUTE_IN_SECONDS * 10);

    return $controller;
  }

  public static function get_controllers($args) {
    $users = get_users($args);
    $controllers = array();
    foreach($users as $user)
      $controllers[] = self::get_controller($user);

    return $controllers;
  }

  public static function profile_update($user_id, $old_user) {
    self::clear_controller_cache($old_user);
  }

  public static function delete_user($user_id, $reassign_id) {
    $user = get_user_by('id', $user_id);
    if ( $user ) self::clear_controller_cache($user);
  }

  public static function clear_controller_cache($user) {
    wp_cache_delete($user->ID, self::CACHE_GROUP);
    wp_cache_delete($user->data->user_email, self::CACHE_GROUP . '_email');
    wp_cache_delete($user->data->user_nicename, self::CACHE_GROUP . '_slug');
    wp_cache_delete($user->data->user_login, self::CACHE_GROUP . '_login');
  }

  protected function __construct($user, $load_extra) {
    $this->user = $user;

    $this->id               =& $user->ID;
    $this->capabilities     =& $user->caps;
    $this->roles            =& $user->roles;
    $this->all_capabilities =& $user->allcaps;

    $this->display_name     =& $user->data->display_name;
    $this->nice_name        =& $user->data->user_nicename;

    $this->login            =& $user->data->user_login;
    $this->email            =& $user->data->user_email;
    $this->status           =& $user->data->user_status;
    $this->registered       =& $user->data->user_registered;

    if ( $load_extra ) {
      $this->first_name       = $user->first_name;
      $this->last_name        = $user->last_name;
      $this->description      = $user->description;
    }

    // Meta class
    $this->meta = new Meta($this->id, 'post');
  }

  public function registered($format) {
    return ( 'timestamp' === $format )
      ? strtotime($this->registered)
      : date($format, strtotime($this->registered));
  }

  public function posts_url() {
    return get_author_posts_url($this->id);
  }

  public static function get_authors($post_types = null) {
    global $wpdb;

    if ( is_array($post_types) ) {
      $place_holders = implode(',', array_fill(0, count($post_types), '%s'));
      $where = $wpdb->prepare("AND P.post_type IN ($place_holders)", $post_types);
    } else if ( $post_types ) {
      $where = $wpdb->prepare("AND P.post_type = %s", $post_types);
    } else
      $where = '';

    $author_ids = $wpdb->get_col("
      SELECT U.ID

      FROM $wpdb->users AS U
        JOIN $wpdb->posts AS P ON U.ID = P.post_author

      WHERE P.post_status = 'publish'
        $where

      GROUP BY U.ID;
    ");

    $authors = array();
    foreach($author_ids as $index => $id)
      $authors[] = self::get_controller($id);

    return $authors;
  }

  public static function get_users_post_count($user_ids, $post_type = null) {
    $user_ids = ( $user_ids ) ? $user_ids : get_users();
    $post_type = ( $post_type ) ? $post_type : get_post_types();

    if ( is_array($post_type) && (count($post_type) > 1) ) {
      $results = array();
      foreach($post_type as $type) {
        $counts = count_many_users_posts($user_ids, $type);

        foreach($counts as $user_id => $count)
          $results[$user_id][$type] = (integer) $count;
      }

      return $results;

    } else {
      $post_type = ( is_array($post_type) ) ? $post_type[0] : $post_type;
      return count_many_users_posts($user_ids, $post_type);
    }
  }
};
?>
