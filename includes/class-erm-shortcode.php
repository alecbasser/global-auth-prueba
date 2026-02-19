<?php
/**
 * Shortcode for displaying education resources.
 *
 * @package Education_Resources_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ERM_Shortcode
 */
class ERM_Shortcode {

	/**
	 * Shortcode tag.
	 */
	const SHORTCODE = 'recursos_educativos';

	/**
	 * Initialize.
	 */
	public function init() {
		add_shortcode( self::SHORTCODE, array( $this, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue frontend assets.
	 */
	public function enqueue_assets() {
		global $post;

		if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, self::SHORTCODE ) ) {
			return;
		}

		wp_enqueue_style(
			'erm-public',
			ERM_URL . 'public/css/public-styles.css',
			array(),
			(string) filemtime( ERM_PATH . 'public/css/public-styles.css' )
		);

		wp_enqueue_script(
			'erm-public',
			ERM_URL . 'public/js/public-scripts.js',
			array(),
			(string) filemtime( ERM_PATH . 'public/js/public-scripts.js' ),
			true
		);

		wp_localize_script(
			'erm-public',
			'ermData',
			array(
				'apiUrl'           => rest_url( ERM_REST_API::NAMESPACE ),
				'nonce'            => wp_create_nonce( 'wp_rest' ),
				'excerptMaxChars'  => (int) ERM_Admin::get_setting( 'erm_excerpt_max_chars' ),
				'i18n'     => array(
					'loading'   => __( 'Cargando...', 'education-resources-manager' ),
					'noResults' => __( 'No se encontraron recursos.', 'education-resources-manager' ),
					'viewResource' => __( 'Ver recurso', 'education-resources-manager' ),
					'filterBy' => __( 'Filtrar por', 'education-resources-manager' ),
					'search'   => __( 'Buscar...', 'education-resources-manager' ),
					'allTypes' => __( 'Todos los tipos', 'education-resources-manager' ),
					'allLevels' => __( 'Todos los niveles', 'education-resources-manager' ),
					'allCategories' => __( 'Todas las categorías', 'education-resources-manager' ),
					'min'       => __( 'min', 'education-resources-manager' ),
					'free'      => __( 'Gratuito', 'education-resources-manager' ),
				),
			)
		);
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'per_page' => 9,
			),
			$atts,
			self::SHORTCODE
		);

		$per_page = absint( $atts['per_page'] );
		if ( $per_page < 1 || $per_page > 50 ) {
			$per_page = 9;
		}

		ob_start();
		?>
		<div class="erm-resources-container" data-per-page="<?php echo esc_attr( $per_page ); ?>">
			<div class="erm-filters">
				<div class="erm-filter-row">
					<div class="erm-filter-group">
						<label for="erm-filter-type"><?php esc_html_e( 'Tipo', 'education-resources-manager' ); ?></label>
						<select id="erm-filter-type" class="erm-filter">
							<option value=""><?php esc_html_e( 'Todos los tipos', 'education-resources-manager' ); ?></option>
							<option value="course"><?php esc_html_e( 'Curso', 'education-resources-manager' ); ?></option>
							<option value="tutorial"><?php esc_html_e( 'Tutorial', 'education-resources-manager' ); ?></option>
							<option value="ebook"><?php esc_html_e( 'Ebook', 'education-resources-manager' ); ?></option>
							<option value="video"><?php esc_html_e( 'Video', 'education-resources-manager' ); ?></option>
						</select>
					</div>
					<div class="erm-filter-group">
						<label for="erm-filter-level"><?php esc_html_e( 'Nivel', 'education-resources-manager' ); ?></label>
						<select id="erm-filter-level" class="erm-filter">
							<option value=""><?php esc_html_e( 'Todos los niveles', 'education-resources-manager' ); ?></option>
							<option value="beginner"><?php esc_html_e( 'Principiante', 'education-resources-manager' ); ?></option>
							<option value="intermediate"><?php esc_html_e( 'Intermedio', 'education-resources-manager' ); ?></option>
							<option value="advanced"><?php esc_html_e( 'Avanzado', 'education-resources-manager' ); ?></option>
						</select>
					</div>
					<div class="erm-filter-group">
						<label for="erm-filter-category"><?php esc_html_e( 'Categoría', 'education-resources-manager' ); ?></label>
						<select id="erm-filter-category" class="erm-filter">
							<option value=""><?php esc_html_e( 'Todas las categorías', 'education-resources-manager' ); ?></option>
							<?php
							$categories = get_terms( array(
								'taxonomy'   => 'erm_resource_category',
								'hide_empty' => true,
							) );
							if ( ! is_wp_error( $categories ) ) {
								foreach ( $categories as $cat ) {
									printf(
										'<option value="%d">%s</option>',
										esc_attr( $cat->term_id ),
										esc_html( $cat->name )
									);
								}
							}
							?>
						</select>
					</div>
					<div class="erm-filter-group erm-filter-search">
						<label for="erm-filter-search"><?php esc_html_e( 'Buscar', 'education-resources-manager' ); ?></label>
						<input type="search" id="erm-filter-search" class="erm-filter" placeholder="<?php esc_attr_e( 'Buscar recursos...', 'education-resources-manager' ); ?>" />
					</div>
				</div>
			</div>

			<div class="erm-loading" aria-hidden="true" style="display: none;">
				<span class="erm-spinner"></span>
				<p><?php esc_html_e( 'Cargando recursos...', 'education-resources-manager' ); ?></p>
			</div>

			<div class="erm-resources-grid" role="list">
				<!-- Resources loaded via JS -->
			</div>

			<div class="erm-pagination" role="navigation" aria-label="<?php esc_attr_e( 'Paginación de recursos', 'education-resources-manager' ); ?>">
				<!-- Pagination loaded via JS -->
			</div>

			<div class="erm-no-results" style="display: none;">
				<p><?php esc_html_e( 'No se encontraron recursos con los filtros seleccionados.', 'education-resources-manager' ); ?></p>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
