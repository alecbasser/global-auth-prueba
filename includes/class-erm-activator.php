<?php
/**
 * Se ejecuta durante la activación del plugin.
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
		self::register_archived_status();
		self::create_tables();
		self::migrate_status_to_post_status();
		// Diferir flush hasta la siguiente carga: el CPT se registra en init(),
		// que se ejecuta después del hook de activación. Si hacemos flush aquí, las
		// reglas se generan sin el CPT y las URLs devuelven 404.
		set_transient( 'erm_flush_rewrite_rules', true, 60 );
	}

	/**
	 * Migrar estado meta a post_status para recursos existentes.
	 * Asegura que los posts con _erm_publication_status = 'archived' obtengan post_status = 'archived'.
	 */
	private static function migrate_status_to_post_status() {
		$posts = get_posts( array(
			'post_type'      => 'erm_resource',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );

		foreach ( $posts as $post_id ) {
			$meta_status = get_post_meta( $post_id, '_erm_publication_status', true );
			$post       = get_post( $post_id );
			if ( ! $post || empty( $meta_status ) ) {
				continue;
			}

			$map = array(
				'draft'     => 'draft',
				'published' => 'publish',
				'archived'  => 'archived',
			);
			$target_status = $map[ $meta_status ] ?? 'publish';
			if ( $post->post_status !== $target_status ) {
				wp_update_post( array(
					'ID'          => $post_id,
					'post_status' => $target_status,
				) );
			}
		}
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
	 * Crear tablas de base de datos personalizadas.
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

	/**
	 * Registrar estado de post personalizado "archived" (necesario antes de la migración).
	 */
	private static function register_archived_status() {
		register_post_status(
			'archived',
			array(
				'label'                     => _x( 'Archivado', 'post status', 'education-resources-manager' ),
				'public'                    => false,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop(
					'Archivado <span class="count">(%s)</span>',
					'Archivados <span class="count">(%s)</span>',
					'education-resources-manager'
				),
			)
		);
	}
}
