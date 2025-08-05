<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// SETTINGS

/**
 * Get default options values
 * @since 0.5.0
 * @version 1.0.4
 */
function patips_get_default_options() {
	$defaults = array(
		'delete_data_on_uninstall'     => 0,
		'patronage_launch_date'        => '',
		'sales_page_id'                => 0,
		'patron_area_page_id'          => 0,
		'display_patron_list_term_ids' => array( 'restricted' ),
		'restricted_post_image_url'    => esc_url( PATIPS_PLUGIN_URL . 'img/piggy-bank.png' ),
		'gray_out_locked_posts'        => 1,
		'price_currency_symbol'        => '$',
		'price_currency_position'      => 'left',
		'price_thousand_separator'     => '.',
		'price_decimal_separator'      => ',',
		'price_decimals_number'        => 2
	);
	
	return apply_filters( 'patips_default_options', $defaults );
}


/**
 * Delete options
 * @since 0.10.0
 * @version 1.0.2
 */
function patips_delete_options() {
	delete_option( 'patips_plugin_version' );
	delete_option( 'patips_db_version' );
	delete_option( 'patips_install_date' );
	
	do_action( 'patips_delete_options' );
}


/**
 * Get option value
 * @since 0.5.0
 * @version 1.0.1
 * @param string $option_group
 * @param string $option_name
 * @param boolean $raw
 * @return mixed|null
 */
function patips_get_option( $option_group, $option_name, $raw = false ) {
	// Get raw value from database (do not use get_option() to avoid cache)
	$options = array();
	if( $raw ) {
		$alloptions = wp_load_alloptions(); // get_option() calls wp_load_alloptions() itself, so there is no overhead at runtime 
		$options = isset( $alloptions[ $option_group ] ) ? maybe_unserialize( $alloptions[ $option_group ] ) : get_option( $option_group );
	} else {
		$options = get_option( $option_group );
	}
	if( ! is_array( $options ) ) { $options = array(); }
	
	$option_value = isset( $options[ $option_name ] ) ? $options[ $option_name ] : null;
	
	if( ! isset( $options[ $option_name ] ) ) {
		$default = patips_get_default_options();
		if( isset( $default[ $option_name ] ) ) {
			$option_value = maybe_unserialize( $default[ $option_name ] );
		}
	}
	
	return apply_filters( 'patips_option_' . $option_group . '/' . $option_name, $option_value, $raw );
}


/**
 * Update option value
 * @since 0.20.0
 * @version 1.0.1
 * @param string $option_group
 * @param string $option_name
 * @param mixed $option_value
 * @return boolean
 */
function patips_update_option( $option_group, $option_name, $option_value ) {
	$alloptions = wp_load_alloptions();
	$options    = isset( $alloptions[ $option_group ] ) ? maybe_unserialize( $alloptions[ $option_group ] ) : get_option( $option_group, array() );
	
	$options[ $option_name ] = $option_value;
	
	$updated = update_option( $option_group, $options );
	
	return $updated;
}




// SCREENS

/**
 * Get Patrons Tips admin screen ids
 * @since 0.25.2
 */
function patips_get_screen_ids() {
	$screens = array(
		'toplevel_page_patrons-tips',
		'patrons-tips_page_patips_tiers',
		'patrons-tips_page_patips_patrons',
		'patrons-tips_page_patips_settings'
	);

	return apply_filters( 'patips_screen_ids', $screens );
}


/**
 * Check if the current page is a Patrons Tips screen
 * @since 0.25.2
 * @param string $screen_id
 * @return boolean
 */
function patips_is_own_screen( $screen_id = '' ) {
	if( ! function_exists( 'get_current_screen' ) ) { return false; }
	
	$current_screen = get_current_screen();
	if( ! $current_screen ) { return false; }
	
	$patips_screens = patips_get_screen_ids();
	if( ! isset( $current_screen->id ) ) { return false; }
	if( $screen_id && $current_screen->id !== $screen_id ) { return false; }
	
	return in_array( $current_screen->id, $patips_screens, true );
}




// ROLES AND CAPABILITIES

/**
 * Add roles and capabilities
 * @since 0.5.0
 */
function patips_set_role_and_cap() {
	$administrator = get_role( 'administrator' );
	if( $administrator ) {
		$administrator->add_cap( 'patips_manage_patrons_tips' );
		$administrator->add_cap( 'patips_manage_tiers' );
		$administrator->add_cap( 'patips_manage_patrons' );
		$administrator->add_cap( 'patips_manage_settings' );
		$administrator->add_cap( 'patips_create_tiers' );
		$administrator->add_cap( 'patips_edit_tiers' );
		$administrator->add_cap( 'patips_delete_tiers' );
		$administrator->add_cap( 'patips_create_patrons' );
		$administrator->add_cap( 'patips_edit_patrons' );
		$administrator->add_cap( 'patips_delete_patrons' );
	}
	
	do_action( 'patips_set_capabilities' );
}


/**
 * Remove roles and capabilities
 * @since 1.0.2 (was patis_unset_role_and_cap)
 */
function patips_unset_role_and_cap() {
	$administrator = get_role( 'administrator' );
	if( $administrator ) {
		$administrator->remove_cap( 'patips_manage_patrons_tips' );
		$administrator->remove_cap( 'patips_manage_tiers' );
		$administrator->remove_cap( 'patips_manage_patrons' );
		$administrator->remove_cap( 'patips_manage_settings' );
		$administrator->remove_cap( 'patips_create_tiers' );
		$administrator->remove_cap( 'patips_edit_tiers' );
		$administrator->remove_cap( 'patips_delete_tiers' );
		$administrator->remove_cap( 'patips_create_patrons' );
		$administrator->remove_cap( 'patips_edit_patrons' );
		$administrator->remove_cap( 'patips_delete_patrons' );
	}
	
	do_action( 'patips_unset_capabilities' );
}




