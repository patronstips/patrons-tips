<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// PAGES

/**
 * Display post states in the posts list table
 * @since 0.11.0
 * @version 0.20.0
 * @param array $post_states
 * @param WP_Post $post
 * @return array
 */
function patips_add_display_post_states( $post_states, $post ) {
	if( (int) patips_get_option( 'patips_settings_general', 'sales_page_id' ) === $post->ID ) {
		$post_states[ 'patips_sales_page' ] = esc_html__( 'Patronage sales page', 'patrons-tips' );
	}
	
	if( (int) patips_get_option( 'patips_settings_general', 'patron_area_page_id' ) === $post->ID ) {
		$post_states[ 'patips_patron_area_page' ] = esc_html__( 'Patron area', 'patrons-tips' );
	}
	
	return $post_states;
}
add_filter( 'display_post_states', 'patips_add_display_post_states', 10, 2 );




// MEDIA

/**
 * Display the list view by default in Media library
 * @since 0.23.2
 * @param string $result Value for the user's option.
 * @param string $option Name of the option being retrieved: "media_library_mode".
 * @param WP_User $user  WP_User object of the user whose option is being retrieved.
 * @return string
 */
function patips_default_media_library_mode( $result, $option = 'media_library_mode', $user = null ) {
	// Display the list view by default
	return $result ? $result : 'list';
}
add_filter( 'get_user_option_media_library_mode', 'patips_default_media_library_mode', 10, 3 );


/**
 * Add a metabox in attachment edit page
 * @since 0.1.0
 * @version 0.5.0
 */
function patips_add_attachment_metaboxes() {
	add_meta_box( 'patips_patronage', esc_html__( 'Patronage', 'patrons-tips' ), 'patips_display_attachment_patronage_meta_box', 'attachment', 'side', 'default' );
}
add_action( 'add_meta_boxes_attachment', 'patips_add_attachment_metaboxes' );


/**
 * Display fields to edit attachment publish date
 * @since 0.15.0
 * @version 0.25.5
 * @param WP_Post $post
 */
function patips_display_attachment_edit_date_fields( $post ) {
?>
	<div class='misc-pub-section misc-pub-edit-curtime'>
		<a href='#edit_timestamp' class='edit-timestamp hide-if-no-js' role='button'>
			<span aria-hidden='true'><?php esc_html_e( 'Edit', 'patrons-tips' ); ?></span>
			<span class='screen-reader-text'><?php esc_html_e( 'Edit date and time', 'patrons-tips' ); ?></span>
		</a>
		<fieldset id='timestampdiv' class='hide-if-js'>
			<legend class='screen-reader-text'><?php esc_html_e( 'Date and time', 'patrons-tips' ); ?></legend>
			<?php touch_time( 1, 1 ); ?>
		</fieldset>
	</div>
<?php
}
add_action( 'attachment_submitbox_misc_actions', 'patips_display_attachment_edit_date_fields', 2, 1 );


/**
 * Display 'Patronage' metabox content in attachment edit page
 * @since 0.1.0
 * @version 0.10.0
 * @param WP_Post $post
 */
