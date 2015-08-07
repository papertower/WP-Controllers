<?php
/*
 * @version 0.7.0
 * @author Jason Adams <jason.the.adams@gmail.com>
 */
class Term extends Controller {
  const CACHE_GROUP = 'termcontroller';

  public
    $id,
    $group,
    $taxonomy_id;

  protected function __construct() {}

  public static function _construct() {
    if ( __CLASS__ === get_called_class() ) {
      add_filter('edit_term', array(__CLASS__, 'edited_term'), 10, 3);
      add_filter('pre_delete_term', array(__CLASS__, 'pre_delete_term'), 10, 2);
    }
  }

  public static function get_controller($key = null, $taxonomy = null, $field = 'id', $options = array()) {
    $options = wp_parse_args($options, array(
      'load_meta'         => true,
    ));

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
    } else {
      $term = get_queried_object();
      if ( !isset($term->term_id) )
        return new WP_Error('invalid_queried_object', 'The queried object is not a term', $term);

      $controller = wp_cache_get($term->term_id, self::CACHE_GROUP);
      if ( false !== $controller ) return $controller;
    }

    $controller = new self();
    $controller->load_properties($term);

    if ( $options['load_meta'] ) $controller->meta();

    wp_cache_set($controller->id, $controller, self::CACHE_GROUP, MINUTE_IN_SECONDS * 10);
    wp_cache_set($controller->slug, $controller->id, self::CACHE_GROUP . '_' . 'slug', MINUTE_IN_SECONDS * 10);
    wp_cache_set($controller->name, $controller->id, self::CACHE_GROUP . '_' . 'name', MINUTE_IN_SECONDS * 10);
    wp_cache_set($controller->taxonomy_id, $controller->id, self::CACHE_GROUP . '_' . 'term_taxonomy_id', MINUTE_IN_SECONDS * 10);

    return $controller;
  }

  public static function edit_term($term_id, $term_taxonomy_id, $taxonomy) {
    self::clear_controller_cache($term_id, $taxonomy);
  }

  public static function pre_delete_term($term_id, $taxonomy) {
    self::clear_controller_cache($term_id, $taxonomy);
  }

  public static function clear_controller_cache($term_id, $taxonomy) {
    $term = get_term($term_id, $taxonomy);
    if ( !$term || is_wp_error($term) ) return;

    wp_cache_delete($term_id, self::CACHE_GROUP);
    wp_cache_delete($term->slug, self::CACHE_GROUP . '_slug');
    wp_cache_delete($term->name, self::CACHE_GROUP . '_name');
    wp_cache_delete($term->term_taxonomy_id, self::CACHE_GROUP . '_term_taxonomy_id');
  }

  public static function distinct_post_terms(array $posts, $fields = '') {
    $ids = array();
    foreach($posts as $post) {
      if ( is_numeric($post) )
        $ids[] = absint($post);
      elseif ( is_a($post, 'WP_Post') )
        $ids[] = $post->ID;
      elseif ( is_a($post, 'PostController') )
        $ids[] = $post->id;
    }

    if ( empty($ids) ) return array();

    global $wpdb;
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
      return get_results($query, OBJECT_K);
    case 'ids':
      return get_col($query);
    default:
      $results = get_results($query);
      foreach($results as &$result)
        $result = self::get_controller($result);
      return $results;
    }
  }

  private static function check_object($object) {
    return isset($object->term_id);
  }

  private function load_properties($term) {
    // Load all the term properties
    foreach($term as $key => $value)
      $this->$key = $value;

    // Extra properties
    $this->term         =& $term;
    $this->id           =& $this->term_id;
    $this->group        =& $this->term_group;
    $this->taxonomy_id  =& $this->term_taxonomy_id;
  }

  public function meta() {
    if ( is_object($this->meta) ) return $this->meta;
    return $this->_meta(get_term_custom($this->id));
  }

  public function url() {
    return isset($this->url) ? $this->url : ( $this->url = get_term_link($this->term) );
  }

  public function oldest_post($post_type) {
    if ( !$this->count ) return;

    $Post = get_post_controllers(array(
      'post_type'   => $post_type,
      'numberposts' => 1,
      'orderby'     => 'date',
      'order'       => 'ASC',
      'tax_query'   => array(
        array(
          'taxonomy'    => $this->taxonomy,
          'terms'       => $this->term_id
        )
      )
    ));

    if ( !empty($Post) ) return $Post[0];
  }

  public function description() {
    return apply_filters('the_content', $this->description);
  }

};

if ( !function_exists('get_term_controller') ) {
  function get_term_controller($key = null, $taxonomy = null, $field = 'id', $options = array()) {
    return Term::get_controller($key, $taxonomy, $field, $options);
  }
}
?>
