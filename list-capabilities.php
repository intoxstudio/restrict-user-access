<?php
/**
 * @package Restrict User Access
 * @copyright Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 */

if (!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

final class RUA_Capabilities_List extends WP_List_Table {

	/**
	 * Constructor
	 *
	 * @since 0.4
	 */
	public function __construct() {
		parent::__construct(array(
			'singular' => __( 'Capability', RUA_App::DOMAIN ),
			'plural'   => __( 'Capabilities', RUA_App::DOMAIN ), 
			'ajax'     => false,
			'screen'   => RUA_App::TYPE_RESTRICT."_caps"
		));
	}

	/**
	 * Text for no members
	 *
	 * @since  0.4
	 * @return void
	 */
	public function no_items() {
		_e( 'No capabilities found.', RUA_App::DOMAIN );
	}

	/**
	 * Table columns with titles
	 *
	 * @since  0.4
	 * @return array
	 */
	public function get_columns() {
		return array(
			'name'       => __( 'Capability'),
			'permit'     => __("Permit"),
			'deny'       => __("Deny")
		);
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array();
	}

	/**
	 * Primary column used for responsive view
	 *
	 * @since  0.4
	 * @return string
	 */
	protected function get_default_primary_column_name() {
		return 'name';
	}

	/**
	 * Default fallback for column render
	 *
	 * @since  0.4
	 * @param  WP_User  $item
	 * @param  string  $column_name
	 * @return mixed
	 */
	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Render name column
	 *
	 * @since  0.4
	 * @param  WP_User  $user
	 * @return string
	 */
	protected function column_name( $name ) {
		return '<strong>'.$name.'</strong>';
	}


	/**
	 * Render checkbox column
	 *
	 * @since  0.4
	 * @param  WP_User  $user
	 * @return string
	 */
	protected function column_permit( $name ) {
		return $this->_column_cap($name,1);
	}

	/**
	 * Render checkbox column
	 *
	 * @since  0.4
	 * @param  WP_User  $user
	 * @return string
	 */
	protected function column_deny( $name ) {
		return $this->_column_cap($name,0);
	}

	protected function _column_cap($name,$value) {
		$metadata = RUA_App::instance()->level_manager->metadata()->get("caps")->get_data(get_the_ID());
		return sprintf(
			'<label class="rua-cb"><input type="checkbox" name="caps[%s]" value="%d" %s/><div></div></label>',
			$name,
			$value,
			checked( isset($metadata[$name]) ? $metadata[$name] : null, $value, false )
		);
	}

	/**
	 * Bulk actions
	 *
	 * @since  0.4
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array();
	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @since 0.4
	 * @param string $which
	 */
	protected function display_tablenav( $which ) {
		?>
	<div class="tablenav <?php echo esc_attr( $which ); ?>">

		<?php if ( $this->has_items() ): ?>
		<div class="alignleft actions bulkactions">
			<?php $this->bulk_actions( $which ); ?>
		</div>
		<?php endif;
		$this->extra_tablenav( $which );
		$this->pagination( $which );
?>

		<br class="clear" />
	</div>
<?php
	}

	public function print_column_headers( $with_id = true ) {
		parent::print_column_headers($with_id);
		if($with_id) {
			$sep = "</tr><tr>";

			$sum_columns = array(
				"deny" => 0,
				"permit" => 1
			);
			list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

			echo $sep;
			foreach ($columns as $column_key => $column_display) {
				
				$class = array( 'manage-column', "column-$column_key" );

				if ( in_array( $column_key, $hidden ) ) {
					$class[] = 'hidden';
				}

				if(isset($sum_columns[$column_key])) {
					$class[] = "sum-".$sum_columns[$column_key];
					$sum = $sum_columns[$column_key];
				}

				if ( $column_key === $primary ) {
					$class[] = 'column-primary';
				}

				$tag = 'th';
				$scope = 'scope="col"';

				if($column_key == "name") {
					$sum = __("Sum of overridden capabilities");
				}

				if ( !empty( $class ) )
					$class = "class='" . implode( ' ', $class ) . "'";

				echo "<$tag $scope $class>$sum</$tag>";
			}
		}
	}

	/**
	 * Get data and set pagination
	 *
	 * @since  0.4
	 * @return void
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		//var_dump(wp_roles());
		//get_editable_roles();
		$role = get_role("administrator");

		$per_page     = $this->get_items_per_page( 'caps_per_page', count($role->capabilities) );
		$current_page = $this->get_pagenum();
		$total_items  = $per_page;

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'total_pages' => 1,
			'per_page'    => $per_page
		) );
		$this->items = array_keys($role->capabilities);
		//$this->items = array();
	}

	/**
	 * Get current action
	 *
	 * @since  0.6
	 * @return string|boolean
	 */
	public function current_action() {
		if ( isset( $_REQUEST['add_users'] ) && isset($_REQUEST["users"])) {
			return 'add_users';
		}
		return parent::current_action();
	}

	/**
	 * Display pagination
	 * Adds hashtag to current url
	 *
	 * @since  0.6
	 * @param  string  $which
	 * @return void
	 */
	protected function pagination( $which ) {
		if ( empty( $this->_pagination_args ) ) {
			return;
		}

		$total_items = $this->_pagination_args['total_items'];
		$total_pages = $this->_pagination_args['total_pages'];
		$infinite_scroll = false;
		if ( isset( $this->_pagination_args['infinite_scroll'] ) ) {
			$infinite_scroll = $this->_pagination_args['infinite_scroll'];
		}

		if ( 'top' === $which && $total_pages > 1 ) {
			$this->screen->render_screen_reader_content( 'heading_pagination' );
		}

		$output = '<span class="displaying-num">' . sprintf( _n( '%s item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

		$current = $this->get_pagenum();

		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "#top#rua-members" );

		$current_url = remove_query_arg( array( 'hotkeys_highlight_last', 'hotkeys_highlight_first' ), $current_url );

		$page_links = array();

		$total_pages_before = '<span class="paging-input">';
		$total_pages_after  = '</span>';

		$disable_first = $disable_last = $disable_prev = $disable_next = false;

 		if ( $current == 1 ) {
			$disable_first = true;
			$disable_prev = true;
 		}
		if ( $current == 2 ) {
			$disable_first = true;
		}
 		if ( $current == $total_pages ) {
			$disable_last = true;
			$disable_next = true;
 		}
		if ( $current == $total_pages - 1 ) {
			$disable_last = true;
		}

		if ( $disable_first ) {
			$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&laquo;</span>';
		} else {
			$page_links[] = sprintf( "<a class='first-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( remove_query_arg( 'paged', $current_url ) ),
				__( 'First page' ),
				'&laquo;'
			);
		}

		if ( $disable_prev ) {
			$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&lsaquo;</span>';
		} else {
			$page_links[] = sprintf( "<a class='prev-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'paged', max( 1, $current-1 ), $current_url ) ),
				__( 'Previous page' ),
				'&lsaquo;'
			);
		}

		if ( 'bottom' === $which ) {
			$html_current_page  = $current;
			$total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page' ) . '</span><span id="table-paging" class="paging-input">';
		} else {
			$html_current_page = sprintf( "%s<input class='current-page' id='current-page-selector' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' />",
				'<label for="current-page-selector" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
				$current,
				strlen( $total_pages )
			);
		}
		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
		$page_links[] = $total_pages_before . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . $total_pages_after;

		if ( $disable_next ) {
			$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&rsaquo;</span>';
		} else {
			$page_links[] = sprintf( "<a class='next-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'paged', min( $total_pages, $current+1 ), $current_url ) ),
				__( 'Next page' ),
				'&rsaquo;'
			);
		}

		if ( $disable_last ) {
			$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&raquo;</span>';
		} else {
			$page_links[] = sprintf( "<a class='last-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
				__( 'Last page' ),
				'&raquo;'
			);
		}

		$pagination_links_class = 'pagination-links';
		if ( ! empty( $infinite_scroll ) ) {
			$pagination_links_class = ' hide-if-js';
		}
		$output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';

		if ( $total_pages ) {
			$page_class = $total_pages < 2 ? ' one-page' : '';
		} else {
			$page_class = ' no-pages';
		}
		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

		echo $this->_pagination;
	}
}

//eol