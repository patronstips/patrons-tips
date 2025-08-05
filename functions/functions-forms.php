<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// FIELDS

/**
 * Display fields
 * @since 0.5.0
 * @version 0.25.5
 * @param array $args
 */
function patips_display_fields( $fields, $args = array() ) {
	if( empty( $fields ) || ! is_array( $fields ) )	{ return; }

	// Format parameters
	if( ! isset( $args[ 'hidden' ] ) || ! is_array( $args[ 'hidden' ] ) )  { $args[ 'hidden' ] = array(); }
	if( ! isset( $args[ 'prefix' ] ) || ! is_string( $args[ 'prefix' ] ) ) { $args[ 'prefix' ] = ''; }

	foreach( $fields as $field_name => $field ) {
		if( empty( $field[ 'type' ] ) ) { continue; }

		if( is_numeric( $field_name ) && ! empty( $field[ 'name' ] ) ) { $field_name = $field[ 'name' ]; }
		if( empty( $field[ 'name' ] ) ) { $field[ 'name' ] = $field_name; }
		$field[ 'name' ]   = ! empty( $args[ 'prefix' ] ) ? $args[ 'prefix' ] . '[' . $field_name . ']' : $field[ 'name' ];
		$field[ 'id' ]     = empty( $field[ 'id' ] ) ? 'patips-' . $field_name : $field[ 'id' ];
		$field[ 'hidden' ] = ! isset( $field[ 'hidden' ] ) ? ( in_array( $field_name, $args[ 'hidden' ], true ) ? 1 : 0 ) : $field[ 'hidden' ];
		
		$wrap_class = '';
		if( ! empty( $field[ 'hidden' ] ) ) { $wrap_class .= ' patips-hidden'; } 
		
		// If custom type, call another function to display this field
		if( substr( $field[ 'type' ], 0, 6 ) === 'custom' ) {
			do_action( 'patips_display_custom_field', $field, $field_name );
			continue;
		}
		
		// Else, display standard field
		?>
		<div class='patips-field-container <?php echo esc_attr( $wrap_class ); ?>' id='<?php echo esc_attr( $field[ 'id' ] . '-container' ); ?>'>
		<?php
			if( ! empty( $field[ 'before' ] ) ) { echo wp_kses_post( $field[ 'before' ] ); }
		
			// Display field title
			if( ! empty( $field[ 'title' ] ) ) { 
				$fullwidth = ! empty( $field[ 'fullwidth' ] ) || in_array( $field[ 'type' ], array( 'checkboxes', 'editor' ), true );
			?>
				<label for='<?php echo esc_attr( sanitize_title_with_dashes( $field[ 'id' ] ) ); ?>' class='<?php if( $fullwidth ) { echo 'patips-fullwidth-label'; } ?>'>
					<?php echo wp_kses_post( $field[ 'title' ] ); if( $fullwidth && ! empty( $field[ 'tip' ] ) ) { patips_tooltip( $field[ 'tip' ] ); unset( $field[ 'tip' ] ); } ?>
				</label>
			<?php
			}
			
			// Display field
			patips_display_field( $field );
			
			if( ! empty( $field[ 'after' ] ) ) { echo wp_kses_post( $field[ 'after' ] ); }
		?>
		</div>
	<?php
	}
}


/**
 * Display various fields
 * @since 0.5.0
 * @version 0.25.6
 * @param array $args ['type', 'name', 'label', 'id', 'class', 'placeholder', 'options', 'attr', 'value', 'tip', 'required']
 */