// PRIVACY

/**
 * Export user patrons data with WP privacy export tool
 * @since 0.25.3
 * @param string $email_address
 * @param int $page
 * @return array
 */
function patips_privacy_exporter_patrons_data( $email_address, $page = 1 ) {
	$user           = get_user_by( 'email', $email_address );
	$user_id        = $user ? $user->ID : 0;
	$data_to_export = array();
	
	$email_patrons = patips_get_patrons_data( array( 'user_emails' => array( $email_address ) ) );
	$user_patrons  = $user_id ? patips_get_patrons_data( array( 'user_ids' => array( $user_id ) ) ) : array();
	$patrons       = array_merge( $email_patrons, $user_patrons );
	
	if( $patrons ) {
		// Allow third party to change the exported data
		$patron_data_to_export = apply_filters( 'patips_privacy_export_patrons_columns', array(
			'id'            => esc_html__( 'ID', 'patrons-tips' ),
			'nickname'      => esc_html__( 'Nickname', 'patrons-tips' ),
			'creation_date' => esc_html__( 'Creation date', 'patrons-tips' ),
			'active'        => esc_html__( 'Active', 'patrons-tips' )
		), $patrons, $email_address );

		// Set the name / value data to export for each patron
		foreach( $patrons as $patron ) {
			$patron_personal_data = array();
			
			foreach( $patron_data_to_export as $key => $name ) {
				$value = apply_filters( 'patips_privacy_export_patron_value', isset( $patron[ $key ] ) ? $patron[ $key ] : '', $key, $patron, $email_address );

				if( $value === '' || ( ! is_string( $value ) && ! is_numeric( $value ) ) ) { continue; }

				$patron_personal_data[] = array(
					'name'  => $name,
					'value' => $value
				);
			}

			if( $patron_personal_data ) {
				$data_to_export[] = array(
					'group_id'    => 'patips_patrons',
					'group_label' => esc_html__( 'Patrons', 'patrons-tips' ),
					'item_id'     => 'patips_patron_' . $patron[ 'id' ],
					'data'        => apply_filters( 'patips_privacy_export_patron_data', $patron_personal_data, $patron, $email_address )
				);
			}
		}
	}
	
	return array(
		'data' => apply_filters( 'patips_privacy_export_patrons_data', $data_to_export, $patrons, $email_address ),
		'done' => true
	);
}


/**
 * Erase user patrons data with WP privacy erase tool
 * @since 0.25.3
 * @param string $email_address
 * @param int $page
 * @return array
 */
function patips_privacy_eraser_patrons_data( $email_address, $page = 1 ) {
	$user    = get_user_by( 'email', $email_address );
	$user_id = $user ? $user->ID : 0;
	
	$email_patrons = patips_get_patrons_data( array( 'user_emails' => array( $email_address ) ) );
	$user_patrons  = $user_id ? patips_get_patrons_data( array( 'user_ids' => array( $user_id ) ) ) : array();
	$patrons       = array_merge( $email_patrons, $user_patrons );
	$patron_ids    = array_keys( $patrons );
		
	$response = array(
		'items_removed' => false,
		'items_retained' => false,
		'messages' => array(),
		'done' => true
	);
	
	if( $patrons ) {
		// Let add-ons remove their data
		$response = apply_filters( 'patips_privacy_erase_patrons_data_before', $response, $patrons, $email_address );

		// Delete the patrons metadata
		$patron_meta_keys_to_erase = apply_filters( 'patips_privacy_erase_patrons_meta_keys', array(), $patrons, $email_address, $page );
		
		if( $patron_meta_keys_to_erase ) {
			$deleted_patron_meta = patips_delete_metadata( 'patron', $patron_ids, $patron_meta_keys_to_erase );
			if( $deleted_patron_meta ) { $response[ 'items_removed' ] = true; }
			else if( $deleted_patron_meta === false ) { 
				$response[ 'items_retained' ] = true;
				$response[ 'messages' ][] = esc_html__( 'Some patron personal metadata may have not be deleted.', 'patrons-tips' );
			}

			// Feedback the user
			if( $response[ 'items_removed' ] ) {
				$response[ 'messages' ][] = esc_html__( 'Personal data attached to the patrons have been successfully deleted.', 'patrons-tips' );
			}
		}
	}
	
	$response = apply_filters( 'patips_privacy_erase_patrons_data', $response, $patrons, $email_address );
	
	// Anonymize the patrons made without account when everything else is finished
	if( $response[ 'done' ] ) {
		$anonymized = false;
		$anonymize_allowed = apply_filters( 'patips_privacy_anonymize_patrons_without_account', true, $email_address, $page );
		if( $anonymize_allowed ) {
			$anonymized = patips_anonymize_patrons( $email_address );
			if( $anonymized ) {
				$response[ 'messages' ][] = esc_html__( 'The patrons made without account have been successfully anonymized.', 'patrons-tips' );
			}
		}
		if( $anonymized === false ) {
			$response[ 'items_retained' ] = true;
			$response[ 'messages' ][] = esc_html__( 'The patrons made without account may have not been anonymized.', 'patrons-tips' );
		}
	}
	
	return $response;
}