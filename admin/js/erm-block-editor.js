/**
 * Personalización del editor de bloques para erm_resource: reemplazar el panel
 * de estado por defecto con un panel simplificado (Borrador / Publicado / Archivado).
 * Sincronización bidireccional con el select de la meta caja "Detalles del Recurso".
 *
 * @package Education_Resources_Manager
 */
( function () {
	'use strict';

	/* ── Funciones auxiliares ── */

	var el        = wp.element.createElement;
	var useEffect = wp.element.useEffect;
	var useRef   = wp.element.useRef;
	var useSelect = wp.data.useSelect;
	var __       = wp.i18n.__;
	var registerPlugin = wp.plugins.registerPlugin;

	var PluginDocumentSettingPanel = wp.editor.PluginDocumentSettingPanel;

	var POST_TO_META = { draft: 'draft', publish: 'published', archived: 'archived' };
	var META_TO_POST = { draft: 'draft', published: 'publish', archived: 'archived' };

	function normalizeStatus( s ) {
		if ( s === 'draft' || s === 'publish' || s === 'archived' ) return s;
		return 'publish';
	}

	function findMetaBoxSelect() {
		var sel = document.getElementById( 'erm_status' );
		if ( sel ) return sel;
		try {
			if ( window.parent && window.parent !== window ) {
				return window.parent.document.getElementById( 'erm_status' );
			}
		} catch ( e ) {}
		return null;
	}

	/* ── Componente principal ── */

	function ERMStatusPanel() {

		/* Reactive read: component re-renders automatically when status changes. */
		var postData = useSelect( function ( select ) {
			var editor = select( 'core/editor' );
			var post   = editor.getCurrentPost();
			return {
				postId:   post ? post.id : 0,
				postType: post ? post.type : '',
				status:   normalizeStatus( editor.getEditedPostAttribute( 'status' ) || 'publish' )
			};
		}, [] );

		if ( postData.postType !== 'erm_resource' ) return null;

		var currentStatus = postData.status;

		/* ── Ocultar panel de estado de publicación por defecto ── */
		useEffect( function () {
			wp.data.dispatch( 'core/editor' ).removeEditorPanel( 'post-status' );
		}, [] );

		/* ── Sincronización: editor → select de meta caja ── */
		useEffect( function () {
			var metaVal = POST_TO_META[ currentStatus ] || 'published';
			var sel = findMetaBoxSelect();
			if ( sel && sel.value !== metaVal ) {
				sel.value = metaVal;
			}
		}, [ currentStatus ] );

		/* ── Sync: meta box select → editor ── */
		var metaBoxBound = useRef( false );
		useEffect( function () {
			if ( metaBoxBound.current ) return;
			var attempts = 0;
			var interval = setInterval( function () {
				attempts++;
				var sel = findMetaBoxSelect();
				if ( ! sel && attempts < 60 ) return;
				clearInterval( interval );
				if ( ! sel ) return;
				metaBoxBound.current = true;
				sel.addEventListener( 'change', function () {
					var postStatus = META_TO_POST[ sel.value ] || 'publish';
					wp.data.dispatch( 'core/editor' ).editPost( { status: postStatus } );
				} );
			}, 500 );
			return function () { clearInterval( interval ); };
		}, [] );

		/* ── Manejador de cambio ── */
		function onStatusChange( newStatus ) {
			wp.data.dispatch( 'core/editor' ).editPost( { status: newStatus } );
		}

		/* ── Renderizado ── */
		var options = [
			{ value: 'draft',    label: __( 'Borrador', 'education-resources-manager' ),  desc: __( 'No listo para publicar.', 'education-resources-manager' ) },
			{ value: 'publish',  label: __( 'Publicado', 'education-resources-manager' ), desc: __( 'Visible por todos.', 'education-resources-manager' ) },
			{ value: 'archived', label: __( 'Archivado', 'education-resources-manager' ), desc: __( 'Recurso archivado, no visible.', 'education-resources-manager' ) }
		];

		return el( PluginDocumentSettingPanel, {
			name: 'erm-resource-status',
			title: __( 'Estado y visibilidad', 'education-resources-manager' ),
			className: 'erm-resource-status-panel'
		},
			el( 'fieldset', { className: 'components-radio-control' },
				el( 'legend', { className: 'components-visually-hidden' },
					__( 'Estado', 'education-resources-manager' )
				),
				el( 'div', { className: 'components-radio-control__group-wrapper' },
					options.map( function ( opt ) {
						return el( 'div', {
							key: opt.value,
							className: 'components-radio-control__option'
						},
							el( 'input', {
								id: 'erm-status-' + opt.value,
								className: 'components-radio-control__input',
								type: 'radio',
								name: 'erm_resource_status',
								value: opt.value,
								checked: currentStatus === opt.value,
								onChange: function () { onStatusChange( opt.value ); }
							} ),
							el( 'label', {
								className: 'components-radio-control__label',
								htmlFor: 'erm-status-' + opt.value
							}, opt.label ),
							el( 'p', {
								className: 'components-radio-control__option-description'
							}, opt.desc )
						);
					} )
				)
			)
		);
	}

	/* ── Registro ── */

	wp.domReady( function () {
		if ( ! wp.plugins || ! wp.data || ! wp.element || ! PluginDocumentSettingPanel ) {
			return;
		}
		registerPlugin( 'erm-resource-status', { render: ERMStatusPanel } );
	} );
} )();
