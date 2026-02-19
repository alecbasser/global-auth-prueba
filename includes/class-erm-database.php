<?php
/**
 * Database operations for resource tracking.
 *
 * @package Education_Resources_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ERM_Database
 */
class ERM_Database {

	/**
	 * Table name (without prefix).
	 */
	const TABLE_NAME = 'erm_resource_tracking';

	/**
	 * Initialize.
	 */
	public function init() {
		// No hooks needed for basic CRUD - called from REST API and other classes.
	}

	/**
	 * Get full table name.
	 *
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Record a view or download action.
	 *
	 * @param int    $resource_id Post ID of the resource.
	 * @param string $action_type 'view' or 'download'.
	 * @return int|false Insert ID or false on failure.
	 */
	public static function record_action( $resource_id, $action_type = 'view' ) {
		global $wpdb;

		$resource_id = absint( $resource_id );
		if ( ! $resource_id ) {
			return false;
		}

		$action_type = in_array( $action_type, array( 'view', 'download' ), true ) ? $action_type : 'view';
		$user_id     = get_current_user_id();
		$ip_address  = self::get_client_ip();

		$table_name = self::get_table_name();

		$result = $wpdb->insert(
			$table_name,
			array(
				'resource_id'  => $resource_id,
				'user_id'      => $user_id,
				'action_type'  => $action_type,
				'ip_address'   => $ip_address,
			),
			array( '%d', '%d', '%s', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get view count for a resource.
	 *
	 * @param int $resource_id Post ID.
	 * @return int
	 */
	public static function get_view_count( $resource_id ) {
		global $wpdb;

		$table_name   = self::get_table_name();
		$resource_id  = absint( $resource_id );

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE resource_id = %d AND action_type = 'view'",
				$resource_id
			)
		);

		return (int) $count;
	}

	/**
	 * Get top N most viewed resources.
	 *
	 * @param int $limit Number of resources to return.
	 * @return array
	 */
	public static function get_top_viewed( $limit = 5 ) {
		global $wpdb;

		$table_name = self::get_table_name();
		$limit      = absint( $limit );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT resource_id, COUNT(*) as view_count 
				FROM {$table_name} 
				WHERE action_type = 'view' 
				GROUP BY resource_id 
				ORDER BY view_count DESC 
				LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Get resources created per month for the last N months.
	 *
	 * @param int $months Number of months.
	 * @return array
	 */
	public static function get_resources_by_month( $months = 6 ) {
		global $wpdb;

		$post_type = ERM_Post_Type::POST_TYPE;
		$months    = absint( $months );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE_FORMAT(post_date, '%%Y-%%m') as month, COUNT(*) as count 
				FROM {$wpdb->posts} 
				WHERE post_type = %s 
				AND post_status = 'publish' 
				AND post_date >= DATE_SUB(CURDATE(), INTERVAL %d MONTH) 
				GROUP BY month 
				ORDER BY month ASC",
				$post_type,
				$months
			),
			ARRAY_A
		);

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Get total resources by type.
	 *
	 * @return array
	 */
	public static function get_count_by_type() {
		global $wpdb;

		$meta_key = ERM_Post_Type::META_TYPE;
		$post_type = ERM_Post_Type::POST_TYPE;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pm.meta_value as type, COUNT(*) as count 
				FROM {$wpdb->postmeta} pm 
				INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
				WHERE pm.meta_key = %s 
				AND p.post_type = %s 
				AND p.post_status = 'publish' 
				GROUP BY pm.meta_value",
				$meta_key,
				$post_type
			),
			ARRAY_A
		);

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Get client IP address.
	 *
	 * @return string|null
	 */
	private static function get_client_ip() {
		$ip_keys = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare.
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		);

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return null;
	}
}
