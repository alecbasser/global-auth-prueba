<?php
/**
 * Custom Post Type: Recurso Educativo.
 *
 * @package Education_Resources_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ERM_Post_Type
 */
class ERM_Post_Type {

	/**
	 * Post type slug.
	 */
	const POST_TYPE = 'erm_resource';

	/**
	 * Meta keys for resource fields.
	 */
	const META_TYPE        = '_erm_resource_type';
	const META_LEVEL       = '_erm_difficulty_level';
	const META_DURATION    = '_erm_duration';
	const META_URL         = '_erm_resource_url';
	const META_INSTRUCTOR  = '_erm_instructor';
	const META_PRICE       = '_erm_price';
	const META_STATUS      = '_erm_publication_status';

	/**
	 * Valid values for meta fields.
	 */
	const VALID_TYPES   = array( 'course', 'tutorial', 'ebook', 'video' );
	const VALID_LEVELS  = array( 'beginner', 'intermediate', 'advanced' );
	const VALID_STATUS = array( 'draft', 'published', 'archived' );

	/**
	 * Initialize.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_archived_status' ), 5 );
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta' ), 10, 2 );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'sync_status_meta_from_post' ), 20, 2 );
		add_filter( 'display_post_states', array( $this, 'display_post_states' ), 10, 2 );
	}

	/**
	 * Register custom post status "archived".
	 */
	public function register_archived_status() {
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

	/**
	 * Map our status to WordPress post_status.
	 *
	 * @param string $status Our meta status (draft, published, archived).
	 * @return string WordPress post_status.
	 */
	private static function status_to_post_status( $status ) {
		$map = array(
			'draft'     => 'draft',
			'published' => 'publish',
			'archived'  => 'archived',
		);
		return $map[ $status ] ?? 'publish';
	}

	/**
	 * Map WordPress post_status to our status.
	 *
	 * @param string $post_status WordPress post_status.
	 * @return string Our meta status.
	 */
	private static function post_status_to_status( $post_status ) {
		$map = array(
			'draft'   => 'draft',
			'publish' => 'published',
			'archived' => 'archived',
		);
		return $map[ $post_status ] ?? 'published';
	}

	/**
	 * Display post states in admin list.
	 *
	 * @param array   $states Post states.
	 * @param WP_Post $post   Post object.
	 * @return array
	 */
	public function display_post_states( $states, $post ) {
		if ( $post->post_type !== self::POST_TYPE ) {
			return $states;
		}
		if ( $post->post_status === 'archived' ) {
			$states['archived'] = __( 'Archivado', 'education-resources-manager' );
		}
		return $states;
	}

	/**
	 * Register the custom post type.
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => _x( 'Recursos Educativos', 'Post Type General Name', 'education-resources-manager' ),
			'singular_name'         => _x( 'Recurso Educativo', 'Post Type Singular Name', 'education-resources-manager' ),
			'menu_name'             => __( 'Recursos Educativos', 'education-resources-manager' ),
			'name_admin_bar'        => __( 'Recurso Educativo', 'education-resources-manager' ),
			'archives'              => __( 'Archivos de Recursos', 'education-resources-manager' ),
			'all_items'             => __( 'Todos los Recursos', 'education-resources-manager' ),
			'add_new_item'          => __( 'Añadir Nuevo Recurso', 'education-resources-manager' ),
			'add_new'               => __( 'Añadir Nuevo', 'education-resources-manager' ),
			'new_item'              => __( 'Nuevo Recurso', 'education-resources-manager' ),
			'edit_item'             => __( 'Editar Recurso', 'education-resources-manager' ),
			'update_item'           => __( 'Actualizar Recurso', 'education-resources-manager' ),
			'view_item'             => __( 'Ver Recurso', 'education-resources-manager' ),
			'search_items'          => __( 'Buscar Recursos', 'education-resources-manager' ),
			'not_found'             => __( 'No encontrado', 'education-resources-manager' ),
			'not_found_in_trash'    => __( 'No encontrado en papelera', 'education-resources-manager' ),
		);

		$args = array(
			'label'               => __( 'Recurso Educativo', 'education-resources-manager' ),
			'description'         => __( 'Recursos educativos: cursos, tutoriales, ebooks, videos', 'education-resources-manager' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'taxonomies'          => array( 'erm_resource_category', 'erm_skill_tag' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 25,
			'menu_icon'           => 'dashicons-welcome-learn-more',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => 'recursos-educativos',
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'post',
			'show_in_rest'        => true,
			'rest_base'           => 'resources',
		);

		register_post_type( self::POST_TYPE, $args );

		// Recursos no se comportan como entradas: sin comentarios, trackbacks, etc.
		remove_post_type_support( self::POST_TYPE, 'comments' );
		remove_post_type_support( self::POST_TYPE, 'trackbacks' );
	}

	/**
	 * Add meta boxes for resource fields.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'erm_resource_details',
			__( 'Detalles del Recurso', 'education-resources-manager' ),
			array( $this, 'render_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render the meta box.
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'erm_save_resource_meta', 'erm_resource_meta_nonce' );

		$type      = get_post_meta( $post->ID, self::META_TYPE, true );
		$level     = get_post_meta( $post->ID, self::META_LEVEL, true );
		$duration  = get_post_meta( $post->ID, self::META_DURATION, true );
		$url       = get_post_meta( $post->ID, self::META_URL, true );
		$instructor = get_post_meta( $post->ID, self::META_INSTRUCTOR, true );
		$price     = get_post_meta( $post->ID, self::META_PRICE, true );
		$status    = self::post_status_to_status( $post->post_status );

		$type_labels = array(
			'course'   => __( 'Curso', 'education-resources-manager' ),
			'tutorial' => __( 'Tutorial', 'education-resources-manager' ),
			'ebook'    => __( 'Ebook', 'education-resources-manager' ),
			'video'    => __( 'Video', 'education-resources-manager' ),
		);

		$level_labels = array(
			'beginner'     => __( 'Principiante', 'education-resources-manager' ),
			'intermediate' => __( 'Intermedio', 'education-resources-manager' ),
			'advanced'     => __( 'Avanzado', 'education-resources-manager' ),
		);

		$status_labels = array(
			'draft'    => __( 'Borrador', 'education-resources-manager' ),
			'published' => __( 'Publicado', 'education-resources-manager' ),
			'archived'  => __( 'Archivado', 'education-resources-manager' ),
		);
		?>
		<table class="form-table">
			<tr>
				<th><label for="erm_type"><?php esc_html_e( 'Tipo de recurso', 'education-resources-manager' ); ?></label></th>
				<td>
					<select name="erm_type" id="erm_type">
						<?php foreach ( $type_labels as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $type, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="erm_level"><?php esc_html_e( 'Nivel de dificultad', 'education-resources-manager' ); ?></label></th>
				<td>
					<select name="erm_level" id="erm_level">
						<?php foreach ( $level_labels as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $level, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="erm_duration"><?php esc_html_e( 'Duración (minutos)', 'education-resources-manager' ); ?></label></th>
				<td>
					<input type="number" name="erm_duration" id="erm_duration" value="<?php echo esc_attr( $duration ); ?>" min="0" class="small-text" />
				</td>
			</tr>
			<tr>
				<th><label for="erm_url"><?php esc_html_e( 'URL del recurso', 'education-resources-manager' ); ?></label></th>
				<td>
					<input type="url" name="erm_url" id="erm_url" value="<?php echo esc_url( $url ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th><label for="erm_instructor"><?php esc_html_e( 'Instructor/Autor', 'education-resources-manager' ); ?></label></th>
				<td>
					<input type="text" name="erm_instructor" id="erm_instructor" value="<?php echo esc_attr( $instructor ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th><label for="erm_price"><?php esc_html_e( 'Precio', 'education-resources-manager' ); ?></label></th>
				<td>
					<input type="text" name="erm_price" id="erm_price" value="<?php echo esc_attr( $price ); ?>" class="regular-text" placeholder="<?php esc_attr_e( '0 o Gratuito', 'education-resources-manager' ); ?>" />
				</td>
			</tr>
			<tr>
				<th><label for="erm_status"><?php esc_html_e( 'Estado de publicación', 'education-resources-manager' ); ?></label></th>
				<td>
					<select name="erm_status" id="erm_status">
						<?php foreach ( $status_labels as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $status, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save_meta( $post_id, $post ) {
		if ( ! isset( $_POST['erm_resource_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['erm_resource_meta_nonce'] ) ), 'erm_save_resource_meta' ) ) {
			return;
		 }

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$type = isset( $_POST['erm_type'] ) ? sanitize_text_field( wp_unslash( $_POST['erm_type'] ) ) : '';
		if ( in_array( $type, self::VALID_TYPES, true ) ) {
			update_post_meta( $post_id, self::META_TYPE, $type );
		}

		$level = isset( $_POST['erm_level'] ) ? sanitize_text_field( wp_unslash( $_POST['erm_level'] ) ) : '';
		if ( in_array( $level, self::VALID_LEVELS, true ) ) {
			update_post_meta( $post_id, self::META_LEVEL, $level );
		}

		$duration = isset( $_POST['erm_duration'] ) ? absint( $_POST['erm_duration'] ) : 0;
		update_post_meta( $post_id, self::META_DURATION, $duration );

		$url = isset( $_POST['erm_url'] ) ? esc_url_raw( wp_unslash( $_POST['erm_url'] ) ) : '';
		update_post_meta( $post_id, self::META_URL, $url );

		$instructor = isset( $_POST['erm_instructor'] ) ? sanitize_text_field( wp_unslash( $_POST['erm_instructor'] ) ) : '';
		update_post_meta( $post_id, self::META_INSTRUCTOR, $instructor );

		$price = isset( $_POST['erm_price'] ) ? sanitize_text_field( wp_unslash( $_POST['erm_price'] ) ) : '';
		update_post_meta( $post_id, self::META_PRICE, $price );

		$status = isset( $_POST['erm_status'] ) ? sanitize_text_field( wp_unslash( $_POST['erm_status'] ) ) : 'published';
		if ( in_array( $status, self::VALID_STATUS, true ) ) {
			update_post_meta( $post_id, self::META_STATUS, $status );

			$wp_status = self::status_to_post_status( $status );
			if ( get_post_status( $post_id ) !== $wp_status ) {
				remove_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta' ), 10 );
				wp_update_post( array(
					'ID'          => $post_id,
					'post_status' => $wp_status,
				) );
				add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta' ), 10, 2 );
			}
		}
	}

	/**
	 * Sincroniza _erm_publication_status con post_status tras guardar.
	 * Necesario cuando el editor de bloques guarda (REST API) y el meta box no envía formulario.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function sync_status_meta_from_post( $post_id, $post ) {
		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== self::POST_TYPE ) {
			return;
		}
		$meta_status = self::post_status_to_status( $post->post_status );
		update_post_meta( $post_id, self::META_STATUS, $meta_status );
	}

	/**
	 * Get resource meta for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public static function get_resource_meta( $post_id ) {
		$post = get_post( $post_id );
		return array(
			'type'       => get_post_meta( $post_id, self::META_TYPE, true ),
			'level'      => get_post_meta( $post_id, self::META_LEVEL, true ),
			'duration'   => (int) get_post_meta( $post_id, self::META_DURATION, true ),
			'url'        => get_post_meta( $post_id, self::META_URL, true ),
			'instructor' => get_post_meta( $post_id, self::META_INSTRUCTOR, true ),
			'price'      => get_post_meta( $post_id, self::META_PRICE, true ),
			'status'     => $post ? self::post_status_to_status( $post->post_status ) : 'published',
		);
	}
}
