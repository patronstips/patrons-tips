$j( document ).ready( function() {
	/**
	 * On page load, display default tab
	 * @version 0.20.0
	 */
	patips_switch_tab_by_url_parameter();

	
	/**
	 * Switch tab when the tab is clicked
	 * @since 0.12.0
	 * @version 0.20.0
	 */
	$j( '.patips-tabs' ).on( 'click', '.patips-tab-link', function() {
		var desired_tab = $j( this ).attr( 'id' ) ? $j( this ).attr( 'id' ).substr( 4 ) : '';
		if( desired_tab ) {
			patips_switch_tab( desired_tab );
		}
	});
	
	
	/**
	 * Get more list items when the "Show more" link is clicked
	 * @since 0.12.0
	 * @param {Event} e
	 */
	$j( 'body' ).on( 'click', '.patips-ajax-list-more-link', function( e ) {
		e.preventDefault();
		patips_display_more_list_items( $j( this ) );
	});
	
	
	/**
	 * Switch tier option - on change
	 * @since 0.16.0
	 */
	$j( 'body' ).on( 'change', '.patips-tier-option-input', function() {
		$j( this ).closest( '.patips-tier-options-container' ).find( '.patips-tier-option-container' ).removeClass( 'patips-selected' );
		$j( this ).closest( '.patips-tier-option-container' ).addClass( 'patips-selected' );
		
		var form = $j( this ).closest( '.patips-add-to-cart-tier-form' );
		form.find( '.patips-notices' ).remove();
	});
	
	
	/**
	 * Process subscription - on submit
	 * @since 0.22.0
	 * @param {Event} e
	 */
	$j( 'body' ).on( 'submit', '.patips-free-subscription-form', function( e ) {
		e.preventDefault();
		patips_submit_susbcription_form( $j( this ) );
	});
});


/**
 * Switch tab
 * @since 0.12.0
 * @version 0.20.0
 * @param {String} tab
 */
function patips_switch_tab( tab ) {
	// Hide all tabs
	$j( '.patips-tab-link, .patips-tab-content' ).removeClass( 'patips-tab-active' );
	
	// Display desired tab
	$j( '.patips-tab-link[id="tab-' + tab + '"], .patips-tab-content[id="' + tab + '"]' ).addClass( 'patips-tab-active' );
}


/**
 * Switch tab according to the "tab" URL parameter
 * @since 0.20.0
 */
function patips_switch_tab_by_url_parameter() {
	var desired_tab = patips_get_url_parameter( 'tab' );
	
	if( ! desired_tab && $j( '.patips-tab-link.patips-tab-active:first' ).length ) {
		if( $j( '.patips-tab-link.patips-tab-active:first' ).attr( 'id' ) ) {
			desired_tab = $j( '.patips-tab-link.patips-tab-active:first' ).attr( 'id' ).substr( 4 );
		}
	}
	
	if( desired_tab ) {
		patips_switch_tab( desired_tab );
	}
}


/**
 * Display more list items in AJAX list
 * @since 0.12.0
 * @param {HTMLElement} more_link
 */
