<?php

namespace WPControllers\CacheHandler;

use \WPControllers\Plugin\Service;
use \WPControllers\User;

/**
 * Handles clearing the cache for the User controller
 */
class UserCache extends BaseHandler implements Service {
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
    add_action('user_register', [$this, 'user_register']);
    add_action('profile_update', [$this, 'profile_update'], 10, 2);
    add_action('delete_user', [$this, 'delete_user'], 10, 2);
  }

  /**
   * Triggers when a new user is registered
   * @param  integer $user_id new user id
   */
  public function user_register($user_id) {
    $this->trigger_flush(get_user_by('id', $user_id), self::EVENT_INSERT);
  }

  /**
   * Triggers when a user is updated
   * @param int $user_id
   * @param WP_User $old_user
   */
  public function profile_update($user_id, $old_user) {
    $this->trigger_flush($old_user, self::EVENT_UPDATE);
  }

  /**
   * Triggers when a user is deleted
   * @param int $user_id
   * @param int $reassign_id
   */
  public function delete_user($user_id, $reassign_id) {
    $this->trigger_flush(get_user_by('id', $user_id), self::EVENT_DELETE);
  }

  /**
   * Triggers the flush to cache with the User controller
   * @param  WP_User  $user  User being affected
   * @param  string   $event Event triggering the flush
   */
  private function trigger_flush($user, $event) {
    User::trigger_flush_cache($user, $event);
  }
}
