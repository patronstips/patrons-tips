<?php
/**
 * Tier editor page
 * @since 0.5.0
 * @version 0.26.3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

$action  = ! empty( $_GET[ 'action' ] ) && in_array( $_GET[ 'action' ], array( 'edit', 'new' ), true ) ? sanitize_title_with_dashes( wp_unslash( $_GET[ 'action' ] ) ) : '';
$tier_id = ! empty( $_GET[ 'tier_id' ] ) ? intval( $_GET[ 'tier_id' ] ) : 0;

if( ! $action ) { return; }
if( $action !== 'new' && ! $tier_id ) { return; }

// Exit if not allowed to edit current tier
$can_edit_tier = $action === 'new' ? current_user_can( 'patips_create_tiers' ) : current_user_can( 'patips_edit_tiers' );
if( ! $can_edit_tier ) { esc_html_e( 'You are not allowed to do that.', 'patrons-tips' ); return; }

// Get tier raw data
$tier = $tier_id ? patips_get_tier_data( $tier_id, true ) : array(); 
if( $action !== 'new' && ! $tier ) { return; }

// Get edit data in the default language
$lang_switched = patips_switch_locale( patips_get_site_default_locale() );
$tier = patips_format_tier_data( $tier, 'edit' );
if( $lang_switched ) { patips_restore_locale(); }

?>
<div class='wrap'>
	<h1><?php echo ! $tier_id ? esc_html__( 'Create Tier', 'patrons-tips' ) : /* translators: %s is the tier ID */ sprintf( esc_html__( 'Edit Tier #%s', 'patrons-tips' ), (int) $tier_id ); ?></h1>
	<hr class='wp-header-end' />
	<div id='patips-tier-editor-page-container' >
		<?php
			do_action( 'patips_tier_editor_page_before', $tier );
			$redirect_url = $tier_id ? 'admin.php?page=patips_tiers&action=edit&tier_id=' . $tier_id : 'admin.php?page=patips_tiers';
		?>
		<form name='post' action='<?php echo esc_url( $redirect_url ); ?>' method='post' id='patips-tier-editor-page-form' novalidate>
			<?php
			/* Used to save closed meta boxes and their order */
			wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
			wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
			?>
			<input type='hidden' name='page' value='patips_tiers'/>
			<input type='hidden' name='action' value='<?php echo $tier_id ? 'edit' : 'create'; ?>'/>
			<input type='hidden' name='nonce' value='<?php echo esc_attr( wp_create_nonce( 'patips_update_tier' ) ); ?>'/>
			<input type='hidden' name='tier_id' value='<?php echo (int) $tier_id; ?>' id='patips-tier-id'/>
			
			<div id='patips-tier-editor-page-lang-switcher' class='patips-lang-switcher'></div>
			
			<div id='poststuff'>
				<div id='post-body' class='metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>'>
					<div id='postbox-container-1' class='postbox-container'>
					<?php
						do_meta_boxes( null, 'side', $tier );
					?>
					</div>
					<div id='postbox-container-2' class='postbox-container'>
					<?php
						do_meta_boxes( null, 'normal', $tier );
						do_meta_boxes( null, 'advanced', $tier );
					?>
					</div>
				</div>
				<br class='clear' />
			</div>
		</form>
		<?php
			do_action( 'patips_tier_editor_page_after', $tier );
		?>
	</div>
</div>
<?php


// TIER EDITOR METABOXES

/**
 * Display 'settings' metabox content for tier
 * @since 0.5.0
 * @version 1.0.5
 * @param array $tier
 */
