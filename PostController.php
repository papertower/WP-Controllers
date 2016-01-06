<?php
/**
 * The PostController class and get_post_controller functions
 */

/**
 * Base PostController Controller.
 * This is a [decorator](http://en.wikipedia.org/wiki/Decorator_pattern) class for the
 * native WP_POST class. It extends it with common controller functions as well as
 * expanding on the general functionality. All other post contollers inherit from this.
 *
 * @version 0.7.0
 * @author Jason Adams <jason.the.adams@gmail.com>
 */
class PostController {
  /**
   * @var string $post_type the post type this controller is intended for
   * @var array $post_types post_type => controller
   * @var array $page_templates template_slug => controller
   */
  private static
    $post_type      = 'post',
    $post_types     = array(),
    $page_templates = array(),
    $page_template  = null;

  /**
   * @var object $post WP_Post class
   */
  protected
    $post;

  /**
   * @var object $post WP_Post class
   * @var int    id
   * @var string slug
   * @var string title
   * @var string excerpt
   * @var string content
   * @var string status
   * @var string type
   * @var int    author
   * @var int    menu_order
   * @var int    comment_count
   * @var string comment_status
   * @var string date
   * @var string date_gmt
   * @var string modified
   * @var string modified_gmt
   * @var Meta   meta
   */
  public
	$id,
	$slug,
	$title,
	$excerpt,
	$content,
	$status,
	$type,
	$author,
	$menu_order,
	$comment_count,
	$comment_status,
	$date,
	$date_gmt,
	$modified,
	$modified_gmt,
    $meta;

  /**
   * Retrieves the controller for the post type
   * @since 0.7.0
   * @param string|int|object $key post id, slug, or WP_Post object
   * @param array $options
   * @return PostController
   */
  public static function get_controller($key = null, $options = array()) {
    if ( is_null($key) ) {
      global $post;
      $key = $post;
      if ( empty($key) )
        return new WP_Error('no_global_post', 'Global post is null', $key);
    }

    // Check for cached object and set $post to the WP_Post otherwise
    if ( is_numeric($key) ) {
      $cached_post = wp_cache_get($key, 'postcontroller');
      if ( false !== $cached_post ) return $cached_post;
      $_post = get_post(intval($key));
      if ( !$_post ) return new WP_Error('post_id_not_found', 'No post was found for the provided ID', $key);

    } elseif ( is_string($key) ) {
      $post_id = wp_cache_get($key, 'postcontroller_slug');
      if ( false !== $post_id ) return wp_cache_get($post_id, 'postcontroller');

      $posts = get_posts(array(
        'name'        => $key,
        'post_type'   => 'any',
        'post_status' => array('publish', 'private'),
        'numberposts' => 1
      ));

      if ( empty($posts) )
        return new WP_Error('post_slug_not_found', 'No post was found for the provided slug', $key);

      $_post = $posts[0];

    } elseif ( is_object($key) ) {
      $_post = wp_cache_get($key->ID, 'postcontroller');
      if ( false !== $_post ) return $_post;

      $_post = $key;

    } else {
      return new WP_Error('invalid_key_type', 'Key provied is unsupported type', $key);
    }

    // Construct, cache, and return post
    if ( $_post->post_type === 'page' ) {
      $template = str_replace('.php', '', get_page_template_slug($_post));

      $controller = $template && isset(self::$page_templates[$template])
        ? new self::$page_templates[$template] ($_post)
        : new Page ($_post);

    } elseif ( $_post->post_type === 'attachment' && wp_attachment_is_image($_post->ID) ) {
      $controller = new self::$post_types['image'] ($_post);

    } else {
      $controller = ( isset(self::$post_types[$_post->post_type]) )
        ? new self::$post_types[$_post->post_type] ($_post)
        : new self ($_post);
    }

    wp_cache_set($controller->slug, $controller->id, 'postcontroller_slug', MINUTE_IN_SECONDS * 10);
    wp_cache_set($controller->id, $controller, 'postcontroller', MINUTE_IN_SECONDS * 10);

    return $controller;
  }

