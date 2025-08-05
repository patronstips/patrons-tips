<?php
/**
 * Tier list page
 * @since 0.5.0
 * @version 0.26.3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

$tiers = patips_get_tiers_data();
?>
<div class='wrap'>
	<h1 class='wp-heading-inline' >
		<?php echo esc_html__( 'Tiers', 'patrons-tips' ); ?>
	</h1>
	
	<?php
	if( $tiers ) { 
		if( current_user_can( 'patips_create_tiers' ) ) { 
			?>
			<a href='<?php echo esc_url( admin_url( 'admin.php?page=patips_tiers&action=new' ) ); ?>' class='page-title-action'>
				<?php esc_html_e( 'Add New Tier', 'patrons-tips' ); ?>
			</a>
			<?php
		}
		
		do_action( 'patips_tier_list_table_page_heading' );
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
	
	<div id='patips-tier-list-table-page-container'>
		<?php do_action( 'patips_tier_list_table_page_before' ); ?>
		
		<div id='patips-tier-filters-container'>
			<form id='patips-tier-filters-form' action=''>
				<input type='hidden' name='page' value='patips_tiers'/>
				<input type='hidden' name='nonce' value='<?php echo esc_attr( wp_create_nonce( 'patips_get_tier_list' ) ); ?>'/>
				<?php
					// Status
					$status = ! empty( $_REQUEST[ 'status' ] ) ? sanitize_title_with_dashes( wp_unslash( $_REQUEST[ 'status' ] ) ) : '';
					if( $status ) {
						echo '<input type="hidden" name="status" value="' . esc_attr( $status ) . '"/>';
					}

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

					do_action( 'patips_tier_filters_before' );
				?>
				<div id='patips-tier-title-filter-container' class='patips-filter-container'>
					<div class='patips-filter-title'>
						<?php esc_html_e( 'Title', 'patrons-tips' ); ?>
					</div>
					<div class='patips-filter-content'>
					<?php
						$args = array( 
							'name'  => 'title',
							'id'    => 'patips-tiers-filter-title',
							'type'  => 'text',
							'value' => ! empty( $_REQUEST[ 'title' ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ 'title' ] ) ) : ''
						);
						patips_display_field( $args );
					?>
					</div>
				</div>
				<?php
					do_action( 'patips_tier_filters_after' );
				?>
				<div id='patips-actions-filter-container' class='patips-filter-container'>
					<div class='patips-filter-title'>
						<?php esc_html_e( 'Actions', 'patrons-tips' ); ?>
					</div>
					<div class='patips-filter-content'>
						<input type='submit' class='button button-primary button-large' id='patips-submit-filter-button' value='<?php esc_html_e( 'Filter the list', 'patrons-tips' ); ?>' title='<?php esc_html_e( 'Filter the list', 'patrons-tips' ); ?>'/>
					</div>
				</div>
			</form>
		</div>

		<div id='patips-tier-list-table-container'>
			<?php do_action( 'patips_tier_list_table_before' ); ?>
			
			<div id='patips-tier-list-table'>
			<?php
				$tier_list_table = new PATIPS_Tiers_List_Table();
				$tier_list_table->prepare_items();
				$tier_list_table->views();
				$tier_list_table->display();
			?>
			</div>
			
			<?php do_action( 'patips_tier_list_table_after' ); ?>
		</div>
		
		<div id='patips-patronage-sales-page-tuto-container'>
			<?php
				// Check if the page is published
				$page_id      = (int) patips_get_option( 'patips_settings_general', 'sales_page_id' );
				$page         = $page_id ? get_post( $page_id ) : null;
				$edit_url     = $page ? get_edit_post_link( $page ) : '';
				$create_url   = admin_url( 'edit.php?post_type=page&patips_action=create-sales-page&nonce=' . wp_create_nonce( 'patips_create_sales_page' ) );
				$is_published = $page ? $page->post_status === 'publish' : false;
				$is_trashed   = $page ? $page->post_status === 'trash' : false;
			?>
			<div class='patips-info'>
				<span>
				<?php
					echo sprintf( 
						/* translators: %1$s = "Patronage form". %2$s = "[patronstips_tier_form]" */
						esc_html__( 'Customers can subscribe to a tier and become a patron thanks to the "%1$s" block or the %2$s shortcode on the patronage sales page.', 'patrons-tips' ),
						'<strong>' . esc_html__( 'Patronage form', 'patrons-tips' ) . '</strong>',
						'<strong><code>[patronstips_tier_form]</code></strong>'
					);
				?>
				</span>
				<a href='<?php echo esc_url( $edit_url && ! $is_trashed ? $edit_url : $create_url ); ?>' class='button button-secondary'>
					<?php echo $edit_url && ! $is_trashed ? esc_html__( 'Edit page', 'patrons-tips' ) : esc_html__( 'Create page', 'patrons-tips' ); ?>
				</a>
			</div>
			<?php if( ! $is_published ) { ?>
			<div class='patips-warning'>
				<span><?php esc_html_e( 'The patronage sales page is not published yet. You need to publish it to allow customers to subscribe to a tier.', 'patrons-tips' ); ?></span>
				<a href='<?php echo esc_url( $edit_url && ! $is_trashed ? $edit_url : $create_url ); ?>' class='button button-secondary'>
					<?php echo $edit_url && ! $is_trashed ? esc_html__( 'Edit page', 'patrons-tips' ) : esc_html__( 'Create page', 'patrons-tips' ); ?>
				</a>
			</div>
			<?php } ?>
		</div>
		
		<?php do_action( 'patips_tier_list_table_page_after' ); ?>
	</div>
</div>