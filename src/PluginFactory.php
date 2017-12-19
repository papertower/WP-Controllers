<?php

namespace WPControllers;

/**
 * Creates and manages the single plugin instance
 */
final class PluginFactory {
  /**
   * Create and return a single instance of the Plugin class
   * @return Plugin Single plugin instance
   */
  public static function create($file, $directory) {
		static $plugin = null;

		if ( null === $plugin ) {
			$plugin = new Plugin($file, $directory);
		}

		return $plugin;
  }
}
