<?php
/**
 * Service interface.
 *
 * @package EPDC\Conversations\Infrastructure
 */

declare( strict_types=1 );

namespace EPDC\Conversations\Infrastructure;

interface ServiceInterface {
	/**
	 * Register hooks for a service.
	 */
	public function register(): void;
}
