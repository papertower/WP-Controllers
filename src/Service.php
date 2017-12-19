<?php

namespace WPControllers;

/**
 * Interface for service classes which are intended to be registerd after instantiation.
 */
interface Service {
	/**
	 * Register the current Service.
	 */
	public function register();
}
