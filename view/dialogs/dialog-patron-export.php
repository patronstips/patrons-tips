<?php
/**
 * Patron export dialog
 * @since 0.8.0
 * @version 0.26.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>

<div id='patips-patron-export-dialog' class='patips-dialog' style='display:none;' title='<?php esc_attr_e( 'Patron export', 'patrons-tips' ); ?>'>
	<form id='patips-patron-export-form'>
		<input type='hidden' name='nonce' value='<?php echo esc_attr( wp_create_nonce( 'patips_patron_export_url' ) ); ?>'/>
		<input type='hidden' name='export_type' value='csv' id='patips-export-type-field'/>
		<div class='patips-info'>
			<span><?php esc_html_e( 'This will export all the patrons of the current list (filters applied).', 'patrons-tips' ); ?></span>
		</div>
		<?php
		do_action( 'patips_patron_export_dialog_before' );
		
		$excel_import_csv   = '<a href="https://support.office.com/en-us/article/import-or-export-text-txt-or-csv-files-5250ac4c-663c-47ce-937b-339e391393ba#ID0EAAFAAA" target="_blank">' . esc_html_x( 'import', 'verb', 'patrons-tips' ) . '</a>';
		$excel_sync_csv     = '<a href="https://support.office.com/en-us/article/import-data-from-external-data-sources-power-query-be4330b3-5356-486c-a168-b68e9e616f5a#ID0EAAHAAA" target="_blank">' . esc_html_x( 'sync', 'verb', 'patrons-tips' ) . '</a>';
		$gsheets_import_csv = '<a href="https://support.google.com/docs/answer/40608" target="_blank">' . esc_html_x( 'import', 'verb', 'patrons-tips' ) . '</a>';
		$gsheets_sync_csv   = '<a href="https://support.google.com/docs/answer/3093335" target="_blank">' . esc_html_x( 'sync', 'verb', 'patrons-tips' ) . '</a>';
		?>
		
		<div class='patips-info'>
			<span>
				<strong><?php esc_html_e( 'How to use:', 'patrons-tips' ); ?></strong>
				<span>
					MS Excel (<?php echo implode( ', ', array( $excel_import_csv, $excel_sync_csv ) ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>),
				</span>
				<span>
					Google Sheets (<?php echo implode( ', ', array( $gsheets_import_csv, $gsheets_sync_csv  ) ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>)...
				</span>
			</span>
		</div>
		
		<?php
		$user_settings  = patips_get_patron_export_settings();
		$export_columns = patips_get_patron_export_columns();
		
		// Push the selected columns at the end of the options in the selected order
		$export_columns_ordered = $export_columns;
		foreach( $user_settings[ 'csv_columns' ] as $col_name ) {
			if( isset( $export_columns_ordered[ $col_name ] ) ) {
				$col_title = $export_columns_ordered[ $col_name ];
				unset( $export_columns_ordered[ $col_name ] );
				$export_columns_ordered[ $col_name ] = $col_title;
			}
		}
		
		$csv_fields = apply_filters( 'patips_patron_export_csv_fields', array(
			'csv_columns' => array(
				'type'        => 'select',
				'name'        => 'csv_columns',
				'id'          => 'patips-csv-columns-to-export',
				'class'       => 'patips-select2-no-ajax', 
				'multiple'    => 1,
				'attr'        => array( '<select>' => ' data-sortable="1"' ),
				'title'       => esc_html__( 'Columns to export (ordered)', 'patrons-tips' ),
				'placeholder' => esc_html__( 'Search...', 'patrons-tips' ),
				'options'     => $export_columns_ordered,
				'value'       => $user_settings[ 'csv_columns' ],
				'tip'         => esc_html__( 'Add the columns you want to export in the order they will be displayed.', 'patrons-tips' )
			),
			'csv_row_type' => array(
				'type'    => 'select',
				'name'    => 'csv_row_type',
				'title'   => esc_html__( 'Data to export', 'patrons-tips' ),
				'id'      => 'patips-select-row-type',
				'options' => array(
					'patron'  => esc_html__( 'Patrons', 'patrons-tips' ),
					'history' => esc_html__( 'Patrons\' patronage history', 'patrons-tips' )
				),
				'value'   => $user_settings[ 'csv_row_type' ],
				'tip'     => sprintf(
					/* translators: %s = "Patrons". */
					esc_html__( '"%s": Export one row per patron. Only their most recent patronage period will be visible in the export.', 'patrons-tips' ),
					'<strong>' . esc_html__( 'Patrons', 'patrons-tips' ) . '</strong>'
				) . '<br/>' . sprintf(
					/* translators: %s = "Patrons\' patronage history". */
					esc_html__( '"%s": Export one row per patronage period per patron. For example, if a patron contributed in March, April and September, three rows will be exported for this patron, one for each period.', 'patrons-tips' ),
					'<strong>' . esc_html__( 'Patrons\' patronage history', 'patrons-tips' ) . '</strong>'
				)
			),
			'csv_raw' => array(
				'type'  => 'checkbox',
				'name'  => 'csv_raw',
				'title' => esc_html__( 'Raw data', 'patrons-tips' ),
				'id'    => 'patips-csv-raw',
				'value' => $user_settings[ 'csv_raw' ],
				'tip'   => esc_html__( 'Display raw data (easy to manipulate), as opposed to formatted data (user-friendly). E.g.: A date will be displayed "1992-12-26 02:00:00" instead of "December 26th, 1992 2:00 AM".', 'patrons-tips' )
			)
		), $args );
		
		patips_display_fields( $csv_fields );
		
		do_action( 'patips_patron_export_dialog_after' );
		
		
		// Display global export fields
		$export_fields = apply_filters( 'patips_patron_export_global_fields', array(
			'per_page' => array(
				'type'  => 'number',
				'name'  => 'per_page',
				'title' => esc_html__( 'Limit', 'patrons-tips' ),
				'id'    => 'patips-select-export-limit',
				'value'	=> $user_settings[ 'per_page' ],
				'tip'   => esc_html__( 'Maximum number of patrons to export. You may need to increase your PHP max execution time if this number is too high.', 'patrons-tips' )
			)
		), $user_settings );
		
		patips_display_fields( $export_fields );
		?>
		
		<div id='patips-patron-export-url-container' style='display:none;'>
			<p><strong><?php esc_html_e( 'Secret link', 'patrons-tips' ); ?></strong></p>
			<div class='patips-export-url'>
				<div class='patips-export-url-field'><input type='text' id='patips-patron-export-url-secret' value='' readonly onfocus='this.select();'/></div>
				<div class='patips-export-button'><input type='button' value='<?php echo esc_html_x( 'Export', 'action', 'patrons-tips' ); ?>' class='button button-primary button-large'/></div>
			</div>
			<p>
				<small><?php esc_html_e( 'Visit this link to get a file export of your patrons (according to filters and settings above), or use it as a dynamic URL feed to synchronize with other apps.', 'patrons-tips' ); ?></small>
			</p>
			<p class='patips-warning'>
				<small>
					<?php esc_html_e( 'This link provides real-time data. However, some apps may synchronize only every 24h, or more.', 'patrons-tips' ); ?>
					<strong> <?php esc_html_e( 'That\'s why your changes won\'t be applied in real time on your synched apps.', 'patrons-tips' ); ?></strong>
				</small>
			</p>
			<p class='patips-warning'>
				<small>
				<?php 
					echo esc_html__( 'Only share this address with those you trust to see all your patrons\' details.', 'patrons-tips' ) . ' ' 
					   . esc_html__( 'You can reset your secret key with the "Reset" button below. This will nullify the previously generated export links.', 'patrons-tips' );
				?>
				</small>
			</p>
		</div>
		
		<?php do_action( 'patips_patron_export_dialog_after' ); ?>
	</form>
</div>