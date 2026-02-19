<?php
/**
 * Fired during plugin activation.
 *
 * @package Education_Resources_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ERM_Activator
 */
class ERM_Activator {

	/**
	 * Activation tasks.
	 */
	public static function activate() {
		self::check_requirements();
		self::create_tables();
		// Diferir flush hasta la siguiente carga: el CPT se registra en init(),
		// que corre después del activation hook. Si hacemos flush aquí, las
		// reglas se generan sin el CPT y las URLs dan 404.
		set_transient( 'erm_flush_rewrite_rules', true, 60 );
	}

	/**
	 * Check system requirements.
	 */
	private static function check_requirements() {
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			deactivate_plugins( ERM_BASENAME );
			wp_die(
				esc_html__( 'Este plugin requiere PHP 7.4 o superior.', 'education-resources-manager' ),
				esc_html__( 'Error de activación', 'education-resources-manager' ),
				array( 'back_link' => true )
			);
		}

		global $wp_version;
		if ( version_compare( $wp_version, '6.0', '<' ) ) {
			deactivate_plugins( ERM_BASENAME );
			wp_die(
				esc_html__( 'Este plugin requiere WordPress 6.0 o superior.', 'education-resources-manager' ),
				esc_html__( 'Error de activación', 'education-resources-manager' ),
				array( 'back_link' => true )
			);
		}
	}

	/**
	 * Create custom database tables.
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name     = $wpdb->prefix . 'erm_resource_tracking';

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			resource_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			action_date datetime DEFAULT CURRENT_TIMESTAMP,
			action_type varchar(20) NOT NULL DEFAULT 'view',
			ip_address varchar(45) DEFAULT NULL,
			PRIMARY KEY (id),
			KEY resource_id (resource_id),
			KEY user_id (user_id),
			KEY action_date (action_date),
			KEY action_type (action_type)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'erm_db_version', ERM_VERSION );
	}
}
