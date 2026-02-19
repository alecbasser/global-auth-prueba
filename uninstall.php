<?php
/**
 * Uninstall script - runs when plugin is deleted via WordPress "Eliminar".
 *
 * Los datos de visualizaciones se conservan para poder recuperarlos
 * si el plugin se reinstala. Para borrar todo, usar la opción "Desinstalar"
 * en el panel de plugins.
 *
 * @package Education_Resources_Manager
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// No borrar datos al eliminar el plugin - conservar para reinstalación.
// La opción "Desinstalar" en el panel de plugins borra todo con confirmación.
