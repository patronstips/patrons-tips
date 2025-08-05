<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( ! class_exists( 'PATIPS_widget_period_income' ) ) { 
	/**
	 * Widget to display the number of patrons
	 * @since 0.17.0
	 * @version 0.26.0
	 */
	class PATIPS_widget_period_income extends WP_Widget {
		
		public $default_data = array();
		
		
		/**
		 * Initialize the widget
		 */
		function __construct() {
			parent::__construct(
				'patips_widget_period_income',
				esc_html__( 'Period income', 'patrons-tips' ),
				array(
					'description' => esc_html__( 'Display the current period income from patronage products.', 'patrons-tips' ),
					'show_instance_in_rest' => true // Allow transform to block
				)
			);
			
			$this->default_data = $this->get_default_data();
		}
		
		
		/**
		 * Widget default data
		 * @version 0.21.0
		 */
		function get_default_data() {
			return array( 
				'zero_text'         => '',
				'raw'               => false,
				'include_tax'       => true,
				'include_discounts' => false,
				'include_scheduled' => true,
				'include_manual'    => false,
				'decimals'          => false
			);
		}
		
		
		/**
		 * Widget Frontend Display
		 * @version 0.25.5
		 */
		public function widget( $args, $instance ) {
			$default_title = esc_html__( 'Collected this month', 'patrons-tips' );
			$title         = apply_filters( 'widget_title', ! empty( $instance[ 'title' ] ) ? $instance[ 'title' ] : $default_title, $instance, $this->id_base );
			
			ob_start();
			
			echo ! empty( $args[ 'before_widget' ] ) ? wp_kses_post( $args[ 'before_widget' ] ) : '';
			
			if ( ! empty( $title ) ) {
				echo ! empty( $args[ 'before_title' ] ) ? wp_kses_post( $args[ 'before_title' ] ) : '';
				echo esc_html( $title );
				echo ! empty( $args[ 'after_title' ] ) ? wp_kses_post( $args[ 'after_title' ] ) : '';
			}
			
			$shortcode_args = apply_filters( 'patips_widget_period_income_args', array_merge( $this->default_data, $instance ) );
			
			$instance[ 'html' ] = patips_shortcode_period_income( $shortcode_args );
			
			echo $instance[ 'html' ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			
			echo ! empty( $args[ 'after_widget' ] ) ? wp_kses_post( $args[ 'after_widget' ] ) : '';
			
			echo apply_filters( 'patips_widget_period_income', ob_get_clean(), $instance, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		
		/**
		 * Widget Settings Form
		 * @version 0.26.0
		 */
		public function form( $instance ) {
			$instance = array_merge( $this->default_data, $instance );
			?>
				<fieldset class='patips-widget-fields patips-widget-fields-general'>
					<legend><?php esc_html_e( 'General options', 'patrons-tips' ); ?></legend>
					
					<div class='patips-widget-field patips-field-title'>
						<label for='<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>'><?php esc_html_e( 'Title', 'patrons-tips' ); ?></label>
						<input type='text' name='<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>' value='<?php echo ! empty( $instance[ 'title' ] ) ? esc_attr( $instance[ 'title' ] ) : esc_html__( 'Collected this month', 'patrons-tips' ); ?>' id='<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>'/>
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
					
					<div class='patips-widget-field patips-field-zero_text'>
						<label for='<?php echo esc_attr( $this->get_field_id( 'zero_text' ) ); ?>'><?php esc_html_e( 'Text when there are no income', 'patrons-tips' ); ?></label>
						<input type='text' name='<?php echo esc_attr( $this->get_field_name( 'zero_text' ) ); ?>' value='<?php echo esc_attr( $instance[ 'zero_text' ] ); ?>' id='<?php echo esc_attr( $this->get_field_id( 'zero_text' ) ); ?>'/>
					</div>
					
					<div class='patips-widget-field patips-field-raw'>
						<label for='<?php echo esc_attr( $this->get_field_id( 'raw' ) ); ?>'><?php esc_html_e( 'Show only number', 'patrons-tips' ); ?></label>
						<select name='<?php echo esc_attr( $this->get_field_name( 'raw' ) ); ?>' id='<?php echo esc_attr( $this->get_field_id( 'raw' ) ); ?>'>
							<option value='0' <?php selected( empty( $instance[ 'raw' ] ) ); ?>><?php esc_html_e( 'No', 'patrons-tips' ); ?></option>
							<option value='1' <?php selected( ! empty( $instance[ 'raw' ] ) ); ?>><?php esc_html_e( 'Yes', 'patrons-tips' ); ?></option>
						</select>
					</div>
				</fieldset>

				<fieldset class='patips-widget-fields patips-widget-fields-calculation'>
					<legend><?php esc_html_e( 'Calculation options', 'patrons-tips' ); ?></legend>
					
					<div class='patips-widget-field patips-field-include_tax'>
						<label for='<?php echo esc_attr( $this->get_field_id( 'include_tax' ) ); ?>'><?php esc_html_e( 'Include tax', 'patrons-tips' ); ?></label>
						<select name='<?php echo esc_attr( $this->get_field_name( 'include_tax' ) ); ?>' id='<?php echo esc_attr( $this->get_field_id( 'include_tax' ) ); ?>'>
							<option value='0' <?php selected( empty( $instance[ 'include_tax' ] ) ); ?>><?php esc_html_e( 'No', 'patrons-tips' ); ?></option>
							<option value='1' <?php selected( ! empty( $instance[ 'include_tax' ] ) ); ?>><?php esc_html_e( 'Yes', 'patrons-tips' ); ?></option>
						</select>
					</div>
					
					<div class='patips-widget-field patips-field-include_discounts'>
						<label for='<?php echo esc_attr( $this->get_field_id( 'include_discounts' ) ); ?>'><?php esc_html_e( 'Include discounts', 'patrons-tips' ); ?></label>
						<select name='<?php echo esc_attr( $this->get_field_name( 'include_discounts' ) ); ?>' id='<?php echo esc_attr( $this->get_field_id( 'include_discounts' ) ); ?>'>
							<option value='0' <?php selected( empty( $instance[ 'include_discounts' ] ) ); ?>><?php esc_html_e( 'No', 'patrons-tips' ); ?></option>
							<option value='1' <?php selected( ! empty( $instance[ 'include_discounts' ] ) ); ?>><?php esc_html_e( 'Yes', 'patrons-tips' ); ?></option>
						</select>
					</div>
					
					<?php if( patips_get_subscription_plugin() ) { ?>
					<div class='patips-widget-field patips-field-include_scheduled'>
						<label for='<?php echo esc_attr( $this->get_field_id( 'include_scheduled' ) ); ?>'><?php esc_html_e( 'Include scheduled payments', 'patrons-tips' ); ?></label>
						<select name='<?php echo esc_attr( $this->get_field_name( 'include_scheduled' ) ); ?>' id='<?php echo esc_attr( $this->get_field_id( 'include_scheduled' ) ); ?>'>
							<option value='0' <?php selected( empty( $instance[ 'include_scheduled' ] ) ); ?>><?php esc_html_e( 'No', 'patrons-tips' ); ?></option>
							<option value='1' <?php selected( ! empty( $instance[ 'include_scheduled' ] ) ); ?>><?php esc_html_e( 'Yes', 'patrons-tips' ); ?></option>
						</select>
					</div>
					<?php } ?>
					
					<div class='patips-widget-field patips-field-include_manual'>
						<label for='<?php echo esc_attr( $this->get_field_id( 'include_manual' ) ); ?>'><?php esc_html_e( 'Include patronage without payments', 'patrons-tips' ); ?></label>
						<select name='<?php echo esc_attr( $this->get_field_name( 'include_manual' ) ); ?>' id='<?php echo esc_attr( $this->get_field_id( 'include_manual' ) ); ?>'>
							<option value='0' <?php selected( empty( $instance[ 'include_manual' ] ) ); ?>><?php esc_html_e( 'No', 'patrons-tips' ); ?></option>
							<option value='1' <?php selected( ! empty( $instance[ 'include_manual' ] ) ); ?>><?php esc_html_e( 'Yes', 'patrons-tips' ); ?></option>
						</select>
					</div>
				</fieldset>
			<?php
		}

		
		/**
		 * Save widget settings
		 * @param array $new_instance
		 * @param array $old_instance
		 * @return array
		 */
		public function update( $new_instance, $old_instance ) {
			$instance = $new_instance;
			
			$instance[ 'title' ]             = ! empty( $new_instance[ 'title' ] ) ? sanitize_text_field( $new_instance[ 'title' ] ) : '';
			$instance[ 'decimals' ]          = isset( $new_instance[ 'decimals' ] ) && is_numeric( $new_instance[ 'decimals' ] ) ? intval( $new_instance[ 'decimals' ] ) : false;
			$instance[ 'zero_text' ]         = ! empty( $new_instance[ 'zero_text' ] ) ? sanitize_text_field( $new_instance[ 'zero_text' ] ) : '';
			$instance[ 'raw' ]               = ! empty( $new_instance[ 'raw' ] ) ? 1 : 0;
			$instance[ 'include_tax' ]       = ! empty( $new_instance[ 'include_tax' ] ) ? 1 : 0;
			$instance[ 'include_discounts' ] = ! empty( $new_instance[ 'include_discounts' ] ) ? 1 : 0;
			$instance[ 'include_scheduled' ] = ! empty( $new_instance[ 'include_scheduled' ] ) ? 1 : 0;
			$instance[ 'include_manual' ]    = ! empty( $new_instance[ 'include_manual' ] ) ? 1 : 0;
			
			return $instance;
		}
	}
}