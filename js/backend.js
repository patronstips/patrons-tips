$j( document ).ready( function() {
	/**
	 * Init tooltip
	 * @since 0.5.0
	 */
	patips_init_tooltip();
	
	
	/**
	 * Init jquery-ui dialogs
	 * @since 0.8.0
	 */
	patips_init_jquery_ui_dialogs();
	
	
	/**
	 * Init Select2
	 * @since 0.5.0
	 */
	patips_init_select2();
	
	
	/**
	 * Init metaboxes
	 * @since 1.0.2
	 */
	if( typeof postboxes !== 'undefined' && typeof pagenow !== 'undefined' ) {
		if( pagenow === 'patrons-tips_page_patips_tiers' || pagenow === 'patrons-tips_page_patips_patrons' ) {
			postboxes.add_postbox_toggles( pagenow );
		}
	}
	
	
	/**
	 * Move option to the bottom of the sortable selectbox when it is selected - on select2:select
	 * Do it only once
	 * @since 0.8.0
	 * @param {Event} e
	 */
	$j( 'body' ).on( 'select2:select', '.patips-select2-ajax[data-sortable="1"], .patips-select2-no-ajax[data-sortable="1"]', function( e ) {
		if( typeof e.params === 'undefined' ) { return; }
		if( typeof e.params.data === 'undefined' ) { return; }
		if( typeof e.params.data.id === 'undefined' ) { return; }
		var option_value = e.params.data.id;
		var option = $j( this ).find( 'option[value="' + option_value + '"]' );
		if( ! option.length ) { return; }
		option.detach();
		$j( this ).append( option );
		$j( this ).trigger( 'change' );
	});
	
	
	/**
	 * Make jQuery UI dialogs close when the user click outside
	 * @since 0.8.0
	 */
	$j( 'body' ).on( 'click', '.ui-widget-overlay', function (){
		$j( '.patips-dialog:ui-dialog:visible' ).dialog( 'close' );
	});
	
	
	/**
	 * Open the uploaded image selector
	 * @since 0.1.0
	 * @param {Event} e
	 */
	var file_frame;
	$j( '.patips-upload-image-button' ).on( 'click', function( e ) {
		e.preventDefault();
		var desired_data = $j( this ).data( 'desired-data' );
		if( ! desired_data ) { desired_data = 'url'; }
		var field_id = $j( this ).data( 'field-id' );
		var field = $j( '#' + field_id );
		if( ! field.length ) { return; }
		
		patips_open_wp_media_modal( file_frame, field, desired_data );
	});
	
	
	/**
	 * Remove selected image from media input
	 * @since 0.5.0
	 */
	$j( '.patips-media-input-remove' ).on( 'click', function() {
		// Hide preview and empty input
		$j( this ).siblings( 'img.patips-media-input-preview-img' ).remove();
		$j( this ).parents( '.patips-media-input-preview' ).removeClass( 'patips-has-img' );
		$j( this ).parents( '.patips-media-inputs' ).find( '.patips-input' ).val( '' );
	});
	
	
	/**
	 * Highlight the selected row in tables
	 * @since 0.6.0
	 */
	$j( '.patips-settings-table, .patips-list-table' ).on( 'click', 'tbody tr', function() {
		$j( '.patips-selected-row' ).removeClass( 'patips-selected-row' );
		$j( this ).addClass( 'patips-selected-row' );
	});
	
	
	/**
	 * Allow select2 to work in a jquery-ui dialog
	 * @since 0.8.0
	 */
	$j( '.patips-dialog' ).dialog({
		"open": function() {
			if( $j.ui && $j.ui.dialog && ! $j.ui.dialog.prototype._allowInteractionRemapped && $j( this ).closest( '.ui-dialog' ).length ) {
				if( $j.ui.dialog.prototype._allowInteraction ) {
					$j.ui.dialog.prototype._allowInteraction = function( e ) {
						if( $j( e.target ).closest( '.select2-drop' ).length ) { return true; }
						if( typeof ui_dialog_interaction === 'undefined' ) { return true; }
						return ui_dialog_interaction.apply( this, arguments );
					};
					$j.ui.dialog.prototype._allowInteractionRemapped = true;
				} else {
					$j.error( 'You must upgrade jQuery UI or else.' );
				}
			}
		},
		"_allowInteraction": function( e ) {
			return ! ( ( ! $j( e.target ).is( '.select2-input' ) ) || this._super( e ) );
		}
	});
	
	
	/**
	 * Dismiss admin notices
	 * @since 0.25.2
	 */
	$j( '.patips-admin-notice' ).on( 'click', '.notice-dismiss', function() {
		var notice_id = $j( this ).closest( '.patips-admin-notice' ).data( 'notice-id' );
		if( notice_id ) {
			patips_dismiss_admin_notice( notice_id );
		}
	});
});


