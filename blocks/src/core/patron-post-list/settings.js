/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, FormTokenField, ToggleControl, SelectControl, TextControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';

/**
 * Patron post list settings component
 * @return {Element} Element to render.
 */
export default function PatipsPatronPostListSettings( props ) {
	
	/**
	 * Get tags and categories from WP data
	 */
	const { postCategories, postTags, productCategories, productTags, attachmentCategories } = useSelect( ( select ) => {
		const { getEntityRecords } = select( 'core' )
		
		return {
			"postCategories":       getEntityRecords( 'taxonomy', 'category' ),
			"postTags":             getEntityRecords( 'taxonomy', 'post_tag' ),
			"productCategories":    getEntityRecords( 'taxonomy', 'product_cat' ),
			"productTags":          getEntityRecords( 'taxonomy', 'product_tag' ),
			"attachmentCategories": getEntityRecords( 'taxonomy', 'patips_attachment_cat' )
		}
	} );
	
	
	/**
	 * Get all categories
	 */
	const getAllTerms = ( type ) => {
		type = type ?? '';
		let categoriesArrays = [ postCategories, productCategories, attachmentCategories ];
		let tagsArrays       = [ postTags, productTags ];
		let termsArrays      = type === 'tag' ? tagsArrays : categoriesArrays;
		let terms            = [];
		
		// Merge terms
		termsArrays.forEach( ( termsArray ) => { 
			termsArray = termsArray ?? [];
			if( termsArray.length ) {
				terms = terms.concat( termsArray );
			}
		} );
		
		return terms;
	};
	
	
	/**
	 * Get term options (categories or tag)
	 */
	const getTermOptions = ( type ) => {
		type         = type ?? '';
		let allTerms = getAllTerms( type === 'tag' ? 'tag' : 'category' );
		let options  = allTerms.length ? allTerms.map( term => term.name + ' (#' + term.id + ')' ) : [];
		
		return options;
	};
	
	
	/**
	 * Get selected term options
	 */
	const getSelectedTermOptions = ( type ) => {
		type            = type ?? '';
		let allTerms    = getAllTerms( type === 'tag' ? 'tag' : 'category' );
		let selectedIds = type === 'tag' ? props.attributes.tags : props.attributes.categories;
		let options     = [];
		
		selectedIds.forEach( ( termID ) => {
			let option = '(#' + termID + ')';
			
			let term = allTerms.find( ( term ) => { return termID === parseInt( term.id ); } );
			if( typeof term !== 'undefined' ) {
				option = term.name + ' ' + option;
			}
			
			options.push( option );
		});
		
		return options;
	};
	
	
	const [ selectedCategories, setSelectedCategories ] = useState( () => getSelectedTermOptions( 'category' ) );
	const [ selectedTags, setSelectedTags ]             = useState( () => getSelectedTermOptions( 'tag' ) );
	
	
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
	 * Set tags attribute according to selected tags
	 */
	const setTagsAttribute = () => {
		props.setAttributes( { 
			"tags": selectedTags.map( ( str ) => { 
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
	 * Update tags when selected tags change
	 */
	useEffect( () => {
		setTagsAttribute();
	}, [ selectedTags ] );
	
	
	/**
	 * Reset selected categories options when categories change
	 */
	useEffect( () => {
		let selectedTermOptions = getSelectedTermOptions( 'category' );
		setSelectedCategories( selectedTermOptions );
	}, [ postCategories, productCategories, attachmentCategories ] );
	
	
	/**
	 * Reset selected tags options when tags change
	 */
	useEffect( () => {
		let selectedTermOptions = getSelectedTermOptions( 'tag' );
		setSelectedTags( selectedTermOptions );
	}, [ postTags, productTags ] );
	
	
	return (
		<InspectorControls>
			<PanelBody title={ __( 'Filters', 'patrons-tips' ) }>
				<SelectControl
					multiple
					label={ __( 'Types', 'patrons-tips' ) }
					value={ props.attributes.types }
					options={ [
						{ label: __( 'Posts', 'patrons-tips' ), value: 'post' },
						{ label: __( 'Pages', 'patrons-tips' ), value: 'page' },
						{ label: __( 'Media', 'patrons-tips' ), value: 'attachment' },
						{ label: __( 'Products', 'patrons-tips' ), value: 'product' }
					] }
					onChange={ ( types ) => props.setAttributes( { 'types': types } ) }
				/>
				<FormTokenField
					label={ __( 'Categories', 'patrons-tips' ) }
					value={ selectedCategories }
					suggestions={ getTermOptions( 'category' ) }
					onChange={ ( newSelectedCategories ) => { setSelectedCategories( newSelectedCategories ) } }
					__experimentalShowHowTo={ true }
					__experimentalExpandOnFocus={ true }
				/>
				<FormTokenField
					label={ __( 'Tags', 'patrons-tips' ) }
					value={ selectedTags }
					suggestions={ getTermOptions( 'tag' ) }
					onChange={ ( newSelectedTags ) => { setSelectedTags( newSelectedTags ) } }
					__experimentalShowHowTo={ true }
					__experimentalExpandOnFocus={ true }
				/>
				<ToggleControl
					label={ __( 'Cumulate categories and tags', 'patrons-tips' ) }
					checked={ props.attributes.catAndTag }
					onChange={ () => props.setAttributes( { "catAndTag": ! props.attributes.catAndTag } ) }
				/>
				<SelectControl
					label={ __( 'Restricted', 'patrons-tips' ) }
					value={ props.attributes.restricted }
					options={ [
						{ label: __( 'Show both restricted and not restricted posts', 'patrons-tips' ), value: -1 },
						{ label: __( 'Show only not restricted posts', 'patrons-tips' ), value: 0 },
						{ label: __( 'Show only restricted posts', 'patrons-tips' ), value: 1 }
					] }
					onChange={ ( restricted ) => props.setAttributes( { "restricted": parseInt( restricted ), "unlocked": parseInt( restricted ) !== 1 ? -1 : props.attributes.unlocked } ) }
				/>
				{ parseInt( props.attributes.restricted ) === 1 &&
					<SelectControl
						label={ __( 'Unlocked', 'patrons-tips' ) }
						value={ props.attributes.unlocked }
						options={ [
							{ label: __( 'Show both locked and unlocked posts', 'patrons-tips' ), value: -1 },
							{ label: __( 'Show only locked posts', 'patrons-tips' ), value: 0 },
							{ label: __( 'Show only unlocked posts', 'patrons-tips' ), value: 1 }
						] }
						onChange={ ( unlocked ) => props.setAttributes( { "unlocked": parseInt( unlocked ), "restricted": parseInt( unlocked ) >= 0 ? 1 : props.attributes.restricted } ) }
					/>
				}
				<TextControl
					type='number'
					label={ __( 'Per page', 'patrons-tips' ) }
					value={ props.attributes.perPage }
					onChange={ ( perPage ) => props.setAttributes( { "perPage": parseInt( perPage ) } ) }
				/>
			</PanelBody>
			<PanelBody title={ __( 'Display', 'patrons-tips' ) }>
				<ToggleControl
					label={ __( 'Gray Out locked posts', 'patrons-tips' ) }
					checked={ props.attributes.grayOut }
					onChange={ () => props.setAttributes( { "grayOut": ! props.attributes.grayOut } ) }
				/>
				<ToggleControl
					label={ __( 'Show image only', 'patrons-tips' ) }
					checked={ props.attributes.imageOnly }
					onChange={ () => props.setAttributes( { "imageOnly": ! props.attributes.imageOnly } ) }
				/>
				<SelectControl
					label={ __( 'Image size', 'patrons-tips' ) }
					value={ props.attributes.imageSize }
					options={ [
						{ label: __( 'Auto', 'patrons-tips' ), value: '' },
						{ label: __( 'Full Size', 'patrons-tips' ), value: 'full' },
						{ label: __( 'Large', 'patrons-tips' ), value: 'large' },
						{ label: __( 'Medium', 'patrons-tips' ), value: 'medium' },
						{ label: __( 'Thumbnail', 'patrons-tips' ), value: 'thumbnail' }
					] }
					onChange={ ( imageSize ) => props.setAttributes( { "imageSize": imageSize } ) }
				/>
				<ToggleControl
					label={ __( 'Remove links', 'patrons-tips' ) }
					checked={ props.attributes.noLink }
					onChange={ () => props.setAttributes( { "noLink": ! props.attributes.noLink } ) }
				/>
			</PanelBody>
		</InspectorControls>
	)
}