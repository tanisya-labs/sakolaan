( function ( blocks, element, components, ssr ) {
	var el = element.createElement;
	var config = window.sakolaanAdmissionBlocks || { blocks: [] };
	config.blocks.forEach( function ( item ) {
		blocks.registerBlockType( 'sakolaan-admission/' + item.name, {
			title: item.title,
			icon: 'welcome-learn-more',
			category: 'widgets',
			attributes: { programId: { type: 'integer', default: 0 } },
			edit: function ( props ) {
				return el(
					'div',
					{},
					el( components.TextControl, {
						label: config.programLabel,
						type: 'number',
						value: props.attributes.programId || '',
						onChange: function ( value ) {
							props.setAttributes( { programId: parseInt( value, 10 ) || 0 } );
						}
					} ),
					el( ssr, { block: 'sakolaan-admission/' + item.name, attributes: props.attributes } )
				);
			},
			save: function () {
				return null;
			}
		} );
	} );
}( window.wp.blocks, window.wp.element, window.wp.components, window.wp.serverSideRender ) );
