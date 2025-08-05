/**
 * External dependencies
 */
import { createBlock } from '@wordpress/blocks';

const transforms = {
	from: [
		{
			type: 'shortcode',
			tag: [ 'patronstips_patron_history' ],
			isMatch( atts ) {
				return true;
			},
			transform( atts, shortcodeObj ) {
				return createBlock( 'patrons-tips/patron-history', {
					"userID":   typeof atts?.named?.user_id !== 'undefined' ? parseInt( atts?.named?.user_id ) : 0,
					"patronID": typeof atts?.named?.patron_id !== 'undefined' ? parseInt( atts?.named?.patron_id ) : 0,
					"tiers":    typeof atts?.named?.tier_ids !== 'undefined' ? atts.named.tier_ids.split( ',' ).map( ( str ) => { return parseInt( str ); } ) : [],
					"period":   atts?.named?.period ?? '',
					"date":     atts?.named?.date ?? '',
					"current":  typeof atts?.named?.current !== 'undefined' ? parseInt( atts.named.current ) : false,
					"active":   typeof atts?.named?.active !== 'undefined' ? parseInt( atts.named.active ) : false,
					"perPage":  typeof atts?.named?.per_page !== 'undefined' ? parseInt( atts.named.per_page ) : 12,
					"columns":  typeof atts?.named?.columns !== 'undefined' ? atts.named.columns.split( ',' ).map( ( item ) => { return item.trim(); } ) : []
				} );
			}
		}
	]
};

export default transforms;