<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( ! class_exists( 'PATIPS_widget_patron_number' ) ) { 
	/**
	 * Widget to display the number of patrons
	 * @since 0.17.0
	 * @version 0.25.5
	 */
	class PATIPS_widget_patron_number extends WP_Widget {
		
		public $default_data = array();
		
		
		/**
		 * Initialize the widget
		 */
		function __construct() {
			parent::__construct(
				'patips_widget_patron_number',
				esc_html__( 'Number of patrons', 'patrons-tips' ),
				array(
					'description' => esc_html__( 'Display the current number of patrons.', 'patrons-tips' ),
					'show_instance_in_rest' => true // Allow transform to block
				)
			);
			
			$this->default_data = $this->get_default_data();
		}
		
		
		/**
		 * Widget default data
		 */
		function get_default_data() {
			return array( 
				'zero_text' => '',
				'raw'       => false
			);
		}
		
		
		/**
		 * Widget Frontend Display
		 * @version 0.25.5
		 */
		public function widget( $args, $instance ) {
			$default_title = esc_html__( 'Number of patrons', 'patrons-tips' );
			$title         = apply_filters( 'widget_title', ! empty( $instance[ 'title' ] ) ? $instance[ 'title' ] : $default_title, $instance, $this->id_base );
			
			ob_start();
			
			echo ! empty( $args[ 'before_widget' ] ) ? wp_kses_post( $args[ 'before_widget' ] ) : '';
			
			if ( ! empty( $title ) ) {
				echo ! empty( $args[ 'before_title' ] ) ? wp_kses_post( $args[ 'before_title' ] ) : '';
				echo esc_html( $title );
				echo ! empty( $args[ 'after_title' ] ) ? wp_kses_post( $args[ 'after_title' ] ) : '';
			}
			
			$shortcode_args = apply_filters( 'patips_widget_patron_number_args', array_merge( $this->default_data, $instance ) );
			
			$instance[ 'html' ] = patips_shortcode_patron_number( $shortcode_args );
			
			echo $instance[ 'html' ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			
			echo ! empty( $args[ 'after_widget' ] ) ? wp_kses_post( $args[ 'after_widget' ] ) : '';
			
			echo apply_filters( 'patips_widget_patron_number', ob_get_clean(), $instance, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		
		/**
		 * Widget Settings Form
		 * @version 0.25.5
		 */
		public function form( $instance ) {
			$instance = array_merge( $this->default_data, $instance );
			?>
				<fieldset class='patips-widget-fields patips-widget-fields-general'>
					<legend><?php esc_html_e( 'General options', 'patrons-tips' ); ?></legend>
					
					<div class='patips-widget-field patips-field-title'>
						<label for='<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>'><?php esc_html_e( 'Title', 'patrons-tips' ); ?></label>
						<input type='text' name='<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>' value='<?php echo ! empty( $instance[ 'title' ] ) ? esc_attr( $instance[ 'title' ] ) : esc_html__( 'Number of patrons', 'patrons-tips' ); ?>' id='<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>'/>
					</div>
					
					<div class='patips-widget-field patips-field-zero_text'>
						<label for='<?php echo esc_attr( $this->get_field_id( 'zero_text' ) ); ?>'><?php esc_html_e( 'Text when there are no patrons', 'patrons-tips' ); ?></label>
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
			
			$instance[ 'title' ]     = ! empty( $new_instance[ 'title' ] ) ? sanitize_text_field( $new_instance[ 'title' ] ) : '';
			$instance[ 'zero_text' ] = ! empty( $new_instance[ 'zero_text' ] ) ? sanitize_text_field( $new_instance[ 'zero_text' ] ) : '';
			$instance[ 'raw' ]       = ! empty( $new_instance[ 'raw' ] ) ? 1 : 0;
			
			return $instance;
		}
	}
}