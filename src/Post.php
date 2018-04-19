<?php

namespace WPControllers;

use \WP_Error;
use \WP_Post;

/**
 * Base Post Controller.
 * This is a [decorator](http://en.wikipedia.org/wiki/Decorator_pattern) class for the
 * native WP_Post class. It extends it with common controller functions as well as
 * expanding on the general functionality. All other post controllers inherit from this.
 *
 * @version 0.7.0
 */
class Post {
  /**
   * @var string $post_type
   */
  public static
    $controller_post_type = 'post';

  /**
   * @var array $_controller_templates        template_slug => controller
   * @var array $_controller_post_types       post_type => controller
   */
  private static
    $_controller_templates      = array(),
    $_controller_post_types     = array();

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
   * @var int    parent_id
   * @var int    menu_order
   * @var int    comment_count
   * @var string comment_status
   * @var string date
   * @var string date_gmt
   * @var string modified
   * @var string modified_gmt
   * @var string password
   * @var string to_ping
   * @var string pinged
   * @var string guide
   * @var string filter
   * @var string ping_status
   * @var string mime_type
   * @var string content_filtered
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
    $parent_id,
    $menu_order,
    $comment_count,
    $comment_status,
    $date,
    $date_gmt,
    $modified,
    $modified_gmt,
    $password,
    $to_ping,
    $pinged,
    $ping_status,
    $guid,
    $filter,
    $mime_type,
    $content_filtered,
    $meta;

  /**
   * Retrieves the controller for the post type
   * @since 0.7.0
   * @param string|int|object $key post id, slug, or WP_Post object
   * @param array $options
   * @return Post|WP_Error
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
        'post_type'   => empty($options['post_type']) ? 'any' : $options['post_type'],
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
    $controller_class = self::get_controller_class($_post, $options);
    $controller = new $controller_class ($_post);

    wp_cache_set($controller->slug, $controller->id, 'postcontroller_slug', MINUTE_IN_SECONDS * 10);
    wp_cache_set($controller->id, $controller, 'postcontroller', MINUTE_IN_SECONDS * 10);

    return $controller;
  }

  /**
   * Returns controllers for an array of posts or wp_query arguments
   * @since 0.7.0
   *
   * @param array $args     Either an array of WP_Post objects or get_posts arguments
   * @param array $options  Options to pass with the get_controllers function
   *
   * @return array
   */
  public static function get_controllers($args = null, $options = array()) {
    if ( is_null($args) ) {
      // Retrieve archive posts
      global $wp_query;
      if ( isset($wp_query->posts) ) {
        $posts = $wp_query->posts;
      } else {
        trigger_error('No posts found in the wp_query. Please use this only in a post archive template.', E_USER_WARNING);
        return array();
      }

    } elseif ( isset($args[0]) && ( is_object($args[0]) || is_numeric($args[0]) ) ) {
      // Turn array of WP_Posts into controllers
      $posts = $args;

    } elseif ( !empty($args) ) {
      // Retrieve posts from get_posts arguments
      if ( !isset($args['suppress_filters']) ) $args['suppress_filters'] = false;
      $posts = get_posts($args);

    } else {
      // Probably empty array of what would be posts or something unexpected
      return array();
    }

    $controllers = array();
    foreach($posts as $post) {
      $controllers[] = self::get_controller($post, $options);
    }

    return $controllers;
  }

  /**
   * Returns the class name for the corresponding post
   * @param  WP_Post $post    WP_Post to get the class for
   * @param  array   $options Options passed for the post
   * @return string           Class name
   */
  private static function get_controller_class($post, $options = array()) {
    // Check for template-specific controller first
    $class = self::get_template_class($post);
    if ( $class ) return $class;

    // Set all the post type classes if not set
    if ( empty(self::$_controller_post_types) ) {
      self::$_controller_post_types['image'] = 'Picture';

      $post_types = get_post_types(array(), 'objects');
      foreach($post_types as $type) {
        if ( !empty($type->wp_controller_class) ) {
          self::$_controller_post_types[$type->name] = $type->wp_controller_class;
        }
      }
    }

    if ( 'attachment' === $post->post_type && wp_attachment_is_image($post) ) {
      $class = self::$_controller_post_types['image'];
    } else {
      $class = isset(self::$_controller_post_types[$post->post_type])
        ? self::$_controller_post_types[$post->post_type]
        : __CLASS__;
    }

    return apply_filters('wp_controllers_post_class', $class, $post, $options);
  }

  /**
   * get_template_class
   * @param  WP_Post  $post post to retrieve the template class for
   * @return string|false   class if there is one, false if not class or template
   */
  private static function get_template_class($post) {
    $template = get_page_template_slug($post);
    if ( !$template ) return false;

    if ( isset(self::$_controller_templates[$template]) ) {
      return self::$_controller_templates[$template];

    } else {
      $data = get_file_data(get_template_directory() . "/$template", array(
        'controller_class' => 'WP Controller'
      ));

      $class = $data['controller_class'];

      if ( is_child_theme() ) {
        $data = get_file_data(get_stylesheet_directory() . "/$template", array(
          'controller_class' => 'WP Controller'
        ));

        $class = !empty($data['controller_class']) ? $data['controller_class'] : $class;
      }

      return self::$_controller_templates[$template] = $class ? $class : false;
    }
  }

