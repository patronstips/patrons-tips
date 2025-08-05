<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if( ! class_exists( 'PATIPS_Patrons_List_Table' ) ) { 
	
	/**
	 * Patrons WP_List_Table
	 * @since 0.6.0
	 * @version 0.26.3
	 */
	class PATIPS_Patrons_List_Table extends WP_List_Table {
		
		public $items;
		public $filters;
		protected $screen;
		
		/**
		 * Set up the Patron list table
		 * @access public
		 */
		public function __construct(){
			// This global variable is required to create screen
			if( ! isset( $GLOBALS[ 'hook_suffix' ] ) ) { $GLOBALS[ 'hook_suffix' ] = null; }
			
			parent::__construct( array(
				'singular' => 'patron',  // Singular name of the listed records
				'plural'   => 'patrons', // Plural name of the listed records
				'ajax'     => false,
				'screen'   => null
			));
			
			// Hide default columns
			add_filter( 'default_hidden_columns', array( $this, 'get_default_hidden_columns' ), 10, 2 );
		}
		
		
		/**
		 * Get patron list table columns
		 * @access public
		 * @return array
		 */
		public function get_columns(){
			// Set the columns
			$columns = array(
//				'cb'      => '<input type="checkbox" />',
				'id'      => esc_html__( 'ID', 'patrons-tips' ),
				'name'    => esc_html__( 'Patron', 'patrons-tips' ),
				'user'    => esc_html__( 'User', 'patrons-tips' ),
				'current' => esc_html__( 'Current tier', 'patrons-tips' ),
				'date'    => esc_html__( 'Creation date', 'patrons-tips' )
			);

			/**
			 * Columns of the patron list table
			 * You must use 'patips_patron_list_table_column_order' php filter to order your custom columns.
			 * You must use 'patips_patron_list_table_default_hidden_columns' php filter to hide your custom columns by default.
			 * You must use 'patips_patron_list_table_item' or 'patips_patron_list_table_items' php filter to fill your custom columns.
			 * 
			 * @param array $columns
			 */
			$columns = apply_filters( 'patips_patron_list_table_column_names', $columns );

			// Sort the columns
			$column_order = array(
//				10 => 'cb',
				20 => 'id',
				30 => 'name',
				40 => 'user',
				50 => 'current',
				70 => 'date'
			);

			/**
			 * Columns order of the patron list table
			 * Order the columns given by the filter 'patips_patron_list_table_columns'
			 * 
			 * @param array $columns
			 */
			$column_order = apply_filters( 'patips_patron_list_table_column_order', $column_order );

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
		 * @access public
		 * @param array $hidden
		 * @param WP_Screen $screen
		 * @return array
		 */
		public function get_default_hidden_columns( $hidden, $screen ) {
			if( $screen->id == $this->screen->id ) {
				$hidden = apply_filters( 'patips_patron_list_table_default_hidden_columns', array(
					'date'
				) );
			}
			return $hidden;
		}
		
		
		/**
		 * Get sortable columns
		 * @version 0.9.0
		 * @access public
		 * @return array
		 */
		protected function get_sortable_columns() {
			return apply_filters( 'patips_patron_list_table_sortable_columns', array(
				'id'   => array( 'id', true ),
				'name' => array( 'nickname', false ),
				'user' => array( 'user_id', false ),
				'date' => array( 'creation_date', true )
			) );
		}
		
		
		/**
		 * Get the number of rows to display per page
		 * @since 0.13.6
		 * @return int
		 */
		public function get_rows_number_per_page() {
			$screen_option  = $this->screen ? $this->screen->get_option( 'per_page', 'option' ) : '';
			$screen_default = $this->screen ? $this->screen->get_option( 'per_page', 'default' ) : 0;
			$option_name    = $screen_option ? $screen_option : 'patips_patrons_per_page';
			$option_default = $screen_default && intval( $screen_default ) > 0 ? intval( $screen_default ) : 20;
			$per_page       = $option_name ? $this->get_items_per_page( $option_name, $option_default ) : $option_default;
			
			return $per_page;
		}
		
		
		/**
		 * Prepare the items to be displayed in the list
		 * @version 0.13.6
		 * @access public
		 * @param array $filters
		 * @param boolean $no_pagination
		 */
		public function prepare_items( $filters = array(), $no_pagination = false ) {
			$this->get_column_info();
			$this->_column_headers[0] = $this->get_columns();
			
			$this->filters = $this->format_filters( $filters );
			
			if( ! $no_pagination ) {
				// Get the number of Patrons to display per page
				$per_page = $this->get_rows_number_per_page();
				
				// Set pagination
				$this->set_pagination_args( array(
					'total_items' => $this->get_total_items_count(),
					'per_page'    => $per_page
				) );
				
				$this->filters[ 'offset' ]   = ( $this->get_pagenum() - 1 ) * $per_page;
				$this->filters[ 'per_page' ] = $per_page;
			}
			
			$items = $this->get_patron_list_items();
			
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
		 * Fill "Name" column and add action buttons
		 * @version 0.25.5
		 * @access public
		 * @param array $item
		 * @return string
		 */
		public function column_name( $item ) {
			$patron_id = $item[ 'id' ];
			$actions = array();
			
			if( current_user_can( 'patips_edit_patrons' ) ) {
				if( $item[ 'active_raw' ] ) {
					$actions[ 'edit' ]        = '<a href="' . esc_url( admin_url( 'admin.php?page=patips_patrons&action=edit&patron_id=' . $patron_id ) ) . '" >' . esc_html__( 'Edit', 'patrons-tips' ) . '</a>';
				}
				if( current_user_can( 'patips_delete_patrons' ) ) {
					if( $item[ 'active_raw' ] ) {
						$actions[ 'trash' ]   = '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?page=patips_patrons&action=trash&patron_id=' . $patron_id ), 'trash-patron_' . $patron_id ) ) . '" >' . esc_html_x( 'Trash', 'verb', 'patrons-tips' ) . '</a>';
					} else { 
						$actions[ 'restore' ] = '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?page=patips_patrons&action=restore&patron_id=' . $patron_id ), 'restore-patron_' . $patron_id ) ) . '" >' . esc_html__( 'Restore', 'patrons-tips' ) . '</a>';
						$actions[ 'delete' ]  = '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin.php?page=patips_patrons&active=0&action=delete&patron_id=' . $patron_id ), 'delete-patron_' . $patron_id ) ) . '" >' . esc_html__( 'Delete Permanently', 'patrons-tips' ) . '</a>';
					}
				}
			}
			
			// Add primary data for responsive views
			$primary_column_name = $this->get_primary_column();
			$primary_data_html = '';
			if( $primary_column_name === 'name' && ! empty( $item[ 'primary_data_html' ] ) ) {
				$primary_data_html = $item[ 'primary_data_html' ];
			}
			
			// Add a span and a class to each action
			$actions = apply_filters( 'patips_patron_list_table_row_actions', $actions, $item );
			foreach( $actions as $action_id => $link ) {
				$actions[ $action_id ] = '<span class="' . $action_id . '">' . $link . '</span>';
			}
			
			return sprintf( '%1$s%2$s %3$s', $item[ 'name' ], $primary_data_html, $this->row_actions( $actions, false ) );
		}
		
		
		/**
		 * Get patron list table items. Parameters can be passed in the URL.
		 * @version 0.25.4
		 * @access public
		 * @return array
		 */
		public function get_patron_list_items() {
			// Request patrons corresponding to filters
			$patrons = patips_get_patrons_data( $this->filters );
			$tiers   = patips_get_tiers_data();
			
			$can_edit_patrons = current_user_can( 'patips_edit_patrons' );
			$can_edit_users   = current_user_can( 'edit_users' );
			
			// Get date format
			$date_format      = get_option( 'date_format' );
			$utc_timezone_obj = new DateTimeZone( 'UTC' );
			$timezone_obj     = patips_get_wp_timezone();
			$now_dt           = new DateTime( 'now', $timezone_obj );
			
			// Build patron list
			$patron_list_items = array();
			foreach( $patrons as $patron ) {
				// User column
				$user      = $patron[ 'user_id' ] ? get_user_by( 'id', $patron[ 'user_id' ] ) : null;
				$user_name = esc_html__( 'No account', 'patrons-tips' );
				if( $user ) {
					$user_name = $user->user_login;
					if( $can_edit_users ) {
						$user_name = '<a href="' . get_edit_user_link( $user->ID ) . '">' . $user_name . '</a>';
					}
				} else if( is_email( $patron[ 'user_email' ] ) ) {
					$user_name .= ' (' . $patron[ 'user_email' ] . ')';
				} else if( $patron[ 'user_id' ] ) {
					/* translators: %s = integer */
					$user_name .= ' (' . sprintf( esc_html__( 'User #%s', 'patrons-tips' ), $patron[ 'user_id' ] ) . ')';
				}
				
				// Name column
				$name = patips_get_patron_nickname( $patron, 'admin' );
				if( $can_edit_patrons ) {
					$name = '<a href="' . esc_url( admin_url( 'admin.php?page=patips_patrons&action=edit&patron_id=' . $patron[ 'id' ] ) ) . '" >' . $name . '</a>';
				}
				
				// Creation date
				$creation_date_raw = patips_sanitize_datetime( $patron[ 'creation_date' ] );
				$creation_date_dt  = new DateTime( $creation_date_raw, $utc_timezone_obj );
				$creation_date_dt->setTimezone( $timezone_obj );
				$creation_date = $creation_date_raw ? patips_format_datetime( $creation_date_dt->format( 'Y-m-d H:i:s' ), $date_format ) : '';
				$creation_date = $creation_date ? '<span title="' . esc_attr( $creation_date_raw ) . '">' . $creation_date . '</span>' : '';
				
				// Get current tiers
				$current_tiers = array();
				$current = '';
				foreach( $patron[ 'history' ] as $history_entry ) {
					$start_dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $history_entry[ 'date_start' ] . ' 00:00:00', $timezone_obj );
					$end_dt   = DateTime::createFromFormat( 'Y-m-d H:i:s', $history_entry[ 'date_end' ] . ' 23:59:59', $timezone_obj );
					if( $history_entry[ 'active' ] && $start_dt <= $now_dt && $end_dt >= $now_dt ) {
						$current_tiers[] = $history_entry;
						
						if( $current ) { $current .= '<br/>'; }
						/* translators: %s is the tier ID */
						$current .= '<strong>' . ( ! empty( $tiers[ $history_entry[ 'tier_id' ] ] ) ? $tiers[ $history_entry[ 'tier_id' ] ][ 'title' ] : sprintf( esc_html__( 'Tier #%s', 'patrons-tips' ), $history_entry[ 'tier_id' ] ) ) . '</strong>';
						/* translators: %s = formatted date (e.g.: July 21st, 2024) */
						$current .= ' (' . sprintf( esc_html__( 'until %s', 'patrons-tips' ), '<em>' . patips_format_datetime( $end_dt->format( 'Y-m-d h:i:s' ) ) . '</em>' ) . ')';
					}
				}
				
				// Add info on the primary column to make them directly visible in responsive view
				$primary_data = array( 
					'<span class="patips-column-id" >(' . esc_html__( 'id', 'patrons-tips' ) . ': ' . $patron[ 'id' ] . ')</span>'
				);
				$primary_data_html = '';
				if( $primary_data ) {
					$primary_data_html = '<div class="patips-primary-data-container">';
					foreach( $primary_data as $single_primary_data ) {
						$primary_data_html .= '<span class="patips-primary-data">' . $single_primary_data . '</span>';
					}
					$primary_data_html .= '</div>';
				}
				
				$patron_item = apply_filters( 'patips_patron_list_table_item', array( 
					'id'                => $patron[ 'id' ],
					'name'              => $name,
					'user'              => $user_name,
					'date'              => $creation_date,
					'current'           => $current,
					'active_raw'        => $patron[ 'active' ],
					'current_tiers'     => $current_tiers,
					'primary_data'      => $primary_data,
					'primary_data_html' => $primary_data_html
				), $patron );
				
				$patron_list_items[] = $patron_item;
			}
			
			return apply_filters( 'patips_patron_list_table_items', $patron_list_items, $patrons, $this );
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
				$filters = patips_get_default_patron_filters();
				foreach( $filters as $key => $default_value ) {
					if( isset( $_REQUEST[ $key ] ) ) {
						$filters[ $key ] = wp_unslash( $_REQUEST[ $key ] );
					}
				}
			}
			
			// Format filters before making the request
			$filters = patips_format_patron_filters( $filters );
			
			if( $filters[ 'active' ] === false ) {
				$filters[ 'active' ] = 1;
			}
			
			return $filters;
		}
		
		
		/**
		 * Get the total amount of patrons according to filters
		 * @access public
		 * @return int
		 */
		public function get_total_items_count() {
			return patips_get_number_of_patron_rows( $this->filters );
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
		 * @version 0.25.5
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
			
			$published_count = patips_get_number_of_patron_rows( $published_filter );
			$trash_count     = patips_get_number_of_patron_rows( $trash_filter );
			
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
			return apply_filters( 'patips_patron_list_table_primary_column', 'name', $this->screen );
		}
		
		
		/**
		 * Display pagination inside a form to allow to jump to a page
		 * @param string $which
		 */
		protected function pagination( $which ) {
			if( $which !== 'top' ) { parent::pagination( $which ); return; }
			?>
			<form action='<?php echo esc_url( add_query_arg( 'paged', '%d' ) ); ?>' class='patips-list-table-go-to-page-form'>
				<input type='hidden' name='page' value='patips_patrons'/>
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