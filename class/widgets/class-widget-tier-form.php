<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( ! class_exists( 'PATIPS_widget_tier_form' ) ) { 
	/**
	 * Widget to display the tier add to cart form
	 * @since 0.17.0
	 * @version 0.25.5
	 */
	class PATIPS_widget_tier_form extends WP_Widget {
		
		/**
		 * Initialize the widget
		 */
		function __construct() {
			parent::__construct(
				'patips_widget_tier_form',
				esc_html__( 'Patronage form', 'patrons-tips' ),
				array(
					'description' => esc_html__( 'Form to subscribe to a patronage tier.', 'patrons-tips' ),
					'show_instance_in_rest' => true // Allow transform to block
				)
			);
		}
		
		
		/**
		 * Widget Frontend Display
		 * @version 0.25.5
		 * @param array $args
		 * @param array $instance
		 */
		public function widget( $args, $instance ) {
			$default_title = esc_html__( 'Choose your tier!', 'patrons-tips' );
			$title         = apply_filters( 'widget_title', ! empty( $instance[ 'title' ] ) ? $instance[ 'title' ] : $default_title, $instance, $this->id_base );
			
			ob_start();
			
			echo ! empty( $args[ 'before_widget' ] ) ? wp_kses_post( $args[ 'before_widget' ] ) : '';
			
			if ( ! empty( $title ) ) {
				echo ! empty( $args[ 'before_title' ] ) ? wp_kses_post( $args[ 'before_title' ] ) : '';
				echo esc_html( $title );
				echo ! empty( $args[ 'after_title' ] ) ? wp_kses_post( $args[ 'after_title' ] ) : '';
			}
			
			$default_args   = patips_get_default_subscription_form_filters();
			$shortcode_args = apply_filters( 'patips_widget_tier_form_args', array_merge( $default_args, $instance ) );
			
			$instance[ 'html' ] = patips_shortcode_subscription_form( $shortcode_args );
			
			echo $instance[ 'html' ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			
			echo ! empty( $args[ 'after_widget' ] ) ? wp_kses_post( $args[ 'after_widget' ] ) : '';
			
			echo apply_filters( 'patips_widget_tier_form', ob_get_clean(), $instance, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		
		/**
		 * Widget Settings Form
		 * @version 0.25.5
		 * @param array $instance
		 */
		public function form( $instance ) {
			$default_args = patips_get_default_subscription_form_filters();
			$instance     = array_merge( $default_args, $instance );
			
			// Get tiers
			$tiers = patips_get_tiers_data();
			
			// Get frequencies
			$frequencies = $instance[ 'frequencies' ];
			if( ! $frequencies ) {
				foreach( $tiers as $tier_id => $tier ) {
					if( isset( $tier[ 'product_ids' ] ) ) {
						foreach( $tier[ 'product_ids' ] as $frequency => $product_ids ) {
							if( ! in_array( $frequency, $frequencies, true ) ) {
								$frequencies[] = $frequency;
							}
						}
					}
				}
			}
			
			?>
				<fieldset class='patips-widget-fields patips-widget-fields-general'>
					<legend><?php esc_html_e( 'General options', 'patrons-tips' ); ?></legend>
					
					<div class='patips-widget-field patips-field-title'>
						<label for='<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>'><?php esc_html_e( 'Title', 'patrons-tips' ); ?></label>
						<input type='text' name='<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>' value='<?php echo ! empty( $instance[ 'title' ] ) ? esc_attr( $instance[ 'title' ] ) : esc_html__( 'Choose your tier!', 'patrons-tips' ); ?>' id='<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>'/>
					</div>
					
					<div class='patips-widget-field patips-field-decimals'>
						<label for='<?php echo esc_attr( $this->get_field_id( 'decimals' ) ); ?>'><?php esc_html_e( 'Decimals', 'patrons-tips' ); ?></label>
						<select name='<?php echo esc_attr( $this->get_field_name( 'decimals' ) ); ?>' id='<?php echo esc_attr( $this->get_field_id( 'decimals' ) ); ?>'>
							<option value='0' <?php selected( $instance[ 'decimals' ] === 0 ); ?>>0</option>
							<option value='1' <?php selected( $instance[ 'decimals' ] === 1 ); ?>>1</option>
							<option value='2' <?php selected( $instance[ 'decimals' ] === 2 ); ?>>2</option>
							<option value='3' <?php selected( $instance[ 'decimals' ] === 3 ); ?>>3</option>
						</select>
					</div>
					
					<div class='patips-widget-field patips-field-submit_label'>
						<label for='<?php echo esc_attr( $this->get_field_id( 'submit_label' ) ); ?>'><?php esc_html_e( 'Submit button label', 'patrons-tips' ); ?></label>
						<input type='text' name='<?php echo esc_attr( $this->get_field_name( 'submit_label' ) ); ?>' value='<?php echo esc_attr( $instance[ 'submit_label' ] ); ?>' id='<?php echo esc_attr( $this->get_field_id( 'submit_label' ) ); ?>'/>
					</div>
				</fieldset>
				
				<fieldset class='patips-widget-fields patips-widget-fields-tiers'>
					<legend><?php esc_html_e( 'Tiers options', 'patrons-tips' ); ?></legend>
					
					<div class='patips-widget-field patips-field-tiers'>
						<label for='<?php echo esc_attr( $this->get_field_id( 'tiers' ) ); ?>'><?php esc_html_e( 'Tiers', 'patrons-tips' ); ?></label>
						<select name='<?php echo esc_attr( $this->get_field_name( 'tiers' ) ); ?>[]' id='<?php echo esc_attr( $this->get_field_id( 'tiers' ) ); ?>' multiple>
							<?php 
								foreach( $tiers as $tier ) { 
									/* translators: %s is the tier ID */
									$title = $tier[ 'title' ] ? $tier[ 'title' ] : ( $tier[ 'id' ] ? sprintf( esc_html__( 'Tier #%s', 'patrons-tips' ), $tier[ 'id' ] ) : '' );
									?>
										<option value='<?php echo esc_attr( $tier[ 'id' ] ); ?>' <?php selected( in_array( $tier[ 'id' ], $instance[ 'tiers' ], true ) ); ?>><?php echo esc_html( $title ); ?></option>
									<?php
								}
							?>
						</select>
					</div>
					
					<div class='patips-widget-field patips-field-default_tier'>
						<label for='<?php echo esc_attr( $this->get_field_id( 'default_tier' ) ); ?>'><?php esc_html_e( 'Default tier', 'patrons-tips' ); ?></label>
						<select name='<?php echo esc_attr( $this->get_field_name( 'default_tier' ) ); ?>' id='<?php echo esc_attr( $this->get_field_id( 'default_tier' ) ); ?>'>
							<option value='0' <?php selected( ! $instance[ 'default_tier' ] ); ?>><?php esc_html_e( 'Auto', 'patrons-tips' ); ?></option>
							<?php
								foreach( $tiers as $tier ) {
									/* translators: %s is the tier ID */
									$title = $tier[ 'title' ] ? $tier[ 'title' ] : ( $tier[ 'id' ] ? sprintf( esc_html__( 'Tier #%s', 'patrons-tips' ), $tier[ 'id' ] ) : '' );
									?>
										<option value='<?php echo esc_attr( $tier[ 'id' ] ); ?>' <?php selected( $tier[ 'id' ] === $instance[ 'default_tier' ] ); ?>><?php echo esc_html( $title ); ?></option>
									<?php 
								}
							?>
						</select>
					</div>
				</fieldset>

				<fieldset class='patips-widget-fields patips-widget-fields-frequency'>
					<legend><?php esc_html_e( 'Frequency options', 'patrons-tips' ); ?></legend>
					
					<div class='patips-widget-field patips-field-frequencies'>
						<label for='<?php echo esc_attr( $this->get_field_id( 'frequencies' ) ); ?>'><?php esc_html_e( 'Frequencies', 'patrons-tips' ); ?></label>
						<select name='<?php echo esc_attr( $this->get_field_name( 'frequencies' ) ); ?>[]' id='<?php echo esc_attr( $this->get_field_id( 'frequencies' ) ); ?>' multiple>
							<?php foreach( $frequencies as $frequency ) { ?>
								<option value='<?php echo esc_attr( $frequency ); ?>' <?php selected( in_array( $frequency, $instance[ 'frequencies' ], true ) ); ?>><?php echo esc_html( patips_get_frequency_name( $frequency ) ); ?></option>
							<?php } ?>
						</select>
					</div>
					
					<div class='patips-widget-field patips-field-default_frequency'>
						<label for='<?php echo esc_attr( $this->get_field_id( 'default_frequency' ) ); ?>'><?php esc_html_e( 'Default frequency', 'patrons-tips' ); ?></label>
						<select name='<?php echo esc_attr( $this->get_field_name( 'default_frequency' ) ); ?>' id='<?php echo esc_attr( $this->get_field_id( 'default_frequency' ) ); ?>'>
							<option value='' <?php selected( ! $instance[ 'default_frequency' ] ); ?>><?php esc_html_e( 'Auto', 'patrons-tips' ); ?></option>
							<?php foreach( $frequencies as $frequency ) { ?>
								<option value='<?php echo esc_attr( $frequency ); ?>' <?php selected( $frequency === $instance[ 'default_frequency' ] ); ?>><?php echo esc_html( patips_get_frequency_name( $frequency ) ); ?></option>
							<?php } ?>
						</select>
					</div>
				</fieldset>
			<?php
		}

		
		/**
		 * Save widget settings
		 * @version 0.22.0
		 * @param array $new_instance
		 * @param array $old_instance
		 * @return array
		 */
		public function update( $new_instance, $old_instance ) {
			$saved_keys = array( 'tiers', 'default_tier', 'frequencies', 'default_frequency', 'decimals', 'submit_label' );
			$instance   = array_intersect_key( patips_format_subscription_form_filters( $new_instance ), array_flip( $saved_keys ) );
			
			$instance[ 'title' ] = ! empty( $new_instance[ 'title' ] ) ? sanitize_text_field( $new_instance[ 'title' ] ) : '';
			
			return $instance;
		}
	}
}