function patips_display_tier_settings_meta_box( $tier ) {
?>
	<h4><?php esc_html_e( 'Presentation', 'patrons-tips' ) ?></h4>
	<?php
	do_action( 'patips_tier_settings_fields_presentation_before', $tier );
	
	// Message to display after Price field
	ob_start();
	if( ! patips_is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	?>
		<div class='patips-info'>
			<span>
			<?php
				esc_html_e( 'The price is for information purposes only, no payments will be made.', 'patrons-tips' );
			?>
				<br/>
			<?php
				echo sprintf(
					/* translators: %1$s = link to "WooCommerce" download page. %2$s = link to the "documentation" */
					esc_html__( 'In order to make online payments, you need to install %1$s (see this %2$s).', 'patrons-tips' ),
					/* translators: This is a plugin name. */
					'<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">' . esc_html__( 'WooCommerce', 'patrons-tips' ) . '</a>',
					'<a href="https://patronstips.com/en/user-documentation/associate-your-tiers-with-woocommerce-products/?utm_source=plugin&utm_medium=plugin&utm_content=tier-settings" target="_blank">' . esc_html__( 'documentation', 'patrons-tips' ) . '</a>'
				);
			?>
			</span>
		</div>
	<?php
	}
	$after_price = ob_get_clean();
	$price_args  = patips_get_price_args();
	
	$fields = apply_filters( 'patips_tier_settings_fields_presentation', array(
		'title' => array( 
			'name'        => 'title',
			'type'        => 'text',
			'class'       => 'patips-translatable',
			'value'       => ! empty( $tier[ 'title' ] ) ? sanitize_text_field( $tier[ 'title' ] ) : '',
			'placeholder' => esc_html__( 'Enter tier title here', 'patrons-tips' ),
			'title'       => esc_html__( 'Title', 'patrons-tips' ),
			'tip'         => esc_html__( 'Concise title to clearly identify the tier in a list.', 'patrons-tips' )
		),
		'icon_id' => array( 
			'name'    => 'icon_id',
			'type'    => 'media',
			'options' => array( 'hide_input' => 1, 'desired_data' => 'id' ),
			'value'   => ! empty( $tier[ 'icon_id' ] ) ? intval( $tier[ 'icon_id' ] ) : 0,
			'title'   => esc_html__( 'Icon', 'patrons-tips' ),
			'tip'     => esc_html__( 'Small image illustrating the tier.', 'patrons-tips' )
		),
		'price' => array( 
			'name'    => 'price',
			'type'    => 'number',
			'options' => array( 'min' => 0, 'step' => pow( 10, $price_args[ 'decimals' ] * -1 ) ),
			'value'   => ! empty( $tier[ 'price' ] ) ? $tier[ 'price' ] : '',
			'title'   => esc_html__( 'Price', 'patrons-tips' ),
			'tip'     => esc_html__( 'The tier price, for information purposes only.', 'patrons-tips' ),
			'after'   => $after_price
		),
		'description' => array( 
			'name'  => 'description',
			'type'  => 'textarea',
			'class' => 'patips-translatable',
			'value' => ! empty( $tier[ 'description' ] ) ? sanitize_textarea_field( $tier[ 'description' ] ) : '',
			'title' => esc_html__( 'Description', 'patrons-tips' ),
			'tip'   => esc_html__( 'Short description of the tier.', 'patrons-tips' )
		)
	), $tier );
	
	// Promote Patrons Tips Pro
	if( ! patips_is_plugin_active( 'patrons-tips-pro/patrons-tips-pro.php' ) ) {
		$fields[ 'period_availability' ] = array(
			'name'    => 'period_availability',
			'type'    => 'number',
			'title'   => esc_html__( 'Availability per period', 'patrons-tips' ),
			'tip'     => esc_html__( 'Limit the number of patrons who can subscribe to this tier per period.', 'patrons-tips' ),
			'attr'    => 'disabled="disabled"',
			'after'   => '<span class="patips-pro-ad-icon"></span>'
		);
	}
	
	?>
	<div id='patips-tier-settings-fields-presentation' class='patips-settings-fields-container'>
		<?php patips_display_fields( $fields ); ?>
	</div>
	
	<?php
		do_action( 'patips_tier_settings_fields_presentation_after', $tier );
	?>
	
	<hr/>
	<h4><?php esc_html_e( 'Content restricted to current patrons', 'patrons-tips' ); ?></h4>
	
	<?php
	do_action( 'patips_tier_settings_fields_restricted_content_before', $tier );
	
	// Get taxonomies that can be restricted by post type
	$taxonomies_by_type = patips_get_restrictable_taxonomies_by_post_type();
	
	// Get terms by post type
	$term_options_by_type = $term_value_by_type = array();
	foreach( $taxonomies_by_type as $post_type => $taxonomies ) {
		$taxonomies = array_filter( $taxonomies, 'taxonomy_exists' );
		$terms = $taxonomies ? get_terms( array( 'taxonomy' => $taxonomies, 'hide_empty' => false ) ) : array();
		if( ! isset( $term_options_by_type[ $post_type ] ) ) {
			$term_options_by_type[ $post_type ] = array();
		}
		foreach( $terms as $term ) {
			$term_options_by_type[ $post_type ][ intval( $term->term_id ) ] = $term->name;
		}
		$term_value_by_type[ $post_type ] = ! empty( $tier[ 'term_ids' ][ 'active' ] ) ? array_values( array_intersect( $tier[ 'term_ids' ][ 'active' ], array_keys( $term_options_by_type[ $post_type ] ) ) ) : array();
	}
	
	$fields        = array();
	$wp_post_types = get_post_types( array(), 'objects' );
	
	// Add a field per post type
	foreach( $term_options_by_type as $post_type => $term_options ) {
		// Get the post type label
		$wp_post_type    = isset( $wp_post_types[ $post_type ] ) ? $wp_post_types[ $post_type ] : null;
		$post_type_label = $wp_post_type && ! empty( $wp_post_type->label ) ? $wp_post_type->label : ucfirst( str_replace( array( '_', '-' ), ' ', $post_type ) );
		
		// Create the field
		$fields[ 'term_ids_' . $post_type ] = array(
			'name'        => 'term_ids[active][' . $post_type . ']',
			'type'        => 'select',
			'multiple'    => true,
			'class'       => 'patips-select2-no-ajax', 
			'options'     => $term_options,
			'value'       => $term_value_by_type[ $post_type ],
			'placeholder' => esc_html__( 'None', 'patrons-tips' ),
			'title'       => $post_type_label,
			/* translators: %s = post type label (e.g. "Post", "Product", "Attachment", etc.) */
			'tip'         => sprintf( esc_html__( 'Patrons of this tier will be allowed to see "%s" with these categories or tags.', 'patrons-tips' ), $post_type_label )
		);
	}
	
	$fields = apply_filters( 'patips_tier_settings_fields_restricted_content', $fields, $tier, $term_options_by_type );
	?>
	<div id='patips-tier-settings-fields-restricted-content' class='patips-settings-fields-container'>
		<p><?php esc_html_e( 'Select the categories or tags to restrict:', 'patrons-tips' ) ?></p>
		<?php patips_display_fields( $fields ); ?>
	</div>
	
	<p><em><?php esc_html_e( 'The content of these articles will be hidden. Only logged in users who are currently patrons will be able to view them.', 'patrons-tips' ) ?></em></p>
	
<?php
	// Promote Patrons Tips Pro
	if( ! patips_is_plugin_active( 'patrons-tips-pro/patrons-tips-pro.php' ) ) {
	?>
		<hr/>
		<h4><?php esc_html_e( 'Content restricted to patrons of the corresponding period', 'patrons-tips' ); ?><span class='patips-pro-ad-icon'></span></h4>
		<div id='patips-pro-ad-restrict-to-period' class='patips-pro-ad'>
			<p>
			<?php echo wp_kses_post( sprintf(
				/* translators: %s = link to "Patrons Tips - Pro" sales page */
				__( 'With %s, you can restrict your content to the users who were patrons <strong>at the time it was posted</strong>.', 'patrons-tips' ),
				patips_get_pro_sales_link()
			) ); ?>
			</p>
			<ul>
				<li><?php esc_html_e( 'March 2025 patrons will always be able to access the restricted content published in March 2025.', 'patrons-tips' ); ?>
				<li><?php esc_html_e( 'Past restricted content will never be available to newcomers.', 'patrons-tips' ); ?>
			</ul>
			<p>
				<em><?php echo wp_kses_post( __( 'This feature <strong>protects and values your work</strong>. You will no longer give away all the content you have ever created to the first comer.', 'patrons-tips' ) ); ?></em>
			</p>
		</div>
	<?php
	}

	do_action( 'patips_tier_settings_fields_restricted_content_after', $tier );
}


/**
 * Display 'publish' metabox content for tier
 * @since 0.5.0
 * @version 0.6.0
 * @param array $tier
 */
function patips_display_tier_publish_meta_box( $tier ) {
	$is_new = isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] === 'new';
?>
	<div class='submitbox' id='submitpost'>
		<div id='major-publishing-actions' >
			<div id='delete-action'>
			<?php
				if( ! $is_new && current_user_can( 'patips_delete_tiers' ) ) {
					echo '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?page=patips_tiers&action=trash&tier_id=' . $tier[ 'id' ] ), 'trash-tier_' . $tier[ 'id' ] ) ) . '" class="submitdelete deletion" >'
							. esc_html_x( 'Trash', 'verb', 'patrons-tips' )
						. '</a>';
				}
			?>
			</div>

			<div id='publishing-action'>
				<span class='spinner'></span>
				<input id='patips-save-tier-button' 
					   name='save' 
					   type='submit' 
					   class='button button-primary button-large' 
					   id='publish' 
					   value='<?php echo ! $is_new ? esc_attr__( 'Update', 'patrons-tips' ) : esc_attr__( 'Publish', 'patrons-tips' ); ?>' 
				/>
			</div>
			<div class='clear'></div>
		</div>
	</div>
<?php
}