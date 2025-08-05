/**
 * External dependencies
 */
import { sprintf, __, _n } from '@wordpress/i18n';


/**
 * Period income component
 * @return {Element} Element to render.
 */
export default function PatipsPeriodIncome( props ) {
	const periodIncome = props?.periodIncome ?? 0;
	const isSelected   = props?.isSelected ? true : false;
	
	// Display HTML
	return (
		<div className={ 'patips-period-income-container' + ( ! periodIncome && props.attributes.zeroText !== '' ? ' patips-zero-text' : '' ) }>
			{ ! periodIncome && props.attributes.zeroText !== '' ? (
					props.attributes.zeroText
				) : (
					props.attributes.raw ? (
						periodIncome
					) : (
						<strong className='patips-period-income'>
							{ patips_format_price( periodIncome, { 'plain_text': true, 'decimals': props?.attributes?.decimals ?? 0 } ) }
						</strong>
					)
				)
			}
		</div>
	);
}