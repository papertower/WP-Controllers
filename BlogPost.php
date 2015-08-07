<?php

class BlogPost extends Post {
  public static
    $post_type = BLOG_TYPE;

  public static function category_permalink($category = null, $campus = null) {
    $campus = is_object($campus) ? $campus->slug : $campus;
    $category = is_object($category) ? $category->slug : $category;

    $category_base  = get_option('category_base');
    $slug = 'stories';

    if ( $category && $campus )
      return site_url("$campus/$category_base/$category");
    elseif ( $category )
      return site_url("$category_base/$category");
    elseif ( $campus )
      return site_url("$campus/$slug");
    else
      return get_post_type_archive_link(self::$post_type);
  }

  public static function permalink($campus = null, $ministry = null) {
    $campus = is_object($campus) ? $campus->slug : $campus;
    $ministry = is_object($ministry) ? $ministry->slug : $ministry;
    $slug = 'stories';

    if ( $campus && $ministry )
      return site_url("$campus/$ministry/$slug");
    elseif ( $campus )
      return site_url("$campus/$slug");
    elseif ( $ministry )
      return site_url("all/$ministry/$slug");
    else
      return get_post_type_archive_link(self::$post_type);
  }

  public function recent($numberposts, $exclude = true) {
    // Return the most recent related posts. First by taxonomy,
    // and then by campus, and finally ministry
    $exclude = $exclude ? array($this->id) : false;

    $set_excluded = function($new_posts) use (&$exclude) {
      if ( false === $exclude ) return;
      foreach($new_posts as $post)
        $exclude[] = $post->id;
    };

    $posts = array();

    $category_ids = array();
    $categories = $this->categories();
    foreach($categories as $category)
      $category_ids[] = $category->term_id;

    $tag_ids = array();
    $tags = $this->tags();
    foreach($tags as $tag)
      $tag_ids[] = $tag->term_id;

    if ( !(empty($tag_ids) && empty($category_ids)) ) {
      $taxonomy_args = array(
        'post_type'   => $this->type,
        'numberposts' => $numberposts,
        'post__not_in'=> $exclude,
        'tax_query'   => array(
          'relation'    => 'OR',
        )
      );

      if ( !empty($tag_ids) )
        $taxonomy_args['tax_query'][] = array(
          'taxonomy'    => 'post_tag',
          'terms'       => $tag_ids
        );

      if ( !empty($category_ids) )
        $taxonomy_args['tax_query'][] = array(
          'taxonomy'    => 'category',
          'terms'       => $category_ids
        );

      $posts_by_tax = self::get_controllers($taxonomy_args);
      $set_excluded($posts_by_tax);
      $posts = array_merge($posts, $posts_by_tax);
    }

    $numberposts -= isset($posts_by_tax) ? count($posts_by_tax) : 0;
    if ( !$numberposts ) return $posts;

    $campuses = $this->campuses();
    foreach($campuses as $campus) {
      $campus_posts = $campus->stories($numberposts, null, array('post__not_in'=> $exclude));
      if ( $count = count($campus_posts) ) {
        $posts = array_merge($posts, $campus_posts);
        $numberposts -= $count;
        if ( !$numberposts ) return $posts;
      }

      $set_excluded($campus_posts);
    }

    $ministries = $this->ministries();
    foreach($ministries as $ministry) {
      $ministry_posts = $ministry->stories($numberposts, array('post__not_in'=> $exclude));
      if ( $count = count($ministry_posts) ) {
        $posts = array_merge($posts, $ministry_posts);
        $numberposts -= $count;
        if ( !$numberposts ) return $posts;
      }
    }

    return $posts;
  }

  public function author() {
    if ( isset($this->_author) ) return $this->_author;
    $author = self::get_controllers(array(
      'post_type'       => STAFF_TYPE,
      'numberposts'     => 1,
      'suppress_filters'=> false,
      'post_belongs'    => $this->id
    ));

    return $this->_author = empty($author[0]) ? null : $author[0];
  }

  public function categories() {
    return isset($this->_categories)
      ? $this->_categories
      : $this->_categories = $this->terms('category');
  }

  public function tags() {
    return isset($this->_tags)
      ? $this->_tags
      : $this->_tags = $this->terms('post_tag');
  }

  public function ministries() {
    return isset($this->_ministries)
      ? $this->_ministries
      : $this->_ministries = self::get_controllers(array(
          'post_type'       => MINISTRY_TYPE,
          'numberposts'     => -1,
          'suppress_filters'=> false,
          'post_belongs'    => $this->id
        ));
  }

  public function campuses() {
    return isset($this->_campuses)
      ? $this->_campuses
      : $this->_campuses = self::get_controllers(array(
          'post_type'       => CAMPUS_TYPE,
          'numberposts'     => -1,
          'suppress_filters'=> false,
          'post_belongs'    => $this->id
        ));
  }
}

?>
