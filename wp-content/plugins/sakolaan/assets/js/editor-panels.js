/**
 * Sakolaan: panel meta di sidebar editor.
 * Vanilla JS tanpa build step; membaca konfigurasi dari window.sakolaanMeta.
 */
( function ( wp ) {
	var config = window.sakolaanMeta || {};
	var i18n = config.i18n || {};
	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var registerPlugin = wp.plugins.registerPlugin;
	var Panel = wp.editPost && wp.editPost.PluginDocumentSettingPanel;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var SelectControl = wp.components.SelectControl;
	var Button = wp.components.Button;
	var MediaUpload = wp.blockEditor.MediaUpload;
	var MediaUploadCheck = wp.blockEditor.MediaUploadCheck;
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;

	if ( ! Panel ) {
		return;
	}

	/** Pratinjau satu attachment (gambar atau berkas). */
	function Attachment( props ) {
		var media = useSelect( function ( select ) {
			return props.id ? select( 'core' ).getMedia( props.id ) : null;
		}, [ props.id ] );

		if ( ! media ) {
			return el( 'span', { className: 'sakolaan-chip' }, '#' + props.id );
		}

		if ( media.media_type === 'image' ) {
			var src = ( media.media_details && media.media_details.sizes && media.media_details.sizes.thumbnail )
				? media.media_details.sizes.thumbnail.source_url
				: media.source_url;
			return el( 'img', { className: 'sakolaan-thumb', src: src, alt: '' } );
		}

		return el( 'span', { className: 'sakolaan-chip' }, media.title && media.title.rendered ? media.title.rendered : '#' + props.id );
	}

	/** Kontrol media: satu berkas. */
	function MediaField( props ) {
		var value = parseInt( props.value, 10 ) || 0;
		var statusState = useState( '' );
		var status = statusState[ 0 ];
		var setStatus = statusState[ 1 ];
		var actionLabel = value ? i18n.replace : i18n.select;

		return el( 'div', { className: 'sakolaan-field' },
			el( 'span', { className: 'components-base-control__label' }, props.field.label ),
			value ? el( Attachment, { id: value } ) : el( 'p', { className: 'sakolaan-empty' }, i18n.noFile ),
			el( MediaUploadCheck, null,
				el( MediaUpload, {
					allowedTypes: props.field.mediaTypes,
					value: value,
					onSelect: function ( media ) {
						var mediaId = media && parseInt( media.id, 10 );

						if ( mediaId ) {
							props.onChange( mediaId );
							setStatus( i18n.selected || '' );
						}
					},
					render: function ( obj ) {
						return el( Button, { variant: 'secondary', onClick: obj.open, 'aria-label': actionLabel + ': ' + props.field.label }, actionLabel );
					}
				} )
			),
			value ? el( Button, {
				variant: 'link',
				isDestructive: true,
				'aria-label': i18n.remove + ': ' + props.field.label,
				onClick: function () {
					props.onChange( 0 );
					setStatus( i18n.removed || '' );
				}
			}, i18n.remove ) : null,
			el( 'span', { className: 'sakolaan-field__status', role: 'status', 'aria-live': 'polite' }, status )
		);
	}

	/** Kontrol media: banyak gambar. */
	function GalleryField( props ) {
		var ids = Array.isArray( props.value ) ? props.value.map( Number ).filter( Boolean ) : [];
		var statusState = useState( '' );
		var status = statusState[ 0 ];
		var setStatus = statusState[ 1 ];
		var actionLabel = ids.length ? i18n.edit : i18n.add;

		return el( 'div', { className: 'sakolaan-field' },
			el( 'span', { className: 'components-base-control__label' }, props.field.label ),
			ids.length ? el( 'div', { className: 'sakolaan-gallery' },
				ids.map( function ( id ) { return el( Attachment, { key: id, id: id } ); } )
			) : el( 'p', { className: 'sakolaan-empty' }, i18n.noFile ),
			el( MediaUploadCheck, null,
				el( MediaUpload, {
					allowedTypes: props.field.mediaTypes,
					multiple: 'add',
					gallery: true,
					value: ids,
					onSelect: function ( items ) {
						var selectedIds = Array.isArray( items ) ? items.map( function ( item ) { return parseInt( item.id, 10 ); } ).filter( Boolean ) : [];
						props.onChange( selectedIds.filter( function ( id, index ) { return selectedIds.indexOf( id ) === index; } ) );
						setStatus( i18n.galleryUpdated || '' );
					},
					render: function ( obj ) {
						return el( Button, { variant: 'secondary', onClick: obj.open, 'aria-label': actionLabel + ': ' + props.field.label }, actionLabel );
					}
				} )
			),
			ids.length ? el( Button, {
				variant: 'link',
				isDestructive: true,
				'aria-label': i18n.remove + ': ' + props.field.label,
				onClick: function () {
					props.onChange( [] );
					setStatus( i18n.removed || '' );
				}
			}, i18n.remove ) : null,
			el( 'span', { className: 'sakolaan-field__status', role: 'status', 'aria-live': 'polite' }, status )
		);
	}

	/** Pilih kontrol sesuai tipe field. */
	function renderField( field, value, onChange ) {
		var common = { key: field.key, label: field.label, help: field.help || undefined, __nextHasNoMarginBottom: true };

		switch ( field.control ) {
			case 'textarea':
				return el( TextareaControl, Object.assign( {}, common, { value: value || '', onChange: onChange } ) );
			case 'select':
				return el( SelectControl, Object.assign( {}, common, { value: value || '', options: field.options, onChange: onChange } ) );
			case 'media':
				return el( MediaField, { key: field.key, field: field, value: value, onChange: onChange } );
			case 'media-multi':
				return el( GalleryField, { key: field.key, field: field, value: value, onChange: onChange } );
			default:
				return el( TextControl, Object.assign( {}, common, { type: field.control, value: value || '', onChange: onChange } ) );
		}
	}

	function SakolaanMetaPanel() {
		var postType = useSelect( function ( select ) {
			return select( 'core/editor' ).getCurrentPostType();
		}, [] );
		var meta = useSelect( function ( select ) {
			return select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {};
		}, [] );
		var editPost = useDispatch( 'core/editor' ).editPost;
		var panel = config[ postType ];

		if ( ! panel || ! Array.isArray( panel.fields ) ) {
			return null;
		}

		return el( Panel, { name: 'sakolaan-meta', title: panel.title, className: 'sakolaan-meta-panel' },
			panel.fields.map( function ( field ) {
				return renderField( field, meta[ field.key ], function ( newValue ) {
					var change = {};
					change[ field.key ] = newValue;
					editPost( { meta: change } );
				} );
			} )
		);
	}

	function SakolaanSeoPanel() {
		var seo = window.sakolaanSeo || {};
		var postType = useSelect( function ( select ) {
			return select( 'core/editor' ).getCurrentPostType();
		}, [] );
		var meta = useSelect( function ( select ) {
			return select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {};
		}, [] );
		var editPost = useDispatch( 'core/editor' ).editPost;
		if ( ! seo.postTypes || seo.postTypes.indexOf( postType ) === -1 || ! Array.isArray( seo.fields ) ) {
			return null;
		}
		return el( Panel, { name: 'sakolaan-seo', title: 'SEO', className: 'sakolaan-seo-panel' },
			el( 'p', { className: 'components-base-control__help' }, 'Kosongkan override untuk menggunakan nilai otomatis atau fallback global.' ),
			seo.fields.map( function ( field ) {
				return renderField( field, meta[ field.key ], function ( newValue ) {
					var change = {};
					change[ field.key ] = newValue;
					editPost( { meta: change } );
				} );
			} )
		);
	}

	registerPlugin( 'sakolaan-meta', { render: SakolaanMetaPanel } );
	registerPlugin( 'sakolaan-seo', { render: SakolaanSeoPanel } );
}( window.wp ) );
