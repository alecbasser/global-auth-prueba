<?php
/**
 * Custom Taxonomies for Education Resources.
 *
 * @package Education_Resources_Manager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ERM_Taxonomy
 */
class ERM_Taxonomy {

	/**
	 * Initialize.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	/**
	 * Register custom taxonomies.
	 */
	public function register_taxonomies() {
		// Categorías de recursos (jerárquica).
		$category_labels = array(
			'name'              => _x( 'Categorías de Recursos', 'taxonomy general name', 'education-resources-manager' ),
			'singular_name'     => _x( 'Categoría de Recurso', 'taxonomy singular name', 'education-resources-manager' ),
			'search_items'      => __( 'Buscar Categorías', 'education-resources-manager' ),
			'all_items'         => __( 'Todas las Categorías', 'education-resources-manager' ),
			'parent_item'       => __( 'Categoría Padre', 'education-resources-manager' ),
			'parent_item_colon' => __( 'Categoría Padre:', 'education-resources-manager' ),
			'edit_item'         => __( 'Editar Categoría', 'education-resources-manager' ),
			'update_item'       => __( 'Actualizar Categoría', 'education-resources-manager' ),
			'add_new_item'      => __( 'Añadir Nueva Categoría', 'education-resources-manager' ),
			'new_item_name'     => __( 'Nombre de Nueva Categoría', 'education-resources-manager' ),
			'menu_name'         => __( 'Categorías', 'education-resources-manager' ),
		);

		register_taxonomy(
			'erm_resource_category',
			array( ERM_Post_Type::POST_TYPE ),
			array(
				'hierarchical'      => true,
				'labels'            => $category_labels,
				'show_ui'           => true,
				'show_admin_column'  => true,
				'query_var'         => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'categoria-recurso' ),
			)
		);

		// Etiquetas de habilidades (no jerárquica).
		$tag_labels = array(
			'name'                       => _x( 'Etiquetas de Habilidades', 'taxonomy general name', 'education-resources-manager' ),
			'singular_name'              => _x( 'Etiqueta de Habilidad', 'taxonomy singular name', 'education-resources-manager' ),
			'search_items'               => __( 'Buscar Etiquetas', 'education-resources-manager' ),
			'popular_items'              => __( 'Etiquetas Populares', 'education-resources-manager' ),
			'all_items'                  => __( 'Todas las Etiquetas', 'education-resources-manager' ),
			'edit_item'                  => __( 'Editar Etiqueta', 'education-resources-manager' ),
			'update_item'                => __( 'Actualizar Etiqueta', 'education-resources-manager' ),
			'add_new_item'               => __( 'Añadir Nueva Etiqueta', 'education-resources-manager' ),
			'new_item_name'              => __( 'Nombre de Nueva Etiqueta', 'education-resources-manager' ),
			'separate_items_with_commas' => __( 'Separar etiquetas con comas', 'education-resources-manager' ),
			'add_or_remove_items'        => __( 'Añadir o quitar etiquetas', 'education-resources-manager' ),
			'menu_name'                  => __( 'Habilidades', 'education-resources-manager' ),
		);

		register_taxonomy(
			'erm_skill_tag',
			array( ERM_Post_Type::POST_TYPE ),
			array(
				'hierarchical'      => false,
				'labels'            => $tag_labels,
				'show_ui'           => true,
				'show_admin_column'  => true,
				'query_var'         => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'habilidad' ),
			)
		);
	}
}
