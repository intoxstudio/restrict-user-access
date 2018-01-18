<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2018 by Joachim Jensen
 */

if (!defined('ABSPATH')) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

final class RUA_Members_List extends WP_List_Table {

	/**
	 * Constructor
	 *
	 * @since 0.4
	 */
	public function __construct() {
		parent::__construct(array(
			'singular' => __( 'Member', 'restrict-user-access' ),
			'plural'   => __( 'Members', 'restrict-user-access' ), 
			'ajax'     => false,
			'screen'   => RUA_App::TYPE_RESTRICT.'_members'
		));
		//adds suffix to bulk name to avoid clash
		$this->_actions = $this->get_bulk_actions();
	}

	/**
	 * Text for no members
	 *
	 * @since  0.4
	 * @return void
	 */
	public function no_items() {
		_e( 'No members found.', 'restrict-user-access' );
	}

	/**
	 * Table columns with titles
	 *
	 * @since  0.4
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'         => '<input type="checkbox" />',
			'user_login' => __( 'Username'),
			'name'       => __( 'Name'),
			'user_email' => __( 'E-mail'),
			'status'     => __('Status','restrict-user-access')
		);
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array();
	}

	/**
	 * Primary column used for responsive view
	 *
	 * @since  0.4
	 * @return string
	 */
	protected function get_default_primary_column_name() {
		return 'user_login';
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
			case 'user_email':
				echo '<a href="mailto:'.$item->{$column_name}.'">'.$item->{$column_name}.'</a>';
			default:
				print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Render checkbox column
	 *
	 * @since  0.4
	 * @param  WP_User  $user
	 * @return string
	 */
	protected function column_cb( $user ) {
		printf(
			'<input type="checkbox" name="user[]" value="%s" />', $user->ID
		);
	}

	/**
	 * Render user_login column
	 *
	 * @since  0.4
	 * @param  WP_User  $user
	 * @return string
	 */
	protected function column_user_login( $user ) {
		$title = '<strong>' . $user->user_login . '</strong>';
		$admin_url = admin_url(sprintf('admin.php?page=wprua-edit&level_id=%s&user=%s',
			$_REQUEST['level_id'],
			$user->ID
		));
		$actions = array(
			'delete' => '<a href="'.wp_nonce_url($admin_url.'&action=remove_user','update-post_'.$_REQUEST['level_id']).'">'.__('Remove').'</a>'
		);
		echo $title . $this->row_actions( $actions );
	}

	/**
	 * Render name column
	 *
	 * @since  0.4
	 * @param  WP_User  $user
	 * @return string
	 */
	protected function column_name( $user ) {
		echo $user->first_name . ' ' . $user->last_name;
	}

	/**
	 * Render expiry date column
	 *
	 * @since  0.5
	 * @param  WP_User  $user
	 * @return string
	 */
	protected function column_status( $user ) {
		$post_id = get_the_ID();
		$expiry = RUA_App::instance()->level_manager->get_user_level_expiry($user->ID,$post_id);
		$status = __('Active','restrict-user-access');
		if($expiry) {
			$is_expired = RUA_App::instance()->level_manager->is_user_level_expired($user->ID,$post_id);
			$h_time = date_i18n( get_option( 'date_format' ), $expiry );
			$t_time = date_i18n( __( 'Y/m/d' ).' '.get_option('time_format'), $expiry );
			$status = $is_expired ? __('Expired %s','restrict-user-access') : __('Active until %s','restrict-user-access');
			$status = sprintf($status,'<abbr title="' . $t_time . '">' . $h_time . '</abbr>');
		}
		$time = get_user_meta($user->ID,RUA_App::META_PREFIX.'level_'.$post_id,true);
		if($time) {
			$m_time = date_i18n( get_option( 'date_format' ), $time );
			$t_time = date_i18n( __( 'Y/m/d' ).' '.get_option('time_format'), $time );
			
			$time_diff = time() - $time;

			if ( $time_diff >= 0 && $time_diff <= DAY_IN_SECONDS ) {
				$h_time = sprintf( __( 'Joined %s ago' ), human_time_diff( $time ) );
			} else {
				$h_time = sprintf(__('Joined on %s'),$m_time);
			}

			$status .= '<br><abbr title="' . $t_time . '">'.$h_time. '</abbr>';
		}
		echo $status;
	}


	/**
	 * Bulk actions
	 *
	 * @since  0.4
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'remove_user' => __( 'Remove', 'restrict-user-access' )
		);
	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @since 0.4
	 * @param string $which
	 */
	public function display_tablenav( $which ) {
		?>
	<div class="tablenav <?php echo esc_attr( $which ); ?>">

		<?php if ( $this->has_items() && $which == 'top' ): ?>
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

	/**
	 * Get data and set pagination
	 *
	 * @since  0.4
	 * @return void
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		$per_page     = $this->get_items_per_page( 'members_per_page', 20 );
		$current_page = $this->get_pagenum();
		$user_query = new WP_User_Query(array(
			'meta_key'   => RUA_App::META_PREFIX.'level',
			'meta_value' => get_the_ID(),
			'number'     => $per_page,
			'offset'     => ($current_page-1)*$per_page
		));
		$total_items  = (int)$user_query->get_total();

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'total_pages' => ceil( $total_items / $per_page ),
			'per_page'    => $per_page
		) );

		$this->items = $user_query->get_results();
	}

	/**
	 * Display pagination
	 * Adds hashtag to current url
	 *
	 * @since  0.6
	 * @param  string  $which
	 * @return void
	 */
	public function pagination( $which ) {
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

		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '#top#section-members' );

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