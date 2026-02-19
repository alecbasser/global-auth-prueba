<?php
/**
 * Admin dashboard view.
 *
 * @package Education_Resources_Manager
 *
 * @var array $by_type    Resources count by type.
 * @var array $top_viewed Top 5 viewed resources.
 * @var array $by_month   Resources created per month.
 * @var array $type_labels Type labels for display.
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap erm-admin">
	<h1><?php esc_html_e( 'Dashboard - Recursos Educativos', 'education-resources-manager' ); ?></h1>

	<div class="erm-stats-grid">
		<div class="erm-stat-card">
			<h3><?php esc_html_e( 'Recursos por tipo', 'education-resources-manager' ); ?></h3>
			<ul class="erm-stat-list">
				<?php
				$total = 0;
				foreach ( $by_type as $row ) :
					$total += (int) $row['count'];
					$label = $type_labels[ $row['type'] ] ?? $row['type'];
					?>
					<li>
						<span class="erm-stat-label"><?php echo esc_html( $label ); ?></span>
						<span class="erm-stat-value"><?php echo esc_html( (string) $row['count'] ); ?></span>
					</li>
				<?php endforeach; ?>
				<?php if ( empty( $by_type ) ) : ?>
					<li><span class="erm-stat-label"><?php esc_html_e( 'Sin datos', 'education-resources-manager' ); ?></span></li>
				<?php endif; ?>
			</ul>
			<?php if ( $total > 0 ) : ?>
				<p class="erm-stat-total"><?php echo esc_html( sprintf( __( 'Total: %d recursos', 'education-resources-manager' ), $total ) ); ?></p>
			<?php endif; ?>
		</div>

		<div class="erm-stat-card">
			<h3><?php esc_html_e( 'Top 5 más visualizados', 'education-resources-manager' ); ?></h3>
			<ol class="erm-stat-list erm-top-viewed">
				<?php
				foreach ( $top_viewed as $row ) :
					$post = get_post( $row['resource_id'] );
					$title = $post ? $post->post_title : __( '(Sin título)', 'education-resources-manager' );
					?>
					<li>
						<a href="<?php echo esc_url( get_edit_post_link( $row['resource_id'] ) ); ?>"><?php echo esc_html( $title ); ?></a>
						<span class="erm-stat-value"><?php echo esc_html( (string) $row['view_count'] ); ?> <?php esc_html_e( 'vistas', 'education-resources-manager' ); ?></span>
					</li>
				<?php endforeach; ?>
				<?php if ( empty( $top_viewed ) ) : ?>
					<li><?php esc_html_e( 'Sin datos de visualizaciones', 'education-resources-manager' ); ?></li>
				<?php endif; ?>
			</ol>
		</div>
	</div>

	<div class="erm-stat-card erm-chart-card">
		<h3><?php esc_html_e( 'Recursos creados por mes (últimos 6 meses)', 'education-resources-manager' ); ?></h3>
		<div class="erm-chart-container">
			<?php
			$month_labels = array(
				'01' => __( 'Ene', 'education-resources-manager' ),
				'02' => __( 'Feb', 'education-resources-manager' ),
				'03' => __( 'Mar', 'education-resources-manager' ),
				'04' => __( 'Abr', 'education-resources-manager' ),
				'05' => __( 'May', 'education-resources-manager' ),
				'06' => __( 'Jun', 'education-resources-manager' ),
				'07' => __( 'Jul', 'education-resources-manager' ),
				'08' => __( 'Ago', 'education-resources-manager' ),
				'09' => __( 'Sep', 'education-resources-manager' ),
				'10' => __( 'Oct', 'education-resources-manager' ),
				'11' => __( 'Nov', 'education-resources-manager' ),
				'12' => __( 'Dic', 'education-resources-manager' ),
			);
			$max_count = 1;
			foreach ( $by_month as $row ) {
				$max_count = max( $max_count, (int) $row['count'] );
			}
			?>
			<div class="erm-chart-bars">
				<?php foreach ( $by_month as $row ) : ?>
					<?php
					$parts = explode( '-', $row['month'] );
					$year  = $parts[0] ?? '';
					$month = $parts[1] ?? '';
					$label = ( $month_labels[ $month ] ?? $month ) . ' ' . $year;
					$count = (int) $row['count'];
					$pct   = $max_count > 0 ? ( $count / $max_count ) * 100 : 0;
					?>
					<div class="erm-chart-bar-item">
						<span class="erm-chart-label"><?php echo esc_html( $label ); ?></span>
						<div class="erm-chart-bar-wrap">
							<div class="erm-chart-bar" style="width: <?php echo esc_attr( $pct ); ?>%;"></div>
							<span class="erm-chart-value"><?php echo esc_html( (string) $count ); ?></span>
						</div>
					</div>
				<?php endforeach; ?>
				<?php if ( empty( $by_month ) ) : ?>
					<p class="erm-no-data"><?php esc_html_e( 'Sin datos para mostrar', 'education-resources-manager' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<div class="erm-shortcode-info">
		<h3><?php esc_html_e( 'Uso del shortcode', 'education-resources-manager' ); ?></h3>
		<p><?php esc_html_e( 'Para mostrar los recursos en cualquier página o entrada, usa el siguiente shortcode:', 'education-resources-manager' ); ?></p>
		<code>[recursos_educativos]</code>
		<p class="description"><?php esc_html_e( 'Opcional: [recursos_educativos per_page="12"] para cambiar recursos por página.', 'education-resources-manager' ); ?></p>
	</div>
</div>
