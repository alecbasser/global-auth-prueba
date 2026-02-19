<?php
/**
 * REST API endpoints for Education Resources Manager.
 *
 * @package Education_Resources_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ERM_REST_API
 */
class ERM_REST_API {

	/**
	 * API namespace.
	 */
	const NAMESPACE = 'erm/v1';

	/**
	 * Initialize.
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		// GET /resources - List resources with filters.
		register_rest_route(
			self::NAMESPACE,
			'/resources',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_resources' ),
				'permission_callback' => '__return_true',
				'args'                => $this->get_resources_args(),
			)
		);

		// GET /resources/{id} - Get single resource.
		register_rest_route(
			self::NAMESPACE,
			'/resources/(?P<id>[\d]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_resource' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'validate_callback' => function( $param ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);

		// POST /resources/{id}/track - Record view/download.
		register_rest_route(
			self::NAMESPACE,
			'/resources/(?P<id>[\d]+)/track',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'track_resource' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id'    => array(
						'validate_callback' => function( $param ) {
							return is_numeric( $param );
						},
					),
					'action' => array(
						'default'           => 'view',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function( $param ) {
							return in_array( $param, array( 'view', 'download' ), true );
						},
					),
					'nonce'  => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// GET /stats - Get statistics.
		register_rest_route(
			self::NAMESPACE,
			'/stats',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_stats' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		);
	}

	/**
	 * Check if current user has admin-level permissions.
	 * Supports both nonce-based auth (JS/AJAX) and direct cookie auth (browser URL).
	 *
	 * @return bool
	 */
	public function check_admin_permission() {
		if ( current_user_can( 'edit_posts' ) ) {
			return true;
		}

		$user_id = wp_validate_auth_cookie( '', 'logged_in' );
		return $user_id && user_can( $user_id, 'edit_posts' );
	}