function patips_display_field( $args ) {
	$args = patips_format_field_args( $args );
	if( ! $args ) { return; }
	
	// Display field according to type

	// TEXT & NUMBER
	if( in_array( $args[ 'type' ], array( 'text', 'hidden', 'number', 'date', 'time', 'email', 'tel', 'password', 'file', 'color', 'url' ), true ) ) {
	?>
		<input type='<?php echo esc_attr( $args[ 'type' ] ); ?>' 
				name='<?php echo esc_attr( $args[ 'name' ] ); ?>' 
				value='<?php echo esc_attr( $args[ 'value' ] ); ?>' 
				autocomplete='<?php echo $args[ 'autocomplete' ] ? esc_attr( $args[ 'autocomplete' ] ) : 'off'; ?>'
				id='<?php echo esc_attr( $args[ 'id' ] ); ?>' 
				class='patips-input <?php echo esc_attr( $args[ 'class' ] ); ?>' 
			<?php if( ! in_array( $args[ 'type' ], array( 'hidden', 'file' ) ) ) { ?>
				placeholder='<?php echo esc_attr( $args[ 'placeholder' ] ); ?>' 
			<?php } 
			if( in_array( $args[ 'type' ], array( 'number', 'date', 'time' ), true ) ) { ?>
				min='<?php echo esc_attr( $args[ 'options' ][ 'min' ] ); ?>' 
				max='<?php echo esc_attr( $args[ 'options' ][ 'max' ] ); ?>'
				step='<?php echo esc_attr( $args[ 'options' ][ 'step' ] ); ?>'
			<?php }
			if( ! empty( $args[ 'attr' ] ) ) { echo wp_kses_post( $args[ 'attr' ] ); }
			if( $args[ 'type' ] === 'file' && $args[ 'multiple' ] ) { echo ' multiple'; }
			if( $args[ 'required' ] ) { echo ' required'; } ?>
		/>
	<?php if( $args[ 'label' ] ) { ?>
		<label for='<?php echo esc_attr( $args[ 'id' ] ); ?>' >
			<?php echo wp_kses_post( $args[ 'label' ] ); ?>
		</label>
	<?php
		}
	}

	// TEXTAREA
	else if( $args[ 'type' ] === 'textarea' ) {
	?>
		<textarea	
			name='<?php echo esc_attr( $args[ 'name' ] ); ?>' 
			id='<?php echo esc_attr( $args[ 'id' ] ); ?>' 
			autocomplete='<?php echo $args[ 'autocomplete' ] ? esc_attr( $args[ 'autocomplete' ] ) : 'off'; ?>'
			class='patips-textarea <?php echo esc_attr( $args[ 'class' ] ); ?>' 
			placeholder='<?php echo esc_attr( $args[ 'placeholder' ] ); ?>'
			<?php if( ! empty( $args[ 'attr' ] ) ) { echo wp_kses_post( $args[ 'attr' ] ); } ?>
			<?php if( $args[ 'required' ] ) { echo ' required'; } ?>
			><?php echo esc_textarea( $args[ 'value' ] ); ?></textarea>
	<?php if( $args[ 'label' ] ) { ?>
			<label for='<?php echo esc_attr( $args[ 'id' ] ); ?>' >
				<?php echo wp_kses_post( $args[ 'label' ] ); ?>
			</label>
	<?php
		}
	}

	// SINGLE CHECKBOX (boolean)
	else if( $args[ 'type' ] === 'checkbox' ) {
		$disabled = strpos( $args[ 'attr' ], 'disabled="disabled"' ) !== false;
		?>
		<div class='patips-onoffswitch' <?php if( $disabled ) { echo 'patips-disabled'; } ?>>
			<?php if( ! $disabled ) { ?>
			<input type='hidden' name='<?php echo esc_attr( $args[ 'name' ] ); ?>' value='0' class='patips-onoffswitch-hidden-input'/>
			<?php } ?>
			<input type='checkbox' 
				   name='<?php echo esc_attr( $args[ 'name' ] ); ?>'
				   id='<?php echo esc_attr( $args[ 'id' ] ); ?>'
				   class='patips-checkbox patips-onoffswitch-checkbox <?php echo esc_attr( $args[ 'class' ] ); ?>' 
				   autocomplete='<?php echo $args[ 'autocomplete' ] ? esc_attr( $args[ 'autocomplete' ] ) : 'off'; ?>'
				   value='1' 
				<?php 
					checked( '1', $args[ 'value' ] );
					if( ! empty( $args[ 'attr' ] ) ) { echo ' ' . wp_kses_post( $args[ 'attr' ] ); }
					if( $args[ 'required' ] && ! $disabled ) { echo ' required'; }
				?>
			/>
			<?php if( $disabled ) { ?>
			<input type='hidden' name='<?php echo esc_attr( $args[ 'name' ] ); ?>' value='<?php echo esc_attr( $args[ 'value' ] ); ?>'/>
			<?php } ?>
			<div class='patips-onoffswitch-knobs'></div>
			<div class='patips-onoffswitch-layer'></div>
		</div>
		<?php 
		if( $args[ 'label' ] ) { ?>
		<label for='<?php echo esc_attr( $args[ 'id' ] ); ?>' >
			<?php echo wp_kses_post( $args[ 'label' ] ); ?>
		</label>
		<?php 
		}
	}

	// MULTIPLE CHECKBOX
	else if( $args[ 'type' ] === 'checkboxes' ) {
		?>
		<input name='<?php echo esc_attr( $args[ 'name' ] ) . '[]'; ?>' 
				id='<?php echo esc_attr( $args[ 'id' ] ) . '_none'; ?>'
				type='hidden' 
				value='none' />
		<?php
		$count = count( $args[ 'options' ] );
		$i = 1;
		foreach( $args[ 'options' ] as $option ) {
		?>
			<div class='patips-checkbox-container <?php if( $i === $count ) { echo 'patips-last-checkbox-container'; } ?>'>
				<input name='<?php echo esc_attr( $args[ 'name' ] ) . '[]'; ?>' 
						id='<?php echo esc_attr( $args[ 'id' ] ) . '_' . esc_attr( $option[ 'id' ] ); ?>' 
						class='patips-checkbox <?php echo esc_attr( $args[ 'class' ] ); ?>' 
						type='checkbox' 
						value='<?php echo esc_attr( $option[ 'id' ] ); ?>'
						<?php if( ! empty( $args[ 'attr' ][ $option[ 'id' ] ] ) ) { echo wp_kses_post( $args[ 'attr' ][ $option[ 'id' ] ] ); } ?>
						<?php if( in_array( $option[ 'id' ], $args[ 'value' ], true ) ) { echo 'checked'; } ?>
				/>
			<?php if( ! empty( $option[ 'label' ] ) ) { ?>
				<label for='<?php echo esc_attr( $args[ 'id' ] . '_' . $option[ 'id' ] ); ?>' >
					<?php echo wp_kses_post( $option[ 'label' ] ); ?>
				</label>
			<?php
				}
				// Display the tip
				if( ! empty( $option[ 'description' ] ) ) {
					$tip = $option[ 'description' ];
					patips_tooltip( $tip );
				}
			?>
			</div>
		<?php
			++$i;
		}
	}

	// RADIO
	else if( $args[ 'type' ] === 'radio' ) {
		$count = count( $args[ 'options' ] );
		$i = 1;
		foreach( $args[ 'options' ] as $option ) {
		?>
			<div class='patips-radio-container <?php if( $i === $count ) { echo 'patips-last-radio-container'; } ?>'>
				<input name='<?php echo esc_attr( $args[ 'name' ] ); ?>' 
						id='<?php echo esc_attr( $args[ 'id' ] . '_' . $option[ 'id' ] ); ?>' 
						class='patips-radio <?php echo esc_attr( $args[ 'class' ] ); ?>' 
						type='radio' 
						value='<?php echo esc_attr( $option[ 'id' ] ); ?>'
						<?php if( ! empty( $args[ 'attr' ][ $option[ 'id' ] ] ) ) { echo wp_kses_post( $args[ 'attr' ][ $option[ 'id' ] ] ); } ?>
						<?php if( isset( $args[ 'value' ] ) ) { checked( $args[ 'value' ], $option[ 'id' ], true ); } ?>
						<?php if( $args[ 'required' ] ) { echo ' required'; } ?>
				/>
			<?php if( $option[ 'label' ] ) { ?>
				<label for='<?php echo esc_attr( $args[ 'id' ] ) . '_' . esc_attr( $option[ 'id' ] ); ?>' >
					<?php echo wp_kses_post( $option[ 'label' ] ); ?>
				</label>
			<?php
				}
				// Display the tip
				if( ! empty( $option[ 'description' ] ) ) {
					$tip = $option[ 'description' ];
					patips_tooltip( $tip );
				}
			?>
			</div>
		<?php
			++$i;
		}
	}

	// SELECT
	else if( $args[ 'type' ] === 'select' ) {
		$is_multiple = $args[ 'multiple' ];
		if( $is_multiple && strpos( $args[ 'name' ], '[]' ) === false ) { $args[ 'name' ] .= '[]'; }
		if( ! $is_multiple && is_array( $args[ 'value' ] ) ) { $args[ 'value' ] = reset( $args[ 'value' ] ); }
		?>
		<select 
			name='<?php echo esc_attr( $args[ 'name' ] ); ?>' 
			id='<?php echo esc_attr( $args[ 'id' ] ); ?>' 
			class='patips-select <?php echo esc_attr( $args[ 'class' ] ); ?>'
			<?php
				if( ! empty( $args[ 'placeholder' ] ) ) { echo ' data-placeholder="' . esc_attr( $args[ 'placeholder' ] ) . '"'; }
				if( ! empty( $args[ 'attr' ][ '<select>' ] ) ) { echo ' ' . wp_kses_post( $args[ 'attr' ][ '<select>' ] ); }
				if( $is_multiple ) { echo ' multiple'; }
				if( $args[ 'required' ] ) { echo ' required'; }
			?>
		>
		<?php foreach( $args[ 'options' ] as $option_id => $option_value ) { ?>
			<option value='<?php echo esc_attr( $option_id ); ?>'
					id='<?php echo esc_attr( $args[ 'id' ] . '_' . $option_id ); ?>' 
					<?php if( $args[ 'multiple' ] ) { ?> 
					title='<?php echo esc_html( $option_value ); ?>' 
					<?php } ?>
					<?php if( ! empty( $args[ 'attr' ][ $option_id ] ) ) { echo wp_kses_post( $args[ 'attr' ][ $option_id ] ); } ?>
					<?php if( $is_multiple ) { selected( true, in_array( $option_id, $args[ 'value' ], true ) ); } else { selected( $args[ 'value' ], $option_id ); }?>
					<?php if( $is_multiple && ( in_array( $option_id, array( 'all', 'none', 'parent', 'site' ), true ) || ( ! empty( $args[ 'attr' ][ $option_id ] ) && strpos( 'data-not-multiple="1"', $args[ 'attr' ][ $option_id ] ) !== false ) ) ) { echo 'disabled="disabled"'; } ?>
			>
					<?php echo esc_html( $option_value ); ?>
			</option>
		<?php } ?>
		</select>
	<?php 
		if( $args[ 'label' ] ) { ?>
		<label for='<?php echo esc_attr( $args[ 'id' ] ); ?>' >
			<?php echo wp_kses_post( $args[ 'label' ] ); ?>
		</label>
	<?php
		}
	}
	
	// Media selector
	else if( $args[ 'type' ] === 'media' ) {
	?>
		<span class='patips-media-inputs'>
			<?php if( ! empty( $args[ 'options' ][ 'preview' ] ) ) { ?>
			<span class='patips-media-input-preview <?php echo ! empty( $args[ 'value' ] ) ? 'patips-has-img' : ''; ?>'>
				<span class='patips-media-input-remove dashicons dashicons-trash'></span>
				<?php if( ! empty( $args[ 'value' ] ) ) {
					if( is_numeric( $args[ 'value' ] ) ) {
						echo wp_kses_post( wp_get_attachment_image( intval( $args[ 'value' ] ), 'thumbnail', true, array( 'class' => 'attachment-thumbnail size-thumbnail patips-media-input-preview-img' ) ) );
					} else {
					?>
						<img src='<?php echo esc_attr( $args[ 'value' ] ); ?>' class='patips-media-input-preview-img'/>
					<?php
					}
				} ?>
			</span>
			<?php } ?>
			<input 
				type='<?php echo ! empty( $args[ 'options' ][ 'hide_input' ] ) ? 'hidden' : 'text'; ?>' 
				name='<?php echo esc_attr( $args[ 'name' ] ); ?>' 
				value='<?php echo esc_attr( $args[ 'value' ] ); ?>' 
				autocomplete='<?php echo $args[ 'autocomplete' ] ? esc_attr( $args[ 'autocomplete' ] ) : 'off'; ?>'
				id='<?php echo esc_attr( $args[ 'id' ] ); ?>' 
				class='patips-input <?php echo esc_attr( $args[ 'class' ] ); ?>'
				placeholder='<?php echo esc_attr( $args[ 'placeholder' ] ); ?>'
				<?php
					if( ! empty( $args[ 'attr' ] ) ) { echo wp_kses_post( $args[ 'attr' ] ); }
					if( $args[ 'type' ] === 'file' && $args[ 'multiple' ] ) { echo ' multiple'; }
					if( $args[ 'required' ] ) { echo ' required'; }
				?>
			/>
			<input type='button' class='patips-upload-image-button button' value='<?php esc_html_e( 'Media', 'patrons-tips' ); ?>' data-field-id='<?php echo esc_attr( $args[ 'id' ] ); ?>' data-desired-data='<?php echo esc_attr( $args[ 'options' ][ 'desired_data' ] ); ?>'/>
		</span>
		<?php if( $args[ 'label' ] ) { ?>
		<label for='<?php echo esc_attr( $args[ 'id' ] ); ?>'>
			<strong><?php echo esc_html( $args[ 'label' ] ); ?></strong>
		</label>
		<?php } ?>
	<?php
	}
	
	// TINYMCE editor
	else if( $args[ 'type' ] === 'editor' ) {
		wp_editor( $args[ 'value' ], $args[ 'id' ], $args[ 'options' ] );
	}

	// User ID
	else if( $args[ 'type' ] === 'user_id' ) {
		patips_display_user_selectbox( array_merge( $args, array( 'selected' => $args[ 'value' ] ), $args[ 'options' ] ) );
	}

	// Other field type
	else {
		do_action( 'patips_display_field_' . $args[ 'type' ], $args );
	}

	// Display the tip
	if( $args[ 'tip' ] ) {
		patips_tooltip( $args[ 'tip' ] );
	}
}


