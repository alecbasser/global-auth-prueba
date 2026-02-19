<?php
/**
 * Vista de la página de configuración.
 *
 * @package Education_Resources_Manager
 */

defined( 'ABSPATH' ) || exit;

$excerpt_max  = ERM_Admin::get_setting( 'erm_excerpt_max_chars' );
$single_layout = ERM_Admin::get_setting( 'erm_single_layout' );
?>
<div class="wrap erm-admin">
	<h1><?php esc_html_e( 'Configuración - Recursos Educativos', 'education-resources-manager' ); ?></h1>

	<form method="post" action="options.php">
		<?php settings_fields( 'erm_settings' ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="erm_excerpt_max_chars"><?php esc_html_e( 'Máximo de caracteres en extracto', 'education-resources-manager' ); ?></label>
				</th>
				<td>
					<input type="number" id="erm_excerpt_max_chars" name="erm_excerpt_max_chars" value="<?php echo esc_attr( $excerpt_max ); ?>" min="0" max="500" step="1" class="small-text" />
					<p class="description">
						<?php esc_html_e( 'Número máximo de caracteres del extracto en las tarjetas de recursos. 0 = sin límite.', 'education-resources-manager' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="erm_single_layout"><?php esc_html_e( 'Layout del recurso individual', 'education-resources-manager' ); ?></label>
				</th>
				<td>
					<select id="erm_single_layout" name="erm_single_layout">
						<option value="default" <?php selected( $single_layout, 'default' ); ?>>
							<?php esc_html_e( 'Predeterminado (imagen arriba, contenido abajo)', 'education-resources-manager' ); ?>
						</option>
						<option value="side-by-side" <?php selected( $single_layout, 'side-by-side' ); ?>>
							<?php esc_html_e( 'Lado a lado (imagen y contenido en columnas)', 'education-resources-manager' ); ?>
						</option>
					</select>
					<p class="description">
						<?php esc_html_e( 'Elige cómo se muestra la página individual de cada recurso.', 'education-resources-manager' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>
</div>