/**
 * Add 0 before a number until it has *max* digits
 * @since 1.0.1
 * @param {String} str
 * @param {int} max
 * @returns {String}
 */
function patips_pad( str, max ) {
	str = str.toString();
	return str.length < max ? patips_pad( '0' + str, max ) : str;
}


/**
 * Init tooltip
 * @since 0.5.0
 */
function patips_init_tooltip() {
	if( typeof $j.fn.tipTip != 'function' ) { return; }
	$j( '.patips-tooltip' ).tipTip({
		'attribute': 'data-message',
		'fadeIn': 200,
		'fadeOut': 200,
		'delay': 200,
		'maxWidth': '300px',
		'keepAlive': true
	});
}


/**
 * Init selectbox with AJAX search
 * @since 0.5.0
 * @version 0.8.0
 */
function patips_init_select2() {
	if( ! $j.fn.select2 ) { return; }
	
	var select2_data = {
		language: patips_var.fullcalendar_locale,
		containerCssClass: 'patips-select2-selection', // Temp fix https://github.com/select2/select2/issues/5843
		selectionCssClass: 'patips-select2-selection',
		dropdownCssClass: 'patips-select2-dropdown',
		minimumResultsForSearch: 1,
		minimumInputLength: 0,
		width: 'element',
		dropdownAutoWidth: true,
		dropdownParent: $j( this ).closest( '.patips-dialog' ).length ? $j( this ).closest( '.patips-dialog' ) : $j( 'body' ),
		escapeMarkup: function( text ) { return text; }
	};
	
	$j( 'body' ).trigger( 'patips_select2_init_data', [ select2_data ] );
	
	// Without AJAX search
	$j( '.patips-select2-no-ajax:not(.select2-hidden-accessible)' ).select2( select2_data );
	
	// With AJAX search
	$j( '.patips-select2-ajax:not(.select2-hidden-accessible)' ).select2( $j.extend( true, select2_data, {
		minimumResultsForSearch: 0,
		ajax: {
			url: patips_var.ajaxurl,
			dataType: 'json',
			delay: 1000,
			data: function( params ) {
				var data_type     = $j( this ).data( 'type' ) ? $j( this ).data( 'type' ).trim() : '';
				var search_params = $j( this ).data( 'params' ) ? JSON.parse( JSON.stringify( $j( this ).data( 'params' ) ) ) : {};
				var current_options = [];
				$j( this ).find( 'option' ).each( function() {
					if( $j( this ).val() !== '' ) {
						current_options.push( { "id": $j( this ).val(), "text": $j( this ).text() } );
					}
				});
				
				var data = $j.extend( search_params, {
					"action": data_type ? 'patipsSelect2Query_' + data_type : 'patipsSelect2Query',
					"term": typeof params.term == 'string' ? params.term : '',
					"options": current_options,
					"name": $j( this ).attr( 'name' ) ? $j( this ).attr( 'name' ) : '',
					"id": $j( this ).attr( 'id' ) ? $j( this ).attr( 'id' ) : '',
					"nonce": patips_var.nonce_query_select2_options
				});
				
				$j( this ).trigger( 'patips_select2_query_data', [ data ] );
				
				return data;
			},
			processResults: function( data ) {
				var results = { "results": typeof data.options !== 'undefined' ? data.options : [] };
				
				$j( this ).trigger( 'patips_select2_query_results', [ results, data ] );
				
				return results;
			},
			transport: function( params, success, failure ) {
				var search_length = params.data.term.length;
				if( search_length >= Math.max( select2_data.minimumInputLength, 3 ) ) {
					var request = $j.ajax( params );
					request.then( success );
					request.fail( failure );
				} else {
					var request = { "abort": function(){} };
					success( { "options": params.data.options } );
				}
				return request;
			},
			cache: true
		}
	} ));
	
	$j( 'body' ).on( 'select2:open', '.patips-select2-ajax', function() { 
		$j( 'input.select2-search__field' ).attr( 'placeholder', patips_var.select2_search_placeholder.replace( '{nb}', Math.max( select2_data.minimumInputLength, 3 ) ) );
	});
	
	// Make options sortable
	patips_select2_sortable_init();
}