function patips_display_attachment_patronage_meta_box( $post ) {
	$preview_id = get_post_meta( $post->ID, 'preview', true );
	$thumb_id   = get_post_meta( $post->ID, 'thumbnail', true );
	
	$fields = apply_filters( 'patips_attachment_settings_fields_patronage', array(
		'preview' => array( 
			'name'    => 'preview',
			'type'    => 'media',
			'id'      => 'patips-attachment-' . $post->ID . '-preview',
			'options' => array( 'hide_input' => 1, 'desired_data' => 'id' ),
			'value'   => $preview_id ? intval( $preview_id ) : '',
			'title'   => esc_html__( 'Public preview', 'patrons-tips' ),
			'tip'     => esc_html__( 'Low quality, watermarked image displayed publicly for advertising purposes.', 'patrons-tips' )
		),
		'thumbnail' => array( 
			'name'    => 'thumbnail',
			'type'    => 'media',
			'id'      => 'patips-attachment-' . $post->ID . '-thumbnail',
			'options' => array( 'hide_input' => 1, 'desired_data' => 'id' ),
			'value'   => $thumb_id ? intval( $thumb_id ) : '',
			'title'   => esc_html__( 'Post thumbnail', 'patrons-tips' ),
			'tip'     => esc_html__( 'Post thumbnail used in post lists.', 'patrons-tips' )
		)
	), $post );
	
	do_action( 'patips_attachment_settings_fields_patronage_before', $post );
	?>
	<div id='patips-attachement-settings-fields-patronage' class='patips-settings-fields-container'>
		<?php patips_display_fields( $fields ); ?>
	</div>
	<?php
	do_action( 'patips_attachment_settings_fields_patronage_after', $post );
}


/**
 * Save values of patronage fields in attachment edit page
 * @since 0.1.0
 * @param int $post_id
 */
function patips_save_attachment_fields_values( $post_id ) {
	if( isset( $_POST[ 'preview' ] ) )   { update_post_meta( $post_id, 'preview', intval( $_POST[ 'preview' ] ) ); }
	if( isset( $_POST[ 'thumbnail' ] ) ) { update_post_meta( $post_id, 'thumbnail', intval( $_POST[ 'thumbnail' ] ) ); }
}
add_action( 'edit_attachment', 'patips_save_attachment_fields_values', 10, 2 );




// FORMS

/**
 * Search users for AJAX selectbox
 * @since 0.6.0
 * @version 0.26.3
 */
