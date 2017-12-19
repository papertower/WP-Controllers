<?php

namespace WPControllers\CacheHandler;

use WPControllers\Service;
use \Post;

/**
 * Handles clearing the cache for the Post controller
 */
class PostCache extends BaseHandler implements Service {

  /**
   * Connects to hooks where the cache should be flushed
   */
  public function register() {
    add_filter('wp_insert_post', [$this, 'wp_insert_post'], 10, 3);
    add_action('pre_delete_post', [$this, 'pre_delete_post']);
  }

  /**
   * Callback for the wp_insert_post filter, used to invalidate the cache
   * @ignore
   */
  public function wp_insert_post($post_id, $post, $is_update) {
    if ( 'revision' !== $post->post_type ) {
      $this->trigger_flush($post, $is_update ? self::EVENT_UPDATE : self::EVENT_INSERT);
    }
  }

  /**
   * Callback for the pre_delete_post action, used to invalidate the cache
   * @ignore
   */
  public function pre_delete_post($post_id) {
    $post = get_post($post_id);
    if ( 'revision' !== $post && !did_action('pre_delete_post') ) {
      $this->trigger_flush($post, self::EVENT_DELETE);
    }
  }

  /**
   * Triggers the flush to cache with the Post controller
   * @param  WP_Post  $post  Post being affected
   * @param  string   $event Event triggering the flush
   */
  private function trigger_flush($post, $event) {
    Post::trigger_cache_flush($post, $event);
  }
}
