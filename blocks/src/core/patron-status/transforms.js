/**
 * External dependencies
 */
import { createBlock } from '@wordpress/blocks';

const transforms = {
	from: [
		{
			type: 'shortcode',
			tag: [ 'patronstips_patron_status' ],
			isMatch( atts ) {
				return true;
			},
			transform( atts, shortcodeObj ) {
				return createBlock( 'patrons-tips/patron-status', {
					"patronID": typeof atts?.named?.patron_id !== 'undefined' ? parseInt( atts?.named?.patron_id ) : 0,
					"userID": typeof atts?.named?.user_id !== 'undefined' ? parseInt( atts?.named?.user_id ) : 0
				} );
			}
		}
	]
};

export default transforms;