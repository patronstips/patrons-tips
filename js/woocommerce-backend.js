$j( document ).ready( function() {
	var patipsCtrlKey = false;
	/**
	 * Add a listener for ctrlKey down
	 * @since 0.13.0
	 * @param {Event} e
	 */
	$j( document ).on( 'keyup keydown', function( e ) {
		if( e.ctrlKey || e.metaKey ) {
			patipsCtrlKey = true;
		} else {
			patipsCtrlKey = false;
		}
	});
	
	
	/**
	 * Toggle selectbox to multiple if CTRL key is down
	 * @since 0.13.0
	 * @param {Event} e
	 */
	$j( '#patips-tier-settings-fields-products' ).on( 'select2:opening', 'select', function( e ) {
		if( patipsCtrlKey ) {
			e.preventDefault();
			$j( this ).off( 'select2:opening' );
			patips_toggle_multiple_select( $j( this ) );
		}
	});
	
	
	/**
	 * Sync patron history with WC orders - on click on button
	 * @since 0.13.0
	 * @version 0.22.0
	 * @param {Event} e
	 */
	$j( 'body' ).on( 'click', '#patips-sync-patron-history', function( e ) {
		e.preventDefault();
		patips_wc_sync_patron_history( $j( this ).data( 'patron_id' ) );
	});
});


/**
 * Sync patron history with WC orders
 * @since 0.13.0 (was patips_sync_patron_history)
 * @param {Integer} patron_id
 */
function patips_wc_sync_patron_history( patron_id ) {
	if( $j( '#patips-sync-patron-history' ).data( 'processing' ) ) { return; }
	
	var container = $j( '#patips-sync-patron-history-container' );
	
	// Reset error notices
	container.find( '.patips-notices' ).remove();
	
	// Hide the link to avoid multiple requests
	$j( '#patips-sync-patron-history' ).data( 'processing', true );
	$j( '#patips-sync-patron-history' ).prop( 'disabled', true );
	
	// Display a loader
	patips_add_loading_html( container );
	
	var data = {
		'action': 'patipsSyncPatronHistory',
		'patron_id': patron_id,
		'nonce': $j( '#patips-sync-patron-history' ).data( 'nonce' )
	};
	
	$j( 'body' ).trigger( 'patips_sync_patron_history_data', [ data ] );
	
	$j.ajax({
		'url': patips_var.ajaxurl,
		'type': 'POST',
		'data': data,
		'dataType': 'json',
		'success': function( response ) {
			if( response.status === 'success' ) {
				// Refresh history table
				$j( '#patips-patron-settings-fields-history' ).replaceWith( response.history_table );
				
				var success_message = typeof response.message !== 'undefined' ? response.message : '';
				if( success_message ) {
					container.append( '<div class="patips-notices"><ul class="patips-success-list"><li>' + success_message + '</li></ul></div>' );
				}
				
				$j( 'body' ).trigger( 'patips_sync_patron_history', [ response, data ] );
				
			} else if( response.status === 'failed' ) {
				var error_message = typeof response.message !== 'undefined' ? response.message : patips_var.error;
				container.append( '<div class="patips-notices"><ul class="patips-error-list"><li>' + error_message + '</li></ul></div>' );
				
				console.log( error_message );
				console.log( response );
			}
		},
		'error': function( e ) {
			// Show the error
			container.append( '<div class="patips-notices"><ul class="patips-error-list"><li>' + 'AJAX ' + patips_var.error + '</li></ul></div>' );
			
			console.log( 'AJAX error' );
			console.log( e );
		},
		'complete': function() {
			container.find( '.patips-notices' ).show();
			patips_remove_loading_html( container );
			
			$j( '#patips-sync-patron-history' ).data( 'processing', false );
			$j( '#patips-sync-patron-history' ).prop( 'disabled', false );
		}
	});
}