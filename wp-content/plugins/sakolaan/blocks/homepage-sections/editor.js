( function ( wp ) {
	'use strict';

	var blockName = 'pijar-sakolaan/homepage-sections';
	var sections = [
		[ 'quick_links', 'Akses cepat' ], [ 'introduction', 'Pengenalan sekolah' ], [ 'news', 'Berita' ],
		[ 'announcements', 'Pengumuman' ], [ 'events', 'Agenda' ], [ 'statistics', 'Statistik' ],
		[ 'principal', 'Sambutan kepala sekolah' ], [ 'achievements', 'Prestasi' ], [ 'facilities', 'Fasilitas' ],
		[ 'programs', 'Program unggulan' ], [ 'extracurricular', 'Ekstrakurikuler' ],
		[ 'testimonials', 'Testimoni alumni' ], [ 'gallery', 'Galeri' ], [ 'ppdb', 'Ajakan PPDB' ]
	];

	var withLayoutControls = wp.compose.createHigherOrderComponent( function ( BlockEdit ) {
		return function ( props ) {
			if ( props.name !== blockName ) { return wp.element.createElement( BlockEdit, props ); }

			var selected = props.attributes.sakolaanLayoutSection || 'news';
			var allLayouts = props.attributes.sectionLayouts || {};
			var layout = allLayouts[ selected ] || {};
			var update = function ( key, value ) {
				var next = Object.assign( {}, allLayouts );
				next[ selected ] = Object.assign( {}, layout, ( function () { var field = {}; field[ key ] = value; return field; }() ) );
				props.setAttributes( { sectionLayouts: next, sakolaanLayoutSection: selected } );
			};
			var controls = wp.element.createElement( wp.blockEditor.InspectorControls, null,
				wp.element.createElement( wp.components.PanelBody, { title: 'Tata letak section', initialOpen: true },
					wp.element.createElement( wp.components.SelectControl, {
						label: 'Section', value: selected,
						options: sections.map( function ( item ) { return { label: item[ 1 ], value: item[ 0 ] }; } ),
						onChange: function ( value ) { props.setAttributes( { sakolaanLayoutSection: value } ); }
					} ),
					wp.element.createElement( wp.components.SelectControl, {
						label: 'Jumlah kolom', value: layout.columns || 'auto',
						options: [ { label: 'Otomatis (default)', value: 'auto' }, { label: '2 kolom', value: '2' }, { label: '3 kolom', value: '3' }, { label: '4 kolom', value: '4' }, { label: '5 kolom', value: '5' } ],
						onChange: function ( value ) { update( 'columns', value === 'auto' ? '' : value ); }
					} ),
					selected === 'quick_links' ? wp.element.createElement( wp.components.SelectControl, {
						label: 'Tampilan tautan', value: layout.style || 'card',
						options: [ { label: 'Kartu', value: 'card' }, { label: 'Pill', value: 'pill' }, { label: 'Link sederhana', value: 'link' } ],
						onChange: function ( value ) { update( 'style', value === 'card' ? '' : value ); }
					} ) : wp.element.createElement( wp.components.SelectControl, {
						label: 'Variasi kartu', value: layout.card || 'standard',
						options: [ { label: 'Standar', value: 'standard' }, { label: 'Ringkas', value: 'compact' }, { label: 'Sorotan', value: 'feature' } ],
						onChange: function ( value ) { update( 'card', value === 'standard' ? '' : value ); }
					} ),
					selected === 'quick_links' ? wp.element.createElement( wp.components.SelectControl, {
						label: 'Perataan', value: layout.alignment || 'start',
						options: [ { label: 'Kiri', value: 'start' }, { label: 'Tengah', value: 'center' } ],
						onChange: function ( value ) { update( 'alignment', value === 'start' ? '' : value ); }
					} ) : wp.element.createElement( wp.components.SelectControl, {
						label: 'Posisi gambar', value: layout.image || 'top',
						options: [ { label: 'Di atas (default)', value: 'top' }, { label: 'Di kiri', value: 'left' }, { label: 'Di kanan', value: 'right' } ],
						onChange: function ( value ) { update( 'image', value === 'top' ? '' : value ); }
					} )
				)
			);
			return wp.element.createElement( wp.element.Fragment, null, controls, wp.element.createElement( BlockEdit, props ) );
		};
	}, 'withSakolaanHomepageLayoutControls' );

	wp.hooks.addFilter( 'editor.BlockEdit', 'pijar-sakolaan/homepage-layout-controls', withLayoutControls );
}( window.wp ) );
