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
				return instance?.raw && idBase === 'patips_widget_patron_number';
			},
			transform( { instance } ) {
				return createBlock( 'patrons-tips/period-results', {
					"display": 'patron_nb',
					"zeroText": instance?.raw?.zero_text ?? '',
					"raw": typeof instance?.raw?.raw !== 'undefined' ? parseInt( instance?.raw?.raw ) : false
				} );
			}
		},
		{
			type: 'block',
			blocks: [ 'core/legacy-widget' ],
			isMatch( { idBase, instance } ) {
				return instance?.raw && idBase === 'patips_widget_period_income';
			},
			transform( { instance } ) {
				return createBlock( 'patrons-tips/period-results', {
					"display": 'income',
					"zeroText": instance?.raw?.zero_text ?? '',
					"raw": typeof instance?.raw?.raw !== 'undefined' ? parseInt( instance?.raw?.raw ) : false,
					"decimals": typeof instance?.raw?.decimals !== 'undefined' ? parseInt( instance?.raw?.decimals ) : 0,
					"includeTax": typeof instance?.raw?.include_tax !== 'undefined' ? parseInt( instance?.raw?.include_tax ) : ( patips_var?.include_tax ?? true ),
					"includeDiscounts": typeof instance?.raw?.include_discounts !== 'undefined' ? parseInt( instance?.raw?.include_discounts ) : false,
					"includeScheduled": typeof instance?.raw?.include_scheduled !== 'undefined' ? parseInt( instance?.raw?.include_scheduled ) : true,
					"includeManual": typeof instance?.raw?.include_manual !== 'undefined' ? parseInt( instance?.raw?.include_manual ) : false
				} );
			}
		},
		{
			type: 'shortcode',
			tag: [ 'patronstips_patron_number' ],
			isMatch( atts ) {
				return true;
			},
			transform( atts, shortcodeObj ) {
				return createBlock( 'patrons-tips/period-results', {
					"display": 'patron_nb',
					"period": atts?.named?.period ?? '',
					"zeroText": atts?.named?.zero_text ?? '',
					"raw": typeof atts?.named?.raw !== 'undefined' ? parseInt( atts?.named?.raw ) : false
				} );
			}
		},
		{
			type: 'shortcode',
			tag: [ 'patronstips_period_income' ],
			isMatch( atts ) {
				return true;
			},
			transform( atts, shortcodeObj ) {
				return createBlock( 'patrons-tips/period-results', {
					"display": 'income',
					"period": atts?.named?.period ?? '',
					"zeroText": atts?.named?.zero_text ?? '',
					"raw": typeof atts?.named?.raw !== 'undefined' ? parseInt( atts?.named?.raw ) : false,
					"decimals": typeof atts?.named?.decimals !== 'undefined' ? parseInt( atts?.named?.decimals ) : 0,
					"includeTax": typeof atts?.named?.include_tax !== 'undefined' ? parseInt( atts?.named?.include_tax ) : ( patips_var?.include_tax ?? true ),
					"includeDiscounts": typeof atts?.named?.include_discounts !== 'undefined' ? parseInt( atts?.named?.include_discounts ) : false,
					"includeScheduled": typeof atts?.named?.include_scheduled !== 'undefined' ? parseInt( atts?.named?.include_scheduled ) : true,
					"includeManual": typeof atts?.named?.include_manual !== 'undefined' ? parseInt( atts?.named?.include_manual ) : false
				} );
			}
		}
	]
};

export default transforms;