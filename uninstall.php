<?php
/**
 * Uninstall script - runs when plugin is deleted.
 *
 * @package Education_Resources_Manager
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$table_name = $wpdb->prefix . 'erm_resource_tracking';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

delete_option( 'erm_db_version' );
