<?php
/**
 * Patron editor page
 * @since 0.6.0
 * @version 0.26.3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

$action    = ! empty( $_GET[ 'action' ] ) && in_array( $_GET[ 'action' ], array( 'edit', 'new' ), true ) ? sanitize_title_with_dashes( wp_unslash( $_GET[ 'action' ] ) ) : '';
$patron_id = ! empty( $_GET[ 'patron_id' ] ) ? intval( $_GET[ 'patron_id' ] ) : 0;

if( ! $action ) { return; }
if( $action !== 'new' && ! $patron_id ) { return; }

// Exit if not allowed to edit current patron
$can_edit_patron = $action === 'new' ? current_user_can( 'patips_create_patrons' ) : current_user_can( 'patips_edit_patrons' );
if( ! $can_edit_patron ) { esc_html_e( 'You are not allowed to do that.', 'patrons-tips' ); return; }

// Get patron raw data
$patron = patips_get_patron_data( $patron_id, array(), true ); 
if( $action !== 'new' && ! $patron ) { return; }

// Get edit data in the default language
$lang_switched = patips_switch_locale( patips_get_site_default_locale() );

// Format patron data
$patron_raw_data = $patron;
$patron = patips_format_patron_data( $patron, 'edit' );
$patron = apply_filters( 'patips_patron_data', $patron, $patron_raw_data, $patron_id, array(), false );

if( $lang_switched ) { patips_restore_locale(); }

?>
<div class='wrap'>
	<h1><?php echo ! $patron_id ? esc_html__( 'Create Patron', 'patrons-tips' ) : /* translators: %s is the patron ID */ sprintf( esc_html__( 'Edit Patron #%s', 'patrons-tips' ), (int) $patron_id ); ?></h1>
	<hr class='wp-header-end'/>
	<div id='patips-patron-editor-page-container'>
		<?php
			do_action( 'patips_patron_editor_page_before', $patron );
			$redirect_url = $patron_id ? 'admin.php?page=patips_patrons&action=edit&patron_id=' . $patron_id : 'admin.php?page=patips_patrons';
		?>
		<form name='post' action='<?php echo esc_url( $redirect_url ); ?>' method='post' id='patips-patron-editor-page-form' novalidate>
			<?php
			/* Used to save closed meta boxes and their order */
			wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
			wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
			?>
			<input type='hidden' name='page' value='patips_patrons'/>
			<input type='hidden' name='action' value='<?php echo $patron_id ? 'edit' : 'create'; ?>'/>
			<input type='hidden' name='nonce' value='<?php echo esc_attr( wp_create_nonce( 'patips_update_patron' ) ); ?>'/>
			<input type='hidden' name='patron_id' value='<?php echo (int) $patron_id; ?>' id='patips-patron-id'/>
			
			<div id='patips-patron-editor-page-lang-switcher' class='patips-lang-switcher'></div>
			
			<div id='poststuff'>
				<div id='post-body' class='metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>'>
					<div id='postbox-container-1' class='postbox-container'>
					<?php
						do_meta_boxes( null, 'side', $patron );
					?>
					</div>
					<div id='postbox-container-2' class='postbox-container'>
					<?php
						do_meta_boxes( null, 'normal', $patron );
						do_meta_boxes( null, 'advanced', $patron );
					?>
					</div>
				</div>
				<br class='clear' />
			</div>
		</form>
		<?php
			do_action( 'patips_patron_editor_page_after', $patron );
		?>
	</div>
</div>
<?php


// PATRON EDITOR METABOXES

/**
 * Display Patron info metabox
 * @since 0.6.0
 * @version 0.25.6
 * @param array $patron
 */
