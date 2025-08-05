/**
 * External dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { Fragment, RawHTML } from '@wordpress/element';


/**
 * Internal dependencies
 */
import './editor.scss';


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
	 * Get Month + Year in current locale format
	 */
	const getCurrentMonthYearLabel = () => {
		let date = new Date();
		date.setDate( 1 );
		
		let locale = typeof patips_var?.locale !== 'undefined' ? patips_var.locale.replace( '_', '-' ) : 'en-US';
		let label  = date.toLocaleString( locale, { month: 'long', year: 'numeric' } );
		
		return label;
	}
	
	
	/**
	 * Get a random date next month in current locale format
	 */
	const getRandomNextMonthDateLabel = () => {
		let randomDate = Math.floor( Math.random() * ( 29 - 1 ) + 1 );
		
		let date = new Date();
		date.setMonth( date.getMonth() + 1 );
		date.setDate( randomDate );
		
		let locale = typeof patips_var?.locale !== 'undefined' ? patips_var.locale.replace( '_', '-' ) : 'en-US';
		let label  = date.toLocaleString( locale, {  day: 'numeric', month: 'long', year: 'numeric' } );
		
		return label;
	}
	
	
	const currentMonthYearLabel = getCurrentMonthYearLabel();
	const isPatron = Math.random() > 0.5;
		
	
	return (
		<Fragment>
			<div { ...blockProps }>
				<div className='patips-patron-status'>
					{ ( props.attributes.userID > 0 || props.attributes.patronID > 0 ) && (
						<div className='patips-patron-status-message'>
							<strong>
							{ props.attributes.userID > 0 ?
									/* translators: %s = integer */
									sprintf( __( 'User #%s', 'patrons-tips' ), props.attributes.userID ) 
								:
									/* translators: %s is the patron ID */ 
									sprintf( __( 'Patron #%s', 'patrons-tips' ), props.attributes.patronID )
							}
							</strong>
						</div>
					) }
					
					<div className='patips-patron-status-period'>
					{ isPatron ?
							/* translators: %s = the period name (e.g.: "September 2024", "First quarter 2024", etc.) */
							sprintf( __( 'You are a patron for this period (%s), thank you!', 'patrons-tips' ), currentMonthYearLabel )
						:
							<Fragment>
								{
									/* translators: %s = the period name (e.g.: "September 2024", "First quarter 2024", etc.). */
									sprintf( __( 'You are not a patron for this period (%s).', 'patrons-tips' ) + ' ', currentMonthYearLabel )
								}
								<a href='#'>{ __( 'Become patron', 'patrons-tips' ) }</a>
							</Fragment>
					}
					</div>
					
					{ isPatron && (
						<div className='patips-patron-status-list'>
							<RawHTML className='patips-patron-status-list-item'>
							{ Math.random() > 0.5 ?
									sprintf( 
										/* translators: %1$s = the Tier name.  %2$s = Formatted price (e.g. $20.00). %3$s = Link to "Manage your subcription". */
										__( 'You are a recurring "%1$s" (%2$s) patron (%3$s)', 'patrons-tips' ),
										/* translators: %s is the tier ID */
										sprintf( __( 'Tier #%s', 'patrons-tips' ), 1 ), 
										patips_format_price( 20, { 'plain_text': true } ), 
										'<a href="#">' + __( 'Manage your subcription', 'patrons-tips' ) + '</a>'
									)
								:
									sprintf(
										/* translators: %1$s = the Tier name. %2$s = Formatted price (e.g. $20.00). %3$s = the period name (e.g.: "September 2024", "First quarter 2024", etc.). %4$s = formatted date (e.g. "September 30th 2024") */
										__( 'You are a "%1$s" (%2$s) patron for %3$s, until %4$s.', 'patrons-tips' ),
										/* translators: %s is the tier ID */
										sprintf( __( 'Tier #%s', 'patrons-tips' ), 1 ), 
										patips_format_price( 20, { 'plain_text': true } ), 
										currentMonthYearLabel, 
										getRandomNextMonthDateLabel() 
									)
							}
							</RawHTML>
						</div>
					) }
				</div>
			</div>
		</Fragment>
	)
}