	/**
	 * Get query args for resources endpoint.
	 *
	 * @return array
	 */
	private function get_resources_args() {
		return array(
			'page'     => array(
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'default'           => 10,
				'sanitize_callback' => 'absint',
			),
			'type'     => array(
				'sanitize_callback' => 'sanitize_text_field',
			),
			'level'    => array(
				'sanitize_callback' => 'sanitize_text_field',
			),
			'category' => array(
				'sanitize_callback' => 'sanitize_text_field',
			),
			'search'   => array(
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * Get list of resources.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_resources( $request ) {
		$args = array(
			'post_type'      => ERM_Post_Type::POST_TYPE,
			'post_status'    => 'publish',
			'paged'          => $request->get_param( 'page' ) ?: 1,
			'posts_per_page' => min( $request->get_param( 'per_page' ) ?: 10, 50 ),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$meta_query = array();

		$type = $request->get_param( 'type' );
		if ( ! empty( $type ) ) {
			if ( in_array( $type, ERM_Post_Type::VALID_TYPES, true ) ) {
				$meta_query[] = array(
					'key'   => ERM_Post_Type::META_TYPE,
					'value' => $type,
				);
			} else {
				$args['post__in'] = array( 0 );
			}
		}

		$level = $request->get_param( 'level' );
		if ( ! empty( $level ) ) {
			if ( in_array( $level, ERM_Post_Type::VALID_LEVELS, true ) ) {
				$meta_query[] = array(
					'key'   => ERM_Post_Type::META_LEVEL,
					'value' => $level,
				);
			} else {
				$args['post__in'] = array( 0 );
			}
		}

		if ( ! empty( $meta_query ) ) {
			$args['meta_query'] = $meta_query;
		}

		$category = $request->get_param( 'category' );
		if ( ! empty( $category ) ) {
			$term = null;
			if ( is_numeric( $category ) ) {
				$term = get_term( absint( $category ), 'erm_resource_category' );
			} else {
				$term = get_term_by( 'slug', $category, 'erm_resource_category' );
			}
			if ( $term && ! is_wp_error( $term ) ) {
				$args['tax_query'] = array(
					array(
						'taxonomy' => 'erm_resource_category',
						'field'    => 'term_id',
						'terms'    => $term->term_id,
					),
				);
			} else {
				// Categoría inexistente: devolver 0 resultados.
				$args['post__in'] = array( 0 );
			}
		}

		$search = $request->get_param( 'search' );
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		$query = new WP_Query( $args );
		$posts = $query->posts;
		$total = $query->found_posts;

		$data = array();
		foreach ( $posts as $post ) {
			$meta = ERM_Post_Type::get_resource_meta( $post->ID );
			$data[] = array(
				'id'          => $post->ID,
				'title'       => $post->post_title,
				'excerpt'     => get_the_excerpt( $post ),
				'permalink'   => get_permalink( $post ),
				'thumbnail'   => get_the_post_thumbnail_url( $post->ID, 'medium' ),
				'type'        => $meta['type'],
				'level'       => $meta['level'],
				'duration'    => $meta['duration'],
				'url'         => $meta['url'],
				'instructor'  => $meta['instructor'],
				'price'       => $meta['price'],
				'categories'  => wp_get_post_terms( $post->ID, 'erm_resource_category' ),
				'skills'      => wp_get_post_terms( $post->ID, 'erm_skill_tag' ),
				'track_nonce' => wp_create_nonce( 'erm_track_' . $post->ID ),
			);
		}

		$response = new WP_REST_Response( $data, 200 );
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', (int) ceil( $total / $args['posts_per_page'] ) );

		return $response;
	}

	/**
	 * Get single resource.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_resource( $request ) {
		$id   = (int) $request->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post || $post->post_type !== ERM_Post_Type::POST_TYPE ) {
			return new WP_Error( 'not_found', __( 'Recurso no encontrado.', 'education-resources-manager' ), array( 'status' => 404 ) );
		}

		if ( $post->post_status !== 'publish' ) {
			return new WP_Error( 'not_found', __( 'Recurso no disponible.', 'education-resources-manager' ), array( 'status' => 404 ) );
		}

		$meta = ERM_Post_Type::get_resource_meta( $post->ID );

		$data = array(
			'id'         => $post->ID,
			'title'      => $post->post_title,
			'content'    => apply_filters( 'the_content', $post->post_content ),
			'excerpt'    => get_the_excerpt( $post ),
			'permalink'  => get_permalink( $post ),
			'thumbnail'  => get_the_post_thumbnail_url( $post->ID, 'large' ),
			'type'       => $meta['type'],
			'level'      => $meta['level'],
			'duration'   => $meta['duration'],
			'url'        => $meta['url'],
			'instructor' => $meta['instructor'],
			'price'      => $meta['price'],
			'categories' => wp_get_post_terms( $post->ID, 'erm_resource_category' ),
			'skills'     => wp_get_post_terms( $post->ID, 'erm_skill_tag' ),
		);

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Track resource view/download.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function track_resource( $request ) {
		$id     = (int) $request->get_param( 'id' );
		$action = $request->get_param( 'action' ) ?: 'view';
		$nonce  = $request->get_param( 'nonce' );

		if ( ! wp_verify_nonce( $nonce, 'erm_track_' . $id ) ) {
			return new WP_Error( 'invalid_nonce', __( 'Verificación de seguridad fallida.', 'education-resources-manager' ), array( 'status' => 403 ) );
		}

		$post = get_post( $id );
		if ( ! $post || $post->post_type !== ERM_Post_Type::POST_TYPE || $post->post_status !== 'publish' ) {
			return new WP_Error( 'not_found', __( 'Recurso no encontrado.', 'education-resources-manager' ), array( 'status' => 404 ) );
		}

		$record_id = ERM_Database::record_action( $id, $action );

		if ( ! $record_id ) {
			return new WP_Error( 'track_failed', __( 'No se pudo registrar la acción.', 'education-resources-manager' ), array( 'status' => 500 ) );
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Acción registrada correctamente.', 'education-resources-manager' ),
			),
			200
		);
	}

	/**
	 * Get statistics.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_stats( $request ) {
		$by_type   = ERM_Database::get_count_by_type();
		$top_viewed = ERM_Database::get_top_viewed( 5 );
		$by_month  = ERM_Database::get_resources_by_month( 6 );

		// Enrich top viewed with post data.
		$top_viewed_data = array();
		foreach ( $top_viewed as $row ) {
			$post = get_post( $row['resource_id'] );
			$top_viewed_data[] = array(
				'id'         => (int) $row['resource_id'],
				'title'      => $post ? $post->post_title : '',
				'view_count' => (int) $row['view_count'],
			);
		}

		$data = array(
			'by_type'    => $by_type,
			'top_viewed' => $top_viewed_data,
			'by_month'   => $by_month,
		);

		return new WP_REST_Response( $data, 200 );
	}
}
