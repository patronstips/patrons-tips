<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if( ! class_exists( 'PATIPS_Tiers_List_Table' ) ) { 
	
	/**
	 * Tiers WP_List_Table
	 * @since 0.5.0
	 * @version 1.0.4
	 */
	class PATIPS_Tiers_List_Table extends WP_List_Table {
		
		public $items;
		public $filters;
		protected $screen;
		
		/**
		 * Set up the Tier list table
		 * @access public
		 */
		public function __construct(){
			// This global variable is required to create screen
			if( ! isset( $GLOBALS[ 'hook_suffix' ] ) ) { $GLOBALS[ 'hook_suffix' ] = null; }
			
			parent::__construct( array(
				'singular' => 'tier',  // Singular name of the listed records
				'plural'   => 'tiers', // Plural name of the listed records
				'ajax'     => false,
				'screen'   => null
			));
			
			// Hide default columns
			add_filter( 'default_hidden_columns', array( $this, 'get_default_hidden_columns' ), 10, 2 );
		}
		
		
		/**
		 * Get tier list table columns
		 * @version 0.23.0
		 * @access public
		 * @return array
		 */
		public function get_columns(){
			// Set the columns
			$columns = array(
//				'cb'         => '<input type="checkbox" />',
				'id'         => esc_html__( 'ID', 'patrons-tips' ),
				'title'      => esc_html__( 'Title', 'patrons-tips' ),
				'price'      => esc_html__( 'Price', 'patrons-tips' ),
				'categories' => esc_html__( 'Restricted categories', 'patrons-tips' ),
				'current'    => esc_html__( 'Current patrons', 'patrons-tips' ),
				'all_time'   => esc_html__( 'All-time patrons', 'patrons-tips' ),
				'author'     => esc_html__( 'Author', 'patrons-tips' ),
				'date'       => esc_html__( 'Date', 'patrons-tips' )
			);

			/**
			 * Columns of the tier list
			 * You must use 'patips_tier_list_table_column_order' php filter to order your custom columns.
			 * You must use 'patips_tier_list_table_default_hidden_columns' php filter to hide your custom columns by default.
			 * You must use 'patips_tier_list_table_item' or 'patips_tier_list_table_items' php filter to fill your custom columns.
			 * 
			 * @param array $columns
			 */
			$columns = apply_filters( 'patips_tier_list_table_column_names', $columns );

			// Sort the columns
			$column_order = array(
//				10 => 'cb',
				20 => 'id',
				30 => 'title',
				40 => 'price',
				50 => 'categories',
				60 => 'current',
				70 => 'all_time',
				80 => 'author',
				90 => 'date'
			);

			/**
			 * Columns order of the tier list
			 * Order the columns given by the filter 'patips_tier_list_table_columns'
			 * 
			 * @param array $columns
			 */
			$column_order = apply_filters( 'patips_tier_list_table_column_order', $column_order );

			ksort( $column_order );

			$displayed_columns = array();
			foreach( $column_order as $column_id ) {
				$displayed_columns[ $column_id ] = $columns[ $column_id ];
			}

			// Return the columns
			return $displayed_columns;
		}
		
		
		/**
		 * Get default hidden columns
		 * @version 0.23.0
		 * @access public
		 * @param array $hidden
		 * @param WP_Screen $screen
		 * @return array
		 */
		public function get_default_hidden_columns( $hidden, $screen ) {
			if( $screen->id == $this->screen->id ) {
				$hidden = apply_filters( 'patips_tier_list_table_default_hidden_columns', array(
					'author', 'date'
				) );
			}
			return $hidden;
		}
		
		
		/**
		 * Get sortable columns
		 * @version 0.23.0
		 * @access public
		 * @return array
		 */
		protected function get_sortable_columns() {
			return array(
				'id'     => array( 'id', true ),
				'title'  => array( 'title', false ),
				'price'  => array( 'price', false ),
				'author' => array( 'user_id', false ),
				'date'   => array( 'creation_date', false )
			);
		}
		
		
		/**
		 * Get the screen property
		 * @access public
		 * @return WP_Screen
		 */
		private function get_wp_screen() {
		   if( empty( $this->screen ) ) {
			  $this->screen = get_current_screen();
		   }
		   return $this->screen;
		}
		
		
		/**
		 * Prepare the items to be displayed in the list table
		 * @access public
		 * @param array $filters
		 * @param boolean $no_pagination
		 */
		public function prepare_items( $filters = array(), $no_pagination = false ) {
			$this->get_column_info();
			$this->_column_headers[0] = $this->get_columns();
			
			$this->filters = $this->format_filters( $filters );
			
			if( ! $no_pagination ) {
				// Get the number of Tiers to display per page
				$screen        = $this->get_wp_screen();
				$screen_option = $screen->get_option( 'per_page', 'option' );
				$per_page      = intval( get_user_meta( get_current_user_id(), $screen_option, true ) );
				if( empty ( $per_page ) || $per_page < 1 ) {
					$per_page = $screen->get_option( 'per_page', 'default' );
				}

				// Set pagination
				$this->set_pagination_args( array(
					'total_items' => $this->get_total_items_count(),
					'per_page'    => $per_page
				) );

				$this->filters[ 'offset' ]   = ( $this->get_pagenum() - 1 ) * $per_page;
				$this->filters[ 'per_page' ] = $per_page;
			}
			
			$items = $this->get_tier_list_items();
			
			$this->items = $items;
		}

		
		/**
		 * Fill columns
		 * @access public
		 * @param array $item
		 * @param string $column_name
		 * @return string
		 */
		public function column_default( $item, $column_name ) {
			$column_content = isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
			
			// Add primary data for responsive views
			$primary_column_name = $this->get_primary_column();
			if( $column_name === $primary_column_name && ! empty( $item[ 'primary_data_html' ] ) ) {
				$column_content .= $item[ 'primary_data_html' ];
			}
			
			return $column_content;
		}
		
		
		/**
		 * Fill "Title" column and add action buttons
		 * @version 0.25.5
		 * @access public
		 * @param array $item
		 * @return string
		 */
		public function column_title( $item ) {
			$tier_id = $item[ 'id' ];
			$actions = array();
			
			if( current_user_can( 'patips_edit_tiers' ) ) {
				if( $item[ 'active_raw' ] ) {
					$actions[ 'edit' ]        = '<a href="' . esc_url( admin_url( 'admin.php?page=patips_tiers&action=edit&tier_id=' . $tier_id ) ) . '" >' . esc_html__( 'Edit', 'patrons-tips' ) . '</a>';
				}
				if( current_user_can( 'patips_delete_tiers' ) ) {
					if( $item[ 'active_raw' ] ) {
						$actions[ 'trash' ]   = '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?page=patips_tiers&action=trash&tier_id=' . $tier_id ), 'trash-tier_' . $tier_id ) ) . '" >' . esc_html_x( 'Trash', 'verb', 'patrons-tips' ) . '</a>';
					} else { 
						$actions[ 'restore' ] = '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?page=patips_tiers&action=restore&tier_id=' . $tier_id ), 'restore-tier_' . $tier_id ) ) . '" >' . esc_html__( 'Restore', 'patrons-tips' ) . '</a>';
						$actions[ 'delete' ]  = '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?page=patips_tiers&active=0&action=delete&tier_id=' . $tier_id ), 'delete-tier_' . $tier_id ) ) . '" >' . esc_html__( 'Delete Permanently', 'patrons-tips' ) . '</a>';
					}
				}
			}
			
			// Add primary data for responsive views
			$primary_column_name = $this->get_primary_column();
			$primary_data_html = '';
			if( $primary_column_name === 'title' && ! empty( $item[ 'primary_data_html' ] ) ) {
				$primary_data_html = $item[ 'primary_data_html' ];
			}
			
			// Add a span and a class to each action
			$actions = apply_filters( 'patips_tier_list_table_row_actions', $actions, $item );
			foreach( $actions as $action_id => $link ) {
				$actions[ $action_id ] = '<span class="' . $action_id . '">' . $link . '</span>';
			}
			
			return sprintf( '%1$s%2$s %3$s', $item[ 'title' ], $primary_data_html, $this->row_actions( $actions, false ) );
		}
		
		
		/**
		 * Get tier list table items. Parameters can be passed in the URL.
		 * @version 1.0.4
		 * @access public
		 * @return array
		 */
		public function get_tier_list_items() {
			// Request tiers corresponding to filters
			$tiers            = patips_get_tiers_data( $this->filters );
			$tiers_nb         = patips_get_tiers_count();
			$restricted_terms = patips_get_restricted_terms();
			
			$can_edit_tiers     = current_user_can( 'patips_edit_tiers' );
			$can_edit_users     = current_user_can( 'edit_users' );
			$can_manage_patrons = current_user_can( 'patips_manage_patrons' );
			$can_manage_terms   = current_user_can( 'manage_categories' );
			
			// Get date format
			$date_format      = get_option( 'date_format' );
			$utc_timezone_obj = new DateTimeZone( 'UTC' );
			$timezone_obj     = patips_get_wp_timezone();
			
			// Build tier list
			$tier_list_items = array();
			foreach( $tiers as $tier ) {
				$tier_id = $tier[ 'id' ];
				/* translators: %s is the tier ID */
				$title   = ! empty( $tier[ 'title' ] ) ? $tier[ 'title' ] : sprintf( esc_html__( 'Tier #%s', 'patrons-tips' ), $tier_id );
				$title   = '<span class="patips-tier-title">' . $title . '</span>';
				
				// Add icon
				if( $tier[ 'icon_id' ] ) {
					$img = wp_get_attachment_image( $tier[ 'icon_id' ], 'thumbnail', true, array( 'class' => 'attachment-thumbnail size-thumbnail patips-tier-icon' ) );
					if( $img ) {
						$title = '<span class="patips-tier-icon-container">' . $img . '</span>' . $title;
					}
				}
				
				// Format title column
				if( $can_edit_tiers ) {
					$title = '<a href="' . esc_url( admin_url( 'admin.php?page=patips_tiers&action=edit&tier_id=' . $tier_id ) ) . '" class="patips-tier-title-container">' . $title . '</a>';
				} else {
					$title = '<span class="patips-tier-title-container">' . $title . '</span>';
				}
				
				// Restricted categories
				$categories = '';
				if( ! empty( $tier[ 'term_ids' ][ 'active' ] ) ) {
					$term_titles = array();
					foreach( $tier[ 'term_ids' ][ 'active' ] as $term_id ) {
						$term          = ! empty( $restricted_terms[ $term_id ][ 'term' ] ) ? $restricted_terms[ $term_id ][ 'term' ] : null;
						/* translators: %s = term ID */
						$term_name     = $term && isset( $term->name ) ? $term->name : sprintf( esc_html__( 'Term #%s', 'patrons-tips' ), $term_id );
						$term_url      = get_edit_term_link( $term ? $term : $term_id );
						$term_title    = $can_manage_terms && $term_url ? '<a href="' . $term_url . '">' . $term_name . '</a>' : $term_name;
						$term_titles[] = $term_title;
					}
					
					$categories = 
					  '<div>'
					.   '<strong>' . esc_html__( 'For current patrons:', 'patrons-tips' ) . '</strong>'
					.   '<p><em>' . implode( '</em>, <em>', $term_titles ) . '</em></p>'
					. '</div>';
				}
				
				// Count current / all-time patrons nb
				$current_nb  = isset( $tiers_nb[ $tier_id ] ) ? intval( $tiers_nb[ $tier_id ]->current ) : 0;
				$all_time_nb = isset( $tiers_nb[ $tier_id ] ) ? intval( $tiers_nb[ $tier_id ]->all_time ) : 0;
				/* translators: %s = a number. */
				$current     = sprintf( esc_html( _n( '%s patron', '%s patrons', $current_nb, 'patrons-tips' ) ), '<strong>' . $current_nb . '</strong>' );
				/* translators: %s = a number. */
				$all_time    = sprintf( esc_html( _n( '%s patron', '%s patrons', $all_time_nb, 'patrons-tips' ) ), '<strong>' . $all_time_nb . '</strong>' );
				if( $can_manage_patrons ) {
					if( $current_nb ) {
						$current  = '<a href="' . esc_url( admin_url( 'admin.php?page=patips_patrons&current=1&tier_ids%5B0%5D=' . $tier_id ) ) . '">' . $current . '</a>';
					}
					if( $all_time_nb ) {
						$all_time = '<a href="' . esc_url( admin_url( 'admin.php?page=patips_patrons&tier_ids%5B0%5D=' . $tier_id ) ) . '">' . $all_time . '</a>';
					}
				}
				
				// Author name
				$user_id     = intval( $tier[ 'user_id' ] );
				$user_object = get_user_by( 'id', $user_id );
				$author      = $user_object ? $user_object->display_name : $user_id;
				if( $can_edit_users && $user_id ) {
					$author = '<a href="' . get_edit_user_link( $user_id ) . '">' . $author . '</a>';
				}
				
				// Creation date
				$creation_date_raw = patips_sanitize_datetime( $tier[ 'creation_date' ] );
				$creation_date_dt  = new DateTime( $creation_date_raw, $utc_timezone_obj );
				$creation_date_dt->setTimezone( $timezone_obj );
				$creation_date = $creation_date_raw ? patips_format_datetime( $creation_date_dt->format( 'Y-m-d H:i:s' ), $date_format ) : '';
				$creation_date = $creation_date ? '<span title="' . esc_attr( $creation_date_raw ) . '">' . $creation_date . '</span>' : '';
				
				
				// Add info on the primary column to make them directly visible in responsive view
				$primary_data = array( 
					'<span class="patips-column-id" >(' . esc_html__( 'id', 'patrons-tips' ) . ': ' . $tier_id . ')</span>'
				);
				$primary_data_html = '';
				if( $primary_data ) {
					$primary_data_html = '<div class="patips-primary-data-container">';
					foreach( $primary_data as $single_primary_data ) {
						$primary_data_html .= '<span class="patips-primary-data">' . $single_primary_data . '</span>';
					}
					$primary_data_html .= '</div>';
				}
				
				$tier_item = apply_filters( 'patips_tier_list_table_item', array( 
					'id'                => $tier_id,
					'title'             => $title,
					'price'             => $tier[ 'price' ] ? patips_format_price( $tier[ 'price' ] ) : '',
					'categories'        => $categories,
					'current'           => $current,
					'current_nb'        => $current_nb,
					'all_time'          => $all_time,
					'all_time_nb'       => $all_time_nb,
					'author'            => $author,
					'date'              => $creation_date,
					'active_raw'        => $tier[ 'active' ],
					'primary_data'      => $primary_data,
					'primary_data_html' => $primary_data_html
				), $tier );
				
				$tier_list_items[] = $tier_item;
			}
			
			return apply_filters( 'patips_tier_list_table_items', $tier_list_items, $tiers );
		}
		
		
		/**
		 * Format filters passed as argument or retrieved via POST or GET
		 * @version 0.26.3
		 * @access public
		 * @param array $filters
		 * @return array
		 */
		public function format_filters( $filters = array() ) {
			// Get filters from URL if no filter was directly passed
			if( ! $filters ) {
				$filters = patips_get_default_tier_filters();
				foreach( $filters as $key => $default_value ) {
					if( isset( $_REQUEST[ $key ] ) ) {
						$filters[ $key ] = wp_unslash( $_REQUEST[ $key ] );
					}
				}
			}
			
			// Format filters before making the request
			$filters = patips_format_tier_filters( $filters );
			
			if( $filters[ 'active' ] === false ) {
				$filters[ 'active' ] = 1;
			}
			
			return $filters;
		}
		
		
		/**
		 * Get the total amount of tiers according to filters
		 * @access public
		 * @return int
		 */
		public function get_total_items_count() {
			return patips_get_number_of_tier_rows( $this->filters );
		}
		
		
		/**
		 * Get the tbody element for the list table
		 * @access public
		 * @return string
		 */
		public function get_rows_or_placeholder() {
			if ( $this->has_items() ) {
				return $this->get_rows();
			} else {
				return '<tr class="no-items"><td class="colspanchange" colspan="' . esc_attr( $this->get_column_count() ) . '">' . esc_html__( 'No items found.', 'patrons-tips' ) . '</td></tr>';
			}
		}
		
		
		/**
		 * Generate the table rows
		 * @access public
		 * @return string
		 */
		public function get_rows() {
			$rows = '';
			foreach ( $this->items as $item ) {
				$rows .= $this->get_single_row( $item );
			}
			return $rows;
		}
		
		
		/**
		 * Returns content for a single row of the table
		 * @access public
		 * @param array $item The current item
		 * @return string
		 */
		public function get_single_row( $item ) {
			$row  = '<tr>';
			$row .= $this->get_single_row_columns( $item );
			$row .= '</tr>';
			
			return $row;
		}
		
		/**
		 * Returns the columns for a single row of the table
		 * 
		 * @access public
		 * @param object $item The current item
		 */
		public function get_single_row_columns( $item ) {
			
			list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();
			
			$returned_columns = '';
			foreach ( $columns as $column_name => $column_display_name ) {
				$classes = "$column_name column-$column_name";
				if ( $primary === $column_name ) {
					$classes .= ' has-row-actions column-primary';
				}

				if ( in_array( $column_name, $hidden, true ) ) {
					$classes .= ' hidden';
				}

				// Comments column uses HTML in the display name with screen reader text.
				// Instead of using esc_attr(), we strip tags to get closer to a user-friendly string.
				$data = 'data-colname="' . wp_strip_all_tags( $column_display_name ) . '"';

				$attributes = "class='$classes' $data";
				
				if ( 'cb' === $column_name ) {
					$returned_columns .= '<th scope="row" class="check-column">';
					$returned_columns .=  $this->column_cb( $item );
					$returned_columns .=  '</th>';
				} elseif ( method_exists( $this, '_column_' . $column_name ) ) {
					$returned_columns .=  call_user_func(
						array( $this, '_column_' . $column_name ),
						$item,
						$classes,
						$data,
						$primary
					);
				} elseif ( method_exists( $this, 'column_' . $column_name ) ) {
					$returned_columns .=  "<td $attributes>";
					$returned_columns .=  call_user_func( array( $this, 'column_' . $column_name ), $item );
					$returned_columns .=  $this->handle_row_actions( $item, $column_name, $primary );
					$returned_columns .=  "</td>";
				} else {
					$returned_columns .=  "<td $attributes>";
					$returned_columns .=  $this->column_default( $item, $column_name );
					$returned_columns .=  $this->handle_row_actions( $item, $column_name, $primary );
					$returned_columns .=  "</td>";
				}
			}
			
			return $returned_columns;
		}
		
		
		/**
		 * Display content for a single row of the table
		 * 
		 * @access public
		 * @param array $item The current item
		 */
		public function single_row( $item ) {
			echo '<tr>';
			$this->single_row_columns( $item );
			echo '</tr>';
		}

		
		/**
		 * Get an associative array ( id => link ) with the list of views available on this table
		 * @return array
		 */
		protected function get_views() {
			$published_current = 'current';
			$trash_current     = '';
			if( isset( $_GET[ 'active' ] ) && $_GET[ 'active' ] == 0 ) { 
				$published_current = '';
				$trash_current     = 'current';
			}
			
			$filters          = $this->format_filters();
			$published_filter = $filters; $published_filter[ 'active' ] = 1;
			$trash_filter     = $filters; $trash_filter[ 'active' ] = 0;
			
			$published_count = patips_get_number_of_tier_rows( $published_filter );
			$trash_count     = patips_get_number_of_tier_rows( $trash_filter );
			
			return array(
				'published' => '<a href="' . esc_url( remove_query_arg( array( 'action', 'active' ) ) ) . '" class="' . $published_current . '" >' . esc_html__( 'Published', 'patrons-tips' ) . ' <span class="count">(' . $published_count . ')</span></a>',
				'trash'     => '<a href="' . esc_url( add_query_arg( array( 'active' => 0 ), remove_query_arg( array( 'action' ) ) ) ) . '" class="' . $trash_current . '" >' . esc_html_x( 'Trash', 'noun', 'patrons-tips' ) . ' <span class="count">(' . $trash_count . ')</span></a>'
			);
		}
		
		
		/**
		 * Generate row actions div
		 * @access protected
		 * @param array $actions
		 * @param bool $always_visible
		 * @return string
		 */
		protected function row_actions( $actions, $always_visible = false ) {
			$action_count = count( $actions );
			$i = 0;

			if( ! $action_count ) { return ''; }

			$class_visible = $always_visible ? 'visible' : '';
			$out = '<div class="row-actions ' . esc_attr( $class_visible ) . '">';
			foreach ( $actions as $action => $link ) {
				++$i;
				$sep  = $i == $action_count ? '' : ' | ';
				$out .= $link . $sep;
			}
			$out .= '</div>';

			return $out;
		}
		
		
		/**
		 * Get default primary column name
		 * @access public
		 * @return string
		 */
		public function get_default_primary_column_name() {
			return apply_filters( 'patips_tier_list_table_primary_column', 'title', $this->screen );
		}
		
		
		/**
		 * Display pagination inside a form to allow to jump to a page
		 * @param string $which
		 */
		protected function pagination( $which ) {
			if( $which !== 'top' ) { parent::pagination( $which ); return; }
			?>
			<form action='<?php echo esc_url( add_query_arg( 'paged', '%d' ) ); ?>' class='patips-list-table-go-to-page-form'>
				<input type='hidden' name='page' value='patips_tiers'/>
				<?php parent::pagination( $which ); ?>
			</form>
			<?php 
		}
		
		
		/**
		 * Gets a list of CSS classes for the WP_List_Table table tag.
		 * @return string[] Array of CSS classes for the table tag.
		 */
		protected function get_table_classes() {
			$classes = parent::get_table_classes();
			$classes[] = 'patips-list-table';
			return $classes;
		}
	}
}