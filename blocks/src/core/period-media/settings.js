/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, FormTokenField, SelectControl, TextControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import PatipsPeriodSelect from '../../components/period-select';


/**
 * Patronage period results settings component
 * @return {Element} Element to render.
 */
export default function PatipsPeriodMediaSettings( props ) {

	/**
	 * Get tags and categories from WP data
	 */
	const { attachmentCategories } = useSelect( ( select ) => {
		const { getEntityRecords } = select( 'core' )
		
		return {
			"attachmentCategories": getEntityRecords( 'taxonomy', 'patips_attachment_cat' )
		}
	} );
	
	
	/**
	 * Get all categories
	 */
	const getAllTerms = () => {
		let categoriesArrays = [ attachmentCategories ];
		let terms            = [];
		
		// Merge terms
		categoriesArrays.forEach( ( categoriesArray ) => { 
			categoriesArray = categoriesArray ?? [];
			if( categoriesArray.length ) {
				terms = terms.concat( categoriesArray );
			}
		} );
		
		return terms;
	};
	
	
	/**
	 * Get term options (categories or tag)
	 */
	const getTermOptions = () => {
		let allTerms = getAllTerms();
		let options  = allTerms.length ? allTerms.map( term => term.name + ' (#' + term.id + ')' ) : [];
		
		return options;
	};
	
	
	/**
	 * Get selected term options
	 */
	const getSelectedTermOptions = () => {
		let allTerms = getAllTerms();
		let options  = [];
		
		props.attributes.categories.forEach( ( termID ) => {
			let option = '(#' + termID + ')';
			
			let term = allTerms.find( ( term ) => { return termID === parseInt( term.id ); } );
			if( typeof term !== 'undefined' ) {
				option = term.name + ' ' + option;
			}
			
			options.push( option );
		});
		
		return options;
	};
	
	
	const [ selectedCategories, setSelectedCategories ] = useState( () => getSelectedTermOptions() );
	
	
	/**
	 * Set categories attribute according to selected categories
	 */
	const setCategoriesAttribute = () => {
		props.setAttributes( { 
			"categories": selectedCategories.map( ( str ) => {
				return parseInt( str.substring( str.lastIndexOf( '#' ) + 1, str.lastIndexOf( ')' ) ) );
			} )
		} );
	};
	
	
	/**
	 * Update categories when selected categories change
	 */
	useEffect( () => {
		setCategoriesAttribute();
	}, [ selectedCategories ] );
	
	
	/**
	 * Reset selected categories options when categories change
	 */
	useEffect( () => {
		let selectedTermOptions = getSelectedTermOptions();
		setSelectedCategories( selectedTermOptions );
	}, [ attachmentCategories ] );

	return (
		<InspectorControls>
			<PanelBody title={ __( 'Period', 'patrons-tips' ) }>
				<PatipsPeriodSelect
					label={ __( 'Period', 'patrons-tips' ) }
					value={ props.attributes.period }
					onChange={ ( period ) => props.setAttributes( { "period": period } ) }
				/>
			</PanelBody>
			<PanelBody title={ __( 'Filters', 'patrons-tips' ) }>
				<FormTokenField
					label={ __( 'Categories', 'patrons-tips' ) }
					value={ selectedCategories }
					suggestions={ getTermOptions() }
					onChange={ ( newSelectedCategories ) => { setSelectedCategories( newSelectedCategories ) } }
					__experimentalShowHowTo={ true }
					__experimentalExpandOnFocus={ true }
				/>
			</PanelBody>
			<PanelBody title={ __( 'Image settings', 'patrons-tips' ) }>
				<SelectControl
					label={ __( 'Image size', 'patrons-tips' ) }
					value={ props.attributes.imageSize }
					options={ [
						{ label: __( 'Full Size', 'patrons-tips' ), value: 'full' },
						{ label: __( 'Large', 'patrons-tips' ), value: 'large' },
						{ label: __( 'Medium', 'patrons-tips' ), value: 'medium' },
						{ label: __( 'Thumbnail', 'patrons-tips' ), value: 'thumbnail' }
					] }
					onChange={ ( imageSize ) => props.setAttributes( { "imageSize": imageSize } ) }
				/>
				<TextControl
					type='number'
					label={ __( 'Per page', 'patrons-tips' ) }
					value={ props.attributes.perPage }
					onChange={ ( perPage ) => props.setAttributes( { "perPage": parseInt( perPage ) } ) }
				/>
			</PanelBody>
		</InspectorControls>
	)
}