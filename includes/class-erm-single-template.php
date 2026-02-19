<?php
/**
 * Single resource - inject styled meta into theme content.
 * Respeta el header y footer del tema.
 *
 * @package Education_Resources_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ERM_Single_Template
 */
class ERM_Single_Template {

	/**
	 * Initialize.
	 */
	public function init() {
		add_filter( 'the_content', array( $this, 'add_resource_meta' ), 10, 1 );
		add_filter( 'body_class', array( $this, 'body_class' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Add body class for single resource.
	 *
	 * @param array $classes Body classes.
	 * @return array
	 */
	public function body_class( $classes ) {
		if ( is_singular( ERM_Post_Type::POST_TYPE ) ) {
			$classes[] = 'erm-single-page';
			if ( ERM_Admin::get_setting( 'erm_single_layout' ) === 'side-by-side' ) {
				$classes[] = 'erm-single-page--side-by-side';
			}
		}
		return $classes;
	}

	/**
	 * Add resource meta to content (badges, author, CTA).
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function add_resource_meta( $content ) {
		if ( ! is_singular( ERM_Post_Type::POST_TYPE ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post_id     = get_the_ID();
		$meta        = ERM_Post_Type::get_resource_meta( $post_id );
		$side_by_side = ( ERM_Admin::get_setting( 'erm_single_layout' ) === 'side-by-side' );

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

		ob_start();
		?>
		<div class="erm-single erm-single-resource">
			<?php if ( $side_by_side ) : ?>
			<div class="erm-single__header">
				<?php if ( has_post_thumbnail( $post_id ) ) : ?>
				<div class="erm-single__header-image">
					<?php echo get_the_post_thumbnail( $post_id, 'large' ); ?>
				</div>
				<?php endif; ?>
				<div class="erm-single__header-info">
			<?php endif; ?>

			<div class="erm-single__actions">
				<button type="button" class="erm-badge erm-badge--neutral erm-badge--action" id="erm-share" aria-label="<?php esc_attr_e( 'Compartir', 'education-resources-manager' ); ?>">
					<span class="erm-badge-icon" aria-hidden="true">⎘</span>
					<?php esc_html_e( 'Compartir', 'education-resources-manager' ); ?>
				</button>
				<button type="button" class="erm-badge erm-badge--neutral erm-badge--action" id="erm-bookmark" aria-label="<?php esc_attr_e( 'Guardar como favorito', 'education-resources-manager' ); ?>">
					<span class="erm-icon-bookmark erm-badge-icon" aria-hidden="true">☆</span>
					<?php esc_html_e( 'Favorito', 'education-resources-manager' ); ?>
				</button>
			</div>
			<div class="erm-single__meta-block">
				<div class="erm-single__badges">
					<?php if ( ! empty( $meta['type'] ) ) : ?>
						<span class="erm-badge erm-badge--primary"><?php echo esc_html( $type_labels[ $meta['type'] ] ?? $meta['type'] ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $meta['level'] ) ) : ?>
						<span class="erm-badge erm-badge--neutral"><?php echo esc_html( $level_labels[ $meta['level'] ] ?? $meta['level'] ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $meta['duration'] ) && $meta['duration'] > 0 ) : ?>
						<span class="erm-badge erm-badge--neutral erm-badge--with-icon">
							<span class="erm-badge-icon" aria-hidden="true">⏱</span>
							<?php echo esc_html( sprintf( __( '%d min', 'education-resources-manager' ), $meta['duration'] ) ); ?>
						</span>
					<?php endif; ?>
				</div>

				<div class="erm-single__author-row">
					<?php if ( ! empty( $meta['instructor'] ) ) : ?>
						<div class="erm-single__author">
							<div class="erm-single__avatar"><?php echo esc_html( mb_substr( $meta['instructor'], 0, 1 ) ); ?></div>
							<div>
								<p class="erm-single__author-label"><?php esc_html_e( 'Escrito por', 'education-resources-manager' ); ?></p>
								<p class="erm-single__author-name"><?php echo esc_html( $meta['instructor'] ); ?></p>
							</div>
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $meta['price'] ) ) : ?>
						<div class="erm-single__price">
							<p class="erm-single__price-label"><?php esc_html_e( 'Precio', 'education-resources-manager' ); ?></p>
							<p class="erm-single__price-value">
								<?php
								$raw_price = $meta['price'];
								if ( strtolower( $raw_price ) === 'gratuito' || $raw_price === '0' ) {
									echo esc_html__( 'Gratuito', 'education-resources-manager' );
								} elseif ( is_numeric( $raw_price ) ) {
									echo '$ ' . esc_html( number_format( (float) $raw_price, 0, ',', '.' ) );
								} else {
									echo esc_html( $raw_price );
								}
								?>
							</p>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( ! empty( $meta['url'] ) ) : ?>
				<div class="erm-single__cta">
					<a href="<?php echo esc_url( $meta['url'] ); ?>" class="erm-single__btn" target="_blank" rel="noopener">
						<span class="erm-btn-icon" aria-hidden="true">▶</span>
						<?php esc_html_e( 'Ver recurso', 'education-resources-manager' ); ?>
					</a>
				</div>
			<?php endif; ?>

			<?php if ( $side_by_side ) : ?>
				</div><!-- .erm-single__header-info -->
			</div><!-- .erm-single__header -->
			<?php endif; ?>

			<div class="erm-single__body">
				<?php if ( ! empty( $content ) ) : ?>
					<h2 class="erm-single__section-title"><?php esc_html_e( 'Sinopsis del recurso', 'education-resources-manager' ); ?></h2>
				<?php endif; ?>
				<div class="erm-single__prose"><?php echo $content; ?></div>
			</div>

		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Enqueue styles and scripts for single resource page.
	 */
	public function enqueue_assets() {
		if ( ! is_singular( ERM_Post_Type::POST_TYPE ) ) {
			return;
		}

		wp_enqueue_style(
			'erm-single',
			ERM_URL . 'public/css/erm-single.css',
			array(),
			(string) filemtime( ERM_PATH . 'public/css/erm-single.css' )
		);

		wp_enqueue_style(
			'erm-fonts',
			'https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap',
			array(),
			null
		);

		wp_enqueue_script(
			'erm-single',
			ERM_URL . 'public/js/erm-single.js',
			array(),
			(string) filemtime( ERM_PATH . 'public/js/erm-single.js' ),
			true
		);
	}
}
