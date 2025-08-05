$j( document ).ready( function() {
	/**
	 * Update "Periods" filter
	 * @since 0.7.0
	 */
	$j( '#patips-patron-filter-container-periods' ).on( 'change', '#patips-patron-filter-period-from-month, #patips-patron-filter-period-from-year, #patips-patron-filter-period-to-month, #patips-patron-filter-period-to-year, #patips-patron-filter-period-current', function() {
		var from_month = $j( '#patips-patron-filter-period-from-month' ).val();
		var from_year  = $j( '#patips-patron-filter-period-from-year' ).val();
		var to_month   = $j( '#patips-patron-filter-period-to-month' ).val();
		var to_year    = $j( '#patips-patron-filter-period-to-year' ).val();
		
		if( ! from_year ) { from_year = new Date().getFullYear(); }
		if( ! to_year )   { to_year   = new Date().getFullYear(); }
		
		var from     = from_month ? patips_pad( from_year, 4 ) + '-' + patips_pad( from_month, 2 ) : '';
		var to       = to_month ? patips_pad( to_year, 4 ) + '-' + patips_pad( to_month, 2 ) : '';
		var duration = from || to ? 1 : '';
		
		$j( '#patips-patron-filter-period-from' ).val( from );
		$j( '#patips-patron-filter-period-to' ).val( to );
		$j( '#patips-patron-filter-period-duration' ).val( duration );
		if( from || to ) {
			$j( '#patips-patron-filter-current' ).val( '' );
		}
	});
	
	
	/**
	 * Reset "Periods" filter when "Current" filter is set
	 * @since 0.7.0
	 */
	$j( '#patips-patron-list-table-filters-form' ).on( 'change', '#patips-patron-filter-current', function() {
		if( $j( this ).val() ) {
			$j( '#patips-patron-filter-period-from-month' ).val( '' );
			$j( '#patips-patron-filter-period-from-year' ).val( '' );
			$j( '#patips-patron-filter-period-to-month' ).val( '' );
			$j( '#patips-patron-filter-period-to-year' ).val( '' );
			$j( '#patips-patron-filter-period-from' ).val( '' );
			$j( '#patips-patron-filter-period-to' ).val( '' );
			$j( '#patips-patron-filter-period-duration' ).val( '' );
		}
	});
	
	
	/**
	 * Toggle User email
	 * @since 0.6.0
	 */
	$j( '#patips-patron-user-id-selectbox' ).on( 'change', function() {
		patips_patron_editor_toggle_user_email_field();
	});
	patips_patron_editor_toggle_user_email_field();
	
	
	/**
	 * Open export patrons dialog - on click
	 * @since 0.8.0
	 */
	$j( '#patips-patron-export-button' ).on( 'click', function() {
		patips_dialog_patron_export();
	});
	
	
	/**
	 * Open export link in a new tab to generate and download the exported file
	 * @since 0.8.0
	 */
	$j( '.patips-export-button input[type="button"]' ).on( 'click', function() {
		var url = $j( this ).closest( '.patips-export-url' ).find( '.patips-export-url-field input' ).val();
		if( url ) { window.open( url, '_blank' ); }
	});
	
	
	/**
	 * Delete a patron history entry
	 * @since 0.23.0
	 */
	$j( 'body' ).on( 'click', '#patips-patron-settings-fields-history .patips-history-delete', function() {
		var row = $j( this ).parents( 'tr' ).first();
		
		var data = { 'is_allowed': true };
		row.trigger( 'patips_delete_patron_history_entry', [ data ] );
		
		if( data.is_allowed ) {
			if( typeof row.data( 'entry' ) !== 'undefined' ) {
				var deleted_nb = $j( '#patips-patron-settings-fields-history .patips-deleted-history-entry-input' ).length;
				$j( '#patips-patron-settings-fields-history' ).append(
					'<input type="hidden" name="deleted_history_entry_ids[' + deleted_nb + ']" value="' + row.data( 'entry' ) + '" class="patips-deleted-history-entry-input"/>'
				);
			}
			
			row.animate( { 'opacity': 0 }, function() { 
				row.remove();
				if( $j( '#patips-patron-settings-fields-history tbody tr' ).length <= 0 ) {
					$j( '#patips-patron-settings-fields-history' ).append( 
						'<div id="patips-patron-history-empty-message">' + patips_var.no_history + '</div>'
					);
				}
			} );
		}
	});
});