function patips_display_more_list_items( more_link ) {
	if( more_link.data( 'processing' ) ) { return; }
	
	var container = more_link.closest( '.patips-ajax-list' );
	
	// Reset error notices
	container.find( '.patips-notices' ).remove();
	
	// Hide the link to avoid multiple requests
	more_link.data( 'processing', true );
	more_link.hide();
	
	// Display a loader
	var more_link_container = more_link.parent();
	patips_add_loading_html( more_link_container );
	
	$j.ajax({
		'url': patips_var.ajaxurl,
		'type': 'POST',
		'data': {
			'action': 'patipsGetListItems',
			'type': JSON.stringify( more_link.data( 'type' ) ),
			'args': JSON.stringify( more_link.data( 'args' ) ),
			'nonce': more_link.data( 'nonce' )
		},
		'dataType': 'json',
		'success': function( response ) {
			if( response.status === 'success' ) {
				// Display the new entries
				container.find( '.patips-ajax-list-items' ).append( response.html );
				
				// Update offset
				if( more_link.data( 'args' )?.filters?.offset ) {
					more_link.data( 'args' ).filters.offset = parseInt( response.offset );
				}
				
				// Hide "Show more" link if there are no more entries
				if( parseInt( response.has_more ) ) {
					more_link.show();
				}
				
			} else if( response.status === 'failed' ) {
				var error_message = typeof response.message !== 'undefined' ? response.message : patips_var.error;
				container.append( '<div class="patips-notices"><ul class="patips-error-list"><li>' + error_message + '</li></ul></div>' );
				
				// Show the link
				var error_code = typeof response.error !== 'undefined' ? response.error : '';
				if( error_code !== 'no_more_items' ) {
					more_link.show();
				}
				
				console.log( error_message );
				console.log( response );
			}
		},
		'error': function( e ) {
			// Show the error
			container.append( '<div class="patips-notices"><ul class="patips-error-list"><li>' + 'AJAX ' + patips_var.error + '</li></ul></div>' );
			
			// Show the "See more" link
			more_link.show();
			
			console.log( 'AJAX error' );
			console.log( e );
		},
		'complete': function() {
			container.find( '.patips-notices' ).show();
			patips_remove_loading_html( more_link_container );
			more_link.data( 'processing', false );
		}
	});
}


/**
 * Submit subscription form
 * @since 0.22.0
 * @version 0.23.0
 * @param {HTMLElement} form
 */
function patips_submit_susbcription_form( form ) {
	if( form.data( 'processing' ) ) { return; }
	
	// Reset error notices
	form.find( '.patips-notices' ).remove();
	
	// Avoid multiple requests
	form.data( 'processing', true );
	form.find( 'input[type="submit"]' ).attr( 'disabled', true );
	
	// Get form field values
	var data = { 
		'form_data':    new FormData( form.get(0) ),
		'redirect_url': typeof form.attr( 'action' ) !== 'undefined' ? form.attr( 'action' ) : ''
	};
	
	// Trigger action before sending form
	form.trigger( 'patips_before_submit_susbcription_form', [ data ] );
	
	if( ! ( data.form_data instanceof FormData ) ) { 
		// Re-enable the submit button
		if( submit_button.length ) { form.find( 'input[type="submit"]' ).prop( 'disabled', false ); }
		return false;
	}
	
	// Change action
	data.form_data.set( 'action', 'patipsSubmitSusbcriptionForm' );
	
	// Display a loader
	patips_add_loading_html( form );
	
	$j.ajax({
		'url': patips_var.ajaxurl,
		'type': 'POST',
		'data': data.form_data,
		'dataType': 'json',
		'cache': false,
        'contentType': false,
        'processData': false,
		'success': function( response ) {
			if( response.status === 'success' ) {
				form.append( '<div class="patips-notices"><ul class="patips-success-list"><li>' + response.message + '</li></ul></div>' );
				
				// Trigger action after sending form
				form.trigger( 'patips_susbcription_form_processed', [ response, data ] );
				
				// Redirect
				if( data.redirect_url ) {
					patips_add_loading_html( form );
					window.location.assign( data.redirect_url );
					patips_remove_loading_html( form );
				}
				
			} else if( response.status === 'failed' ) {
				var error_message = typeof response.message !== 'undefined' ? response.message : patips_var.error;
				form.append( '<div class="patips-notices"><ul class="patips-error-list"><li>' + error_message + '</li></ul></div>' );
				
				console.log( error_message );
				console.log( response );
			}
		},
		'error': function( e ) {
			// Show the error
			form.append( '<div class="patips-notices"><ul class="patips-error-list"><li>' + 'AJAX ' + patips_var.error + '</li></ul></div>' );
			
			console.log( 'AJAX error' );
			console.log( e );
		},
		'complete': function() {
			form.find( '.patips-notices' ).show();
			patips_remove_loading_html( form );
			form.data( 'processing', false );
			form.find( 'input[type="submit"]' ).attr( 'disabled', false );
		}
	});
}