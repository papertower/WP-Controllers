<?php

namespace WPControllers\CacheHandler;

use \WPControllers\Plugin\Service;
use \WPControllers\Term;

/**
 * Handles clearing the cache for the Term controller
 */
class TermCache extends BaseHandler implements Service {
  /**
   * Constants for what type of event occurred
   * @var string
   */
  const EVENT_INSERT = 'insert';
  const EVENT_UPDATE = 'update';
  const EVENT_DELETE = 'delete';

  /**
   * Connects to hooks where the cache should be flushed
   */
  public function register() {
    add_action('created_term', [$this, 'created_term'], 10, 3);
    add_filter('edit_term', [$this, 'edit_term'], 10, 3);
    add_filter('pre_delete_term', [$this, 'pre_delete_term'], 10, 2);
  }

  /**
   * Triggers when a new term is inserted
   * @param  integer  $term_id            New term id
   * @param  integer  $term_taxonomy_id   New term taxonomy id
   * @param  string   $taxonomy           Taxonomy of new term
   */
  public function created_term($term_id, $term_taxonomy_id, $taxonomy) {
    $this->trigger_flush(get_term($term_id, $taxonomy), self::EVENT_INSERT);
  }

  /**
   * Triggers when an existing term is updated
   * @param  integer  $term_id            New term id
   * @param  integer  $term_taxonomy_id   New term taxonomy id
   * @param  string   $taxonomy           Taxonomy of new term
   */
  public function edit_term($term_id, $term_taxonomy_id, $taxonomy) {
    $this->trigger_flush(get_term($term_id, $taxonomy), self::EVENT_UPDATE);
  }

  /**
   * Triggers when an existing term is deleted
   * @param  integer  $term_id            New term id
   * @param  string   $taxonomy           Taxonomy of new term
   */
  public function pre_delete_term($term_id, $taxonomy) {
    $this->trigger_flush(get_term($term_id, $taxonomy), self::EVENT_DELETE);
  }

  /**
   * Triggers the flush to cache with the Term controller
   * @param  WP_Term  $term  Term being affected
   * @param  string   $event Event triggering the flush
   */
  private function trigger_flush($term, $event) {
    Term::trigger_flush_cache($term, $event);
  }
}
