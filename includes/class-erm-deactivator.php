<?php
/**
 * Fired during plugin deactivation.
 *
 * @package Education_Resources_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ERM_Deactivator
 */
class ERM_Deactivator {

	/**
	 * Deactivation tasks.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
