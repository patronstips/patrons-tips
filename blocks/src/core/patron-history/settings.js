/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, FormTokenField } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';


/**
 * Patronage period results settings component
 * @return {Element} Element to render.
 */
export default function PatipsPatronHistorySettings( props ) {
	const tiers        = patips_var?.tiers ?? {};
	const columnsTitle = patips_var?.patron_history_columns ?? {};
	
	
	/**
	 * Get tier options for SelectControl
	 * @param {String} firstOption
	 * @return {Array}
	 */
	const getTierOptions = () => {
		let options = [];
		
		if( tiers ) {
			Object.keys( tiers ).map( ( i ) => {
				let tier = tiers[ i ];
				options.push( 
					{
						"value": tier.id,
						/* translators: %s is the tier ID */
						"label": tier?.title ?? sprintf( __( 'Tier #%s', 'patrons-tips' ), tier.id )
					} 
				);
			});
		}
		
		return options;
	};
	
	
	/**
	 * Get column options
	 */
	const getColumnOptions = () => {
		let options = [];
		
		Object.keys( columnsTitle ).forEach( ( columnKey ) => {
			options.push( columnsTitle[ columnKey ] );
		} );
		
		return options;
	};
	
	
	/**
	 * Get selected column options
	 */
	const getSelectedColumnOptions = () => {
		let options = [];
		
		props.attributes.columns.forEach( ( columnKey ) => {
			let option = columnsTitle?.[ columnKey ] ?? columnKey;
			options.push( option );
		});
		
		return options;
	};
	
	
	const [ selectedColumns, setSelectedColumns ] = useState( () => getSelectedColumnOptions() );
	
	
	/**
	 * Get column key by column title
	 */
	const getColumnKey = ( title ) => {
		var columnKey = Object.keys( columnsTitle ).find( ( key ) => { return columnsTitle?.[ key ] === title; } );
		return columnKey ?? title;
	};
	
	
	/**
	 * Set columns attribute according to selected columns
	 */
	const setColumnsAttribute = () => {
		props.setAttributes( { 
			"columns": selectedColumns.map( ( str ) => {
				return getColumnKey( str );
			} )
		} );
	};
	
	
	/**
	 * Update columns when selected columns change
	 */
	useEffect( () => {
		setColumnsAttribute();
	}, [ selectedColumns ] );
	
	
	return (
		<InspectorControls>
			<PanelBody title={ __( 'Filters', 'patrons-tips' ) }>
				<SelectControl
					multiple
					label={ __( 'Tiers', 'patrons-tips' ) }
					value={ props.attributes.tiers }
					options={ getTierOptions() }
					onChange={ ( tier_ids ) => props.setAttributes( { "tiers": tier_ids.map( ( str ) => { return parseInt( str ); } ) } ) }
				/>
				<TextControl
					type='number'
					label={ __( 'Per page', 'patrons-tips' ) }
					value={ props.attributes.perPage }
					onChange={ ( perPage ) => props.setAttributes( { "perPage": parseInt( perPage ) } ) }
				/>
			</PanelBody>
			<PanelBody title={ __( 'Display', 'patrons-tips' ) }>
				<FormTokenField
					label={ __( 'Columns', 'patrons-tips' ) }
					value={ selectedColumns }
					suggestions={ getColumnOptions() }
					onChange={ ( newSelectedColumns ) => { setSelectedColumns( newSelectedColumns ) } }
					__experimentalShowHowTo={ true }
					__experimentalExpandOnFocus={ true }
				/>
			</PanelBody>
		</InspectorControls>
	)
}