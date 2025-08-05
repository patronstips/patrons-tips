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
import PatipsPatronPostListSettings from './settings';


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
	
	// Prepare variables
	const [ isLoading, setIsLoading ]                   = useState( false );
	const [ patronPostListHTML, setPatronPostListHTML ] = useState( '' );
	
	
	/**
	 * Get patron post list via AJAX
	 */
	const queryPatronPostList = debounce( 300, () => {
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
				"action":      'patipsGetPatronPostList',
				"user_id":     props.attributes.userID,
				"user_email":  props.attributes.userEmail,
				"patron_id":   props.attributes.patronID,
				"types":       JSON.stringify( props.attributes.types ),
				"categories":  JSON.stringify( props.attributes.categories ),
				"tags":        JSON.stringify( props.attributes.tags ),
				"cat_and_tag": props.attributes.catAndTag ? 1 : 0,
				"restricted":  parseInt( props.attributes.restricted ),
				"unlocked":    parseInt( props.attributes.unlocked ),
				"per_page":    props.attributes.perPage,
				"gray_out":    props.attributes.grayOut ? 1 : 0,
				"image_only":  props.attributes.imageOnly ? 1 : 0,
				"image_size":  props.attributes.imageSize,
				"nonce":       patips_var.nonce_get_patron_post_list
			} )
		} )
		.then( response => response.json() )
		.then( response => {
			if( response?.status === 'success' ) {
				if( typeof response.html !== 'undefined' ) {
					setPatronPostListHTML( response.html ? response.html : __( 'No results.', 'patrons-tips' ) );
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
		queryPatronPostList();
	}, [ 
		props.attributes.types,
		props.attributes.categories,
		props.attributes.tags,
		props.attributes.catAndTag,
		props.attributes.restricted,
		props.attributes.unlocked,
		props.attributes.perPage,
		props.attributes.grayOut,
		props.attributes.imageOnly,
		props.attributes.imageSize
	] );
	
	
	return (
		<Fragment>
			{ /* See ./settings.js */ }
			<PatipsPatronPostListSettings
				attributes={ {
					"types": props.attributes.types,
					"categories": props.attributes.categories,
					"tags": props.attributes.tags,
					"catAndTag": props.attributes.catAndTag,
					"restricted": props.attributes.restricted,
					"unlocked": props.attributes.unlocked,
					"perPage": props.attributes.perPage,
					"grayOut": props.attributes.grayOut,
					"imageOnly": props.attributes.imageOnly,
					"imageSize": props.attributes.imageSize,
					"noLink": props.attributes.noLink
				} }
				setAttributes={ props.setAttributes }
			/>
			
			
			<div { ...blockProps }>
				<div className={ 'patips-patron-post-list-block' + ( props.isSelected ? ' patips-selected-block' : '' ) }>
					{ isLoading ? <Spinner/> : <RawHTML>{ patronPostListHTML }</RawHTML> }
				</div>
			</div>
		</Fragment>
	)
}
