/**
 * External dependencies
 */
import { sprintf, __, _n } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { useEffect, useState, Fragment } from '@wordpress/element';
import { TextControl } from '@wordpress/components';


/**
 * Internal dependencies
 */
import './editor.scss';
import PatipsTierFormSettings from './settings';
import PatipsTierField from '../../components/tier-field'


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
	const tiers      = patips_var?.tiers ?? {};
	
	
	/**
	 * Get a frequency name
	 * @param {String} frequency
	 * @param {Boolean} adverb
	 * @return {String}
	 */
	const getFrequencyName = ( frequency, adverb ) => {
		adverb          = typeof adverb !== 'undefined' ? adverb : true;
		let separator_i = frequency.indexOf( '_' );
		let interval    = separator_i !== false ? parseInt( frequency.substr( 0, separator_i ) ) : 1;
		let period      = separator_i !== false ? frequency.substr( separator_i + 1 ) : frequency;
		
		if( frequency === 'one_off' ) {
			interval = 0;
			period   = 'one_off';
		}
		
		let frequencyNames = {
			'one_off': __( 'one-off', 'patrons-tips' ),
			'day':     interval === 1 && adverb ? __( 'daily', 'patrons-tips' ) : /* translator: %s = a number. */ sprintf( _n( '%s day', '%s days', interval, 'patrons-tips' ), interval ),
			'week':    interval === 1 && adverb ? __( 'weekly', 'patrons-tips' ) : /* translator: %s = a number. */ sprintf( _n( '%s week', '%s weeks', interval, 'patrons-tips' ), interval ),
			'month':   interval === 1 && adverb ? __( 'monthly', 'patrons-tips' ) : /* translator: %s = a number. */ sprintf( _n( '%s month', '%s months', interval, 'patrons-tips' ), interval ),
			'year':    interval === 1 && adverb ? __( 'yearly', 'patrons-tips' ) : /* translator: %s = a number. */ sprintf( _n( '%s year', '%s years', interval, 'patrons-tips' ), interval )
		};
		
		let frequencyName = frequencyNames?.[ period ] ?? frequency;
		
		if( adverb && interval !== 1 && frequency !== 'one_off' && typeof frequencyNames?.[ period ] !== 'undefined' ) {
			/* translators: %s = number + period (e.g. "2 months", "4 weeks", "3 years", etc.) */
			frequencyName = sprintf( __( 'every %s', 'patrons-tips' ), frequencyName );
		}
		
		return frequencyName;
	};
	
	
	/**
	 * Get tiers to display
	 * @return {Array}
	 */
	const getDisplayedTiers = () => {
		let _tiers = [];
		
		if( tiers ) {
			Object.keys( tiers ).map( ( i ) => {
				let tier = tiers[ i ];
				if( ! props.attributes.tiers.length || props.attributes.tiers.includes( tier.id ) ) {
					_tiers.push( tier );
				}
			});
		}
		
		return _tiers;
	};
	
	
	/**
	 * Get frequencies to display
	 * @return {Array}
	 */
	const getDisplayedFrequencies = () => {
		let _frequencies = [];
		
		if( tiers ) {
			Object.keys( tiers ).map( ( i ) => {
				let tier = tiers[ i ];
				if( tier?.product_ids ) {
					Object.keys( tier.product_ids ).map( ( frequency ) => {
						if( ! props.attributes.frequencies.length || props.attributes.frequencies.includes( frequency ) ) {
							if( ! _frequencies.includes( frequency ) ) {
								_frequencies.push( frequency );
							}
						}
					});
				}
			});
		}
		
		return _frequencies;
	};
	
	
	const [ displayedTiers, setDisplayedTiers ]             = useState( () => getDisplayedTiers() );
	const [ displayedFrequencies, setDisplayedFrequencies ] = useState( () => getDisplayedFrequencies() );
	
	
	/**
	 * Get selected tier id
	 * @return {Integer}
	 */
	const getSelectedTierId = () => {
		let _selectedTierId = props.attributes.defaultTier;
		
		if( _selectedTierId === 0 && displayedTiers.length ) {
			let _selectedTier = displayedTiers[ Math.max( 0, Math.ceil( displayedTiers.length / 2 ) - 1 ) ];
			_selectedTierId   = typeof _selectedTier !== 'undefined' ? parseInt( _selectedTier?.id ) : 0;
		}
		
		return _selectedTierId;
	};
	
	
	/**
	 * Get selected frequency id
	 * @return {String}
	 */
	const getSelectedFrequency = () => {
		let _selectedFrequency = props.attributes.defaultFrequency;
		
		if( _selectedFrequency === '' && displayedFrequencies.length ) {
			_selectedFrequency = displayedFrequencies[ displayedFrequencies.length - 1 ];
		}
		
		return _selectedFrequency;
	};
	
	
	const [ selectedTierId, setSelectedTierId ]       = useState( () => getSelectedTierId() );
	const [ selectedFrequency, setSelectedFrequency ] = useState( () => getSelectedFrequency() );
	const [ submitLabel, setSubmitLabel ]             = useState( props.attributes.submitLabel ?? '' );
	
	
	/**
	 * Update displayed tiers when tiers attribute changes
	 */
	useEffect( () => {
		const _displayedTiers = getDisplayedTiers();
		setDisplayedTiers( _displayedTiers );
	}, [ props.attributes.tiers ] );
	
	
	/**
	 * Update selected tier when defaultTier attributes changes
	 */
	useEffect( () => {
		const _selectedTierId = getSelectedTierId();
		setSelectedTierId( _selectedTierId );
	}, [ props.attributes.defaultTier, displayedTiers ] );
	
	
	/**
	 * Update displayed frequencies when frequencies attribute changes
	 */
	useEffect( () => {
		const _displayedFrequencies = getDisplayedFrequencies();
		setDisplayedFrequencies( _displayedFrequencies );
	}, [ props.attributes.frequencies ] );
	
	
	/**
	 * Update selected frequency when defaultFrequency attribute changes
	 */
	useEffect( () => {
		const _selectedFrequency = getSelectedFrequency();
		setSelectedFrequency( _selectedFrequency );
	}, [ props.attributes.defaultFrequency, displayedFrequencies ] );
	
	
	return (
		<Fragment>
			{ /* See ./settings.js */ }
			<PatipsTierFormSettings
				getFrequencyName={ getFrequencyName }
				attributes={ {
					"tiers": props.attributes.tiers,
					"defaultTier": props.attributes.defaultTier,
					"frequencies": props.attributes.frequencies,
					"defaultFrequency": props.attributes.defaultFrequency,
					"decimals": props.attributes.decimals,
					"submitLabel": props.attributes.submitLabel
				} }
				setAttributes={ props.setAttributes }
			/>
			
			
			<div { ...blockProps }>
				<div className={ 'patips-add-to-cart-tier-form' + ( props.isSelected ? ' patips-selected-block' : '' ) }>
					<div className='patips-tier-options-container'>
					{
						displayedTiers && Object.keys( displayedTiers ).map( ( i ) => {
							let tier = displayedTiers[ i ];
							
							return (
								// A "key" is required by React in "map" functions
								<PatipsTierField
									key={ tier.id }
									tier={ tier }
									productsPrice={ patips_var?.tiers_products_price?.[ tier.id ] ?? {} }
									attributes={ {
										"defaultFrequency": props.attributes.defaultFrequency,
										"decimals": props.attributes.decimals
									} }
									isDefault={ ( selectedTierId && selectedTierId === tier.id ) || ( ! selectedTierId && i === 0 ) }
									isSelected={ props.isSelected }
								/>
							)
						})
					}
					</div>
					<div className='patips-tier-frequency-container'>
					{
						displayedFrequencies && Object.keys( displayedFrequencies ).map( ( i ) => {
							let frequency     = displayedFrequencies[ i ];
							let frequencyName = getFrequencyName( frequency );

							return (
								// A "key" is required by React in "map" functions
								<div key={ frequency }
								     id={ 'patips-tier-frequency-' + frequency } 
								     className={ 'patips-tier-frequency' + ( selectedFrequency && selectedFrequency === frequency ? ' patips-selected' : '' ) }>
										<label htmlFor={ 'patips-tier-frequency-input-' + frequency }>{ frequencyName }</label>
								</div>
							)
						})
					}
					</div>
					<div className='patips-tier-form-submit-container'>
					{
						props.isSelected ? (
							<TextControl
								type='text'
								label={ __( 'Submit button label', 'patrons-tips' ) }
								value={ props.attributes.submitLabel }
								onChange={ ( newSubmitLabel ) => props.setAttributes( { "submitLabel": newSubmitLabel } ) }
							/>
						) : (
							<input type='button' value={ props.attributes.submitLabel !== '' ? props.attributes.submitLabel : __( 'Become patron', 'patrons-tips' ) } className='patips-tier-form-submit'/>
						)
					}
					</div>
				</div>
			</div>
		</Fragment>
	)
}
