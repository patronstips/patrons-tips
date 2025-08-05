/**
 * External dependencies
 */
import { sprintf, __, _n } from '@wordpress/i18n';
import { RawHTML } from '@wordpress/element';


/**
 * Number of patrons component
 * @return {Element} Element to render.
 */
export default function PatipsPatronNb( props ) {
	const periodPatronNb = props?.periodPatronNb ?? 0;
	const isSelected     = props?.isSelected ? true : false;
	
	// Display HTML
	return (
		<div className={ 'patips-patron-nb-container' + ( ! periodPatronNb && props.attributes.zeroText !== '' ? ' patips-zero-text' : '' ) }>
			{ ! periodPatronNb && props.attributes.zeroText !== '' ? (
					props.attributes.zeroText
				) : (
					props.attributes.raw ? (
						periodPatronNb
					) : (
						<RawHTML>
							{ /* %s = a number. */ sprintf( _n( '%s patron', '%s patrons', periodPatronNb, 'patrons-tips' ), '<strong class="patips-patron-nb">' + periodPatronNb + '</strong>' ) }
						</RawHTML>
					)
				)
			}
		</div>
	);
}