  /**
   * Returns controllers for an array of posts or wp_query arguments
   * @since 0.7.0
   * @param array $args Either an array of WP_Post objects or get_posts arguments
   * @return array
   */
  public static function get_controllers($args = null) {
    if ( is_null($args) ) {
      // Retrieve archive posts
      global $wp_query;
      if ( isset($wp_query->posts) ) {
        $posts = $wp_query->posts;
      } else {
        return new WP_Error('no_posts_found', 'No posts were found in the wp_query. This must be used within a single or archive template.');
      }

    } elseif ( isset($args[0]) && is_object($args[0]) ) {
      // Turn array of WP_Posts into controllers
      $posts = $args;

    } else {
      // Retrieve posts from get_posts arguments
      if ( !isset($args['suppress_filters']) ) $args['suppress_filters'] = false;
      $posts = get_posts($args);

    }

    $controllers = array();
    foreach($posts as $post) {
      $controllers[] = self::get_controller($post);
    }

    return $controllers;
  }

  /**
   * Static initialization.
   * Called after this file is required. Adds the post type or page template
   * to the self::$post_types and self::$page_templates arrays, respectively,
   * for later use with the self::get_controller() function.
   * @since 0.4.0
   * @see PostController::$post_types
   * @see PostController::$page_templates
   * @see PostController::get_controller()
   */
  public static function _construct() {
    $static_class = get_called_class();

    // Add post type to list
    if ( isset(static::$post_type) ) {
      $post_types = is_array(static::$post_type) ? static::$post_type : array(static::$post_type);
      foreach($post_types as $post_type) {
        if ( !isset(self::$post_types[$post_type]) )
          self::$post_types[$post_type] = $static_class;
      }
    }

    // Add page template to list
    if ( isset(static::$page_template) ) {
      $templates = is_array(static::$page_template) ? static::$page_template : array(static::$page_template);
      foreach($templates as $template) {
        if ( !isset(self::$page_templates[$template]) )
          self::$page_templates[$template] = $static_class;
      }
    }

    // Apply hooks once
    if ( __CLASS__ === $static_class ) {
      add_filter('wp_insert_post', array(__CLASS__, 'wp_insert_post'), 10, 3);
      add_action('trash_post', array(__CLASS__, 'trash_post'));
    }
  }

  /**
   * Callback for the wp_insert_post filter, used to invalidate the cached
   * controllers for the saved post
   * @ignore
   */
  public static function wp_insert_post($post_id, $post, $is_update) {
    if ( !$is_update ) return;

    wp_cache_delete($post->post_name, 'postcontroller_slug');
    wp_cache_delete($post_id, 'postcontroller');
  }

  /**
   * Callback for the trash_post filter, used to invalidate the cached
   * controllers for the trashed post
   * @ignore
   */
  public static function trash_post() {
    if ( did_action('trash_post') ) return;

    wp_cache_delete($post->post_name, 'postcontroller_slug');
    wp_cache_delete($post_id, 'postcontroller');
  }

  /**
   * Constructor.
   * Protect the constructor as we do not want the controllers
   * to be instantiated directly. This is to ensure caching.
   * @since 0.7.0
   * @param WP_Post $post
   */
  protected function __construct($post) {
    // Standard Properties
    $this->post       =& $post;
    $this->id         =& $this->post->ID;
    $this->slug       =& $this->post->post_name;
    $this->title      =& $this->post->post_title;
    $this->excerpt    =& $this->post->post_excerpt;
    $this->content    =& $this->post->post_content;
    $this->status     =& $this->post->post_status;
    $this->type       =& $this->post->post_type;
    $this->author     =& $this->post->post_author;
    $this->menu_order =& $this->post->menu_order;

    // Comments
    $this->comment_count  =& $this->post->comment_count;
    $this->comment_status =& $this->post->comment_status;

    // Dates
    $this->date         =& $this->post->post_date;
    $this->date_gmt     =& $this->post->post_date_gmt;
    $this->modified     =& $this->post->post_modified;
    $this->modified_gmt =& $this->post->post_modified_gmt;

    // Meta class
    $this->meta = new Meta($this->id, 'post');
  }

  /**
   * Returns adjacent post controller.
   *
   * @see https://codex.wordpress.org/Function_Reference/get_adjacent_post
   *
   * @param bool|false $same_term
   * @param array $excluded_terms
   * @param bool|true $previous
   * @param string $taxonomy
   *
   * @return PostController|false Returns controller if available, and false if no post
   */
  protected function adjacent_post($same_term = false, $excluded_terms = array(), $previous = true, $taxonomy = 'category') {
    $date_type = $previous ? 'before' : 'after';

    $arguments = array(
      'post_type'   => $this->type,
      'numberposts' => 1,
      'order'       => $previous ? 'DESC' : 'ASC',
      'post__not_in'=> array($this->id),
      'date_query'  => array(
        array(
          $date_type    => $this->date('F j, Y')
        )
      )
    );

    if ( $taxonomy && $same_term ) {
      $terms = get_terms($taxonomy, array(
        'fields'  => 'ids',
        'exclude' => $excluded_terms
      ));

      if ( ! (empty($terms) || is_wp_error($terms) ) ) {
        $arguments['tax_query'] = array(
          array(
            'taxonomy'  => $taxonomy,
            'field'     => 'term_id',
            'terms'     => $terms
          )
        );
      }
    }

    $posts = get_posts($arguments);
    return isset($posts[0]) ? self::get_controller($posts[0]) : false;
  }