/**
 * Format arguments to display a proper field
 * @since 0.5.0
 * @version 0.25.6
 * @param array $raw_args ['type', 'name', 'label', 'id', 'class', 'placeholder', 'options', 'attr', 'value', 'multiple', 'tip', 'required']
 * @return array|false
 */
function patips_format_field_args( $raw_args ) {
	// If $args is not an array, return
	if( ! is_array( $raw_args ) ) { return false; }

	// If fields type or name are not set, return
	if( ! isset( $raw_args[ 'type' ] ) || ! isset( $raw_args[ 'name' ] ) ) { return false; }
	
	$default_args = array(
		'type'         => '',
		'name'         => '',
		'label'        => '',
		'id'           => '',
		'class'        => '',
		'placeholder'  => '',
		'options'      => array(),
		'attr'         => '',
		'value'        => '',
		'multiple'     => false,
		'tip'          => '',
		'required'     => 0,
		'autocomplete' => 0
	);
	
	$args = wp_parse_args( $raw_args, $default_args );
	
	// Sanitize id and name
	$args[ 'id' ] = sanitize_title_with_dashes( $args[ 'id' ] );
	
	// Generate a random id
	if( ! esc_attr( $args[ 'id' ] ) ) { $args[ 'id' ] = md5( wp_rand() ); }

	// Sanitize required
	$args[ 'required' ] = isset( $args[ 'required' ] ) && $args[ 'required' ] ? 1 : 0;
	if( $args[ 'required' ] ) { $args[ 'class' ] .= ' patips-required-field'; }

	// Make sure 'attr' is an array for fields with multiple options
	if( in_array( $args[ 'type' ], array( 'checkboxes', 'radio', 'select', 'user_id' ) ) ) {
		if( ! is_array( $args[ 'attr' ] ) ) { $args[ 'attr' ] = array(); }
	} else {
		if( ! is_string( $args[ 'attr' ] ) ) { $args[ 'attr' ] = ''; }
	}
	
	// Pass attributes to user_id field
	if( $args[ 'type' ] === 'user_id' ) {
		if( empty( $args[ 'options' ][ 'name' ] ) ) { $args[ 'options' ][ 'name' ] = $args[ 'name' ]; }
		if( empty( $args[ 'options' ][ 'id' ] ) && ! empty( $args[ 'id' ] ) ) { $args[ 'options' ][ 'id' ] = $args[ 'id' ] . '-selectbox'; }
	}
	
	// If multiple, make sure name has brackets and value is an array
	if( in_array( $args[ 'multiple' ], array( 'true', true, '1', 1 ), true ) ) {
		if( strpos( $args[ 'name' ], '[]' ) === false ) { $args[ 'name' ] .= '[]'; }
	}

	// Make sure checkboxes have their value as an array
	if( $args[ 'type' ] === 'checkboxes' || ( $args[ 'multiple' ] && $args[ 'type' ] !== 'file' ) ){
		if( ! is_array( $args[ 'value' ] ) ) { $args[ 'value' ] = $args[ 'value' ] !== '' ? array( $args[ 'value' ] ) : array(); }
	}

	// Make sure 'number' has min and max
	else if( in_array( $args[ 'type' ], array( 'number', 'date', 'time' ) ) ) {
		$args[ 'options' ][ 'min' ]  = isset( $args[ 'options' ][ 'min' ] ) ? $args[ 'options' ][ 'min' ] : '';
		$args[ 'options' ][ 'max' ]  = isset( $args[ 'options' ][ 'max' ] ) ? $args[ 'options' ][ 'max' ] : '';
		$args[ 'options' ][ 'step' ] = isset( $args[ 'options' ][ 'step' ] ) ? $args[ 'options' ][ 'step' ] : '';
	}

	// Make sure that if 'media' has options, options is an array
	else if( $args[ 'type' ] === 'media' ) {
		if( ! is_array( $args[ 'options' ] ) ) { $args[ 'options' ] = array(); }
		$args[ 'options' ][ 'preview' ]      = isset( $args[ 'options' ][ 'preview' ] ) ? ! empty( $args[ 'options' ][ 'preview' ] ) : true;
		$args[ 'options' ][ 'hide_input' ]   = isset( $args[ 'options' ][ 'hide_input' ] ) ? ! empty( $args[ 'options' ][ 'hide_input' ] ) : true;
		$args[ 'options' ][ 'desired_data' ] = ! empty( $args[ 'options' ][ 'desired_data' ] ) ? $args[ 'options' ][ 'desired_data' ] : 'url';
	}

	// Make sure that if 'editor' has options, options is an array
	else if( $args[ 'type' ] === 'editor' ) {
		if( ! is_array( $args[ 'options' ] ) ) { $args[ 'options' ] = array(); }
		$args[ 'options' ][ 'default_editor' ] = ! empty( $args[ 'options' ][ 'default_editor' ] ) ? sanitize_title_with_dashes( $args[ 'options' ][ 'default_editor' ] ) : 'html'; // Workaround to correctly load TinyMCE in dialogs
		$args[ 'options' ][ 'textarea_name' ]  = $args[ 'name' ];
		$args[ 'options' ][ 'editor_class' ]   = $args[ 'class' ];
		$args[ 'options' ][ 'editor_height' ]  = ! empty( $args[ 'height' ] ) ? intval( $args[ 'class' ] ) : 120;
	}

	return apply_filters( 'patips_formatted_field_args', $args, $raw_args );
}


