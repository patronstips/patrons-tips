/**
 * External dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl } from '@wordpress/components';


/**
 * Patronage add to cart form settings component
 * @return {Element} Element to render.
 */
export default function PatipsTierFormSettings( props ) {
	const tiers = patips_var?.tiers ?? {};
	
	const getFrequencyName = props.getFrequencyName;
	
	/**
	 * Check if a variable is a number
	 * @param {String|Int|Float} n
	 * @returns {Boolean}
	 */
	const isNumeric = ( n ) => {
		return ! isNaN( parseFloat( n ) ) && isFinite( n );
	};
	
	
	/**
	 * Get tier options for SelectControl
	 * @param {String} firstOption
	 * @return {Array}
	 */
	const getTierOptions = ( firstOption ) => {
		firstOption = firstOption ?? '';
		let options = [];
		
		if( firstOption === 'auto' ) {
			options.push( 
				{
					"value": 0,
					"label": __( 'Auto', 'patrons-tips' )
				}
			);
		}
		
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
	 * Get frequency options for SelectControl
	 * @param {String} firstOption
	 * @return {Array}
	 */
	const getFrequencyOptions = ( firstOption ) => {
		firstOption     = firstOption ?? '';
		let options     = [];
		let frequencies = [];
		
		if( firstOption === 'auto' ) {
			options.push( 
				{
					"value": '',
					"label": __( 'Auto', 'patrons-tips' )
				}
			);
		}
		
		if( tiers ) {
			Object.keys( tiers ).map( ( i ) => {
				let tier = tiers[ i ];
				
				if( tier?.product_ids ) {
					Object.keys( tier.product_ids ).map( ( frequency ) => {
						if( ! frequencies.includes( frequency ) ) {
							frequencies.push( frequency );
							options.push(
								{
									"value": frequency,
									"label": getFrequencyName( frequency )
								}
							);
						}
					});
				}
			});
		}
		
		return options;
	};
	
	
	return (
		<InspectorControls>
			<PanelBody title={ __( 'Patronage form options', 'patrons-tips' ) }>
				<SelectControl
					multiple
					label={ __( 'Tiers', 'patrons-tips' ) }
					value={ props.attributes.tiers }
					options={ getTierOptions() }
					onChange={ ( tier_ids ) => props.setAttributes( { "tiers": tier_ids.map( ( str ) => { return parseInt( str ); } ) } ) }
				/>
				<SelectControl
					label={ __( 'Default tier', 'patrons-tips' ) }
					value={ props.attributes.defaultTier }
					options={ getTierOptions( 'auto' ) }
					onChange={ ( defaultTier ) => props.setAttributes( { "defaultTier": isNumeric( defaultTier ) ? parseInt( defaultTier ) : 0 } ) }
				/>
				{ ( ( patips_var?.subscription_plugin ?? '' ) !== '' &&
				<SelectControl
					multiple
					label={ __( 'Frequencies', 'patrons-tips' ) }
					value={ props.attributes.frequencies }
					options={ getFrequencyOptions() }
					onChange={ ( frequencies ) => props.setAttributes( { "frequencies": frequencies } ) }
				/>
				) }
				{ ( ( patips_var?.subscription_plugin ?? '' ) !== '' &&
				<SelectControl
					label={ __( 'Default Frequency', 'patrons-tips' ) }
					value={ props.attributes.defaultFrequency }
					options={ getFrequencyOptions( 'auto' ) }
					onChange={ ( defaultFrequency ) => props.setAttributes( { "defaultFrequency": defaultFrequency } ) }
				/>
				) }
				<RangeControl
					label={ __( 'Number of decimals', 'patrons-tips' ) }
					value={ props.attributes.decimals }
					onChange={ ( decimals ) => props.setAttributes( { "decimals": parseInt( decimals ) } ) }
					min={ 0 }
					max={ 3 }
					step={ 1 }
					withInputField
					isShiftStepEnabled
				/>
			</PanelBody>
		</InspectorControls>
	)
}