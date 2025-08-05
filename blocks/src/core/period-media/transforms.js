/**
 * External dependencies
 */
import { createBlock } from '@wordpress/blocks';

const transforms = {
	from: [
		{
			type: 'block',
			blocks: [ 'core/legacy-widget' ],
			isMatch( { idBase, instance } ) {
				return instance?.raw && idBase === 'patips_widget_period_media';
			},
			transform( { instance } ) {
				return createBlock( 'patrons-tips/period-media', {
					"imageSize": instance?.raw?.image_size ?? ''
				} );
			}
		},
		{
			type: 'shortcode',
			tag: [ 'patronstips_period_media' ],
			isMatch( atts ) {
				return true;
			},
			transform( atts, shortcodeObj ) {
				return createBlock( 'patrons-tips/period-media', {
					"period": atts?.named?.date ?? '',
					"categories": typeof atts?.named?.categories !== 'undefined' ? atts.named.categories.split( ',' ).map( ( str ) => { return parseInt( str ); } ) : [],
					"imageSize": atts?.named?.image_size ?? '',
					"perPage": typeof atts?.named?.per_page !== 'undefined' ? parseInt( atts.named.per_page ) : 1
				} );
			}
		}
	]
};

export default transforms;