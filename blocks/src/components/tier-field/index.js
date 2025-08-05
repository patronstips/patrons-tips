/**
 * External dependencies
 */
import { sprintf, __, _n } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';
import { TextControl } from '@wordpress/components';
import { useState, useEffect, RawHTML } from '@wordpress/element';


/**
 * Period goal component
 * @return {Element} Element to render.
 */
export default function PatipsTierField( props ) {
	const tier_id          = props?.tier?.id ?? 0;
	const productsPrice    = props?.productsPrice ?? {};
	const defaultFrequency = props?.attributes?.defaultFrequency ?? '';
	const isDefault        = props?.isDefault ? true : false;
	const isSelected       = props?.isSelected ? true : false;
	
	/**
	 * Get tier price
	 * @return {Float|Integer}
	 */
	const getPrice = () => {
		let price = false;
		
		if( props?.tier?.product_ids ) {
			Object.keys( props?.tier?.product_ids ).map( ( frequency ) => { 
				let productIds = props.tier.product_ids[ frequency ];
				let productId  = parseInt( productIds[ 0 ] );

				if( price === false || ( productId && frequency === defaultFrequency ) ) {
					if( typeof productsPrice?.[ frequency ]?.[ productId ] !== 'undefined' ) {
						price = productsPrice?.[ frequency ]?.[ productId ];
					}
				}
			});
		}
	
		return price !== false ? price : ( props?.tier?.price ?? 0 );
	};
	
	const tierPrice = getPrice();
	
	
	// Display HTML
	return (
		<div 
			className={ 'patips-tier-option-container' + ( isDefault ? ' patips-selected' : '' ) }
			id={ 'patips-tier-option-container-' + tier_id }
			data-tier_id={ tier_id }
		>
			<label htmlFor={ 'patips-tier-option-input-' + tier_id }>
				<div className='patips-tier-option'>
					<RawHTML className='patips-tier-option-icon'>
						{ props?.tier?.icon ?? '' }
					</RawHTML>
					<div className='patips-tier-option-text'>
						<div className='patips-tier-option-title'>
							{ /* translators: %s is the tier ID */ props?.tier?.title ?? sprintf( __( 'Tier #%s', 'patrons-tips' ), tier_id ) }
						</div>
						<div className='patips-tier-option-price'>
							{ patips_format_price( tierPrice, { 'plain_text': true, 'decimals': props?.attributes?.decimals ?? 0 } ) }
						</div>
						<div className='patips-tier-option-description'>
							{ props?.tier?.description ?? '' }
						</div>
					</div>
				</div>
			</label>
		</div>
	);
}