  /**
   * Returns post url
   * @return string
   */
  public function url() {
    return isset($this->_url) ? $this->_url : $this->_url = get_permalink($this->id);
  }

  /**
   * Returns archive url
   * @return string
   */
  public function archive_url() {
    if ( isset($this->_archive_url) ) return $this->_archive_url;
    return $this->_archive_url = get_post_type_archive_link($this->type);
  }

  /**
   * Returns the author User controller
   * @return object
   */
  public function author() {
    if ( isset($this->_author) ) return $this->_author;
    return $this->_author = get_user_controller($this->author);
  }

  /**
   * Returns timestamp or formatted date
   * @param string  $format   Date format
   * @param boolean $gmt      whether to use gmt date
   * @return false|string
   */
  public function date($format, $gmt = false) {
    if ( 'timestamp' === $format ) {
      return $gmt ? strtotime($this->date_gmt) : strtotime($this->date);
    } else {
      return $gmt ? date($format, $this->date('timestamp', $this->date_gmt)) : date($format, $this->date('timestamp', $this->date));
    }
  }

  /**
   * Returns timestamp or formatted modified date
   * @param string  $format   Date format
   * @param boolean $gmt      whether to use gmt date
   * @return false|string
   */
  public function modified($format, $gmt = false) {
    if ( 'timestamp' === $format ) {
      return $gmt ? strtotime($this->modified_gmt) : strtotime($this->modified);
    } else {
      return $gmt ? date($format, $this->date('timestamp', $this->modified_gmt)) : date($format, $this->date('timestamp', $this->modified));
    }
  }

  /**
   * Returns next post if available
   * @param boolean $same_term
   * @param array   $excluded_terms
   * @param string  $taxonomy
   * @return null|object
   */
  public function next_post($same_term = false, $excluded_terms = array(), $taxonomy = 'category') {
    return $this->adjacent_post($same_term, $excluded_terms, false, $taxonomy);
  }

  /**
   * Returns previous post if available
   * @param boolean $same_term
   * @param array   $excluded_terms
   * @param string  $taxonomy
   * @return null|object
   */
  public function previous_post($same_term = false, $excluded_terms = array(), $taxonomy = 'category') {
    return $this->adjacent_post($same_term, $excluded_terms, true, $taxonomy);
  }

  /**
   * Retrieves taxonomy terms and can apply urls
   * @since 0.1.0
   * @param string|array $taxonomies
   * @return array|WP_Error
   */
  public function terms($taxonomies) {
    // Retrieve terms
    $terms = wp_get_post_terms($this->id, $taxonomies);

    if ( is_wp_error($terms) ) return $terms;

    $controllers = array();
    foreach($terms as $term) {
      $controllers[] = Term::get_controller($term);
    }

    return $controllers;
  }

  /**
   * Returns array of posts that share taxonomy
   * @since 0.1.0
   * @param string $post_type
   * @param string|array $taxonomies
   * @param array $options (optional)
   * @return array|null of controllers
   */
  public function related_posts($post_type, $taxonomies, $options = null) {
    $options = wp_parse_args( $options, array(
      'count'    => -1,
      'meta_query'=> null
    ));

    $args = array(
      'post_type'     => $post_type,
      'posts_per_page'=> $options['count'],
      'post__not_in'  => array($this->id),
      'tax_query'     => array(
        'relation'      => 'OR',
      )
    );

    if ( $options['meta_query'] ) {
      $args['meta_query'] = $options['meta_query'];
    }

    if ( !is_array($taxonomies) )
      $taxonomies = array($taxonomies);

    foreach($taxonomies as $index => $taxonomy) {
      // Retrieve terms and continue if empty
      $terms = $this->terms($taxonomy);
      if ( is_null($terms) ) continue;

      // Store the ids into an array
      $term_ids = array();
      foreach($terms as $term)
        $term_ids[] = $term->term_id;

      $args['tax_query'][] = array(
        'taxonomy'  => $taxonomy,
        'field'    => 'id',
        'terms'    => $term_ids
      );
    }

    if ( count($args['tax_query']) === 1 )
      return null;

    return self::get_controllers($args);
  }

