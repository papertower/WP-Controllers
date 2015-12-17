<?php

/**
 * Class Page
 */
class Page extends PostController {

  /**
   * @var string $post_type
   */
  protected static
    $post_type = 'page';

  /**
   * @var PostController $_parent
   * @var PostController[] $_children
   */
  protected
    $_parent,
    $_children;

  /**
   * Returns pages children, if any
   * @return array
   */
  public function children() {
    if ( $this->_children ) return $this->_children;

    $this->_children = self::get_pages(array(
      'hierarchical'  => false,
      'parent'        => $this->id,
      'post_status'        => 'publish,private'
    ));

    return $this->_children;
  }

  /**
   * Returns page parent controller, if any
   * @return object|null
   */
  public function parent() {
    if ( $this->_parent ) return $this->_parent;
    if ( $this->post->post_parent == 0 ) return null;

    return $this->_parent = self::get_controller($this->post->post_parent);
  }

  /**
   * Same as standard get_pages but returns as controllers
   *
   * @link http://codex.wordpress.org/Function_Reference/get_pages
   * @param array $args
   * @return array
   */
  public static function get_pages($args) {
    $pages = get_pages($args);

    foreach($pages as &$page)
      $page = self::get_controller($page);

    return $pages;
  }

  /**
   * Returns only top-level pages
   * @param array $args (optional)
   * @return array
   */
  public static function get_base_pages($args = array()) {
    $args['hierarchical'] = false;
    $args['parent'] = 0;

    return self::get_pages($args);
  }

  /**
   * Returns pages of specified template(s)
   * @param array|string $templates
   * @param array $options (optional)
   * @return array
   */
  public static function get_page_templates($templates, $options = array()) {
    $options = array_merge_recursive($options, array(
      'post_type'   => 'page',
      'numberposts' => -1,
      'meta_query'  => array(
        array(
          'key'       => '_wp_page_template',
          'value'     => $templates
        )
      )
    ));

    return self::get_controllers($options);
  }

  /**
   * Returns parent controller for post in loop
   * @return object
   */
  public static function get_parent() {
    global $post;
    return ( $post->post_parent ) ? self::get_controller($post->post_parent) : null;
  }

}

