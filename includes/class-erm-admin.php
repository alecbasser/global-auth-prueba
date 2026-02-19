<?php
/**
 * Admin panel for Education Resources Manager.
 *
 * @package Education_Resources_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ERM_Admin
 */
class ERM_Admin {

	/**
	 * Menu slug.
	 */
	const MENU_SLUG = 'erm-dashboard';

	/**
	 * Initialize.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'manage_' . ERM_Post_Type::POST_TYPE . '_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_' . ERM_Post_Type::POST_TYPE . '_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( $this, 'add_list_filters' ) );
		add_filter( 'pre_get_posts', array( $this, 'filter_list_query' ) );
	}

	/**
	 * Add admin menu page.
	 */
	public function add_menu_page() {
		add_submenu_page(
			'edit.php?post_type=' . ERM_Post_Type::POST_TYPE,
			__( 'Dashboard', 'education-resources-manager' ),
			__( 'Dashboard', 'education-resources-manager' ),
			'edit_posts',
			self::MENU_SLUG,
			array( $this, 'render_dashboard' )
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( strpos( $hook_suffix, self::MENU_SLUG ) === false && strpos( $hook_suffix, 'erm_resource' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'erm-admin',
			ERM_URL . 'admin/css/admin-styles.css',
			array(),
			ERM_VERSION
		);

		if ( strpos( $hook_suffix, self::MENU_SLUG ) !== false ) {
			wp_enqueue_script(
				'erm-admin',
				ERM_URL . 'admin/js/admin-scripts.js',
				array(),
				ERM_VERSION,
				true
			);
		}
	}

	/**
	 * Add custom columns to resources list.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( $key === 'title' ) {
				$new_columns['erm_type']   = __( 'Tipo', 'education-resources-manager' );
				$new_columns['erm_level']  = __( 'Nivel', 'education-resources-manager' );
				$new_columns['erm_views']  = __( 'Visualizaciones', 'education-resources-manager' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function render_column( $column, $post_id ) {
		switch ( $column ) {
			case 'erm_type':
				$type = get_post_meta( $post_id, ERM_Post_Type::META_TYPE, true );
				$labels = array(
					'course'   => __( 'Curso', 'education-resources-manager' ),
					'tutorial' => __( 'Tutorial', 'education-resources-manager' ),
					'ebook'    => __( 'Ebook', 'education-resources-manager' ),
					'video'    => __( 'Video', 'education-resources-manager' ),
				);
				echo esc_html( $labels[ $type ] ?? $type );
				break;
			case 'erm_level':
				$level = get_post_meta( $post_id, ERM_Post_Type::META_LEVEL, true );
				$labels = array(
					'beginner'     => __( 'Principiante', 'education-resources-manager' ),
					'intermediate' => __( 'Intermedio', 'education-resources-manager' ),
					'advanced'     => __( 'Avanzado', 'education-resources-manager' ),
				);
				echo esc_html( $labels[ $level ] ?? $level );
				break;
			case 'erm_views':
				echo esc_html( (string) ERM_Database::get_view_count( $post_id ) );
				break;
		}
	}

	/**
	 * Add dropdown filters to resources list.
	 *
	 * @param string $post_type Current post type.
	 */
	public function add_list_filters( $post_type ) {
		if ( $post_type !== ERM_Post_Type::POST_TYPE ) {
			return;
		}

		$type  = isset( $_GET['erm_filter_type'] ) ? sanitize_text_field( wp_unslash( $_GET['erm_filter_type'] ) ) : '';
		$level = isset( $_GET['erm_filter_level'] ) ? sanitize_text_field( wp_unslash( $_GET['erm_filter_level'] ) ) : '';
		$cat   = isset( $_GET['erm_filter_category'] ) ? absint( $_GET['erm_filter_category'] ) : 0;

		?>
		<select name="erm_filter_type">
			<option value=""><?php esc_html_e( 'Todos los tipos', 'education-resources-manager' ); ?></option>
			<option value="course" <?php selected( $type, 'course' ); ?>><?php esc_html_e( 'Curso', 'education-resources-manager' ); ?></option>
			<option value="tutorial" <?php selected( $type, 'tutorial' ); ?>><?php esc_html_e( 'Tutorial', 'education-resources-manager' ); ?></option>
			<option value="ebook" <?php selected( $type, 'ebook' ); ?>><?php esc_html_e( 'Ebook', 'education-resources-manager' ); ?></option>
			<option value="video" <?php selected( $type, 'video' ); ?>><?php esc_html_e( 'Video', 'education-resources-manager' ); ?></option>
		</select>
		<select name="erm_filter_level">
			<option value=""><?php esc_html_e( 'Todos los niveles', 'education-resources-manager' ); ?></option>
			<option value="beginner" <?php selected( $level, 'beginner' ); ?>><?php esc_html_e( 'Principiante', 'education-resources-manager' ); ?></option>
			<option value="intermediate" <?php selected( $level, 'intermediate' ); ?>><?php esc_html_e( 'Intermedio', 'education-resources-manager' ); ?></option>
			<option value="advanced" <?php selected( $level, 'advanced' ); ?>><?php esc_html_e( 'Avanzado', 'education-resources-manager' ); ?></option>
		</select>
		<?php
		$categories = get_terms( array( 'taxonomy' => 'erm_resource_category', 'hide_empty' => true ) );
		if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) :
			?>
			<select name="erm_filter_category">
				<option value=""><?php esc_html_e( 'Todas las categorías', 'education-resources-manager' ); ?></option>
				<?php foreach ( $categories as $term ) : ?>
					<option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $cat, $term->term_id ); ?>><?php echo esc_html( $term->name ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php
		endif;
	}

	/**
	 * Filter admin list query by type, level, category.
	 *
	 * @param WP_Query $query Query object.
	 */
	public function filter_list_query( $query ) {
		global $pagenow;

		if ( ! is_admin() || 'edit.php' !== $pagenow || ! $query->is_main_query() ) {
			return;
		}

		if ( $query->get( 'post_type' ) !== ERM_Post_Type::POST_TYPE ) {
			return;
		}

		$meta_query = $query->get( 'meta_query' ) ?: array();

		$type = isset( $_GET['erm_filter_type'] ) ? sanitize_text_field( wp_unslash( $_GET['erm_filter_type'] ) ) : '';
		if ( ! empty( $type ) && in_array( $type, ERM_Post_Type::VALID_TYPES, true ) ) {
			$meta_query[] = array( 'key' => ERM_Post_Type::META_TYPE, 'value' => $type );
		}

		$level = isset( $_GET['erm_filter_level'] ) ? sanitize_text_field( wp_unslash( $_GET['erm_filter_level'] ) ) : '';
		if ( ! empty( $level ) && in_array( $level, ERM_Post_Type::VALID_LEVELS, true ) ) {
			$meta_query[] = array( 'key' => ERM_Post_Type::META_LEVEL, 'value' => $level );
		}

		if ( ! empty( $meta_query ) ) {
			$query->set( 'meta_query', $meta_query );
		}

		$cat = isset( $_GET['erm_filter_category'] ) ? absint( $_GET['erm_filter_category'] ) : 0;
		if ( $cat > 0 ) {
			$query->set( 'tax_query', array(
				array(
					'taxonomy' => 'erm_resource_category',
					'field'    => 'term_id',
					'terms'    => $cat,
				),
			) );
		}
	}

	/**
	 * Render dashboard page.
	 */
	public function render_dashboard() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$by_type   = ERM_Database::get_count_by_type();
		$top_viewed = ERM_Database::get_top_viewed( 5 );
		$by_month  = ERM_Database::get_resources_by_month( 6 );

		$type_labels = array(
			'course'   => __( 'Cursos', 'education-resources-manager' ),
			'tutorial' => __( 'Tutoriales', 'education-resources-manager' ),
			'ebook'    => __( 'Ebooks', 'education-resources-manager' ),
			'video'    => __( 'Videos', 'education-resources-manager' ),
		);

		include ERM_PATH . 'admin/views/admin-page.php';
	}
}