function patips_display_patron_info_meta_box( $patron ) {
	$fields = apply_filters( 'patips_patron_settings_fields_general', array(
		'user_id' => array( 
			'name'    => 'user_id',
			'type'    => 'user_id',
			'id'      => 'patips-patron-user-id',
			'options' => array( 'option_label' => array( 'first_name', ' ', 'last_name', ' (', 'user_login', ' / ', 'user_email', ')' ) ),
			'value'   => ! empty( $patron[ 'user_id' ] ) ? intval( $patron[ 'user_id' ] ) : '',
			'title'   => esc_html__( 'User', 'patrons-tips' ),
			'tip'     => esc_html__( 'Link a user account to that patron.', 'patrons-tips' )
		),
		'user_email' => array( 
			'name'  => 'user_email',
			'type'  => 'email',
			'id'    => 'patips-patron-user-email',
			'value' => ! empty( $patron[ 'user_email' ] ) ? sanitize_email( $patron[ 'user_email' ] ) : '',
			'title' => esc_html__( 'Email', 'patrons-tips' ),
			'tip'   => esc_html__( 'If the patron is not linked to any account, enter their email address here.', 'patrons-tips' )
		),
		'active'     => array(
			'name'  => 'active',
			'type'  => 'checkbox',
			'value' => isset( $patron[ 'active' ] ) ? intval( $patron[ 'active' ] ) : 1,
			'title' => esc_html__( 'Active', 'patrons-tips' ),
			'tip'   => esc_html__( 'If the patron is deactivated, they will no longer be able to access restricted content.', 'patrons-tips' )
		)
	), $patron );
	
	// Promote Patrons Tips Pro
	if( ! patips_is_plugin_active( 'patrons-tips-pro/patrons-tips-pro.php' ) ) {
		$prepend_fields = array(
			'nickname' => array( 
				'name'  => 'nickname',
				'type'  => 'text',
				'value' => patips_generate_patron_nickname( $patron ),
				'title' => esc_html__( 'Nickname', 'patrons-tips' ),
				'tip'   => esc_html__( 'Your patrons can set their nickname from their account, it is displayed in the thank you lists. You can change it here if necessary.', 'patrons-tips' ),
				'attr'  => 'disabled="disabled"',
				'after' => '<span class="patips-pro-ad-icon"></span>'
			)
		);
		$fields = $prepend_fields + $fields;
	}
	
	do_action( 'patips_patron_settings_fields_general_before', $patron );
	?>
	<div id='patips-patron-settings-fields-general' class='patips-settings-fields-container'>
		<?php patips_display_fields( $fields ); ?>
	</div>
	<?php
	do_action( 'patips_patron_settings_fields_general_after', $patron );
}


/**
 * Display Patron history metabox
 * @since 0.6.0
 * @version 0.26.0
 * @param array $patron
 */
