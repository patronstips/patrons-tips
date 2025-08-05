<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( ! class_exists( 'PATIPS_widget_period_media' ) ) { 
	/**
	 * Widget to display the tier add to cart form
	 * @since 0.17.0
	 * @version 0.25.5
	 */
	class PATIPS_widget_period_media extends WP_Widget {
		
		public $default_data = array();
		
		
		/**
		 * Initialize the widget
		 */
		function __construct() {
			parent::__construct(
				'patips_widget_period_media',
				esc_html__( 'Image of the month', 'patrons-tips' ),
				array(
					'description' => esc_html__( 'Display the highlighted media for the current period.', 'patrons-tips' ),
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
				'image_size' => 'large'
			);
		}
		
		
		/**
		 * Widget Frontend Display
		 * @version 0.25.5
		 */
		public function widget( $args, $instance ) {
			/* translators: %s = the period name (e.g. "November 2024") */
			$title = ! empty( $instance[ 'title' ] ) ? $instance[ 'title' ] : esc_html__( 'Month illustration - %s', 'patrons-tips' );
			if( strpos( $title, '%s' ) !== false ) {
				$period_name = patips_get_period_name( patips_get_current_period() );
				$title       = sprintf( $title, $period_name );
			}
			$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );
			
			ob_start();
			
			echo ! empty( $args[ 'before_widget' ] ) ? wp_kses_post( $args[ 'before_widget' ] ) : '';
			
			if ( ! empty( $title ) ) {
				echo ! empty( $args[ 'before_title' ] ) ? wp_kses_post( $args[ 'before_title' ] ) : '';
				echo esc_html( $title );
				echo ! empty( $args[ 'after_title' ] ) ? wp_kses_post( $args[ 'after_title' ] ) : '';
			}
			
			$shortcode_args = apply_filters( 'patips_widget_period_media_args', array_merge( $this->default_data, $instance ) );
			
			$instance[ 'html' ] = patips_shortcode_period_media( $shortcode_args );
			
			echo $instance[ 'html' ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			
			echo ! empty( $args[ 'after_widget' ] ) ? wp_kses_post( $args[ 'after_widget' ] ) : '';
			
			echo apply_filters( 'patips_widget_period_media', ob_get_clean(), $instance, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
						<input type='text' name='<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>' value='<?php echo ! empty( $instance[ 'title' ] ) ? esc_attr( $instance[ 'title' ] ) : /* translators: %s = the period name (e.g. "November 2024") */ esc_html__( 'Month illustration - %s', 'patrons-tips' ); ?>' id='<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>'/>
					</div>

					<div class='patips-widget-field patips-field-decimals'>
						<label for='<?php echo esc_attr( $this->get_field_id( 'image_size' ) ); ?>'><?php esc_html_e( 'Size', 'patrons-tips' ); ?></label>
						<select name='<?php echo esc_attr( $this->get_field_name( 'image_size' ) ); ?>' id='<?php echo esc_attr( $this->get_field_id( 'image_size' ) ); ?>'>
							<option value='full' <?php selected( $instance[ 'image_size' ] === 'full' ); ?>><?php esc_html_e( 'Full Size', 'patrons-tips' ); ?></option>
							<option value='large' <?php selected( $instance[ 'image_size' ] === 'large' ); ?>><?php esc_html_e( 'Large', 'patrons-tips' ); ?></option>
							<option value='medium' <?php selected( $instance[ 'image_size' ] === 'medium' ); ?>><?php esc_html_e( 'Medium', 'patrons-tips' ); ?></option>
							<option value='thumbnail' <?php selected( $instance[ 'image_size' ] === 'thumbnail' ); ?>><?php esc_html_e( 'Thumbnail', 'patrons-tips' ); ?></option>
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
			
			$instance[ 'title' ]      = ! empty( $new_instance[ 'title' ] ) ? sanitize_text_field( $new_instance[ 'title' ] ) : '';
			$instance[ 'image_size' ] = ! empty( $new_instance[ 'image_size' ] ) && in_array( $new_instance[ 'image_size' ], array( 'full', 'large', 'medium', 'thumbnail' ), true ) ? $new_instance[ 'image_size' ] : 'large';
			
			return $instance;
		}
	}
}