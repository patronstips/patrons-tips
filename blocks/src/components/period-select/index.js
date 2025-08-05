/**
 * External dependencies
 */
import { sprintf, __, _n } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';

export default function PatipsPeriodSelect( props ) {
	const periodDuration = props?.periodDuration ?? ( typeof patips_var?.period_duration !== 'undefined' ? parseInt( patips_var.period_duration ) : 1 );
	const periodNb       = props?.periodNb ?? 12;
	const options        = props?.options ?? [];
	
	
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
	 * Get period options (last 12 months)
	 */
	const getPeriodOptions = () => {
		let periodOptions = options.concat( [
			{ label: __( 'Current period', 'patrons-tips' ), value: 'current' }
		] );
		
		let date = new Date();
		date.setDate( 1 );
		
		for( let i = 1; i <= periodNb; i++ ) {
			date.setMonth( date.getMonth() - periodDuration );
			periodOptions.push( {
				label: /* translators: %s = a number. */ sprintf( _n( '%s period before', '%s periods before', i, 'patrons-tips' ), i )
				+ ' (' + getDateLabel( date.getFullYear(), date.getMonth(), 0 ) + ')',
				value: 'first day of this month -' + ( i * periodDuration ) + ' month'
			} );
		}
		
		return periodOptions;
	};
	
	
	return (
		<SelectControl
			label={ props?.label ?? '' }
			value={ props?.value ?? '' }
			options={ getPeriodOptions() }
			onChange={ ( value ) => props.onChange( value ) }
		/>
	);
}