/**
 * Make select2 multiple select sortable
 * @since 0.8.0
 * @param {String} selectbox_selector
 */
function patips_select2_sortable_init( selectbox_selector ) {
	if( typeof selectbox_selector === 'undefined' ) {
		selectbox_selector = '.select2-hidden-accessible[data-sortable="1"] + .select2-container .patips-select2-selection.select2-selection--multiple .select2-selection__rendered';
	}
	if( ! $j( selectbox_selector ).length ) { return; }
	
	$j( selectbox_selector ).sortable({
		containment: 'parent',
		items: '.select2-selection__choice',
		
		// When the position changes, also change the corresponding <option> position in the <select>
		update: function( e, ui ) {
			// Get the selectbox
			var selectbox = $j( ui.item ).parents( '.select2-container' ).prev( '.select2-hidden-accessible' );
			if( ! selectbox.length ) { return; }
			if( ! selectbox.data( 'sortable' ) ) { return; }
			
			$j( ui.item ).parents( '.select2-container' ).find( '.select2-selection__choice' ).each( function( i, li ) {
				// Get the option value from the list item
				var option_value = false;
				if( typeof $j( li ).data( 'data' ) !== 'undefined' ) {
					if( typeof $j( li ).data( 'data' ).id !== 'undefined' ) {
						option_value = $j( li ).data( 'data' ).id;
					}
				}
				if( option_value === false ) { return true; } // continue

				// Get the option
				var option = selectbox.find( 'option[value="' + option_value + '"]' );
				if( ! option.length ) { return true; } // continue

				// Move the options
				option.detach();
				selectbox.append( option );
			});
		}
	});
}


/**
 * Initialize jQuery UI dialogs
 * @since 0.8.0
 * @param {String} scope
 */
function patips_init_jquery_ui_dialogs( scope ) {
	if( typeof scope === 'undefined' ) { scope = '.patips-dialog'; }
	$j( scope ).dialog({ 
		"modal":       true,
		"autoOpen":    false,
		"minHeight":   300,
		"minWidth":    460,
		"resize":      'auto',
		"show":        true,
		"hide":        true,
		"dialogClass": 'patips-jquery-ui-dialog'
	});
}


/**
 * Open WP media modal to pick a media
 * @since 0.1.0
 * @param {wp.media} frame
 * @param {HTMLElement} field
 * @param {String} desired_data
 */
function patips_open_wp_media_modal( frame, field, desired_data ) {
	// If the media frame already exists, reopen it.
	if( frame ) { frame.open(); return;	}

	// Create a new media frame
	frame = wp.media({
		multiple: false // Set to true to allow multiple files to be selected
	});

	// When an image is selected in the media frame...
	frame.on( 'select', function() {
		var attachment = frame.state().get( 'selection' ).first().toJSON();
		
		// Fill field value
		if( typeof attachment[ desired_data ] !== 'undefined' ) { 
			field.val( attachment[ desired_data ] );
		}
		
		// Display preview
		if( field.siblings( '.patips-media-input-preview' ).length ) {
			field.siblings( '.patips-media-input-preview' ).find( 'img.patips-media-input-preview-img' ).remove();
			field.siblings( '.patips-media-input-preview' ).removeClass( 'patips-has-img' );
			if( attachment?.url ) {
				field.siblings( '.patips-media-input-preview' ).append( '<img src="' + attachment.url + '" class="patips-media-input-preview-img"/>' );
				field.siblings( '.patips-media-input-preview' ).addClass( 'patips-has-img' );
			}
		}
	});

	// Finally, open the modal on click
	frame.open();
}




// FORM

/**
 * Serialize a form into a single object (works with multidimentionnal inputs of any depth)
 * @since 0.8.0
 * @param {HTMLElement} form
 * @returns {object}
 */
