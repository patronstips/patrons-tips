/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, SelectControl } from '@wordpress/components';


/**
 * Patronage period results settings component
 * @return {Element} Element to render.
 */
export default function PatipsPatronListSettings( props ) {
	const tiers = patips_var?.tiers ?? {};
	
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
	 * Get the "period" option options
	 * @return {String}
	 */
	const getPeriodOptions = () => {
		let options = [
			{ label: __( 'Current patrons', 'patrons-tips' ), value: 'currentPatrons' }
		];
		
		if( patips_var?.is_pro ) {
			options.push( { label: __( 'Patrons as of post publication date', 'patrons-tips' ), value: 'postDate' } );
			options.push( { label: __( 'Current period patrons', 'patrons-tips' ), value: 'currentPeriod' } );
			options.push( { label: __( 'Post period patrons', 'patrons-tips' ), value: 'postPeriod' } );
		}
		
		return options;
	}
	
	
	/**
	 * Get the "period" option value
	 * @return {String}
	 */
	const getPeriodValue = () => {
		let periodValue = props.attributes.period;
		
		if( props.attributes.period === 'post' ) {
			periodValue = 'postPeriod';
		} else if( props.attributes.period === 'current' ) {
			periodValue = 'currentPeriod';
		} else if( props.attributes.date === 'post' ) {
			periodValue = 'postDate';
		} else if( props.attributes.current ) {
			periodValue = 'currentPatrons';
		}
		
		return periodValue;
	}
	
	
	/**
	 * Set attributes related to the period
	 * @param {String} period
	 */
	const setPeriodAttributes = ( period ) => {
		let periodValues = { 
			'postPeriod': 'post',
			'currentPeriod': 'current',
			'postDate': '',
			'currentPatrons': ''
		};
		
		props.setAttributes( { 
			"period":  periodValues?.[ period ] ?? period,
			"date":    period === 'postDate' ? 'post' : '',
			"current": period === 'currentPatrons'
		} )
	};
	
	
	return (
		<InspectorControls>
			<PanelBody title={ __( 'Filters', 'patrons-tips' ) }>
				{ patips_var?.is_pro && 
					<SelectControl
						label={ __( 'Period', 'patrons-tips' ) }
						value={ getPeriodValue() }
						options={ getPeriodOptions() }
						onChange={ ( period ) => setPeriodAttributes( period ) }
					/>
				}
				<SelectControl
					multiple
					label={ __( 'Tiers', 'patrons-tips' ) }
					value={ props.attributes.tiers }
					options={ getTierOptions() }
					onChange={ ( tier_ids ) => props.setAttributes( { "tiers": tier_ids.map( ( str ) => { return parseInt( str ); } ) } ) }
				/>
			</PanelBody>
			<PanelBody title={ __( 'Display', 'patrons-tips' ) }>
				<ToggleControl
					label={ __( 'Show thanks', 'patrons-tips' ) }
					checked={ props.attributes.showThanks }
					onChange={ () => props.setAttributes( { "showThanks": ! props.attributes.showThanks } ) }
				/>
			</PanelBody>
		</InspectorControls>
	)
}