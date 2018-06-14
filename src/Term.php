<?php

namespace WPControllers;

use \WP_Error;
use \WP_Term;

/**
 * Base Term Controller.
 * This is a [decorator](http://en.wikipedia.org/wiki/Decorator_pattern) class for the
 * native WP_Term class. It extends it with common controller functions as well as
 * expanding on the general functionality. All other term controllers inherit from this.
 *
 * @version 0.7.0
 */
class Term {

  /**
   *
   */
  const CACHE_GROUP = 'termcontroller';

  /**
   * @var array $taxonomies The taxonomies and their corresponding controller
   */
  private static
    $_controller_taxonomies;

  /**
   * @var object $term
   */
  private
    $term;

  /**
   * @var int $id
   * @var string $slug
   * @var string $name
   * @var string $description
   * @var string $group
   * @var string $taxonomy
   * @var int $taxonomy_id
   * @var int $count
   * @var int $parent
   * @var Meta $meta
   */
  public
    $id,
    $slug,
    $name,
    $description,
    $group,
    $taxonomy,
    $taxonomy_id,
    $count,
    $parent,
    $meta;

  /**
   * Retrieve the controller for the term
   * @see https://codex.wordpress.org/Function_Reference/get_term_by
   * @param int|string|object $key term value
   * @param string $taxonomy taxonomy name
   * @param string $field field to retrieve by
   * @param array $options controller options
   * @return Term|WP_Error
   */
  public static function get_controller($key = null, $taxonomy = null, $field = 'id', $options = array()) {
    if ( is_object($key) ) {
      $term = wp_cache_get($key->term_id, self::CACHE_GROUP);
      if ( false !== $term ) return $term;
      $term = $key;

    } elseif ( $key ) {
      if ( $field == 'id' ) {
        $term = wp_cache_get($key, self::CACHE_GROUP);
        if ( false !== $term ) return $term;
      } else {
        $term_id = wp_cache_get($key, self::CACHE_GROUP . '_' . $field);
        if ( false !== $term_id )
          return wp_cache_get($term_id, self::CACHE_GROUP);
      }
      $term = get_term_by($field, $key, $taxonomy);

    } else {
      $term = get_queried_object();
      if ( !isset($term->term_id) )
        return new WP_Error('invalid_queried_object', 'The queried object is not a term', $term);

      $controller = wp_cache_get($term->term_id, self::CACHE_GROUP);
      if ( false !== $controller ) return $controller;
    }

    if ( false === $term ) {
      return $term;
    }

    // Construct, cache, and return term
    $controller_class = self::get_controller_class($term, $options);
    $controller = new $controller_class ($term);

    wp_cache_set($controller->id, $controller, self::CACHE_GROUP, MINUTE_IN_SECONDS * 10);
    wp_cache_set($controller->slug, $controller->id, self::CACHE_GROUP . '_' . 'slug', MINUTE_IN_SECONDS * 10);
    wp_cache_set($controller->name, $controller->id, self::CACHE_GROUP . '_' . 'name', MINUTE_IN_SECONDS * 10);
    wp_cache_set($controller->taxonomy_id, $controller->id, self::CACHE_GROUP . '_' . 'term_taxonomy_id', MINUTE_IN_SECONDS * 10);

    return $controller;
  }

  /**
   * Returns an array of Term controllers from either arguments for an array of ids. If an array of
   * ids is supplied, the second parameter is the taxonomy the ids belong to
   * @param  array $args        get_terms arguments or array of ids
   * @param  string $taxonomy   taxonomy for array of ids
   * @return array              array of controllers
   */
  public static function get_controllers($args, $taxonomy = '') {
    if ( isset($args[0]) || empty($args) ) {
      $terms = $args;
    } else {
      $terms = get_terms($args);
      
      if ( isset($args['fields']) && 'all' !== $args['fields'] ) {
        return $terms;
      }
    }

    $Terms = array();
    foreach($terms as $term) {
      $Terms[] = self::get_controller($term, $taxonomy);
    }

    return $Terms;
  }

  /**
   * Returns the class name for the corresponding term
   * @param  WP_Term $term    WP_Term to get the class for
   * @param  array   $options Array of options
   * @return string           Class name
   */
  private static function get_controller_class($term, $options = array()) {
    if ( !is_array(self::$_controller_taxonomies) ) {
      self::$_controller_taxonomies = array();

      $taxonomies = get_taxonomies(array(), 'objects');
      foreach($taxonomies as $taxonomy) {
        if ( !empty($taxonomy->wp_controller_class) ) {
          self::$_controller_taxonomies[$taxonomy->name] = $taxonomy->wp_controller_class;
        }
      }
    }

    $class = isset(self::$_controller_taxonomies[$term->taxonomy])
      ? self::$_controller_taxonomies[$term->taxonomy]
      : __CLASS__;

    return apply_filters('wp_controllers_term_class', $class, $term, $options);
  }

