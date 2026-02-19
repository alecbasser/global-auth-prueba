<?php
/**
 * Plugin Name:       Education Resources Manager
 * Plugin URI:        https://github.com/GlobalAuthenticity/education-resources-manager
 * Description:       Sistema de gestión de recursos educativos para WordPress (cursos, tutoriales, ebooks, videos).
 * Version:           2.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Global Authenticity
 * Author URI:        https://globalauthenticity.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       education-resources-manager
 * Domain Path:       /languages
 *
 * @package Education_Resources_Manager
 */

defined( 'ABSPATH' ) || exit;

// Constantes del plugin.
define( 'ERM_VERSION', '2.0.0' );
define( 'ERM_FILE', __FILE__ );
define( 'ERM_PATH', plugin_dir_path( __FILE__ ) );
define( 'ERM_URL', plugin_dir_url( __FILE__ ) );
define( 'ERM_BASENAME', plugin_basename( __FILE__ ) );

// Incluir archivos requeridos.
require_once ERM_PATH . 'includes/class-erm-activator.php';
require_once ERM_PATH . 'includes/class-erm-deactivator.php';
require_once ERM_PATH . 'includes/class-erm-post-type.php';
require_once ERM_PATH . 'includes/class-erm-taxonomy.php';
require_once ERM_PATH . 'includes/class-erm-database.php';
require_once ERM_PATH . 'includes/class-erm-admin.php';
require_once ERM_PATH . 'includes/class-erm-rest-api.php';
require_once ERM_PATH . 'includes/class-erm-shortcode.php';
require_once ERM_PATH . 'includes/class-erm-single-template.php';
require_once ERM_PATH . 'includes/class-erm-uninstall.php';

/**
 * Plugin activation hook.
 */
function erm_activate() {
	ERM_Activator::activate();
}
register_activation_hook( __FILE__, 'erm_activate' );

/**
 * Hook de desactivación del plugin.
 */
function erm_deactivate() {
	ERM_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'erm_deactivate' );

/**
 * Inicializar el plugin.
 */
function erm_init() {
	// Cargar dominio de texto.
	load_plugin_textdomain( 'education-resources-manager', false, dirname( ERM_BASENAME ) . '/languages' );

	// Inicializar componentes.
	$erm_post_type = new ERM_Post_Type();
	$erm_post_type->init();

	$erm_taxonomy = new ERM_Taxonomy();
	$erm_taxonomy->init();

	$erm_database = new ERM_Database();
	$erm_database->init();

	$erm_rest_api = new ERM_REST_API();
	$erm_rest_api->init();

	$erm_shortcode = new ERM_Shortcode();
	$erm_shortcode->init();

	$erm_single_template = new ERM_Single_Template();
	$erm_single_template->init();

	if ( is_admin() ) {
		$erm_admin = new ERM_Admin();
		$erm_admin->init();

		$erm_uninstall = new ERM_Uninstall();
		$erm_uninstall->init();
	}
}
add_action( 'plugins_loaded', 'erm_init' );

/**
 * Track view when user visits single resource page.
 */
function erm_track_single_resource_view() {
	if ( ! is_singular( ERM_Post_Type::POST_TYPE ) ) {
		return;
	}

	$post_id = get_queried_object_id();
	if ( $post_id ) {
		ERM_Database::record_action( $post_id, 'view' );
	}
}
add_action( 'template_redirect', 'erm_track_single_resource_view' );

/**
 * Vaciar reglas de reescritura tras la activación (diferido hasta que el CPT se registre).
 */
function erm_maybe_flush_rewrite_rules() {
	if ( get_transient( 'erm_flush_rewrite_rules' ) ) {
		flush_rewrite_rules();
		delete_transient( 'erm_flush_rewrite_rules' );
	}
}
add_action( 'init', 'erm_maybe_flush_rewrite_rules', 99 );