/**
 * Display a user selectbox
 * @since 0.5.0
 * @version 0.25.5
 * @param array $raw_args
 * @return string|void
 */
function patips_display_user_selectbox( $raw_args ) {
	$defaults = array(
		'allow_tags' => 0, 'allow_clear' => 1, 'allow_current' => 0, 
		'option_label' => array( 'display_name' ), 'placeholder' => esc_html__( 'Search...', 'patrons-tips' ),
		'ajax' => 1, 'select2' => 1, 'sortable' => 0, 'echo' => 1,
		'selected' => array(), 'multiple' => 0, 'name' => 'user_id', 'class' => '', 'id' => '',
		'include' => array(), 'exclude' => array(),
		'role' => array(), 'role__in' => array(), 'role__not_in' => array(),
		'no_account' => false,
		'meta' => true, 'meta_single' => true,
		'orderby' => 'display_name', 'order' => 'ASC'
	);
	
	$args = apply_filters( 'patips_user_selectbox_args', wp_parse_args( $raw_args, $defaults ), $raw_args );
	
	$is_allowed = current_user_can( 'list_users' ) || current_user_can( 'edit_users' );
	$users = ! $args[ 'ajax' ] && $is_allowed ? patips_get_users_data( $args ) : array();
	$args[ 'class' ] = $args[ 'ajax' ] ? 'patips-select2-ajax ' . trim( $args[ 'class' ] ) : ( $args[ 'select2' ] ? 'patips-select2-no-ajax ' . trim( $args[ 'class' ] ) : trim( $args[ 'class' ] ) );
	
	// Format selected user ids
	if( ! is_array( $args[ 'selected' ] ) ) { $args[ 'selected' ] = $args[ 'selected' ] || in_array( $args[ 'selected' ], array( 0, '0' ), true ) ? array( $args[ 'selected' ] ) : array(); }
	$selected_user_ids = patips_ids_to_array( $args[ 'selected' ] );
	
	if( $args[ 'ajax' ] && $args[ 'selected' ] && $is_allowed ) {
		$selected_users = $selected_user_ids ? patips_get_users_data( array( 'include' => $selected_user_ids ) ) : array();
		if( $selected_users ) { $users = $selected_users; }
	}
	
	if( $args[ 'multiple' ] && strpos( $args[ 'name' ], '[]' ) === false ) { $args[ 'name' ] .= '[]'; } 
	
	$options = array();
	if( $users ) {
		foreach( $users as $user ) {
			$selected_key = array_search( intval( $user->ID ), $selected_user_ids, true );
			if( $selected_key !== false ) { unset( $selected_user_ids[ $selected_key ] ); }
			
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
					$value  = maybe_unserialize( $user->{ $show } );
					$label .= is_array( $value ) ? implode( ',', $value ) : $value;
				} else {
					$label .= $show;
				}
			}
			$options[] = array( 'id' => $user->ID, 'text' => $label, 'selected' => $selected_key !== false );
		}
	}
	
	$options = apply_filters( 'patips_user_selectbox_options', $options, $args, $raw_args );
	
	ob_start();
	?>
	<input type='hidden' name='<?php echo esc_attr( $args[ 'name' ] ); ?>' value=''/>
	<select <?php if( $args[ 'id' ] ) { echo 'id="' . esc_attr( $args[ 'id' ] ) . '"'; } ?> 
		name='<?php echo esc_attr( $args[ 'name' ] ); ?>' 
		class='patips-user-selectbox <?php echo esc_attr( $args[ 'class' ] ); ?>'
		data-tags='<?php echo ! empty( $args[ 'allow_tags' ] ) ? 1 : 0; ?>'
		data-allow-clear='<?php echo ! empty( $args[ 'allow_clear' ] ) ? 1 : 0; ?>'
		data-placeholder='<?php echo ! empty( $args[ 'placeholder' ] ) ? esc_attr( $args[ 'placeholder' ] ) : ''; ?>'
		data-sortable='<?php echo ! empty( $args[ 'sortable' ] ) ? 1 : 0; ?>'
		data-type='users'
		data-params='{"no_account":<?php echo ! empty( $args[ 'no_account' ] ) ? 1 : 0; ?>}'
		<?php if( $args[ 'multiple' ] ) { echo ' multiple'; } ?>>
		<?php if( ! $args[ 'multiple' ] ) {  ?>
			<option><!-- Used for the placeholder --></option>
		<?php
			}
			// Keep both numeric and string values
			foreach( $args[ 'selected' ] as $selected ) {
				if( $selected && ! is_numeric( $selected ) && is_string( $selected ) ) {
					$selected_user_ids[] = $selected;
				}
			}
			
			if( $args[ 'allow_current' ] ) {
				$selected_key = array_search( 'current', $selected_user_ids, true );
				if( $selected_key !== false ) { unset( $selected_user_ids[ $selected_key ] ); }
			?>
				<option value='current' <?php if( $selected_key !== false ) { echo 'selected'; } ?>><?php esc_html_e( 'Current user', 'patrons-tips' ); ?></option>
			<?php
			}

			do_action( 'patips_add_user_selectbox_options', $args, $users );
			
			if( $options ) {
				foreach( $options as $option ) {
				?>
					<option value='<?php echo esc_attr( $option[ 'id' ] ); ?>' <?php if( ! empty( $option[ 'selected' ] ) ) { echo 'selected'; } ?>><?php echo esc_html( $option[ 'text' ] ); ?></option>
				<?php
				}
			}
			
			if( $args[ 'allow_tags' ] && $selected_user_ids ) {
				foreach( $selected_user_ids as $selected_user_id ) {
				?>
					<option value='<?php echo esc_attr( $selected_user_id ); ?>' selected><?php echo esc_html( $selected_user_id ); ?></option>
				<?php
				}
			}
		?>
	</select>
	<?php
	$output = ob_get_clean();

	if( ! $args[ 'echo' ] ) { return $output; }
	echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}


