/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, RangeControl, SelectControl, TextControl } from '@wordpress/components';


/**
 * Internal dependencies
 */
import PatipsPeriodSelect from '../../components/period-select'


/**
 * Patronage period results settings component
 * @return {Element} Element to render.
 */
export default function PatipsPeriodResultsSettings( props ) {
	return (
		<InspectorControls>
			<PanelBody title={ __( 'Period', 'patrons-tips' ) }>
				<PatipsPeriodSelect
					label={ __( 'Period', 'patrons-tips' ) }
					value={ props.attributes.period }
					onChange={ ( period ) => props.setAttributes( { "period": period } ) }
				/>
			</PanelBody>
			
			<PanelBody title={ __( 'Display', 'patrons-tips' ) }>
				<SelectControl
					label={ __( 'Data to display', 'patrons-tips' ) }
					value={ props.attributes.display }
					options={ [
						{ label: __( 'Number of patrons and income', 'patrons-tips' ), value: 'both' },
						{ label: __( 'Number of patrons', 'patrons-tips' ), value: 'patron_nb' },
						{ label: __( 'Income', 'patrons-tips' ), value: 'income' }
					] }
					onChange={ ( display ) => props.setAttributes( { "display": display } ) }
				/>
				<TextControl
					type='text'
					label={ __( 'Text when value is zero', 'patrons-tips' ) }
					value={ props.attributes.zeroText }
					onChange={ ( zeroText ) => props.setAttributes( { "zeroText": zeroText } ) }
				/>
				<ToggleControl
					label={ __( 'Show value without formatting', 'patrons-tips' ) }
					checked={ props.attributes.raw }
					onChange={ () => props.setAttributes( { "raw": ! props.attributes.raw } ) }
				/>
			</PanelBody>
			
			{ ( props.attributes.display === 'income' || props.attributes.display === 'both' ) &&
				<PanelBody title={ __( 'Calculation', 'patrons-tips' ) }>
					<RangeControl
						label={ __( 'Number of decimals', 'patrons-tips' ) }
						value={ props.attributes.decimals }
						onChange={ ( decimals ) => props.setAttributes( { "decimals": parseInt( decimals ) } ) }
						min={ 0 }
						max={ 3 }
						step={ 1 }
						withInputField
						isShiftStepEnabled
					/>
					<ToggleControl
						label={ __( 'Include tax', 'patrons-tips' ) }
						checked={ props.attributes.includeTax }
						onChange={ () => props.setAttributes( { "includeTax": ! props.attributes.includeTax } ) }
					/>
					<ToggleControl
						label={ __( 'Include discounts', 'patrons-tips' ) }
						checked={ props.attributes.includeDiscounts }
						onChange={ () => props.setAttributes( { "includeDiscounts": ! props.attributes.includeDiscounts } ) }
					/>
					{ ( ( patips_var?.subscription_plugin ?? '' ) !== '' &&
						<ToggleControl
							label={ __( 'Include scheduled payments', 'patrons-tips' ) }
							checked={ props.attributes.includeScheduled }
							onChange={ () => props.setAttributes( { "includeScheduled": ! props.attributes.includeScheduled } ) }
						/>
					) }
					<ToggleControl
						label={ __( 'Include patronage without payments', 'patrons-tips' ) }
						checked={ props.attributes.includeManual }
						onChange={ () => props.setAttributes( { "includeManual": ! props.attributes.includeManual } ) }
					/>
				</PanelBody>
			}
		</InspectorControls>
	)
}