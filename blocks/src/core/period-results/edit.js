/**
 * External dependencies
 */
import { sprintf, __, _n } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { useEffect, useState, Fragment } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import { debounce } from 'throttle-debounce';


/**
 * Internal dependencies
 */
import './editor.scss';
import PatipsPatronNb from '../../components/patron-number';
import PatipsPeriodIncome from '../../components/period-income';
import PatipsPeriodResultsSettings from './settings';


/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit( props ) {
	const blockProps = useBlockProps();
	
	/**
	 * Check if we need to display zero text once and for all
	 * @return {Array}
	 */
	const getIsZeroText = () => {
		return props.attributes.display === 'both' && ! periodPatronNb && ! periodIncome && props.attributes.zeroText !== '';
	};
	
	
	// Get number of patrons / income for the period
	const [ isLoading, setIsLoading ]           = useState( false );
	const [ periodPatronNb, setPeriodPatronNb ] = useState( 0 );
	const [ periodIncome, setPeriodIncome ]     = useState( 0 );
	const [ isZeroText, setIsZeroText ]         = useState( () => getIsZeroText() );
	
	
	/**
	 * Get period income via AJAX
	 */
	const queryPeriodResults = debounce( 300, () => {
		// Display loading feedback
		setIsLoading( true );
		
		// AJAX request
		fetch( patips_var.ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
				'Cache-Control': 'no-cache'
			},
			body: new URLSearchParams( { 
				"action": 'patipsGetPeriodResults',
				"period": props.attributes.period,
				"include_tax": props.attributes.includeTax ? 1 : 0,
				"include_discounts": props.attributes.includeDiscounts ? 1 : 0,
				"include_scheduled": props.attributes.includeScheduled ? 1 : 0,
				"include_manual": props.attributes.includeManual ? 1 : 0,
				"nonce": patips_var.nonce_get_period_results
			} )
		} )
		.then( response => response.json() )
		.then( response => {
			if( response?.status === 'success' ) {
				if( typeof response.patron_nb !== 'undefined' ) {
					setPeriodPatronNb( response.patron_nb );
				}
				
				if( typeof response.income !== 'undefined' ) {
					setPeriodIncome( response.income );
				}
			}
			setIsLoading( false );
		} )
		.catch( error => {
			console.log( 'AJAX error', error );
			setIsLoading( false );
		} );
	});
	
	
	/**
	 * Update current value from DB when period changes
	 */
	useEffect( () => {
		setIsZeroText( getIsZeroText() );
	}, [ props.attributes.display, periodPatronNb, periodIncome, props.attributes.zeroText ] );
	
	
	/**
	 * Update current value from DB when period changes
	 */
	useEffect( () => {
		queryPeriodResults();
	}, [ props.attributes.period ] );
	
	
	/**
	 * Update current value from DB when calculation parameter changes
	 */
	useEffect( () => {
		if( props.attributes.display === 'income' || props.attributes.display === 'both' ) {
			queryPeriodResults();
		}
	}, [ props.attributes.includeTax, props.attributes.includeDiscounts, props.attributes.includeScheduled, props.attributes.includeManual ] );
	
	
	return (
		<Fragment>
			{ /* See ./settings.js */ }
			<PatipsPeriodResultsSettings
				attributes={ {
					"period": props.attributes.period,
					"display": props.attributes.display,
					"zeroText": props.attributes.zeroText,
					"raw": props.attributes.raw,
					"decimals": props.attributes.decimals,
					"includeTax": props.attributes.includeTax,
					"includeDiscounts": props.attributes.includeDiscounts,
					"includeScheduled": props.attributes.includeScheduled,
					"includeManual": props.attributes.includeManual
				} }
				setAttributes={ props.setAttributes }
			/>
			
			
			<div { ...blockProps }>
				<div className={ 'patips-period-results' + ( props.isSelected ? ' patips-selected-block' : '' ) }>
					{ isLoading && ( 
						<Spinner/>
					)}
					
					{ isZeroText && ! isLoading && ( 
						<div className='patips-zero-text'>
							{ props.attributes.zeroText }
						</div>
					)}
					
					{ ( props.attributes.display === 'patron_nb' || props.attributes.display === 'both' ) && ! isZeroText && ! isLoading && (
						<PatipsPatronNb
							periodPatronNb={ periodPatronNb } 
							attributes={ {
								"zeroText": props.attributes.display === 'patron_nb' ? props.attributes.zeroText : '',
								"raw": props.attributes.raw
							} }
							isSelected={ props.isSelected }
						/>
					) }

					{ ( props.attributes.display === 'income' || props.attributes.display === 'both' ) && ! isZeroText && ! isLoading && (
						<PatipsPeriodIncome
							periodIncome={ periodIncome } 
							attributes={ {
								"zeroText": props.attributes.display === 'income' ? props.attributes.zeroText : '',
								"raw": props.attributes.raw,
								"decimals": props.attributes.decimals
							} }
							isSelected={ props.isSelected }
						/>
					)}
				</div>
			</div>
		</Fragment>
	)
}
