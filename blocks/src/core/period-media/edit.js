/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { useState, useEffect, Fragment, RawHTML } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import { debounce } from 'throttle-debounce';


/**
 * Internal dependencies
 */
import './editor.scss';
import PatipsPeriodMediaSettings from './settings';


/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit( props ) {
	const blockProps = useBlockProps();
	
	// Get number of patrons / income for the period
	const [ isLoading, setIsLoading ]             = useState( false );
	const [ periodMediaHTML, setPeriodMediaHTML ] = useState( '' );
	
	
	/**
	 * Get period media via AJAX
	 */
	const queryPeriodMedia = debounce( 300, () => {
		// Display loading feedback
		setIsLoading( true );
		
		// AJAX request
		fetch( patips_var.ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
				'Cache-Control': 'no-cache'
			},
			body: new URLSearchParams( { 
				"action": 'patipsGetPeriodMedia',
				"period": props.attributes.period,
				"categories": JSON.stringify( props.attributes.categories ),
				"image_size": props.attributes.imageSize,
				"per_page": props.attributes.perPage,
				"nonce": patips_var.nonce_get_period_media
			} )
		} )
		.then( response => response.json() )
		.then( response => {
			if( response?.status === 'success' ) {
				if( typeof response.html !== 'undefined' ) {
					setPeriodMediaHTML( response.html ? response.html : __( 'No results.', 'patrons-tips' ) );
				}
			}
			setIsLoading( false );
		} )
		.catch( error => {
			console.log( 'AJAX error', error );
			setIsLoading( false );
		} );
	});
	
	
	/**
	 * Update current value from DB when period changes
	 */
	useEffect( () => {
		queryPeriodMedia();
	}, [ props.attributes.period, props.attributes.categories, props.attributes.imageSize, props.attributes.perPage ] );
	
	
	return (
		<Fragment>
			{ /* See ./settings.js */ }
			<PatipsPeriodMediaSettings
				attributes={ {
					"period": props.attributes.period,
					"categories": props.attributes.categories,
					"imageSize": props.attributes.imageSize,
					"perPage": props.attributes.perPage
				} }
				setAttributes={ props.setAttributes }
			/>
			
			
			<div { ...blockProps }>
				<div className='patips-period-media'>
					{ isLoading ? <Spinner/> : <RawHTML>{ periodMediaHTML }</RawHTML> }
				</div>
			</div>
		</Fragment>
	)
}