  /**
   * Called when the cache for a term controller needs to be flushed. Calls the flush_cache static
   * method for the class the term belongs to.
   * @param  WP_Term $term  term object that needs to be invalidated
   * @param  string  $event event which triggered the flush
   */
  public static function trigger_flush_cache($term, $event) {
    wp_cache_delete($term->term_id, self::CACHE_GROUP);
    wp_cache_delete($term->slug, self::CACHE_GROUP . '_slug');
    wp_cache_delete($term->name, self::CACHE_GROUP . '_name');
    wp_cache_delete($term->term_taxonomy_id, self::CACHE_GROUP . '_term_taxonomy_id');

    $controller_class = self::get_controller_class($term);
    if ( $controller_class && method_exists($controller_class, 'flush_cache') ) {
      $controller_class::flush_cache($term, $event);
    }
  }

  /**
   * Term constructor.
   *
   * @param object $term
   */
  protected function __construct($term) {
    // Load all the term properties
    foreach(get_object_vars($term) as $key => $value)
      $this->$key = $value;

    // Extra properties
    $this->term         =& $term;
    $this->id           =& $term->term_id;
    $this->group        =& $term->term_group;
    $this->taxonomy_id  =& $term->term_taxonomy_id;

    // Meta class
    $this->meta = new Meta($this->id, 'term');
  }

  /**
   * Returns the term url
   * @return string|WP_Error
   */
  public function url() {
    return isset($this->url) ? $this->url : ( $this->url = get_term_link($this->term) );
  }

  /**
   * Returns the term name filtered by the standard filters
   * @return string filtered term name
   */
  public function title() {
    switch($this->taxonomy) {
      case 'category': return apply_filters('single_cat_title', $this->name);
      case 'post_tag': return apply_filters('single_tag_title', $this->name);
      default: return apply_filters('single_term_title', $this->name);
    }
  }

  /**
   * Returns the parent term controller if there is one
   * @return Term|null controller if has parent
   */
  public function parent() {
    return $this->parent ? self::get_controller($this->parent, $this->taxonomy, 'id') : null;
  }

  /**
   * Returns the children term controllers
   * @return array child term controllers
   */
  public function children() {
    return self::get_controllers(array(
      'taxonomy'  => $this->taxonomy,
      'child_of'  => $this->id
    ));
  }

  /**
   * Returns the term description filtered by the_content
   * @return string filtered description
   */
  public function description() {
    return apply_filters('the_content', $this->description);
  }

  /**
   * Returns the posts that have this term
   * @param  string  $post_type post type(s) to limit the query to; default: any
   * @param  integer $count     the number of posts to return; default: -1
   * @return Post[]             Post controllers
   */
  public function posts($post_type = 'any', $count = -1) {
    if ( !$this->count ) return array();

    return Post::get_controllers(array(
      'post_type'   => $post_type,
      'numberposts' => $count,
      'tax_query'   => array(
        array(
          'taxonomy'    => $this->taxonomy,
          'terms'       => $this->id
        )
      )
    ));
  }

  /**
   * @param $post_type
   * @return Post|null
   */
  public function oldest_post($post_type) {
    if ( !$this->count ) return null;

    $Post = get_post_controllers(array(
      'post_type'   => $post_type,
      'numberposts' => 1,
      'orderby'     => 'date',
      'order'       => 'ASC',
      'tax_query'   => array(
        array(
          'taxonomy'    => $this->taxonomy,
          'terms'       => $this->id
        )
      )
    ));

    return empty($Post) ? $Post[0] : null;
  }

  /**
   * Returns the distinct terms for an array of posts
   *
   * @param array $posts
   * @param string $fields
   *
   * @return array|null|object
   */
  public static function distinct_post_terms(array $posts, $fields = '') {
    $ids = array();
    foreach($posts as $post) {
      if ( is_numeric($post) )
        $ids[] = absint($post);
      elseif ( is_a($post, 'WP_Post') )
        $ids[] = $post->ID;
      elseif ( is_a($post, 'Post') )
        $ids[] = $post->id;
    }

    if ( empty($ids) ) return array();

    global $wpdb;
    $ids = array_map( 'intval', $ids );
    $ids = implode(',', $ids);

    $query = "
      SELECT DISTINCT t.term_id, t.name, t.slug, t.term_group

      FROM `wp_terms` as t
      JOIN `wp_term_taxonomy` as tax ON t.term_id = tax.term_id
      JOIN `wp_term_relationships` as rel ON tax.term_taxonomy_id = rel.term_taxonomy_id

      WHERE rel.object_id IN ($ids);
    ";


    switch($fields) {
    case 'raw':
      return $wpdb->get_results($query, OBJECT_K);
    case 'ids':
      return $wpdb->get_col($query);
    default:
      $results = $wpdb->get_results($query);
      foreach($results as &$result)
        $result = self::get_controller($result);
      return $results;
    }
  }
};
