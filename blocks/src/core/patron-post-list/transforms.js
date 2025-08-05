/**
 * External dependencies
 */
import { createBlock } from '@wordpress/blocks';

const transforms = {
	from: [
		{
			type: 'shortcode',
			tag: [ 'patronstips_patron_posts' ],
			isMatch( atts ) {
				return true;
			},
			transform( atts, shortcodeObj ) {
				return createBlock( 'patrons-tips/patron-post-list', {
					"types":      typeof atts?.named?.types !== 'undefined' ? atts.named.types.split( ',' ).map( ( str ) => { return str.trim(); } ) : [],
					"categories": typeof atts?.named?.categories !== 'undefined' ? atts.named.categories.split( ',' ).map( ( str ) => { return parseInt( str ); } ) : [],
					"tags":       typeof atts?.named?.tags !== 'undefined' ? atts.named.tags.split( ',' ).map( ( str ) => { return parseInt( str ); } ) : [],
					"restricted": typeof atts?.named?.restricted !== 'undefined' ? parseInt( atts.named.restricted ) : false,
					"unlocked":   typeof atts?.named?.unlocked !== 'undefined' ? parseInt( atts.named.unlocked ) : false,
					"perPage":    typeof atts?.named?.per_page !== 'undefined' ? parseInt( atts.named.per_page ) : 12,
					"grayOut":    typeof atts?.named?.gray_out !== 'undefined' ? parseInt( atts.named.gray_out ) : true,
					"imageOnly":  typeof atts?.named?.image_only !== 'undefined' ? parseInt( atts.named.image_only ) : false,
					"imageSize":  instance?.raw?.image_size ?? '',
					"noLink":     typeof atts?.named?.no_link !== 'undefined' ? parseInt( atts.named.no_link ) : false
				} );
			}
		}
	]
};

export default transforms;