/**
 * Display info tooltip
 * @since 0.5.0
 * @version 0.25.5
 * @param string $message
 */
function patips_tooltip( $message, $echo = true ){
	$html = "<span class='patips-icon-tooltip patips-tooltip' data-message='" . esc_attr( $message ) . "'></span>";
	
	if( $echo ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	
	return $html;
}


// SANITIZE

/**
 * Sanitize the values of an array
 * @since 0.5.0
 * @version 0.13.3
 * @param array $default_data
 * @param array $raw_data
 * @param array $keys_by_type
 * @param array $sanitized_data
 * @return array
 */
function patips_sanitize_values( $default_data, $raw_data, $keys_by_type, $sanitized_data = array() ) {
	// Sanitize the keys-by-type array
	$allowed_types = array( 'int', 'absint', 'float', 'absfloat', 'numeric', 'bool', 'str', 'str_id', 'url', 'str_html', 'email', 'color', 'array', 'array_ids', 'array_str_ids', 'datetime', 'date' );
	foreach( $allowed_types as $allowed_type ) {
		if( ! isset( $keys_by_type[ $allowed_type ] ) ) { $keys_by_type[ $allowed_type ] = array(); }
	}

	// Make an array of all keys that will be sanitized
	$keys_to_sanitize = array();
	foreach( $keys_by_type as $type => $keys ) {
		if( ! in_array( $type, $allowed_types, true ) ) { continue; }
		if( ! is_array( $keys ) ) { $keys_by_type[ $type ] = array( $keys ); }
		foreach( $keys as $key ) {
			$keys_to_sanitize[] = $key;
		}
	}

	// Format each value according to its type
	foreach( $default_data as $key => $default_value ) {
		// Do not process keys without types
		if( ! in_array( $key, $keys_to_sanitize, true ) ) { continue; }
		// Skip already sanitized values
		if( isset( $sanitized_data[ $key ] ) ) { continue; }
		// Set undefined values to default and continue
		if( ! isset( $raw_data[ $key ] ) ) { $sanitized_data[ $key ] = $default_value; continue; }

		// Sanitize integers
		if( in_array( $key, $keys_by_type[ 'int' ], true ) ) { 
			$sanitized_data[ $key ] = is_numeric( $raw_data[ $key ] ) ? intval( $raw_data[ $key ] ) : $default_value;
		}
		
		// Sanitize absolute integers
		if( in_array( $key, $keys_by_type[ 'absint' ], true ) ) { 
			$sanitized_data[ $key ] = is_numeric( $raw_data[ $key ] ) ? abs( intval( $raw_data[ $key ] ) ) : $default_value;
		}

		// Sanitize floats
		if( in_array( $key, $keys_by_type[ 'float' ], true ) ) { 
			$sanitized_data[ $key ] = is_numeric( $raw_data[ $key ] ) ? floatval( $raw_data[ $key ] ) : $default_value;
		}

		// Sanitize absolute floats
		if( in_array( $key, $keys_by_type[ 'absfloat' ], true ) ) { 
			$sanitized_data[ $key ] = is_numeric( $raw_data[ $key ] ) ? abs( floatval( $raw_data[ $key ] ) ) : $default_value;
		}

		// Sanitize numeric
		if( in_array( $key, $keys_by_type[ 'numeric' ], true ) ) { 
			$sanitized_data[ $key ] = is_numeric( $raw_data[ $key ] ) ? $raw_data[ $key ] : $default_value;
		}

		// Sanitize string identifiers
		else if( in_array( $key, $keys_by_type[ 'str_id' ], true ) ) { 
			$sanitized_data[ $key ] = is_string( $raw_data[ $key ] ) ? sanitize_title_with_dashes( stripslashes( $raw_data[ $key ] ) ) : $default_value;
		}

		// Sanitize text
		else if( in_array( $key, $keys_by_type[ 'str' ], true ) ) { 
			$sanitized_data[ $key ] = is_string( $raw_data[ $key ] ) ? sanitize_text_field( stripslashes( $raw_data[ $key ] ) ) : $default_value;
		}

		// Sanitize text with html
		else if( in_array( $key, $keys_by_type[ 'str_html' ], true ) ) { 
			$sanitized_data[ $key ] = is_string( $raw_data[ $key ] ) ? wp_kses_post( stripslashes( $raw_data[ $key ] ) ) : $default_value;
		}
		
		// Sanitize email
		else if( in_array( $key, $keys_by_type[ 'email' ], true ) ) { 
			$sanitized_data[ $key ] = is_string( $raw_data[ $key ] ) ? sanitize_email( stripslashes( $raw_data[ $key ] ) ) : $default_value;
		}
		
		// Sanitize URL
		else if( in_array( $key, $keys_by_type[ 'url' ], true ) ) { 
			$sanitized_data[ $key ] = is_string( $raw_data[ $key ] ) ? sanitize_url( stripslashes( $raw_data[ $key ] ) ) : $default_value;
		}
		
		// Sanitize hex color
		else if( in_array( $key, $keys_by_type[ 'color' ], true ) ) { 
			$sanitized_data[ $key ] = is_string( $raw_data[ $key ] ) ? sanitize_hex_color( stripslashes( $raw_data[ $key ] ) ) : $default_value;
		}

		// Sanitize array
		else if( in_array( $key, $keys_by_type[ 'array' ], true ) ) { 
			$sanitized_data[ $key ] = is_array( $raw_data[ $key ] ) ? $raw_data[ $key ] : $default_value;
		}

		// Sanitize array of ids
		else if( in_array( $key, $keys_by_type[ 'array_ids' ], true ) ) { 
			$sanitized_data[ $key ] = is_numeric( $raw_data[ $key ] ) || is_array( $raw_data[ $key ] ) ? patips_ids_to_array( $raw_data[ $key ] ) : $default_value;
		}

		// Sanitize array of str ids
		else if( in_array( $key, $keys_by_type[ 'array_str_ids' ], true ) ) { 
			$sanitized_data[ $key ] = is_string( $raw_data[ $key ] ) || is_array( $raw_data[ $key ] ) ? patips_str_ids_to_array( $raw_data[ $key ] ) : $default_value;
		}

		// Sanitize boolean
		else if( in_array( $key, $keys_by_type[ 'bool' ], true ) ) { 
			$sanitized_data[ $key ] = in_array( $raw_data[ $key ], array( 1, '1', true, 'true' ), true ) ? 1 : ( in_array( $raw_data[ $key ], array( 0, '0', false, 'false' ), true ) ? 0 : $default_value );
		}

		// Sanitize datetime
		else if( in_array( $key, $keys_by_type[ 'datetime' ], true ) ) { 
			$sanitized_data[ $key ] = is_string( $raw_data[ $key ] ) ? patips_sanitize_datetime( stripslashes( $raw_data[ $key ] ) ) : '';
			if( ! $sanitized_data[ $key ] ) { $sanitized_data[ $key ] = $default_value; }
		}

		// Sanitize date
		else if( in_array( $key, $keys_by_type[ 'date' ], true ) ) { 
			$sanitized_data[ $key ] = is_string( $raw_data[ $key ] ) ? patips_sanitize_date( stripslashes( $raw_data[ $key ] ) ) : '';
			if( ! $sanitized_data[ $key ] ) { $sanitized_data[ $key ] = $default_value; }
		}
	}

	return apply_filters( 'patips_sanitized_values', $sanitized_data, $default_data, $raw_data, $keys_by_type );
}