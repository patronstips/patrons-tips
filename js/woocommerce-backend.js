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
	
	
	/**
	 * Toggle create / edit product buttons in tier settings - on load
	 * @since 1.1.0
	 */
	patips_wc_toggle_tier_product_buttons();
	
	
	/**
	 * Toggle create / edit product buttons in tier settings - on change
	 * @since 1.1.0
	 */
	$j( 'body' ).on( 'change', '#patips-tier-settings-fields-products .patips-wc-products-selectbox', function() {
		patips_wc_toggle_tier_product_buttons();
	});
	
	
	/**
	 * Create tier product - on click
	 * @since 1.1.0
	 */
	$j( 'body' ).on( 'click', '.patips-tier-create-product-button', function() {
		patips_wc_create_tier_product( $j( this ) );
	});
	
	
	/**
	 * Edit tier product - on click
	 * @since 1.1.0
	 */
	$j( 'body' ).on( 'click', '.patips-tier-edit-product-button', function() {
		patips_wc_edit_tier_product( $j( this ) );
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


/**
 * Toggle create / edit product buttons in tier settings
 * @since 1.1.0
 */
function patips_wc_toggle_tier_product_buttons() {
	$j( '#patips-tier-settings-fields-products .patips-wc-products-selectbox' ).each( function() {
		var container = $j( this ).closest( '.patips-field-container' );
		var has_value = $j( this ).val() ? ( $j( this ).val().length ? true : false ) : false;
		container.find( '.patips-tier-create-product-button' ).toggle( ! has_value );
		container.find( '.patips-tier-edit-product-button' ).toggle( has_value );
	});
}


/**
 * Toggle create product from tier settings
 * @since 1.1.0
 * @param {HTMLElement} button
 */
function patips_wc_create_tier_product( button ) {
	if( button.data( 'processing' ) ) { return; }
	
	var container = button.closest( '.patips-field-container' );
	var selectbox = container.find( '.patips-wc-products-selectbox' );
	
	// Reset error notices
	container.find( '.patips-notices' ).remove();
	
	// Hide the link to avoid multiple requests
	button.data( 'processing', true );
	button.prop( 'disabled', true );
	
	// Display a loader
	patips_add_loading_html( container );
	
	var data = {
		'action': 'patipsWCCreateTierProduct',
		'tier_id': parseInt( $j( '#patips-tier-id' ).val() ),
		'frequency': selectbox.attr( 'id' ).replace( 'patips-', '' ),
		'nonce': $j( '#patips-wc-create-tier-product-nonce' ).val()
	};
	
	$j( 'body' ).trigger( 'patips_wc_create_tier_product_data', [ data, button ] );
	
	$j.ajax({
		'url': patips_var.ajaxurl,
		'type': 'POST',
		'data': data,
		'dataType': 'json',
		'success': function( response ) {
			if( response.status === 'success' ) {
				// Toggle selectbox to non multiple
				patips_toggle_multiple_select( selectbox, false );
				
				// Add the product option to the selectbox if it doesn't exist yet
				if( ! selectbox.find( 'option[value="' + response.product_id + '"]' ).length ) {
					selectbox.append( '<option value="' + response.product_id + '">' + response.product_title + '</option>' );
				}
				
				// Select the product
				selectbox.val( response.product_id ).trigger( 'change' );
				
				// Hide the create button and show the edit button instead
				patips_wc_toggle_tier_product_buttons();
				
				$j( 'body' ).trigger( 'patips_wc_tier_product_created', [ response, data, button ] );
				
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
			
			button.data( 'processing', false );
			button.prop( 'disabled', false );
		}
	});
}


/**
 * Redirect to selected product edit page from tier settings
 * @since 1.1.0
 * @param {HTMLElement} button
 */
function patips_wc_edit_tier_product( button ) {
	if( button.data( 'processing' ) ) { return; }
	
	var container = button.closest( '.patips-field-container' );
	var selectbox = container.find( '.patips-wc-products-selectbox' );
	
	// Reset error notices
	container.find( '.patips-notices' ).remove();
	
	// Hide the link to avoid multiple requests
	button.data( 'processing', true );
	button.prop( 'disabled', true );
	
	// Display a loader
	patips_add_loading_html( container );
	
	var data = {
		'action': 'patipsWCGetProductEditURL',
		'product_id': parseInt( selectbox.val() ),
		'nonce': $j( '#patips-wc-edit-tier-product-nonce' ).val()
	};
	
	$j( 'body' ).trigger( 'patips_wc_edit_tier_product_data', [ data, button ] );
	
	$j.ajax({
		'url': patips_var.ajaxurl,
		'type': 'POST',
		'data': data,
		'dataType': 'json',
		'success': function( response ) {
			if( response.status === 'success' ) {
				$j( 'body' ).trigger( 'patips_wc_edit_tier_product_redirect', [ response, data, button ] );
				
				// Redirect
				if( response.redirect_url ) {
					patips_add_loading_html( container );
					window.location.assign( response.redirect_url );
					patips_remove_loading_html( container );
				}
				
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
			
			button.data( 'processing', false );
			button.prop( 'disabled', false );
		}
	});
}