function patips_serialize_object( form ) {
	var data = {};

	function buildInputObject( arr, val ) {
		if( arr.length < 1 ) {
			return val;  
		}
		var objkey = arr[ 0 ];
		if( objkey.slice( -1 ) == ']' ) {
			objkey = objkey.slice( 0, -1 );
		}  
		var result = {};
		if( arr.length == 1 ) {
			result[ objkey ] = val;
		} else {
			arr.shift();
			var nestedVal = buildInputObject( arr, val );
			result[ objkey ] = nestedVal;
		}
		return result;
	}
	
	function gatherMultipleValues( the_form ) {
		var final_array = [];
		$j.each( the_form.serializeArray(), function( key, field ) {
			// Copy normal fields to final array without changes
			if( field.name.indexOf( '[]' ) < 0 ){
				final_array.push( field );
				return true; // That's it, jump to next iteration
			}
			
			// Remove "[]" from the field name
			var field_name = field.name.split( '[]' )[ 0 ];

			// Add the field value in its array of values
			var has_value = false;
			$j.each( final_array, function( final_key, final_field ){
				if( final_field.name === field_name ) {
					has_value = true;
					final_array[ final_key ][ 'value' ].push( field.value );
				}
			});
			// If it doesn't exist yet, create the field's array of values
			if( ! has_value ) {
				final_array.push( { "name": field_name, "value": [ field.value ] } );
			}
		});
		return final_array;
	}
	
	// Handle fields allowing multiple values first (they contain "[]" in their name)
	var final_array = gatherMultipleValues( form );
	
	// Then, create the object
	$j.each( final_array, function() {
		var val = this.value;
		var c = this.name.split( '[' );
		var a = buildInputObject( c, val );
		$j.extend( true, data, a );
	});

	return data;
}


/**
 * Toggle a selectbox multiple status
 * @since 0.13.0
 * @version 0.25.6
 * @param {HTMLElement} selectbox
 * @param {Boolean} is_multiple
 */
function patips_toggle_multiple_select( selectbox, is_multiple ) {
	// Cast values to booleans and make sure to compare booleans only
	is_multiple = typeof is_multiple !== 'undefined' ? ( is_multiple ? true : false ) : ! selectbox.prop( 'multiple' );
	if( is_multiple !== ( ! selectbox.prop( 'multiple' ) ) ) { return; }
	
	selectbox.trigger( 'patips_toggle_multiple_select_before', [ is_multiple ] );
	
	// Toggle the selectbox
	selectbox.prop( 'multiple', is_multiple );
	
	// Forbidden values if multiple selection is allowed
	selectbox.find( 'option[value="all"]' ).prop( 'disabled', is_multiple );
	selectbox.find( 'option[value="none"]' ).prop( 'disabled', is_multiple );
	selectbox.find( 'option[data-not-multiple="1"]' ).prop( 'disabled', is_multiple );
	selectbox.find( 'option:disabled:selected' ).prop( 'selected', false );
	
	// Add or remove the [] at the end of the select name
	var select_name = selectbox.attr( 'name' );
	if( is_multiple ) { 
		selectbox.attr( 'name', select_name + '[]' ); 
	} else { 
		selectbox.attr( 'name', select_name.replace( '[]', '' ) ); 
	}
	
	// Select the first available value
	if( ! is_multiple ) {
		// Get the currently selected values
		var values = selectbox.val();
		var first_available_value = null;
		if( values ) {
			if( ! Array.isArray( values ) ) { values = [ values ]; }
			$j.each( values, function( i, value ) {
				var option = selectbox.find( 'option[value="' + value + '"]' );
				if( option.length ) {
					if( ! option.is( ':disabled' ) ) {
						first_available_value = option.attr( 'value' );
						return false;
					}
				}
			});
		}
		selectbox.val( first_available_value ).trigger( 'change' );
	}
	
	if( selectbox.hasClass( 'select2-hidden-accessible' ) ) {
		if( ! $j.fn.select2 ) { return; }
		selectbox.select2( 'destroy' );
		patips_init_select2();
	}
	
	selectbox.trigger( 'patips_toggle_multiple_select_after', [ is_multiple ] );
}


/**
 * Dismiss an admin notice
 * @since 0.25.2
 * @param {String} notice_id
 */
function patips_dismiss_admin_notice( notice_id ) {
	if( notice_id == '' ) { return; }
	
	$j.ajax({
		url: patips_var.ajaxurl,
		type: 'POST',
		data: {
			"action": 'patipsDismissAdminNotice',
			"notice_id": notice_id,
			"nonce": patips_var.nonce_dismiss_admin_notice
		},
		dataType: 'json',
		success: function( response ) {
			if( response.status !== 'success' ) {
				console.log( notice_id, response );
			}
		},
		error: function( e ) {
			console.log( e );
		},
		complete: function() {}
	});
}