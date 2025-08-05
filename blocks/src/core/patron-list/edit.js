/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { useState, useEffect, Fragment, RawHTML } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { debounce } from 'throttle-debounce';


/**
 * Internal dependencies
 */
import './editor.scss';
import PatipsPatronListSettings from './settings';


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
	
	// Get data from store
	const postId = useSelect( select => select( 'core/editor' ).getCurrentPostId() );
	
	// Prepare variables
	const [ isLoading, setIsLoading ]           = useState( false );
	const [ patronListHTML, setPatronListHTML ] = useState( '' );
	
	
	/**
	 * Get patron list via AJAX
	 */
	const queryPatronList = debounce( 300, () => {
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
				"action": 'patipsGetPatronList',
				"post_id": postId,
				"period": props.attributes.period,
				"date": props.attributes.date,
				"current": props.attributes.current ? 1 : 0,
				"tier_ids": JSON.stringify( props.attributes.tiers ),
				"show_thanks": props.attributes.showThanks ? 1 : 0,
				"nonce": patips_var.nonce_get_patron_list
			} )
		} )
		.then( response => response.json() )
		.then( response => {
			if( response?.status === 'success' ) {
				if( typeof response.html !== 'undefined' ) {
					setPatronListHTML( response.html ? response.html : __( 'No results.', 'patrons-tips' ) );
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
	 * Update current value from DB when attributes change
	 */
	useEffect( () => {
		queryPatronList();
	}, [ 
		props.attributes.period, 
		props.attributes.date, 
		props.attributes.current, 
		props.attributes.tiers, 
		props.attributes.showThanks 
	] );
	
	
	return (
		<Fragment>
			{ /* See ./settings.js */ }
			<PatipsPatronListSettings
				attributes={ {
					"period": props.attributes.period,
					"date": props.attributes.date,
					"current": props.attributes.current,
					"tiers": props.attributes.tiers,
					"showThanks": props.attributes.showThanks
				} }
				setAttributes={ props.setAttributes }
			/>
			
			
			<div { ...blockProps }>
				<div className={ 'patips-patron-list-block' + ( props.isSelected ? ' patips-selected-block' : '' ) }>
					{ isLoading ? <Spinner/> : <RawHTML>{ patronListHTML }</RawHTML> }
				</div>
			</div>
		</Fragment>
	)
}