function patips_controller_search_select2_users() {
	// Check nonce
	$is_nonce_valid	= check_ajax_referer( 'patips_query_select2_options', 'nonce', false );
	if( ! $is_nonce_valid ) { patips_send_json_invalid_nonce( 'search_select2_users' ); }
	
	// Check permission
	if( ! current_user_can( 'list_users' ) && ! current_user_can( 'edit_users' ) ) { patips_send_json_not_allowed( 'search_select2_users' ); }
	
	// Sanitize search
	$term         = isset( $_REQUEST[ 'term' ] )           ? sanitize_text_field( wp_unslash( $_REQUEST[ 'term' ] ) ) : '';
	$id__in       = ! empty( $_REQUEST[ 'id__in' ] )       ? patips_ids_to_array( wp_unslash( $_REQUEST[ 'id__in' ] ) ) : array();
	$id__not_in   = ! empty( $_REQUEST[ 'id__not_in' ] )   ? patips_ids_to_array( wp_unslash( $_REQUEST[ 'id__not_in' ] ) ) : array();
	$role         = ! empty( $_REQUEST[ 'role' ] )         ? patips_str_ids_to_array( wp_unslash( $_REQUEST[ 'role' ] ) ) : array();
	$role__in     = ! empty( $_REQUEST[ 'role__in' ] )     ? patips_str_ids_to_array( wp_unslash( $_REQUEST[ 'role__in' ] ) ) : array();
	$role__not_in = ! empty( $_REQUEST[ 'role__not_in' ] ) ? patips_str_ids_to_array( wp_unslash( $_REQUEST[ 'role__not_in' ] ) ) : array();
	$no_account   = ! empty( $_REQUEST[ 'no_account' ] );
	
	// Check if the search is not empty
	if( ! $term && ! $id__in && ! $role && ! $role__in && ! $no_account ) { patips_send_json( array( 'status' => 'failed', 'error' => 'empty_query' ), 'search_select2_users' ); }
	
	$defaults = array(
		'name' => isset( $_REQUEST[ 'name' ] ) ? sanitize_title_with_dashes( wp_unslash( $_REQUEST[ 'name' ] ) ) : '', // For developers to identify the selectbox
		'id' => isset( $_REQUEST[ 'id' ] ) ? sanitize_title_with_dashes( wp_unslash( $_REQUEST[ 'id' ] ) ) : '',       // For developers to identify the selectbox
		'search' => $term !== '' ? '*' . esc_attr( $term ) . '*' : '',
		'search_columns' => array( 'user_login', 'user_url', 'user_email', 'user_nicename', 'display_name' ),
		'option_label' => array( 'display_name', ' (', 'user_login', ' / ', 'user_email', ')' ),
		'include' => $id__in, 'exclude' => $id__not_in,
		'role' => $role, 'role__in' => $role__in, 'role__not_in' => $role__not_in,
		'meta' => true, 'meta_single' => true,
		'orderby' => 'display_name', 'order' => 'ASC'
	);
	$args = apply_filters( 'patips_ajax_select2_users_args', $defaults );
	
	$users   = patips_get_users_data( $args );
	$options = array();
	
	// Add user options
	foreach( $users as $user ) {
		// Build the option label based on the array
		$label = '';
		foreach( $args[ 'option_label' ] as $show ) {
			// If the key contain "||" display the first not empty value
			if( strpos( $show, '||' ) !== false ) {
				$keys = explode( '||', $show );
				$show = $keys[ 0 ];
				foreach( $keys as $key ) {
					if( ! empty( $user->{ $key } ) ) { $show = $key; break; }
				}
			}
			
			// Display the value if the key exists, else display the key as is, as a separator
			if( isset( $user->{ $show } ) ) {
				$label .= $user->{ $show };
			} else {
				$label .= $show;
			}
		}
		$options[] = array( 'id' => intval( $user->ID ), 'text' => esc_html( $label ) );
	}
	
	// Retrieve user emails from patrons not linked to any account
	if( $no_account && $term ) {
		$patrons = patips_get_patrons_data( array( 'no_account' => true ) );
		$patron_emails = array();
		foreach( $patrons as $patron ) {
			$option = apply_filters( 'patips_ajax_select2_patron_no_account_option', ! empty( $patron[ 'user_email' ] ) && strpos( strtolower( $patron[ 'user_email' ] ), strtolower( $term ) ) !== false ? array( 
				'id'   => esc_attr( $patron[ 'user_email' ] ), 
				'text' => esc_html( $patron[ 'user_email' ] )
			) : array(), $patron, $term );
			
			if( $option ) {
				$options[] = $option;
			}
		}
	}
	
	// Allow plugins to add their values
	$select2_options = apply_filters( 'patips_ajax_select2_users_options', $options, $args );
	
	if( ! $select2_options ) {
		patips_send_json( array( 'status' => 'failed', 'error' => 'no_results' ), 'search_select2_users' );
	}
	
	patips_send_json( array( 'status' => 'success', 'options' => $select2_options ), 'search_select2_users' );
}
add_action( 'wp_ajax_patipsSelect2Query_users', 'patips_controller_search_select2_users' );




// USERS

/**
 * Update user patron data when the user is deleted
 * @since 0.13.4
 * @version 0.25.0
 * @param int $user_id
 * @param int $reassign_user_id
 * @param WP_User $user
 */
function patips_controller_deleted_user_update_patron( $user_id, $reassign_user_id, $user ) {
	$patron        = $user_id ? patips_get_user_patron_data( $user_id ) : array();
	$user_email    = ! empty( $user->user_email ) ? sanitize_email( $user->user_email ) : '';
	
	// Update patron user_email and remove user_id
	if( $patron ) {
		$patron_data                 = patips_sanitize_patron_data( $patron );
		$patron_data[ 'id' ]         = $patron[ 'id' ];
		$patron_data[ 'user_id' ]    = -1;
		$patron_data[ 'user_email' ] = $user_email ? $user_email : 'null';
		
		$patron_data = apply_filters( 'patips_deleted_user_patron_data', $patron_data, $user, $reassign_user_id );
		
		$updated = patips_update_patron( $patron_data );
		
		if( $updated ) {
			wp_cache_delete( 'patron_' . $patron[ 'id' ], 'patrons-tips' );
			wp_cache_delete( 'patron_user_' . $user_id, 'patrons-tips' );
			wp_cache_delete( 'patron_user_' . $user_email, 'patrons-tips' );
		}
	}
}
add_action( 'delete_user', 'patips_controller_deleted_user_update_patron', 10, 3 );