function patips_display_patron_history_meta_box( $patron ) {
	do_action( 'patips_patron_settings_fields_history_before', $patron );
	
	// Display the patron history settings table
	echo patips_get_patron_settings_history_table( $patron ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	
	do_action( 'patips_patron_settings_fields_history_after', $patron );
	
	// Promote Patrons Tips Pro
	if( ! patips_is_plugin_active( 'patrons-tips-pro/patrons-tips-pro.php' ) ) {
		// Get current period
		$timezone    = patips_get_wp_timezone();
		$start_dt    = new DateTime( 'now', $timezone );
		$end_dt      = patips_compute_period_end( $start_dt );
		$period      = patips_get_period_by_date( $start_dt->format( 'Y-m-d H:i:s' ) );
		$period_name = patips_get_period_name( $period );
		
		// Get tier options
		$tiers = patips_get_tiers_data();
		if( ! $tiers ) {
			$tiers = array(
				/* translators: %s is the tier ID */
				1 => array( 'id' => 1, 'title' => sprintf( esc_html__( 'Tier #%s', 'patrons-tips' ), 1 ) ),
				/* translators: %s is the tier ID */
				2 => array( 'id' => 2, 'title' => sprintf( esc_html__( 'Tier #%s', 'patrons-tips' ), 2 ) ),
				/* translators: %s is the tier ID */
				3 => array( 'id' => 3, 'title' => sprintf( esc_html__( 'Tier #%s', 'patrons-tips' ), 3) )
			);
		}
		
		$tier_options = array( '' => esc_html__( 'Select a tier', 'patrons-tips' ) );
		foreach( $tiers as $tier_id => $tier ) {
			/* translators: %s is the tier ID */
			$title = $tier[ 'title' ] ? $tier[ 'title' ] : ( $tier_id ? sprintf( esc_html__( 'Tier #%s', 'patrons-tips' ), $tier_id ) : '' );
			$tier_options[ $tier[ 'id' ] ] = $title;
		}
	?>
		<hr/>
		
		<div id='patips-pro-ad-manual-patron-history' class='patips-pro-ad'>
			<h4>
				<?php esc_html_e( 'Manually add, edit and delete entries from patron history', 'patrons-tips' ); ?>
				<span class='patips-pro-ad-icon'></span>
			</h4>
			
			<p>
				<?php echo sprintf(
					/* translators: %s = link to 'Patrons Tips - Pro' sales page */
					esc_html__( 'With %s, you can easily assign a tier to a patron for the desired month, change it, or revoke it.', 'patrons-tips' ),
					patips_get_pro_sales_link() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				); ?>
			</p>
			
			<table class='patips-settings-table patips-responsive-table'>
				<thead>
					<tr>
						<th class='patips-patron-history-column-title' data-column='start'>
							<span class='patips-patron-history-title'><?php esc_html_e( 'Start', 'patrons-tips' ); ?></span>
							<?php patips_tooltip( esc_html__( 'Choose the start date of the patronage.', 'patrons-tips' ) ); ?>
						</th>
						<th class='patips-patron-history-column-title' data-column='duration'>
							<span class='patips-patron-history-title'><?php esc_html_e( 'End', 'patrons-tips' ); ?></span>
							<?php patips_tooltip( '<em>' . esc_html__( 'It is automatically computed based on the period start date.', 'patrons-tips' ) . '</em>' ); ?>
						</th>
						<th class='patips-patron-history-column-title' data-column='period'>
							<span class='patips-patron-history-title'><?php esc_html_e( 'Period', 'patrons-tips' ); ?></span>
							<?php patips_tooltip( '<em>' . esc_html__( 'It is automatically computed based on the period start date.', 'patrons-tips' ) . '</em>' ); ?>
						</th>
						<th class='patips-patron-history-column-title' data-column='tier'>
							<span class='patips-patron-history-title'><?php esc_html_e( 'Tier', 'patrons-tips' ); ?></span>
							<?php patips_tooltip( esc_html__( 'Select the patronage tier unlocked for this period.', 'patrons-tips' ) ); ?>
						</th>
						<th class='patips-patron-history-column-title' data-column='active'>
							<span class='patips-patron-history-title'><?php esc_html_e( 'Active', 'patrons-tips' ); ?></span>
							<?php patips_tooltip( esc_html__( 'Deactivate a patronage period to ignore it.', 'patrons-tips' ) ); ?>
						</th>
						<th class='patips-patron-history-column-title' data-column='origin'>
							<span class='patips-patron-history-title'><?php esc_html_e( 'Origin', 'patrons-tips' ); ?></span>
						</th>
						<th class='patips-patron-history-column-title' data-column='actions'>
							<span class='patips-patron-history-title'></span>
						</th>
					</tr>
				</thead>
				<tbody>
					<tr data-entry='' data-active='1'>
						<td class='patips-patron-history-column-value ' data-column='start' data-column-label='<?php esc_html_e( 'Start', 'patrons-tips' ); ?>'>
							<div class='patips-history-start-container'>
								<?php
									patips_display_field( array(
										'name'  => 'date_start',
										'type'  => 'date',
										'value' => $start_dt->format( 'Y-m-d' ),
										'class' => 'patips-settings-table-value patips-history-date_start'
									) );
								?>
							</div>
						</td>
						<td class='patips-patron-history-column-value ' data-column='duration' data-column-label='<?php esc_html_e( 'End', 'patrons-tips' ); ?>'>
							<div class='patips-history-duration-container'>
								<span class='patips-history-date_end'>
								<?php 
									echo esc_html( patips_format_datetime( $end_dt->format( 'Y-m-d H:i:s' ) ) );
								?>
								</span>
							</div>
						</td>
						<td class='patips-patron-history-column-value ' data-column='period' data-column-label='<?php esc_html_e( 'Period', 'patrons-tips' ); ?>'>
							<div class='patips-history-period-container'>
								<span class='patips-history-period'>
								<?php 
									echo esc_html( $period_name );
								?>
								</span>
							</div>
						</td>
						<td class='patips-patron-history-column-value ' data-column='tier' data-column-label='<?php esc_html_e( 'Tier', 'patrons-tips' ); ?>'>
							<div class='patips-history-tier-container'>
							<?php
								patips_display_field( array(
									'name'    => 'tier_id',
									'type'    => 'select',
									'options' => $tier_options,
									'class'   => 'patips-settings-table-value patips-history-tier_id'
								) );
							?>
							</div>
						</td>
						<td class='patips-patron-history-column-value ' data-column='active' data-column-label='<?php esc_html_e( 'Active', 'patrons-tips' ); ?>'>
							<div class='patips-history-active-container'>
								<?php
									patips_display_field( array(
										'name'  => 'active',
										'type'  => 'checkbox',
										'class' => 'patips-history-active',
										'value' => 1
									) );
								?>
							</div>
						</td>
						<td class='patips-patron-history-column-value ' data-column='origin' data-column-label='<?php esc_html_e( 'Origin', 'patrons-tips' ); ?>'>
							<div class='patips-history-origin-container'>
								<span class='patips-history-origin_label'><?php esc_html_e( 'Manual', 'patrons-tips' ); ?></span>
							</div>
						</td>
						<td class='patips-patron-history-column-value ' data-column='actions' data-column-label=''>
							<div class='patips-history-actions-container'>
								<button type='button' id='patips-pro-ad-delete-manual-entry' class='patips-pro-ad-button button button-secondary patips-delete-button patips-tooltip' data-message='<?php echo esc_attr( esc_html__( 'You can delete the lines you added.', 'patrons-tips' ) ); ?>'>
									<?php esc_html_e( 'Delete', 'patrons-tips' ); ?>
								</button>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
			
			<button type='button' id='patips-pro-ad-add-new-entry' class='patips-pro-ad-button button button-secondary patips-tooltip' data-message='<?php echo esc_attr( esc_html__( 'Add as many lines as you want.', 'patrons-tips' ) ); ?>'>
				<?php esc_html_e( 'Add New Entry', 'patrons-tips' ); ?>
			</button>
		</div>
	<?php
	}
}


/**
 * Display 'loyalty' metabox content for patron
 * @since 0.25.0
 * @version 1.0.2
 * @param array $patron
 */
function patips_display_patron_loyalty_meta_box( $patron ) {
	do_action( 'patips_patron_settings_fields_loyalty_before', $patron );
	
	// Promote Patrons Tips Pro
	if( ! patips_is_plugin_active( 'patrons-tips-pro/patrons-tips-pro.php' ) ) {
	?>
		<div id='patips-pro-ad-patron-loyalty' class='patips-pro-ad'>
			<h4>
				<?php esc_html_e( 'Reward your patrons loyalty', 'patrons-tips' ); ?>
				<span class='patips-pro-ad-icon'></span>
			</h4>
			
			<p>
				<?php echo wp_kses_post( sprintf(
					/* translators: %s = link to 'Patrons Tips - Pro' sales page */
					__( '%s automatically generates <strong>a badge</strong> every twelve months of contribution.', 'patrons-tips' ),
					patips_get_pro_sales_link()
				) ); ?>
			</p>
			<div>
				<img src='<?php echo esc_url( PATIPS_PLUGIN_URL . 'img/demo-badges.png' ); ?>' title='<?php esc_html_e( 'Loyalty badges', 'patrons-tips' ); ?>' class='patips-demo-loyalty-badges-img'/>
			</div>
			<p>
				<em><?php esc_html_e( 'This badge is proudly displayed next to the patron\'s name in thank you lists.', 'patrons-tips' ); ?></em>
			</p>
			<hr/>
			<p>
				<?php echo wp_kses_post( __( 'Moreover, if you want to send your patron another reward, a <strong>tracking system</strong> lets you know when to send what to whom.', 'patrons-tips' ) ); ?>
			</p>
		</div>
	<?php
	}
	
	do_action( 'patips_patron_settings_fields_loyalty_after', $patron );
}


/**
 * Display 'publish' metabox content for patron
 * @since 0.6.0
 * @version 0.25.5
 * @param array $patron
 */
function patips_display_patron_publish_meta_box( $patron ) {
	$is_new = isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] === 'new';
?>
	<div class='submitbox' id='submitpost'>
		<div id='major-publishing-actions' >
			<div id='delete-action'>
			<?php
				if( ! $is_new && current_user_can( 'patips_delete_patrons' ) ) {
					echo '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?page=patips_patrons&action=trash&patron_id=' . $patron[ 'id' ] ), 'trash-patron_' . $patron[ 'id' ] ) ) . '" class="submitdelete deletion" >'
							. esc_html_x( 'Trash', 'verb', 'patrons-tips' )
						. '</a>';
				}
			?>
			</div>

			<div id='publishing-action'>
				<span class='spinner'></span>
				<input id='patips-save-patron-button' 
					   name='save' 
					   type='submit' 
					   class='button button-primary button-large' 
					   id='publish' 
					   value='<?php echo $is_new ? esc_attr__( 'Publish', 'patrons-tips' ) : esc_attr__( 'Update', 'patrons-tips' ); ?>' 
				/>
			</div>
			<div class='clear'></div>
		</div>
	</div>
<?php
}