<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Display order links in patron history table
 * @since 0.22.0 (was patips_patron_settings_history_column_actions)
 * @version 0.25.5
 * @param array $entry
 * @param array $field
 * @param array $patron
 */
function patips_wc_patron_settings_history_column_actions( $entry, $field, $patron ) {
	// View order
	$order_id = ! empty( $entry[ 'order_id' ] ) ? intval( $entry[ 'order_id' ] ) : 0;
	if( $order_id ) {
		$url = '';
		if( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
			$url = \Automattic\WooCommerce\Utilities\OrderUtil::get_order_admin_edit_url( $order_id );
		}
		// WooCommerce Backward Compatibility
		else if( function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
			if( $order ) {
				$url = $order->get_edit_order_url();
			}
		}

		if( $url ) {
		?>
			<a href='<?php echo esc_url( $url ); ?>' class='patips-history-action patips-history-view-order-link button button-secondary'>
			<?php
				/* translators: %s = order ID */
				echo sprintf( esc_html__( 'View order #%s', 'patrons-tips' ), $order_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			</a>
		<?php
		}
	}
	
	// View subscription
	$subscription_id = ! empty( $entry[ 'subscription_id' ] ) ? intval( $entry[ 'subscription_id' ] ) : 0;
	if( $subscription_id ) {
		$url = patips_get_subscription_edit_url_by_plugin( $subscription_id, $entry[ 'subscription_plugin' ] );

		if( $url ) {
		?>
			<a href='<?php echo esc_url( $url ); ?>' class='patips-history-action patips-history-view-subscription-link button button-secondary'>
			<?php
				/* translators: %s = subscription ID */
				echo sprintf( esc_html__( 'View subscription #%s', 'patrons-tips' ), $subscription_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			</a>
		<?php
		}
	}
}
add_action( 'patips_patron_settings_history_column_actions', 'patips_wc_patron_settings_history_column_actions', 10, 3 );


/**
 * AJAX Controller - Sync patron history
 * @since 0.22.0 (was patips_controller_sync_patron_history)
 */
function patips_wc_controller_sync_patron_history() {
	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'patips_nonce_sync_patron_history', 'nonce', false );
	if( ! $is_nonce_valid ) { patips_send_json_invalid_nonce( 'sync_patron_history' ); }
	
	// Check permission
	$is_allowed = current_user_can( 'patips_edit_patrons' );
	if( ! $is_allowed ) { patips_send_json_not_allowed( 'sync_patron_history' ); }
	
	// Get patron data
	$patron_id = isset( $_POST[ 'patron_id' ] ) ? intval( $_POST[ 'patron_id' ] ) : 0;
	if( ! $patron_id ) {
		patips_send_json( array( 
			'status'  => 'failed', 
			'error'   => 'patron_not_found', 
			'message' => esc_html__( 'Patron data not found.', 'patrons-tips' )
		), 'sync_patron_history' );
	}
	
	// Sync patron history
	patips_wc_sync_patron_history( $patron_id );
	
	// Refresh patron data
	wp_cache_delete( 'patron_' . $patron_id, 'patrons-tips' );
	$patron = patips_get_patron_data( $patron_id, array( 'active' => false ) );
	
	// Get new patron history settings table
	$history_table = patips_get_patron_settings_history_table( $patron );
	
	$response = apply_filters( 'patips_sync_patron_history_ajax_response', array( 
		'status'        => 'success',
		'history_table' => $history_table,
		'message'       => esc_html__( 'The patronage history has been successfully synced with WooCommerce orders.', 'patrons-tips' )
	), $patron );
	
	patips_send_json( $response, 'sync_patron_history' );
}
add_action( 'wp_ajax_patipsSyncPatronHistory', 'patips_wc_controller_sync_patron_history' );
add_action( 'wp_ajax_nopriv_patipsSyncPatronHistory', 'patips_wc_controller_sync_patron_history' );


/**
 * Promote Patrons Tips Pro - patron list WC action buttons 
 * @since 0.25.0
 */
function patips_wc_display_ad_for_sync_patrons_button() {
	if( ! patips_is_plugin_active( 'patrons-tips-pro/patrons-tips-pro.php' ) ) {
		$tooltip = sprintf(
			/* translators: %s = link to "Patrons Tips - Pro" sales page */
			__( '%s can automatically search for new patrons through all your WooCommerce orders, and add them to the list with their patronage history (according to your tiers\' settings), in a single click.', 'patrons-tips' ),
			patips_get_pro_sales_link()
		);
	?>
		<button type='button' id='patips-pro-ad-sync-new-patrons' class='patips-pro-ad-button page-title-action patips-tooltip' data-message='<?php echo esc_attr( $tooltip ); ?>' disabled='disabled'>
			<?php esc_html_e( 'Sync New Patrons', 'patrons-tips' ); ?>
			<span class='patips-pro-ad-icon'></span>
		</button>
	<?php
	}
}
add_action( 'patips_patron_list_table_page_heading', 'patips_wc_display_ad_for_sync_patrons_button', 20 );