  /**
   * Returns the featured image
   * @since 0.1.0
   * @param array $options (optional)
   * @return object|null
   */
  public function featured_image($options = array()) {
    $id = get_post_thumbnail_id($this->id);
    return $id ? get_picture_controller( $id, $options ) : null;
  }

  /**
   * Returns the content with standard filters applied
   * @return string
   */
  public function content() {
    return apply_filters('the_content', $this->content);
  }

  /**
   * Returns the title with the WP filters applied
   * @return string
   */
  public function title() {
    return apply_filters('the_title', $this->title, $this->id, $this);
  }

  /**
   * Returns the filtered excpert or limited content if no filter exists
   * @since 0.1.0
   * @param int $word_count (default: 40)
   * @param string $ellipsis (default: '...')
   * @param boolean $apply_filters (default: true)
   * @return string
   */
  public function excerpt($word_count = 40, $ellipsis = '...', $apply_filters = true) {
    if ( !empty($this->excerpt) )
      return ($apply_filters) ? apply_filters('the_excerpt', $this->excerpt) : $this->excerpt;

    if ( $apply_filters ) {
      remove_filter('the_excerpt', 'wpautop');
      $content = wp_kses($this->content, array(
        'em'    => array(),
        'strong'=> array(),
        'u'     => array(),
        'a'     => array(
          'href'  => array(),
          'title' => array()
        )
      ));
      $content = strip_shortcodes($content);
      $content = apply_filters('the_excerpt', $content);
    } else
      $content = $this->content;

    return $word_count ? wp_trim_words($content, $word_count, $ellipsis) : $content;
  }

  /**
   * Returns post controllers organized by terms
   * @since 0.1.0
   * @param string $taxonomy
   * @return array
   */
  protected static function get_categorized_posts($taxonomy) {
    $categorized_posts = array();
    $terms = get_terms($taxonomy);

    foreach($terms as $term) {
      $posts = get_posts(array(
        'post_type'    => static::$post_type,
        'numberposts'  => -1,
        'tax_query'    => array(
          array(
            'taxonomy'    => $taxonomy,
            'field'      => 'id',
            'terms'      => $term->term_id
          )
        )
      ));

      foreach($posts as $post)
        $term->posts[] = self::get_controller($post);

      $categorized_posts[] = $term;
    }

    return $categorized_posts;
  }

  /**
   * Returns the most recent n posts
   * @since 0.1.0
   * @param int $numberposts
   * @param boolean|array $exclude excludes current post if true
   * @return array
   */
  public static function get_recent($numberposts, $exclude = true) {
    if ( !is_array($exclude) ) {
      if ( true === $exclude ) {
        $id = get_the_ID();
        if ( false !== $id )
          $exclude = array($id);
      } else
        $exclude = array();
    }

    return self::get_controllers(array(
      'post_type'   => static::$post_type,
      'numberposts' => $numberposts,
      'post__not_in'=> $exclude
    ));
  }

  /**
   * Retrieves random posts
   *
   * Retrieves a number of randomized posts. Use this instead of the
   * ORDER BY RAND method, as that can have tremendous overhead
   * @since 0.5.0
   * @param int           $count      the number of posts to return randomized
   * @param array|string  $post_type  (optional) post type name or array thereof
   * @return object                   array of controllers or empty array
   */
  public static function random_posts($count = -1, $post_type = null) {
    $post_type = ( $post_type ) ? $post_type : static::$post_type;

    $ids = get_posts(array(
      'post_type'   => $post_type,
      'numberposts' => -1,
      'fields'      => 'ids'
    ));

    if ( empty($ids) ) return array();

    shuffle($ids);

    if ( $count !== -1 )
      $ids = array_slice($ids, 0, $count);

    return self::get_controllers(array(
      'post_type'   => $post_type,
      'numberposts' => $count,
      'post__in'    => $ids
    ));
  }

  /**
   * Returns whether provided id belongs to post type
   * @param integer $id
   * @param string $type
   * @return boolean
   */
  public static function is_post($id, $type = 'any') {
    $args = array(
      'suppress_filters'=> false
      ,'post_type'      => $type
      ,'fields'         => 'ids'
      ,'posts_per_page' => 1
    );

    if ( is_numeric($id) ) {
      $args['post__in'] = array($id);
    } else {
      $args['name'] = $id;
    }

    return ( !empty(get_posts($args)) );
  }
};

