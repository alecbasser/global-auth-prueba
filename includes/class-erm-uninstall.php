<?php
/**
 * Manejador de desinstalación: limpieza completa de datos con confirmación.
 *
 * @package Education_Resources_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ERM_Uninstall
 */
class ERM_Uninstall {

	/**
	 * Slug de la página.
	 */
	const PAGE_SLUG = 'erm-desinstalar';

	/**
	 * Initialize.
	 */
	public function init() {
		add_filter( 'plugin_action_links_' . ERM_BASENAME, array( $this, 'add_uninstall_link' ) );
		add_action( 'admin_menu', array( $this, 'add_menu_page' ), 999 );
		add_action( 'admin_init', array( $this, 'handle_uninstall' ) );
	}

	/**
	 * Agregar enlace "Desinstalar" a las acciones del plugin.
	 *
	 * @param array $links Enlaces de acción del plugin.
	 * @return array
	 */
	public function add_uninstall_link( $links ) {
		$url = wp_nonce_url(
			admin_url( 'admin.php?page=' . self::PAGE_SLUG ),
			'erm_desinstalar',
			'_ermnonce'
		);
		$links['erm_desinstalar'] = '<a href="' . esc_url( $url ) . '" style="color: #b32d2e;">' . esc_html__( 'Desinstalar', 'education-resources-manager' ) . '</a>';
		return $links;
	}

	/**
	 * Add hidden menu page for uninstall.
	 */
	public function add_menu_page() {
		add_submenu_page(
			null,
			__( 'Desinstalar plugin', 'education-resources-manager' ),
			__( 'Desinstalar', 'education-resources-manager' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Procesar envío del formulario de desinstalación.
	 */
	public function handle_uninstall() {
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== self::PAGE_SLUG ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos para realizar esta acción.', 'education-resources-manager' ) );
		}

		if ( ! isset( $_POST['erm_confirm_desinstalar'] ) || $_POST['erm_confirm_desinstalar'] !== '1' ) {
			return;
		}

		check_admin_referer( 'erm_desinstalar_confirm', 'erm_desinstalar_nonce' );

		$this->run_full_cleanup();

		// Desactivar plugin.
		deactivate_plugins( ERM_BASENAME );

		// Mostrar página de éxito (sin redirección: el plugin está desactivado, así que mostramos ahora).
		$this->render_success_page();
		exit;
	}

	/**
	 * Render confirmation page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos.', 'education-resources-manager' ) );
		}

		if ( ! isset( $_GET['_ermnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_ermnonce'] ) ), 'erm_desinstalar' ) ) {
			wp_die( esc_html__( 'Enlace no válido o expirado.', 'education-resources-manager' ) );
		}

		$count_posts = wp_count_posts( ERM_Post_Type::POST_TYPE );
		$total_posts = isset( $count_posts->publish ) ? (int) $count_posts->publish : 0;
		$total_posts += isset( $count_posts->draft ) ? (int) $count_posts->draft : 0;
		$total_posts += isset( $count_posts->pending ) ? (int) $count_posts->pending : 0;

		?>
		<div class="wrap erm-uninstall-wrap">
			<h1><?php esc_html_e( 'Desinstalar Education Resources Manager', 'education-resources-manager' ); ?></h1>

			<div class="erm-uninstall-warning" style="max-width: 600px; margin: 2rem 0; padding: 1.5rem; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
				<h2 style="margin-top: 0; color: #856404;"><?php esc_html_e( '¿Está seguro?', 'education-resources-manager' ); ?></h2>
				<p><?php esc_html_e( 'Esta acción borrará permanentemente:', 'education-resources-manager' ); ?></p>
				<ul style="margin: 1rem 0;">
					<li><?php esc_html_e( 'Todos los recursos educativos', 'education-resources-manager' ); ?> (<?php echo esc_html( (string) $total_posts ); ?> <?php esc_html_e( 'elementos', 'education-resources-manager' ); ?>)</li>
					<li><?php esc_html_e( 'Todas las categorías y etiquetas', 'education-resources-manager' ); ?></li>
					<li><?php esc_html_e( 'Todos los datos de visualizaciones', 'education-resources-manager' ); ?></li>
				</ul>
				<p><strong><?php esc_html_e( 'Esta acción no se puede deshacer.', 'education-resources-manager' ); ?></strong></p>
			</div>

			<form method="post" action="" id="erm-desinstalar-form">
				<?php wp_nonce_field( 'erm_desinstalar_confirm', 'erm_desinstalar_nonce' ); ?>
				<input type="hidden" name="erm_confirm_desinstalar" value="1" />
				<p>
					<button type="submit" class="button button-primary" id="erm-desinstalar-btn" style="background: #b32d2e; border-color: #b32d2e;">
						<?php esc_html_e( 'Sí, desinstalar todo', 'education-resources-manager' ); ?>
					</button>
					<a href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>" class="button"><?php esc_html_e( 'Cancelar', 'education-resources-manager' ); ?></a>
				</p>
			</form>
		</div>

		<script>
		document.getElementById('erm-desinstalar-form').addEventListener('submit', function(e) {
			if (!confirm('<?php echo esc_js( __( '¿Confirmar desinstalación? Se borrarán todos los datos permanentemente.', 'education-resources-manager' ) ); ?>')) {
				e.preventDefault();
			}
		});
		</script>
		<?php
	}

	/**
	 * Renderizar página de éxito tras la desinstalación.
	 */
	private function render_success_page() {
		wp_safe_redirect( admin_url( 'plugins.php?erm_desinstalado=1&plugin_status=inactive' ) );
		exit;
	}

	/**
	 * Run full cleanup: delete all plugin data.
	 */
	private function run_full_cleanup() {
		global $wpdb;

		// 1. Eliminar todos los recursos (posts).
		$posts = get_posts( array(
			'post_type'      => ERM_Post_Type::POST_TYPE,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );

		foreach ( $posts as $post_id ) {
			wp_delete_post( $post_id, true );
		}

		// 2. Delete taxonomy terms.
		$taxonomies = array( 'erm_resource_category', 'erm_skill_tag' );
		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_terms( array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'ids',
			) );
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term_id ) {
					wp_delete_term( $term_id, $taxonomy );
				}
			}
		}

		// 3. Eliminar tabla de seguimiento.
		$table_name = $wpdb->prefix . 'erm_resource_tracking';
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

		// 4. Eliminar opciones.
		delete_option( 'erm_db_version' );
	}
}
