<?php
/**
 * Plugin Name:       Education Resources Manager
 * Plugin URI:        https://github.com/GlobalAuthenticity/education-resources-manager
 * Description:       Sistema de gestión de recursos educativos para WordPress (cursos, tutoriales, ebooks, videos).
 * Version:           1.0.0
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

// Plugin constants.
define( 'ERM_VERSION', '1.0.0' );
define( 'ERM_FILE', __FILE__ );
define( 'ERM_PATH', plugin_dir_path( __FILE__ ) );
define( 'ERM_URL', plugin_dir_url( __FILE__ ) );
define( 'ERM_BASENAME', plugin_basename( __FILE__ ) );

// Include required files.
require_once ERM_PATH . 'includes/class-erm-activator.php';
require_once ERM_PATH . 'includes/class-erm-deactivator.php';
require_once ERM_PATH . 'includes/class-erm-post-type.php';
require_once ERM_PATH . 'includes/class-erm-taxonomy.php';
require_once ERM_PATH . 'includes/class-erm-database.php';
require_once ERM_PATH . 'includes/class-erm-admin.php';
require_once ERM_PATH . 'includes/class-erm-rest-api.php';
require_once ERM_PATH . 'includes/class-erm-shortcode.php';

/**
 * Plugin activation hook.
 */
function erm_activate() {
	ERM_Activator::activate();
}
register_activation_hook( __FILE__, 'erm_activate' );

/**
 * Plugin deactivation hook.
 */
function erm_deactivate() {
	ERM_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'erm_deactivate' );

/**
 * Initialize the plugin.
 */
function erm_init() {
	// Load text domain.
	load_plugin_textdomain( 'education-resources-manager', false, dirname( ERM_BASENAME ) . '/languages' );

	// Initialize components.
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

	if ( is_admin() ) {
		$erm_admin = new ERM_Admin();
		$erm_admin->init();
	}
}
add_action( 'plugins_loaded', 'erm_init' );
