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
import PatipsPatronHistorySettings from './settings';


/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit( props ) {
	const blockProps      = useBlockProps();
	const selectedColumns = props.attributes.columns.length ? props.attributes.columns : [ 'period', 'end', 'tier', 'price', 'actions' ];
	
	
	/**
	 * Get demo tiers (use real tiers if exists, otherwise, use demo data)
	 */
	const getDemoTiers = () => {
		let demoTiers = {};
		
		if( typeof patips_var?.tiers !== 'undefined' ) {
			if( Object.keys( patips_var.tiers ).length ) {
				demoTiers = patips_var.tiers;
			}
		}
		
		if( ! Object.keys( demoTiers ).length ) {
			demoTiers = { 
				/* translators: %s is the tier ID */
				1: { "id": 1, "title": sprintf( __( 'Tier #%s', 'patrons-tips' ), 1 ), "price": 10 },
				/* translators: %s is the tier ID */
				2: { "id": 2, "title": sprintf( __( 'Tier #%s', 'patrons-tips' ), 2 ), "price": 20 },
				/* translators: %s is the tier ID */
				3: { "id": 3, "title": sprintf( __( 'Tier #%s', 'patrons-tips' ), 3 ), "price": 30 }
			}
		}
		
		return demoTiers;
	}
	
	const tiers = getDemoTiers();
	
	
	/**
	 * Get date in current locale format
	 */
	const getDateLabel = ( year, monthId, day ) => {
		let date   = new Date( year, monthId, day ? day : 1 );
		let locale = typeof patips_var?.locale !== 'undefined' ? patips_var.locale.replace( '_', '-' ) : 'en-US';
		let label  = date.toLocaleString( locale, day ? { day: 'numeric', month: 'long', year: 'numeric' } : { month: 'long', year: 'numeric' } );
		
		return label;
	}
	
	
	/**
	 * Generate demo data to display demo rows in the history table
	 */
	const getDemoItems = () => {
		let items  = [];
		let itemNb = Math.min( props.attributes.perPage, 3 );
		let date   = new Date();
		date.setDate( 1 );
		
		let selectedTiers = props.attributes.tiers.length ? props.attributes.tiers : Object.keys( tiers );
		
		for( let i = 0; i < itemNb; i++ ) {
			let startDate     = new Date( date.getFullYear(), date.getMonth(), 1 );
			let endDate       = new Date( date.getFullYear(), date.getMonth() + 1, 0 ); // last day of the month
			let randomTierID  = selectedTiers[ Math.floor( Math.random() * selectedTiers.length ) ];
			let randomTier    = tiers?.[ randomTierID ] ?? {};
			let randomOrderID = Math.floor( Math.random() * ( 10000 - 1 ) + 1 );
			let randomSubID   = Math.floor( Math.random() * ( 1000 - 1 ) + 1 );
			/* translators: %s = order ID */
			let actions       = Math.random() > 0.1 ? '<a href="#" className="woocommerce-button button view">' + sprintf( __( 'View order #%s', 'patrons-tips' ), randomOrderID ) + '</a>' : '';
			/* translators: %s = subscription ID */
			actions          += actions !== '' && Math.random() > 0.3 ? '<a href="#" className="woocommerce-button button view">' + sprintf( __( 'View subscription #%s', 'patrons-tips' ), randomSubID ) + '</a>' : '';
			
			items.push( {
				"period":  getDateLabel( date.getFullYear(), date.getMonth(), 0 ),
				"start":   getDateLabel( startDate.getFullYear(), startDate.getMonth(), startDate.getDate() ),
				"end":     getDateLabel( endDate.getFullYear(), endDate.getMonth(), endDate.getDate() ),
				/* translators: %s is the tier ID */
				"tier":    randomTier?.title ?? sprintf( __( 'Tier #%s', 'patrons-tips' ), 1 ),
				"price":   patips_format_price( randomTier?.price ?? 10, { 'plain_text': true } ),
				"actions": actions
			} );
			date.setMonth( date.getMonth() - 1 );
		}
		
		return items;
	};
	
	
	const demoItems = getDemoItems();
	
	
	return (
		<Fragment>
			{ /* See ./settings.js */ }
			<PatipsPatronHistorySettings
				attributes={ {
					"tiers": props.attributes.tiers,
					"period": props.attributes.period,
					"current": props.attributes.current,
					"active": props.attributes.active,
					"perPage": props.attributes.perPage,
					"columns": props.attributes.columns
				} }
				setAttributes={ props.setAttributes }
			/>


			<div { ...blockProps }>
				<div className={ 'patips-patron-history-block' + ( props.isSelected ? ' patips-selected-block' : '' ) }>
					<div className='patips-ajax-list patips-patron-history-list'>
						<table className='patips-responsive-table'>
							<thead>
								<tr>
								{ selectedColumns.map( ( columnID ) => { 
									let columnTitle = patips_var?.patron_history_columns?.[ columnID ] ? patips_var?.patron_history_columns?.[ columnID ] : columnID;

									return (
										<th key={ columnID } className={ 'patips-patron-history-list-column-' + columnID }>{ columnTitle }</th>
									)
								} ) }
								</tr>
							</thead>
							<tbody className='patips-ajax-list-items patips-patron-history-list-items'>
							{ Object.keys( demoItems ).map( ( i ) => {
								let demoItem = demoItems[ i ];

								return (
									<tr key={ i }>
										{ 
											selectedColumns.map( ( columnID ) => { 
												let columnTitle = patips_var?.patron_history_columns?.[ columnID ] ? patips_var?.patron_history_columns?.[ columnID ] : columnID;

												return (
													<td key={ i + '_' + columnID } data-title={ columnTitle } className={ 'patips-patron-history-list-column-' + columnID }>
														<RawHTML>{ demoItem?.[ columnID ] ?? '' }</RawHTML>
													</td>
												)
											} )
										}
									</tr>
								)
							} ) }
							</tbody>
						</table>
						
						{ props.attributes.perPage < 3 && (
							<div className='patips-ajax-list-more patips-patron-history-list-more'>
								<a href='#' className='patips-ajax-list-more-link patips-patron-history-list-more-link'>
									{ __( 'See more', 'patrons-tips' ) }
								</a>
							</div>
						) }
					</div>
				</div>
			</div>
		</Fragment>
	)
}
