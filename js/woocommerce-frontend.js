$j( document ).ready( function() {
	/**
	 * Switch frequency option - on load
	 * @since 0.23.1
	 */
	if( $j( '.patips-add-to-cart-tier-form' ).length ) {
		$j( '.patips-add-to-cart-tier-form' ).each( function() {
			patips_wc_add_to_cart_tier_form_frequency_switched( $j( this ) );
		});
	}
	
	
	/**
	 * Switch frequency option - on change
	 * @since 0.16.0
	 * @version 0.23.1
	 */
	$j( 'body' ).on( 'change', '.patips-tier-frequency-input', function() {
		var form = $j( this ).closest( '.patips-add-to-cart-tier-form' );
		patips_wc_add_to_cart_tier_form_frequency_switched( form );
	});
	
	
	/**
	 * Refresh and toggle add to cart tier form instructions - on change tier
	 * @since 0.23.1
	 */
	$j( 'body' ).on( 'change', '.patips-add-to-cart-tier-form .patips-tier-option-input', function() {
		var form = $j( this ).closest( '.patips-add-to-cart-tier-form' );
		patips_wc_refresh_add_to_cart_tier_form_instructions( form );
	});
	
	
	/**
	 * Check WC add to cart tier form values and display errors - on submit
	 * @since 0.16.0
	 * @version 0.23.1
	 * @param {Event} e
	 */
	$j( 'body' ).on( 'submit', '.patips-add-to-cart-tier-form', function( e ) {
		$j( this ).find( '.patips-notices' ).remove();
		
		var product_id = $j( this ).find( '.patips-tier-option-input:checked' ).val();
		
		if( ! product_id ) {
			e.preventDefault();
			$j( this ).find( '.patips-tier-form-submit-container' ).before( '<div class="patips-notices"><ul class="patips-error-list"><li>' + ( patips_var?.no_tier_products ?? '' ) + '</li></ul></div>' );
			$j( this ).find( '.patips-notices' ).show();
		}
	});
});


/**
 * Update form after switching frequency
 * @since 0.23.1
 * @version 1.0.4
 * @param {HTMLElement} form
 */
function patips_wc_add_to_cart_tier_form_frequency_switched( form ) {
	var frequency = form.find( '.patips-tier-frequency-input:checked' ).val();
	
	// Add selected CSS class
	form.find( '.patips-tier-frequency-container .patips-tier-frequency' ).removeClass( 'patips-selected' );
	form.find( '.patips-tier-frequency-' + frequency ).addClass( 'patips-selected' );

	// Change tier options value based on selected frequency
	var select_default = false;

	// Display all options
	form.find( '.patips-notices' ).remove();
	form.find( '.patips-tier-option-container' ).removeClass( 'patips-hidden' );

	form.find( '.patips-tier-option-input' ).each( function() {
		var product_id = $j( this ).data( 'product-id-' + frequency );
		$j( this ).attr( 'value', product_id ? product_id : '' );

		// If the option is not available for this frequency, hide it
		if( ! product_id ) {
			$j( this ).closest( '.patips-tier-option-container' ).addClass( 'patips-hidden' );
			if( $j( this ).is( ':checked' ) ) {
				select_default = true;
			}
		}
	});
	
	// If there are no options, display an error message
	if( ! form.find( '.patips-tier-option-container:not(.patips-hidden)' ).length ) {
		if( form.find( '.patips-tier-frequency-container:not(.patips-hidden)' ).length ) {
			form.find( '.patips-tier-options-container' ).after( '<div class="patips-notices"><ul class="patips-error-list"><li>' + ( patips_var?.no_tiers_products_for_frequency ?? '' ) + '</li></ul></div>' );
		} else {
			form.before( '<div class="patips-no-results">' + ( patips_var?.no_tiers_products ?? '' ) + '</div>' );
			form.hide();
		}
	}

	// The selected option is not available for this frequency, select the first option available
	else if( select_default ) {
		form.find( '.patips-tier-option-container:visible:first' ).find( '.patips-tier-option-input' ).prop( 'checked', true ).trigger( 'change' );
	}
	
	// Refresh displayed prices
	patips_wc_refresh_add_to_cart_tier_form_prices( form );
	
	// Refresh and toggle instructions
	patips_wc_refresh_add_to_cart_tier_form_instructions( form );
	
	form.trigger( 'wc_add_to_cart_tier_form_frequency_switched' );
}


/**
 * Refresh add to cart tier form displayed prices
 * @since 0.26.2
 * @version 1.0.4
 * @param {HTMLElement} form
 */
function patips_wc_refresh_add_to_cart_tier_form_prices( form ) {
	// Get selected frequency
	var frequency = form.find( '.patips-tier-frequency.patips-selected .patips-tier-frequency-input' ).val();
	
	// Change the displayed price of each tier
	form.find( '.patips-tier-option-container' ).each( function() {
		// Get corresponding product price
		var tier_id    = $j( this ).data( 'tier_id' );
		var product_id = $j( this ).find( '.patips-tier-option-input' ).val();
		var price      = patips_var?.tiers_products_price?.[ tier_id ]?.[ frequency ]?.[ product_id ] ?? '';
		var price_args = $j( this ).find( '.patips-tier-option-price' ).data( 'price-args' );
		var price_html = price !== '' ? patips_format_price( price, price_args ) : '';
		
		// Replace price HTML
		$j( this ).find( '.patips-tier-option-price' ).html( price_html );
	});
	
	form.trigger( 'wc_add_to_cart_tier_form_prices_refreshed' );
}


/**
 * Refresh and toggle add to cart tier form instructions
 * @since 0.23.1
 * @version 1.0.4
 * @param {HTMLElement} form
 */
function patips_wc_refresh_add_to_cart_tier_form_instructions( form ) {
	var has_tier       = form.find( '.patips-tier-option-container.patips-selected' ).length > 0;
	var has_frequency  = form.find( '.patips-tier-frequency.patips-selected' ).length > 0;
	var is_one_off     = form.find( '.patips-tier-frequency-one_off.patips-selected' ).length > 0;
	
	var title_tag      = form.find( '.patips-tier-option-container.patips-selected .patips-tier-option-title' ).text();
	var price_tag      = form.find( '.patips-tier-option-container.patips-selected .patips-tier-option-price' ).text();
	var frequency_tag  = form.find( '.patips-tier-frequency.patips-selected label' ).text();
	var one_off_text   = form.find( '.patips-tier-form-instruction-one-off' ).data( 'text' ).replace( '{title}', title_tag ? title_tag : '' ).replace( '{price}', price_tag ? price_tag : '' ).replace( '{frequency}', frequency_tag ? frequency_tag : '' );
	var recurring_text = form.find( '.patips-tier-form-instruction-recurring' ).data( 'text' ).replace( '{title}', title_tag ? title_tag : '' ).replace( '{price}', price_tag ? price_tag : '' ).replace( '{frequency}', frequency_tag ? frequency_tag : '' );
	
	form.find( '.patips-tier-form-instruction-one-off' ).text( one_off_text ).toggle( has_tier && has_frequency && is_one_off );
	form.find( '.patips-tier-form-instruction-recurring' ).text( recurring_text ).toggle( has_tier && has_frequency && ! is_one_off );
	
	form.trigger( 'wc_add_to_cart_tier_form_instructions_refreshed' );
}