<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// SCREEN OPTIONS

/**
 * Display patron page options in screen options area
 * @since 0.6.0
 * @version 0.23.0
 */
function patips_display_patrons_screen_options() {
	$screen = get_current_screen();

	// Don't do anything if we are not on the patron page
	if( ! is_object( $screen ) || $screen->id != 'patrons-tips_page_patips_patrons' ) { return; }

	if( ! empty( $_GET[ 'action' ] ) && in_array( $_GET[ 'action' ], array( 'edit', 'new' ), true ) ) {
		add_screen_option( 'layout_columns', array( 
			'max'     => 2, 
			'default' => 2 
		));
	} else {
		add_screen_option( 'per_page', array(
			'label'   => esc_html__( 'Patrons per page:', 'patrons-tips' ),
			'default' => 20,
			'option'  => 'patips_patrons_per_page'
		));
	}
}




// PATRON EDITOR METABOXES

/**
 * Get patron settings history table
 * @since 0.13.0
 * @version 0.25.6
 * @param array $patron
 * @return string
 */
function patips_get_patron_settings_history_table( $patron ) {
	$columns    = patips_get_patron_settings_history_columns();
	$history    = apply_filters( 'patips_patron_settings_history', $patron[ 'history' ], $patron, $columns );
	$no_history = ! $history ? 'patips-no-history' : '';
	
	ob_start();
?>
	<div id='patips-patron-settings-fields-history' class='patips-settings-fields-container <?php echo esc_attr( $no_history ); ?>'>
		<table class='patips-settings-table patips-responsive-table'>
			<thead>
				<tr>
				<?php foreach( $columns as $key => $column ) { ?>
					<th class='patips-patron-history-column-title' data-column='<?php echo esc_attr( $key ); ?>'>
					<?php ob_start(); ?>
						<span class='patips-patron-history-title'>
							<?php echo isset( $column[ 'title' ] ) ? esc_html( $column[ 'title' ] ) : esc_html( $key ); ?>
						</span>
					<?php 
						if( ! empty( $column[ 'required' ] ) ) {
						?>
							<span class='patips-patron-history-required'>*</span>
						<?php
						}
						if( ! empty( $column[ 'tip' ] ) ) {
							patips_tooltip( $column[ 'tip' ] );
							unset( $columns[ $key ][ 'tip' ] );
						}
						
						$column[ 'key' ] = $key;
						echo apply_filters( 'patips_patron_settings_history_column_title_' . $key, ob_get_clean(), $column, $patron ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
					</th>
				<?php } ?>
				</tr>
			</thead>
			<tbody>
			<?php 
			do_action( 'patips_patron_settings_history_rows_before', $patron, $columns, $history );
			
			foreach( $history as $i => $entry ) {
			?>
				<tr data-entry='<?php echo ! empty( $entry[ 'id' ] ) ? esc_attr( $entry[ 'id' ] ) : ''; ?>' data-active='<?php echo ! empty( $entry[ 'active' ] ) ? 1 : 0; ?>'>
					<?php foreach( $columns as $key => $column ) {
						$column_label          = isset( $column[ 'title' ] ) ? $column[ 'title' ] : $key;
						$entry[ 'row_key' ]    = $i;
						$entry[ 'column_key' ] = $key;
						
						ob_start();
						do_action( 'patips_patron_settings_history_column_' . $key, $entry, $column, $patron );
						$column_content = ob_get_clean();
					?>
						<td class='patips-patron-history-column-value <?php echo ! $column_content ? 'patips-empty-column' : ''; ?>' data-column='<?php echo esc_attr( $key ); ?>' data-column-label='<?php echo esc_attr( $column_label ); ?>'>
							<div class='patips-history-<?php echo esc_attr( sanitize_title_with_dashes( $key ) ); ?>-container'><?php echo $column_content; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?></div>
						</td>
					<?php } ?>
				</tr>
			<?php 
			}
			
			do_action( 'patips_patron_settings_history_rows_after', $patron, $columns, $history );
			?>
			</tbody>
		</table>
		
		<?php if( $no_history ) { ?>
			<div id='patips-patron-history-empty-message'>
				<?php esc_html_e( 'No history.', 'patrons-tips' ); ?>
			</div>
		<?php } ?>
	</div>
<?php

	return apply_filters( 'patips_patron_settings_history_table', ob_get_clean(), $patron, $columns, $history );
}


/**
 * Patron history settings columns 
 * @since 0.6.0
 * @version 0.25.6
 * @return array
 */
function patips_get_patron_settings_history_columns() {
	// Get tier options
	$tiers = patips_get_tiers_data();
	$tier_options = array( '' => esc_html__( 'Select a tier', 'patrons-tips' ) );
	foreach( $tiers as $tier_id => $tier ) {
		/* translators: %s is the tier ID */
		$title = $tier[ 'title' ] ? esc_html( $tier[ 'title' ] ) : ( $tier_id ? sprintf( esc_html__( 'Tier #%s', 'patrons-tips' ), $tier_id ) : '' );
		$tier_options[ $tier[ 'id' ] ] = $title;
	}
	
	return apply_filters( 'patips_patron_settings_history_columns', array(
		'start'    => array(
			'title'   => esc_html__( 'Start', 'patrons-tips' )
		),
		'duration' => array(
			'title'   => esc_html__( 'End', 'patrons-tips' )
		),
		'period' => array(
			'title'   => esc_html__( 'Period', 'patrons-tips' )
		),
		'tier'     => array(
			'options' => $tier_options,
			'title'   => esc_html__( 'Tier', 'patrons-tips' )
		),
		'origin'     => array(
			'title'   => esc_html__( 'Origin', 'patrons-tips' )
		),
		'active'     => array(
			'title'   => esc_html__( 'Active', 'patrons-tips' )
		),
		'actions'  => array(
			'title'   => '',
		)
	) );
}




// CREATE - UPDATE

/**
 * Create a patron
 * @since 0.6.0
 * @version 0.10.0
 * @param array $patron_data
 * @return int|false
 */
function patips_create_patron( $patron_data ) {
	// Insert the patron
	$patron_id = patips_insert_patron( $patron_data );
	if( ! $patron_id ) { return false; }
	
	// Insert metadata
	$patron_meta = array_intersect_key( $patron_data, patips_get_default_patron_meta() );
	if( $patron_meta ) {
		$patron_meta = array_map( 'maybe_unserialize', $patron_meta );
		patips_insert_metadata( 'patron', $patron_id, $patron_meta );
	}
	
	// Insert new entries in patron history
	if( ! empty( $patron_data[ 'history' ] ) ) {
		patips_insert_patron_history( $patron_id, $patron_data[ 'history' ] );
	}
	
	do_action( 'patips_patron_created', $patron_id, $patron_data );
	
	return $patron_id;
}


/**
 * Update a patron data
 * @since 0.6.0
 * @param array $patron_data
 * @return int|false
 */
function patips_update_patron_data( $patron_id, $patron_data ) {
	// Update the patron
	$updated1 = patips_update_patron( array_merge( $patron_data, array( 'id' => $patron_id ) ) );
	
	// Update metadata
	$updated2  = 0;
	$patron_meta = array_intersect_key( $patron_data, patips_get_default_patron_meta() );
	if( $patron_meta ) {
		$patron_meta = array_map( 'maybe_unserialize', $patron_meta );
		$updated2 = patips_update_metadata( 'patron', $patron_id, $patron_meta );
	}
	
	if( $updated1 === false || $updated2 === false ) { $updated = false; }
	else { $updated = (int)$updated1 + (int)$updated2; }
	
	$updated = apply_filters( 'patips_patron_updated', $updated, $patron_id, $patron_data );
	
	return $updated;
}