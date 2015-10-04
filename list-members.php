<?php
/*!
 * @package Restrict User Access
 * @copyright Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 */

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
			'singular' => __( 'Member', RestrictUserAccess::DOMAIN ),
			'plural'   => __( 'Members', RestrictUserAccess::DOMAIN ), 
			'ajax'     => false,
			'screen'   => RestrictUserAccess::TYPE_RESTRICT."_members"
		));
	}

	/**
	 * Text for no members
	 *
	 * @since  0.4
	 * @return void
	 */
	public function no_items() {
		_e( 'No members found.', RestrictUserAccess::DOMAIN );
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
			'date'       => __("Joined Level")
		);
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		$sortable_columns = array(
			'name' => array( 'name', true ),
			'city' => array( 'city', false )
		);

		return $sortable_columns;
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
				return $item->{$column_name};
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
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
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />', $user->ID
		);
	}

	/**
	 * Render date column
	 *
	 * @since  0.4
	 * @param  WP_User  $user
	 * @return string
	 */
	protected function column_date( $user ) {
		global $post;
		
		$time = get_user_meta($user->ID,WPCACore::PREFIX."level_".$post->ID,true);
		$m_time = date_i18n( get_option( 'date_format' ), $time );
		$t_time = date_i18n( __( 'Y/m/d g:i:s a' ), $time );
		
		$time_diff = time() - $time;

		if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
			$h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
		} else {
			$h_time = $m_time;
		}

		echo '<abbr title="' . $t_time . '">' . $h_time . '</abbr>';
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
		return $title;
	}

	/**
	 * Render name column
	 *
	 * @since  0.4
	 * @param  WP_User  $user
	 * @return string
	 */
	protected function column_name( $user ) {
		return $user->first_name . ' ' . $user->last_name;
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

	/**
	 * Get data and set pagination
	 *
	 * @since  0.4
	 * @return void
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		$user_query = new WP_User_Query(array(
			'meta_key' => WPCACore::PREFIX."level",
			'meta_value' => get_the_ID()
		));

		$total_items = $user_query->get_total();
		$per_page     = $this->get_items_per_page( 'members_per_page', 10 );
		$current_page = $this->get_pagenum();

		$this->set_pagination_args( [
			'total_items' => $total_items,
			'total_pages' => ceil( $total_items / $per_page ),
			'per_page' => $per_page
		] );

		$this->items = $user_query->get_results();
	}
}

//eol