<?php
/**
 * Patron list page
 * @since 0.6.0
 * @version 1.0.4
 * @global WP_Locale $wp_locale
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

global $wp_locale;
$tiers = patips_get_tiers_data();
?>
<div class='wrap'>
	<h1 class='wp-heading-inline'>
		<?php echo esc_html__( 'Patrons', 'patrons-tips' ); ?>
	</h1>
	
	<?php
	if( $tiers ) {
		// Promote Patrons Tips Pro
		if( ! patips_is_plugin_active( 'patrons-tips-pro/patrons-tips-pro.php' ) ) {
			$tooltip = sprintf(
				/* translators: %s = link to "Patrons Tips - Pro" sales page */
				__( 'Manually add a patron thanks to %s.', 'patrons-tips' ),
				patips_get_pro_sales_link()
			);
			?>
				<button type='button' id='patips-pro-ad-add-new-patron' class='patips-pro-ad-button page-title-action patips-tooltip' data-message='<?php echo esc_attr( $tooltip ); ?>' disabled='disabled'>
					<?php esc_html_e( 'Add New Patron', 'patrons-tips' ); ?>
					<span class='patips-pro-ad-icon'></span>
				</button>
			<?php
		}

		do_action( 'patips_patron_list_table_page_heading' );
	}
	?>

	<hr class='wp-header-end'/>
	
	<?php
	// Check if there are tiers
	if( ! $tiers ) {
		patips_display_first_tier_notice();
		?></div><!-- end of wp wrap --><?php
		exit;
	}
	?>
	
	<div id='patips-patron-list-table-page-container'>
		<?php do_action( 'patips_patron_list_table_page_before' ); ?>
		
		<div id='patips-patron-list-table-filters-container'>
			<form id='patips-patron-list-table-filters-form' action=''>
				<input type='hidden' name='page' value='patips_patrons'/>
				<input type='hidden' name='nonce' value='<?php echo esc_attr( wp_create_nonce( 'patips_get_patron_list_table' ) ); ?>'/>
				<?php
					// Status
					$status = ! empty( $_REQUEST[ 'status' ] ) ? sanitize_title_with_dashes( wp_unslash( $_REQUEST[ 'status' ] ) ) : '';
					if( $status ) {
						echo '<input type="hidden" name="status" value="' . esc_attr( $status ) . '"/>';
					}
					
					// Active
					$active = ! empty( $_REQUEST[ 'active' ] ) ? intval( $_REQUEST[ 'active' ] ) : 1;
					echo '<input type="hidden" name="active" value="' . esc_attr( $active ) . '"/>';
					
					// Display sorting data
					$order_by_key = ! empty( $_REQUEST[ 'order_by' ] ) ? 'order_by' : 'orderby';
					$order_by     = ! empty( $_REQUEST[ $order_by_key ] ) ? patips_str_ids_to_array( wp_unslash( $_REQUEST[ $order_by_key ] ) ) : array();
					if( $order_by ) {
						$i=0;
						foreach( $order_by as $column_name ) {
							if( $i === 0 ) {
								echo '<input type="hidden" name="orderby" value="' . esc_attr( $column_name ) . '" />';
							}
							echo '<input type="hidden" name="order_by[' . (int) $i . ']" value="' . esc_attr( $column_name ) . '" />';
							++$i;
						}
					}
					$order = ! empty( $_REQUEST[ 'order' ] ) ? sanitize_title_with_dashes( wp_unslash( $_REQUEST[ 'order' ] ) ) : '';
					if( $order ) {
						echo '<input type="hidden" name="order" value="' . esc_attr( $order ) . '" />';
					}

					do_action( 'patips_patron_filters_before' );
				?>
				<div id='patips-patron-filter-container-title' class='patips-filter-container'>
					<div class='patips-filter-title'>
						<?php esc_html_e( 'User', 'patrons-tips' ); ?>
					</div>
					<div class='patips-filter-content'>
					<?php
						$args = array( 
							'type'    => 'user_id',
							'name'    => 'user',
							'id'      => 'patips-patron-filter-user',
							'options' => array(
								'show_option_all' => esc_html__( 'All', 'patrons-tips' ),
								'option_label'    => array( 'display_name', ' (', 'user_login', ' / ', 'user_email', ')' ),
								'no_account'      => 1,
								'allow_clear'     => 1,
								'allow_tags'      => 1
							),
							'value'   => ! empty( $_REQUEST[ 'user' ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ 'user' ] ) ) : ''
						);
						patips_display_field( $args );
					?>
					</div>
				</div>
				<div id='patips-patron-filter-container-tier' class='patips-filter-container'>
					<div class='patips-filter-title'>
						<?php esc_html_e( 'Tiers', 'patrons-tips' ); ?>
					</div>
					<div class='patips-filter-content'>
					<?php
						// Get tier options
						$tier_options = array();
						foreach( $tiers as $tier_id => $tier ) {
							/* translators: %s is the tier ID */
							$title = $tier[ 'title' ] ? $tier[ 'title' ] : ( $tier_id ? sprintf( esc_html__( 'Tier #%s', 'patrons-tips' ), $tier_id ) : '' );
							$tier_options[ $tier[ 'id' ] ] = $title;
						}
					
						$args = array( 
							'type'     => 'select',
							'name'     => 'tier_ids',
							'multiple' => 1,
							'id'       => 'patips-patron-filter-tiers',
							'class'    => 'patips-select2-no-ajax',
							'options'  => $tier_options,
							'value'    => ! empty( $_REQUEST[ 'tier_ids' ] ) ? patips_ids_to_array( wp_unslash( $_REQUEST[ 'tier_ids' ] ) ) : array()
						);
						patips_display_field( $args );
					?>
					</div>
				</div>
				<div id='patips-patron-filter-container-current' class='patips-filter-container'>
					<div class='patips-filter-title'>
						<?php esc_html_e( 'Currently', 'patrons-tips' ); ?>
					</div>
					<div class='patips-filter-content'>
					<?php
						$args = array( 
							'type'    => 'select',
							'name'    => 'current',
							'id'      => 'patips-patron-filter-current',
							'options' => array(
								''  => '',
								'1' => esc_html__( 'Currently patron', 'patrons-tips' )
							),
							'value'   => isset( $_REQUEST[ 'current' ] ) && is_numeric( $_REQUEST[ 'current' ] ) ? intval( $_REQUEST[ 'current' ] ) : ''
						);
						patips_display_field( $args );
					?>
					</div>
				</div>
				<div id='patips-patron-filter-container-periods' class='patips-filter-container patips-filter-dates-container'>
					<div class='patips-filter-title'>
						<?php esc_html_e( 'Periods', 'patrons-tips' ); ?>
					</div>
					<div class='patips-filter-content'>
						<?php
							$from = isset( $_REQUEST[ 'period_from' ] ) ? patips_sanitize_date( wp_unslash( $_REQUEST[ 'period_from' ] ) ) : '';
							$to   = isset( $_REQUEST[ 'period_to' ] ) ? patips_sanitize_date( wp_unslash( $_REQUEST[ 'period_to' ] ) ) : '';
							if( ! $from ) { $from = isset( $_REQUEST[ 'period_from' ] ) ? substr( patips_sanitize_datetime( wp_unslash( $_REQUEST[ 'period_from' ] ) ), 0, 10 ) : ''; }
							if( ! $to )   { $to   = isset( $_REQUEST[ 'period_to' ] ) ? substr( patips_sanitize_datetime( wp_unslash( $_REQUEST[ 'period_to' ] ) ), 0, 10 ) : ''; }
							$from_year  = $from ? substr( $from, 0, 4 ) : '';
							$from_month = $from ? substr( $from, 5, 2 ) : '';
							$to_year    = $to ? substr( $to, 0, 4 ) : '';
							$to_month   = $to ? substr( $to, 5, 2 ) : '';
							
							// Generate Years options from the launch date
							$launch_date = patips_get_option( 'patips_settings_general', 'patronage_launch_date' );
							$first_year  = $launch_date ? intval( substr( $launch_date, 0, 4 ) ) : 0;
							
							// Get the first patron history entry to determine the first year
							if( ! $first_year ) {
								$patrons_entries = patips_get_patrons_history( 
									patips_format_patron_history_filters( 
										array(
											'order_by' => array( 'date_start' ),
											'order'    => 'asc',
											'per_page' => 1,
										) 
									) 
								);
								$patron_entries  = $patrons_entries ? reset( $patrons_entries ) : array();
								$first_entry     = $patron_entries ? reset( $patron_entries ) : array();
								$first_year      = ! empty( $first_entry[ 'date_start' ] ) ? substr( $first_entry[ 'date_start' ], 0, 4 ) : gmdate( 'Y' );
							}
							
							// Create one option per year
							$years         = range( gmdate( 'Y' ), $first_year );
							$years_options = array( '' => '' ) + array_combine( $years, $years );
							$years_class   = count( $years_options ) > 10 ? 'patips-select2-no-ajax' : '';
							
							$period_duration_field = array(
								'type'    => 'hidden',
								'name'    => 'period_duration',
								'id'      => 'patips-patron-filter-period-duration',
								'value'   => isset( $_REQUEST[ 'period_duration' ] ) && is_numeric( $_REQUEST[ 'period_duration' ] ) ? intval( $_REQUEST[ 'period_duration' ] ) : ''
							);
							patips_display_field( $period_duration_field );
							
							$period_from_field = array(
								'type'    => 'hidden',
								'name'    => 'period_from',
								'id'      => 'patips-patron-filter-period-from',
								'value'   => $from ? $from : ''
							);
							patips_display_field( $period_from_field );
							
							$period_to_field = array(
								'type'    => 'hidden',
								'name'    => 'period_to',
								'id'      => 'patips-patron-filter-period-to',
								'value'   => $to ? $to : ''
							);
							patips_display_field( $period_to_field );
						?>
						<div class='patips-filter-dates-from-container'>
							<label for='patips-patron-filter-period-from-month'>
								<?php echo esc_html_x( 'From', 'date', 'patrons-tips' ); ?>
							</label>
							<?php
								$period_from_month_field = array(
									'type'    => 'select',
									'name'    => 'period_from_month',
									'id'      => 'patips-patron-filter-period-from-month',
									'class'   => 'patips-patron-filter-month',
									'options' => $wp_locale ? array( '' => '' ) + $wp_locale->month : array(),
									'value'   => $from_month
								);
								patips_display_field( $period_from_month_field );
								
								$period_from_year_field = array(
									'type'    => 'select',
									'name'    => 'period_from_year',
									'id'      => 'patips-patron-filter-period-from-year',
									'class'   => 'patips-patron-filter-year ' . $years_class,
									'options' => $years_options,
									'value'   => $from_year
								);
								patips_display_field( $period_from_year_field );
							?>
						</div>
						<div class='patips-filter-dates-to-container'>
							<label for='patips-patron-filter-period-to-month'>
								<?php echo esc_html_x( 'To', 'date', 'patrons-tips' ); ?>
							</label>
							<?php
								$period_to_month_field = array(
									'type'    => 'select',
									'name'    => 'period_to_month',
									'id'      => 'patips-patron-filter-period-to-month',
									'class'   => 'patips-patron-filter-month',
									'options' => $wp_locale ? array( '' => '' ) + $wp_locale->month : array(),
									'value'   => $to_month
								);
								patips_display_field( $period_to_month_field );
								
								$period_to_year_field = array(
									'type'    => 'select',
									'name'    => 'period_to_year',
									'id'      => 'patips-patron-filter-period-to-year',
									'class'   => 'patips-patron-filter-year ' . $years_class,
									'options' => $years_options,
									'value'   => $to_year
								);
								patips_display_field( $period_to_year_field );
							?>
						</div>
					</div>
				</div>
				<?php
					do_action( 'patips_patron_filters_after' );
				?>
				<div id='patips-filter-container-actions' class='patips-filter-container'>
					<div class='patips-filter-title'>
						<?php esc_html_e( 'Actions', 'patrons-tips' ); ?>
					</div>
					<div class='patips-filter-content'>
						<input type='submit' class='button button-primary button-large' id='patips-submit-filter-button' value='<?php esc_html_e( 'Filter the list', 'patrons-tips' ); ?>' title='<?php esc_html_e( 'Filter the list', 'patrons-tips' ); ?>'/>
						<input type='button' class='button button-primary button-large' id='patips-patron-export-button' value='<?php esc_html_e( 'Export patrons', 'patrons-tips' ); ?>' title='<?php esc_html_e( 'Export patrons', 'patrons-tips' ); ?>'/>
					</div>
				</div>
			</form>
		</div>

		<div id='patips-patron-list-table-container'>
			<?php do_action( 'patips_patron_list_table_before' ); ?>
			
			<div id='patips-patron-list-table'>
			<?php
				$patron_list_table = new PATIPS_Patrons_List_Table();
				$patron_list_table->prepare_items();
				$patron_list_table->views();
				$patron_list_table->display();
			?>
			</div>
			
			<?php do_action( 'patips_patron_list_table_after' ); ?>
		</div>
		
		<div id='patips-patron-area-page-tuto-container'>
			<?php
				// Check if the page is published
				$page_id      = (int) patips_get_option( 'patips_settings_general', 'patron_area_page_id' );
				$page         = $page_id ? get_post( $page_id ) : null;
				$edit_url     = $page ? get_edit_post_link( $page ) : '';
				$create_url   = admin_url( 'edit.php?post_type=page&patips_action=create-patron-area-page&nonce=' . wp_create_nonce( 'patips_create_patron_area_page' ) );
				$is_published = $page ? $page->post_status === 'publish' : false;
				$is_trashed   = $page ? $page->post_status === 'trash' : false;
			?>
			<div class='patips-info'>
				<span><?php esc_html_e( 'Patrons can manage their patronage from the patron area page.', 'patrons-tips' ); ?></span>
				<a href='<?php echo esc_url( $edit_url && ! $is_trashed ? $edit_url : $create_url ); ?>' class='button button-secondary'>
					<?php echo $edit_url && ! $is_trashed ? esc_html__( 'Edit page', 'patrons-tips' ) : esc_html__( 'Create page', 'patrons-tips' ); ?>
				</a>
			</div>
			<?php if( ! $is_published && ! empty( $patron_list_table->items ) ) { ?>
			<div class='patips-warning'>
				<span><?php esc_html_e( 'The patron area page is not published yet. You need to publish it to allow your patrons to manage their patronage.', 'patrons-tips' ); ?></span>
				<a href='<?php echo esc_url( $edit_url && ! $is_trashed ? $edit_url : $create_url ); ?>' class='button button-secondary'>
					<?php echo $edit_url && ! $is_trashed ? esc_html__( 'Edit page', 'patrons-tips' ) : esc_html__( 'Create page', 'patrons-tips' ); ?>
				</a>
			</div>
			<?php } ?>
		</div>
		
		<?php
			do_action( 'patips_patron_list_table_page_after' );
			
			include_once( 'dialogs/dialog-patron-export.php' );
		?>
	</div>
</div>