/**
 * External dependencies
 */
import { createBlock } from '@wordpress/blocks';

const transforms = {
	from: [
		{
			type: 'shortcode',
			tag: [ 'patronstips_patron_list' ],
			isMatch( atts ) {
				return true;
			},
			transform( atts, shortcodeObj ) {
				return createBlock( 'patrons-tips/patron-list', {
					"period": atts?.named?.period ?? '',
					"date": atts?.named?.date ?? '',
					"current": typeof atts?.named?.current !== 'undefined' ? parseInt( atts.named.current ) : false,
					"tiers": typeof atts?.named?.tier_ids !== 'undefined' ? atts.named.tier_ids.split( ',' ).map( ( str ) => { return parseInt( str ); } ) : [],
					"showThanks": false
				} );
			}
		},
		{
			type: 'shortcode',
			tag: [ 'patronstips_patron_list_thanks' ],
			isMatch( atts ) {
				return true;
			},
			transform( atts, shortcodeObj ) {
				return createBlock( 'patrons-tips/patron-list', {
					"period": atts?.named?.period ?? '',
					"date": atts?.named?.date ?? '',
					"current": typeof atts?.named?.current !== 'undefined' ? parseInt( atts.named.current ) : false,
					"tiers": typeof atts?.named?.tier_ids !== 'undefined' ? atts.named.tier_ids.split( ',' ).map( ( str ) => { return parseInt( str ); } ) : [],
					"showThanks": true
				} );
			}
		}
	]
};

export default transforms;