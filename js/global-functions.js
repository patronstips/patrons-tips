// LOADING

/**
 * Get loading spinner and text
 * @since 0.8.0
 * @returns {HTMLElement}
 */
function patips_get_loading_html() {
	return '<div class="patips-loading-container"><div class="patips-loading-image"><div class="patips-spinner"></div></div><div class="patips-loading-text">' + patips_var.loading + '</div></div>';
}


/**
 * Add loading spinner + text
 * @since 0.8.0
 * @param {HTMLElement} element
 * @param {String} where 'append' (Default), 'prepend', 'before', after'
 */
function patips_add_loading_html( element, where ) {
	where = where ? where : 'append';
	var loading_html = patips_get_loading_html();
	     if( where === 'before' )  { element.before( loading_html ); }
	else if( where === 'after' )   { element.after( loading_html ); }
	else if( where === 'prepend' ) { element.prepend( loading_html ); }
	else                           { element.append( loading_html ); }
}


/**
 * Remove loading spinner + text
 * @since 0.8.0
 * @param {HTMLElement} element
 */
function patips_remove_loading_html( element ) {
	element.find( '.patips-loading-container' ).addBack( '.patips-loading-container' ).remove();
}


/**
 * Get URL parameter value
 * @since 0.12.0
 * @param {String} desired_param
 * @param {String} url
 * @returns {String}
 */
function patips_get_url_parameter( desired_param, url ) {
	var url_search = '';
	
	if( typeof url === 'undefined' ) { 
		url_search = window.location.search.substring( 1 );
	} else {
		var tmp = document.createElement( 'a' );
		tmp.href = url;
		url_search = tmp.search.substring( 1 );
	}
	
	var url_variables = url_search.split( '&' );
	
	for( var i = 0; i < url_variables.length; i++ ) {
		var param_name = url_variables[ i ].split( '=' );
		if( param_name[ 0 ] == desired_param ) {
			return decodeURIComponent( param_name[ 1 ].replace( /\+/g, '%20' ) );
		}
	}
	
	return '';
}


/**
 * Format a price with currency symbol
 * @since 0.23.2
 * @version 0.25.0
 * @param {Integer|Float} price_raw
 * @param {Object} args_raw Arguments to format a price (default to price settings values) {
 *     @type {String} currency_symbol    Currency symbol.
 *     @type {String} decimal_separator  Decimal separator.
 *     @type {String} thousand_separator Thousand separator.
 *     @type {String} decimals           Number of decimals.
 *     @type {String} price_format       Price format depending on the currency position.
 *     @type {Boolean} plain_text        Return plain text instead of HTML.
 * }
 * @return string
 */
function patips_format_price( price_raw, args_raw ) {
	if( isNaN( price_raw ) ) { return ''; }
	
	var price = parseFloat( price_raw );
	
	args_raw = typeof args_raw !== 'undefined' ? args_raw : {};
	if( ! $j.isPlainObject( args_raw ) ) { args_raw = {}; } 
	
	var args = $j.extend( {}, patips_var.price_args, args_raw );
	
	$j( 'body' ).trigger( 'patips_formatted_price_args', [ args, args_raw, price_raw ] );
	
	args.formatted_amount = patips_number_format( Math.abs( price ), args.decimals, args.decimal_separator, args.thousand_separator );
	
	args.formatted_price = '';
	if( ! args.plain_text ) {
		args.formatted_price += `<span class='patips-price'><bdi>`;
		if( price < 0 ) {
			args.formatted_price += `<span class='patips-price-sign'>-</span>`;
		} 
		args.formatted_price += args.price_format.replace( '%2$s', '<span class="patips-price-amount">' + args.formatted_amount + '</span>' ).replace( '%1$s', '<span class="patips-price-currency-symbol">' + args.currency_symbol + '</span>' );
		args.formatted_price += `</bdi></span>`;
		
		// Decode HTML entities
		args.formatted_price = $j( '<div/>' ).html( args.formatted_price ).html();
	} else {
		if( price < 0 ) { args.formatted_price += '-'; }
		args.formatted_price += args.price_format.replace( '%2$s', args.formatted_amount ).replace( '%1$s', args.currency_symbol );
		
		// Decode HTML entities
		args.formatted_price = $j( '<div/>' ).html( args.formatted_price ).text();
	}
	
	$j( 'body' ).trigger( 'patips_formatted_price', [ args, price_raw, args_raw ] );
	
	return args.formatted_price;
}


/**
 * Format a number with desired number of decimals, decimal separator and thousand separator
 * @since 0.23.2
 * @param {int|float} number
 * @param {int} decimals
 * @param {string} decimal_separator
 * @param {string} thousand_separator
 * @returns {string}
 */
function patips_number_format( number, decimals, decimal_separator, thousand_separator ) {
	number = parseFloat( number );

	// Keep n decimals
	formatted_number = number.toFixed( parseInt( decimals ) );

	// Split int and decimal parts
	var num_parts = formatted_number.toString().split( '.' );

	// Add thousand separators to the int part
	num_parts[ 0 ] = num_parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, thousand_separator );

	// Join int and decimal parts again with decimal separators
	formatted_number = num_parts.join( decimal_separator );

	return formatted_number;
}