/* NOTICES */

/**
 * AJAX Controller - Dismiss an admin notice
 * @since 0.25.2
 * @version 0.26.3
 */
function patips_controller_dismiss_admin_notice() {
	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'patips_nonce_dismiss_admin_notice', 'nonce', false );
	if( ! $is_nonce_valid ) { patips_send_json_invalid_nonce( 'dismiss_admin_notice' ); }
	
	$notice_id = ! empty( $_POST[ 'notice_id' ] ) ? sanitize_title_with_dashes( wp_unslash( $_POST[ 'notice_id' ] ) ) : '';
	if( ! $notice_id ) { return; }
	
	$updated  = update_option( 'patips_notice_dismissed_' . $notice_id, 1 );
	$response = $updated ? array( 'status' => 'success' ) : array( 'status' => 'failed', 'error' => 'update_option_failed' );
	
	patips_send_json( $response, 'dismiss_admin_notice' );
}
add_action( 'wp_ajax_patipsDismissAdminNotice', 'patips_controller_dismiss_admin_notice' );


/** 
 * Display a warning notice if the patronage sales page is not published
 * @since 0.25.2
 * @version 0.26.3
 */
function patips_display_notice_publish_patronage_sales_page() {
	if( ! patips_is_own_screen() ) { return; }
	
	if( ! current_user_can( 'patips_manage_patrons_tips' ) ) { return; }
	
	$dismissed = get_option( 'patips_notice_dismissed_publish_patronage_sales_page' );
	if( $dismissed ) { return; }
	
	// Check if the page is published
	$is_block     = use_block_editor_for_post_type( 'page' );
	$page_id      = (int) patips_get_option( 'patips_settings_general', 'sales_page_id' );
	$page         = $page_id ? get_post( $page_id ) : null;
	$edit_url     = $page ? get_edit_post_link( $page ) : '';
	$create_url   = admin_url( 'edit.php?post_type=page&patips_action=create-sales-page&nonce=' . wp_create_nonce( 'patips_create_sales_page' ) );
	$is_published = $page ? $page->post_status === 'publish' : false;
	$is_trashed   = $page ? $page->post_status === 'trash' : false;
	
	if( $is_published ) { return; }
	
	// Check if there are active tiers
	$tiers = patips_get_tiers_data();
	if( ! $tiers ) { return; }
	
	// Display notice
	?>
		<div id='patips-publish-patronage-sales-page-notice' class='notice notice-warning is-dismissible patips-admin-notice' data-notice-id='publish_patronage_sales_page'>
			<p><?php esc_html_e( 'The patronage sales page is not published yet. You need to publish it to allow customers to subscribe to a tier.', 'patrons-tips' ); ?></p>
			<p>
				<a href='<?php echo esc_url( $edit_url && ! $is_trashed ? $edit_url : $create_url ); ?>' class='button button-secondary'>
					<?php echo $edit_url && ! $is_trashed ? esc_html__( 'Edit page', 'patrons-tips' ) : esc_html__( 'Create page', 'patrons-tips' ); ?>
				</a>
			</p>
		</div>
	<?php
}
add_action( 'admin_notices', 'patips_display_notice_publish_patronage_sales_page' );


/** 
 * Display a warning notice if the patron area page is not published
 * @since 0.25.2
 * @version 0.26.3
 */