  /**
   * Called when the cache for a post controller needs to be flushed. Calls the flush_cache static
   * method for the class the post belongs to.
   * @param  WP_Post $post  post object that needs to be invalidated
   * @param  string  $event event which triggered the flush
   */
  public static function trigger_cache_flush($post, $event) {
    wp_cache_delete($post->post_name, 'postcontroller_slug');
    wp_cache_delete($post->ID, 'postcontroller');

    $controller_class = self::get_controller_class($post);
    if ( $controller_class && method_exists($controller_class, 'flush_cache') ) {
      $controller_class::flush_cache($post, $event);
    }
  }

  /**
   * Returns the meta class to be used for the post
   * @param  WP_Post $post Post the meta class will be used for
   * @return string        Meta class
   */
  protected static function get_meta_class($post) {
    return apply_filters('wp_controllers_meta_class', Meta::class, $post);
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
    $this->post       = $post;
    $this->id         = $this->post->ID;
    $this->slug       = $this->post->post_name;
    $this->title      = $this->post->post_title;
    $this->excerpt    = $this->post->post_excerpt;
    $this->content    = $this->post->post_content;
    $this->status     = $this->post->post_status;
    $this->type       = $this->post->post_type;
    $this->parent_id  = $this->post->post_parent;
    $this->author     = $this->post->post_author;
    $this->menu_order = $this->post->menu_order;
    $this->password   = $this->post->post_password;
    $this->to_ping    = $this->post->to_ping;
    $this->pinged     = $this->post->pinged;
    $this->guid       = $this->post->guid;
    $this->filter     = $this->post->filter;
    $this->ping_status = $this->post->ping_status;
    $this->mime_type = $this->post->post_mime_type;
    $this->content_filtered = $this->post->post_content_filtered;

    // Comments
    $this->comment_count  = $this->post->comment_count;
    $this->comment_status = $this->post->comment_status;

    // Dates
    $this->date         = $this->post->post_date;
    $this->date_gmt     = $this->post->post_date_gmt;
    $this->modified     = $this->post->post_modified;
    $this->modified_gmt = $this->post->post_modified_gmt;

    // Meta class
    $meta_class = static::get_meta_class($post);
    $this->meta = new $meta_class($this->id, 'post');
  }

  public function __get($name) {
    if ( 'ID' === $name ) {
      return $this->id;
    }

    if ( 'post_name' === $name ) {
      return $this->slug;
    }

    if ( 'post_parent' === $name ) {
      return $this->parent_id;
    }

    if ( 0 === strpos($name, 'post_') ) {
      $short_key = substr($name, 5);
      if ( isset($this->$short_key) ) {
        return $this->$short_key;
      }
    }

    return null;
  }

  public function __isset($name) {
    if ( 0 === strpos($name, 'post_') ) {
      $short_key = substr($name, 5);
      return isset($this->$short_key);
    }

    return false;
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
   * @return Post|false Returns controller if available, and false if no post
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
    return isset($posts[0]) ? static::get_controller($posts[0]) : false;
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
  public static function archive_url() {
    return get_post_type_archive_link(static::$controller_post_type);
  }

  /**
   * Returns the author User controller
   * @return User|WP_Error
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

    return static::get_controllers($args);
  }

  /**
   * Returns the featured image
   * @since 0.1.0
   * @param array $options (optional)
   * @return object|null
   */
  public function featured_image($options = array()) {
    $id = $this->meta->_thumbnail_id('single');
    return $id ? static::get_controller( $id, $options ) : null;
  }

  /**
   * Returns whether or not the given term(s) are associated with the post. If no terms are provided
   * then whether or not the post has any terms within the taxonomy
   * @param  string             $taxonomy Single taxonomy name
   * @param  int|string|array   $term     Optional. Term term_id, name, slug or array of said. Default null.
   * @return boolean
   */
  public function has_term($taxonomy, $term = null) {
    $result = is_object_in_term($this->id, $taxonomy, $term);
    return $result && !is_wp_error($result);
  }

  /**
   * Returns the content with standard filters applied
   * if password is required it returns the form
   * @return string
   */
  public function content() {
    if ( $this->password_required() ) {
      return get_the_password_form($this->post);
    } else {
      return apply_filters('the_content', $this->content);
    }
  }

  /**
   * Returns true if the post is password protected and the password is still required
   * @return boolean
   */
  public function password_required() {
    return post_password_required($this->post);
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
        'post_type'    => static::$controller_post_type,
        'numberposts'  => -1,
        'tax_query'    => array(
          array(
            'taxonomy'    => $taxonomy,
            'field'      => 'id',
            'terms'      => $term->term_id
          )
        )
      ));

      $term->posts = array();
      foreach($posts as $post)
        $term->posts[] = static::get_controller($post);

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

    return static::get_controllers(array(
      'post_type'   => static::$controller_post_type,
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
   * @return array                    array of controllers or empty array
   */
  public static function random_posts($count = -1, $post_type = null) {
    $post_type = ( $post_type ) ? $post_type : static::$controller_post_type;

    $ids = get_posts(array(
      'post_type'   => $post_type,
      'numberposts' => -1,
      'fields'      => 'ids'
    ));

    if ( empty($ids) ) return array();

    shuffle($ids);

    if ( $count !== -1 )
      $ids = array_slice($ids, 0, $count);

    return static::get_controllers(array(
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
