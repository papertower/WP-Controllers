<?php

namespace WPControllers\CacheHandler;

abstract class BaseHandler {
  /**
   * Constants for what type of event occurred
   * @var string
   */
  const EVENT_INSERT = 'insert';
  const EVENT_UPDATE = 'update';
  const EVENT_DELETE = 'delete';
}