function patips_display_notice_publish_patron_area_page() {
	if( ! patips_is_own_screen() ) { return; }
	
	if( ! current_user_can( 'patips_manage_patrons_tips' ) ) { return; }
	
	$dismissed = get_option( 'patips_notice_dismissed_publish_patron_area_page' );
	if( $dismissed ) { return; }
	
	// Check if the page is published
	$page_id      = (int) patips_get_option( 'patips_settings_general', 'patron_area_page_id' );
	$page         = $page_id ? get_post( $page_id ) : null;
	$edit_url     = $page ? get_edit_post_link( $page ) : '';
	$create_url   = admin_url( 'edit.php?post_type=page&patips_action=create-patron-area-page&nonce=' . wp_create_nonce( 'patips_create_patron_area_page' ) );
	$is_published = $page ? $page->post_status === 'publish' : false;
	$is_trashed   = $page ? $page->post_status === 'trash' : false;
	
	if( $is_published ) { return; }
	
	// Check if there are patrons
	$patrons = patips_get_patrons_data();
	if( ! $patrons ) { return; }
	
	// Display notice
	?>
		<div id='patips-publish-patronage-sales-page-notice' class='notice notice-warning is-dismissible patips-admin-notice' data-notice-id='publish_patron_area_page'>
			<p><?php esc_html_e( 'The patron area page is not published yet. You need to publish it to allow your patrons to manage their patronage.', 'patrons-tips' ); ?></p>
			<p>
				<a href='<?php echo esc_url( $edit_url && ! $is_trashed ? $edit_url : $create_url ); ?>' class='button button-secondary'>
					<?php echo $edit_url && ! $is_trashed ? esc_html__( 'Edit page', 'patrons-tips' ) : esc_html__( 'Create page', 'patrons-tips' ); ?>
				</a>
			</p>
		</div>
	<?php
}
add_action( 'admin_notices', 'patips_display_notice_publish_patron_area_page' );


/** 
 * Display a notice to request users to rate the plugin 5 stars
 * @since 0.25.2
 */
function patips_display_notice_five_stars_rating() {
	if( ! patips_is_own_screen() ) { return; }
	
	if( ! current_user_can( 'patips_manage_patrons_tips' ) ) { return; }
	
	$dismissed = get_option( 'patips_notice_dismissed_five_stars_rating' );
	if( $dismissed ) { return; }
	
	$install_date = get_option( 'patips_install_date' );
	if( ! $install_date ) { return; }
	
	// Display the notice two months after install
	$install_datetime = DateTime::createFromFormat( 'Y-m-d H:i:s', $install_date );
	$current_datetime = new DateTime();
	$nb_days          = floor( $install_datetime->diff( $current_datetime )->days );
	if( $nb_days < 61 ) { return; }
	
	?>
		<div id='patips-five-stars-rating-notice' class='notice notice-info is-dismissible patips-admin-notice' data-notice-id='five_stars_rating'>
			<p>
				<?php 
					/* translators: %s: Plugin name */
					echo sprintf( esc_html__( '%s has been helping you for two months now.', 'patrons-tips' ), '<strong>Patrons Tips</strong>' );
				?>
				<br/>
				<?php
					/* translators: %s: five stars */
					echo sprintf( esc_html__( 'Would you help us back leaving a %s rating? We need you too.', 'patrons-tips' ), '<a href="https://wordpress.org/support/plugin/patrons-tips/reviews?rate=5#new-post" target="_blank" >&#9733;&#9733;&#9733;&#9733;&#9733;</a>' );
				?>
			</p>
			<p>
				<a class='button button-secondary' href='<?php echo esc_url( 'https://wordpress.org/support/plugin/patrons-tips/reviews?rate=5#new-post' ); ?>' target='_blank'><?php esc_html_e( 'Ok, I\'ll rate you five stars!', 'patrons-tips' ); ?></a>
			</p>
		</div>
	<?php
}
add_action( 'admin_notices', 'patips_display_notice_five_stars_rating' );


/**
 * Display a message in the admin footer in Patrons Tips screens only
 * @since 0.25.6
 * @param string $footer_text
 * @return string
 */
