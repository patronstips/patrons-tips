/**
 * External dependencies
 */
import { createBlock } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

const transforms = {
	from: [
		{
			type: 'block',
			blocks: [ 'core/legacy-widget' ],
			isMatch( { idBase, instance } ) {
				return instance?.raw && idBase === 'patips_widget_tier_form';
			},
			transform( { instance } ) {
				return createBlock( 'patrons-tips/tier-form', {
					"tiers": instance?.raw?.tiers ?? [],
					"defaultTier": instance?.raw?.default_tier ?? 0,
					"frequencies": instance?.raw?.frequencies ?? [],
					"defaultFrequency": instance?.raw?.default_frequency ?? '',
					"decimals": typeof instance?.raw?.decimals !== 'undefined' ? parseInt( instance?.raw?.decimals ) : 0,
					"submitLabel": instance?.raw?.submit_label ?? __( 'Add to cart', 'patrons-tips' )
				} );
			}
		},
		{
			type: 'shortcode',
			tag: [ 'patronstips_tier_form' ],
			isMatch( atts ) {
				return true;
			},
			transform( atts, shortcodeObj ) {
				return createBlock( 'patrons-tips/tier-form', {
					"tiers": typeof atts?.named?.tiers !== 'undefined' ? atts.named.tiers.split( ',' ).map( ( str ) => { return parseInt( str ); } ) : [],
					"defaultTier": typeof atts?.named?.default_tier !== 'undefined' && ! isNaN( parseInt( atts?.named?.default_tier ) ) ? parseInt( atts.named.default_tier ) : 0,
					"frequencies": typeof atts?.named?.frequencies !== 'undefined' ? atts.named.frequencies.split( ',' ).map( ( str ) => { return str.trim(); } ) : [],
					"defaultFrequency": atts?.named?.default_frequency ?? '',
					"decimals": typeof atts?.named?.decimals !== 'undefined' && ! isNaN( parseInt( atts?.named?.decimals ) ) ? parseInt( atts.named.decimals ) : 0,
					"submitLabel": atts?.named?.submit_label ?? __( 'Add to cart', 'patrons-tips' )
				} );
			}
		}
	]
};

export default transforms;