/**
 * Toggle patron user email field
 * @since 0.6.0
 */
function patips_patron_editor_toggle_user_email_field() {
	if( $j( '#patips-patron-user-id-selectbox' ).val() ) {
		$j( '#patips-patron-user-email' ).val( '' );
		$j( '#patips-patron-user-email-container' ).hide();
	} else {
		$j( '#patips-patron-user-email-container' ).show();
	}
}


/**
 * Export patrons dialog
 * @since 0.8.0
 */
function patips_dialog_patron_export() {
	// Reset dialog
	$j( '#patips-patron-export-url-secret' ).val( '' );
	$j( '#patips-patron-export-url-container' ).hide();
	$j( '#patips-patron-export-dialog .patips-notices' ).remove();
	
	// Add the buttons
	$j( '#patips-patron-export-dialog' ).dialog( 'option', 'buttons',
		// OK button   
		[{
			'text': patips_var.button_generate_export_link,			
			'click': function() { 
				patips_generate_patron_export_url( false );
			}
		},
		// Reset the address
		{
			'text': patips_var.button_reset,
			'class': 'patips-dialog-delete-button patips-dialog-left-button',
			'click': function() { 
				patips_generate_patron_export_url( true );
			}
		}]
    );
	
	// Open the modal dialog
    $j( '#patips-patron-export-dialog' ).dialog( 'open' );
}


/**
 * Generate the URL to export patrons
 * @since 0.8.0
 * @version 0.16.0
 * @param {string} reset_key
 */
function patips_generate_patron_export_url( reset_key ) {
	reset_key = reset_key || false;
	
	// Reset error notices
	$j( '#patips-patron-export-dialog .patips-notices' ).remove();

	// Display a loader
	patips_add_loading_html( $j( '#patips-patron-export-dialog' ) );
	
	// Get current filters and export settings
	var data            = patips_serialize_object( $j( '#patips-patron-export-form' ) );
	data.action         = 'patipsPatronExportUrl';
	data.reset_key      = reset_key ? 1 : 0;
	data.patron_filters = patips_get_patron_list_table_filters();
	
	$j( 'body' ).trigger( 'patips_patron_export_url_data', [ data, reset_key ] );
	
	$j.ajax({
		url: ajaxurl,
		type: 'POST',
		data: data,
		dataType: 'json',
		success: function( response ) {
			if( response.status === 'success' ) {
				$j( '#patips-patron-export-url-secret' ).val( response.url );
				$j( '#patips-patron-export-dialog' ).append( '<div class="patips-notices"><ul class="patips-success-list"><li>' + response.message + '</li></ul></div>' );
				
				$j( '#patips-patron-export-url-container' ).show();
				
				$j( 'body' ).trigger( 'patips_patron_export_url', [ response ] );

			} else if( response.status === 'failed' ) {
				var error_message = typeof response.message !== 'undefined' ? response.message : patips_var.error;
				$j( '#patips-patron-export-dialog' ).append( '<div class="patips-notices"><ul class="patips-error-list"><li>' + error_message + '</li></ul></div>' );
				console.log( error_message );
				console.log( response );
			}
		},
		error: function( e ) {
			$j( '#patips-patron-export-dialog' ).append( '<div class="patips-notices"><ul class="patips-error-list"><li>' + 'AJAX ' + patips_var.error + '</li></ul></div>' );
			console.log( 'AJAX ' + patips_var.error );
			console.log( e );
		},
		complete: function() {
			$j( '#patips-patron-export-dialog .patips-notices' ).show();
			patips_remove_loading_html( $j( '#patips-patron-export-dialog' ) );
		}
	});
}


/**
 * Get patron list table filters as a serialized object
 * @since 0.11.0 (was patips_get_patron_list_filters)
 * @returns {Object}
 */
function patips_get_patron_list_table_filters() {
	var filters = $j( '#patips-patron-list-table-filters-form' ).length ? patips_serialize_object( $j( '#patips-patron-list-table-filters-form' ) ) : {};
	$j( '#patips-patron-list-table-filters-form' ).trigger( 'patips_patron_list_table_filters', [ filters ] );
	return filters;
}