function patips_display_admin_footer_text( $footer_text ) {
	if( ! current_user_can( 'patips_manage_patrons_tips' ) || ! function_exists( 'patips_is_own_screen' ) ) {
		return $footer_text;
	}
	
	if( ! patips_is_own_screen() ) { return $footer_text; }
	
	$footer_text = '<span id="footer-thankyou">' . sprintf( 
		/* translators: %1$s = "Patrons Tips", %2$s: five stars */
		esc_html__( 'Does %1$s help you? Help us back leaving a %2$s rating. We need you too.', 'patrons-tips' ), 
		'<strong>Patrons Tips</strong>',
		'<a href="https://wordpress.org/support/plugin/patrons-tips/reviews?rate=5#new-post" target="_blank" >&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
	) . '</span>';
	
	return $footer_text;
}
add_filter( 'admin_footer_text', 'patips_display_admin_footer_text', 10, 1 );


/**
 * Display an admin error notice if an add-on is outdated and will cause malfunction
 * @since 0.25.2
 * @version 1.0.2
 */
function patips_display_notice_add_ons_compatibility_error() {
	$add_ons = patips_get_active_add_ons();
	
	// Add Patrons Tips, and allow plugins to change its min required version
	$add_ons[ 'patips' ] = array( 
		'title'         => 'Patrons Tips', 
		'version_const' => 'PATIPS_PLUGIN_VERSION', 
		'min_version'   => apply_filters( 'patips_min_version', PATIPS_PLUGIN_VERSION )
	);
	
	$outdated_add_ons = array();
	foreach( $add_ons as $prefix => $add_on ) {
		$constant_name = $add_on[ 'version_const' ];
		if( ! defined( $constant_name ) ) { continue; }
		if( version_compare( constant( $constant_name ), $add_on[ 'min_version' ], '<' ) ) {
			$outdated_add_ons[ $prefix ] = $add_on;
		}
	}
	if( ! $outdated_add_ons ) { return; }
?>
	<div class='notice notice-error patips-add-ons-compatibility-notice'>
		<p>
			<?php
				/* translators: %s = Plugin name (Patrons Tips) */
				echo sprintf( esc_html__( '%s is experiencing major compatibility issues.', 'patrons-tips' ), '<strong>Patrons Tips</strong>' )
				. ' ' . esc_html__( 'You need to update the following plugins now.', 'patrons-tips' );
			?>
		</p>
		<ul>
			<?php 
				foreach( $outdated_add_ons as $prefix => $outdated_add_on ) {
					$add_on_version = constant( $outdated_add_on[ 'version_const' ] );
					?>
					<li><strong><?php echo esc_html( $outdated_add_on[ 'title' ] ); ?></strong> <em><?php echo esc_html( $add_on_version ); ?></em> &#8594; 
					<?php 
					/* translators: %s = a version number (e.g.: 1.2.6) */
					echo sprintf( esc_html__( 'version %s or later required.', 'patrons-tips' ), '<strong>' . esc_html( $outdated_add_on[ 'min_version' ] ) . '</strong>' );
				}
			?>
		</ul>
		<?php
			unset( $outdated_add_ons[ 'patips' ] );
			
			if( $outdated_add_ons ) {
				?>
				<p>
					<em>
					<?php
						echo sprintf( 
							/* translators: %s = Link to the "documentation". */
							esc_html__( 'If the add-ons update doesn\'t work, follow the instructions here: %s.', 'patrons-tips' ), 
							'<strong>'
								. '<a href="https://patronstips.com/" target="_blank">' 
									. esc_html__( 'documentation', 'patrons-tips' ) 
								. '</a>'
							. '</strong>'
						);
					?>
					</em>
				</p>
				<?php
			}
		?>
	</div>
<?php
}
add_action( 'admin_notices', 'patips_display_notice_add_ons_compatibility_error' );