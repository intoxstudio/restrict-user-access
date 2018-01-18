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

class RUA_Level_List_Table extends WP_List_Table {

	/**
	 * Trash view
	 * @var boolean
	 */
	private $is_trash;

	/**
	 * Extended access levels
	 * @var array
	 */
	private $extended_levels = array();

	public function __construct( $args = array() ) {
		parent::__construct(array(
			'singular' => 'level',
			'plural'   => 'levels', 
			'ajax'     => false,
			'screen'   => isset( $args['screen'] ) ? $args['screen'] : null
		));
	}

	/**
	 * Load filtered levels for current query
	 *
	 * @since  0.15
	 * @return void
	 */
	public function prepare_items() {
		global $avail_post_stati, $wp_query;

		$this->_column_headers = $this->get_column_info();

		$avail_post_stati = get_available_post_statuses(RUA_App::TYPE_RESTRICT);

		$per_page = $this->get_items_per_page( 'rua_levels_per_page', 20 );
		$current_page = $this->get_pagenum();

		$args = array(
			'post_type'              => RUA_App::TYPE_RESTRICT,
			'post_status'            => array(
				'publish',
				'draft',
				'future',
				'private'
			),
			'posts_per_page'         => $per_page,
			'paged'                  => $current_page,
			'orderby'                => 'title',
			'order'                  => 'asc',
			'update_post_term_cache' => false
		);

		if(isset($_REQUEST['s']) && strlen($_REQUEST['s'])) {
			$args['s'] = $_REQUEST['s'];
		}

		//Make sure post_status!=all if present to avoid auto-drafts
		if(isset($_REQUEST['post_status']) && $_REQUEST['post_status'] != 'all') {
			$args['post_status'] = $_REQUEST['post_status'];
		}

		if ( isset( $_REQUEST['orderby'] ) ) {
			$meta = str_replace('meta_', '', $_REQUEST['orderby']);
			if($meta != $_REQUEST['orderby']) {
				$args['orderby'] = 'meta_value';
				$args['meta_key'] = RUA_App::META_PREFIX . $meta;
			} else {
				$args['orderby'] = $_REQUEST['orderby'];
			}
		}

		if ( isset( $_REQUEST['order'] ) ) {
			$args['order'] = $_REQUEST['order'] == 'asc' ? 'asc' : 'desc';
		}

		$wp_query = new WP_Query($args);

		if ( $wp_query->found_posts || $current_page === 1 ) {
			$total_items = $wp_query->found_posts;
		} else {
			$post_counts = (array) wp_count_posts( RUA_App::TYPE_RESTRICT );

			if ( isset( $_REQUEST['post_status'] ) && in_array( $_REQUEST['post_status'] , $avail_post_stati ) ) {
				$total_items = $post_counts[ $_REQUEST['post_status'] ];
			} else {
				$total_items = array_sum( $post_counts );

				// Subtract post types that are not included in the admin all list.
				foreach ( get_post_stati( array( 'show_in_admin_all_list' => false ) ) as $state ) {
					$total_items -= $post_counts[ $state ];
				}
			}
		}

		$this->items = $wp_query->posts;

		//get extended levels
		$post_parents = array();
		foreach ($this->items as $post) {
			if($post->post_parent) {
				$post_parents[] = $post->post_parent;
			}
		}
		if($post_parents) {
			$args = array(
				'post_type'              => RUA_App::TYPE_RESTRICT,
				'post_status'            => array(
					'publish',
					'draft',
					'future',
					'private'
				),
				'post__in'               => $post_parents,
				'posts_per_page'         => -1,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false
			);
			$extend_query = new WP_Query($args);
			foreach($extend_query->posts as $post) {
				$this->extended_levels[$post->ID] = $post;
			}
		}

		$this->is_trash = isset( $_REQUEST['post_status'] ) && $_REQUEST['post_status'] == 'trash';
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'total_pages' => ceil( $total_items / $per_page ),
			'per_page'    => $per_page
		) );

		//Make sure filter is run
		RUA_App::instance()->level_manager->populate_metadata();
	}

	/**
	 * Render on no items
	 *
	 * @since  0.15
	 * @return void
	 */
	public function no_items() {
		if ( $this->is_trash ) {
			echo get_post_type_object( RUA_App::TYPE_RESTRICT )->labels->not_found_in_trash;
		} else {
			//todo show more text to get started
			echo get_post_type_object( RUA_App::TYPE_RESTRICT )->labels->not_found;
		}
	}

	/**
	 * Get link to view
	 *
	 * @since  0.15
	 * @param  array   $args
	 * @param  string  $label
	 * @param  string  $class
	 * @return string
	 */
	public function get_view_link( $args, $label, $class = '' ) {
		$screen = get_current_screen();
		$args['page'] = $screen->parent_base;
		$url = add_query_arg( $args, 'admin.php' );

		$class_html = '';
		if ( ! empty( $class ) ) {
			 $class_html = sprintf(
				' class="%s"',
				esc_attr( $class )
			);
		}

		return sprintf(
			'<a href="%s"%s>%s</a>',
			esc_url( $url ),
			$class_html,
			$label
		);
	}

	/**
	 * Get views (level statuses)
	 *
	 * @since  0.15
	 * @return array
	 */
	public function get_views() {
		global $locked_post_status, $avail_post_stati;

		if ( !empty($locked_post_status) )
			return array();

		$status_links = array();
		$num_posts = wp_count_posts( RUA_App::TYPE_RESTRICT ); //do not include private
		$total_posts = array_sum( (array) $num_posts );
		$class = '';

		// Subtract post types that are not included in the admin all list.
		foreach ( get_post_stati( array( 'show_in_admin_all_list' => false ) ) as $state ) {
			$total_posts -= $num_posts->$state;
		}

		if ( empty( $class ) && ( !isset($_REQUEST['post_status']) || isset( $_REQUEST['all_posts'] ) ) ) {
			$class = 'current';
		}

		$all_inner_html = sprintf(
			_nx(
				'All <span class="count">(%s)</span>',
				'All <span class="count">(%s)</span>',
				$total_posts,
				'levels',
				'restrict-user-access'
			),
			number_format_i18n( $total_posts )
		);

		$status_links['all'] = $this->get_view_link( array(), $all_inner_html, $class );

		//no way to change post status per post type, replace here instead
		$label_replacement = array(
			'publish' => _n_noop( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', 'restrict-user-access'),
			'draft' => _n_noop( 'Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', 'restrict-user-access')
		);

		foreach ( get_post_stati(array('show_in_admin_status_list' => true), 'objects') as $status ) {
			$class = '';

			$status_name = $status->name;

			if ( ! in_array( $status_name, $avail_post_stati ) || empty( $num_posts->$status_name ) ) {
				continue;
			}

			if ( isset($_REQUEST['post_status']) && $status_name == $_REQUEST['post_status'] ) {
				$class = 'current';
			}

			$status_args = array(
				'post_status' => $status_name
			);

			$label_count = $status->label_count;
			if(isset($label_replacement[$status->name])) {
				$label_count = $label_replacement[$status->name];
			}

			$status_label = sprintf(
				translate_nooped_plural( $label_count, $num_posts->$status_name ),
				number_format_i18n( $num_posts->$status_name )
			);

			$status_links[ $status_name ] = $this->get_view_link( $status_args, $status_label, $class );
		}

		return $status_links;
	}

	/**
	 * Get bulk actions
	 *
	 * @since  0.15
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array();
		$post_type_obj = get_post_type_object( RUA_App::TYPE_RESTRICT);

		if ( current_user_can( $post_type_obj->cap->edit_posts ) ) {
			if ( $this->is_trash ) {
				$actions['untrash'] = __( 'Restore' );
			}
		}

		if ( current_user_can( $post_type_obj->cap->delete_posts ) ) {
			if ( $this->is_trash || ! EMPTY_TRASH_DAYS ) {
				$actions['delete'] = __( 'Delete Permanently' );
			} else {
				$actions['trash'] = __( 'Move to Trash' );
			}
		}

		//todo: add filter
		return $actions;
	}

	/**
	 * Render extra table navigation and actions
	 *
	 * @since  0.15
	 * @param  string  $which
	 * @return void
	 */
	public function extra_tablenav( $which ) {

		echo '<div class="alignleft actions">';
		if ( $this->is_trash && current_user_can( get_post_type_object( RUA_App::TYPE_RESTRICT )->cap->edit_others_posts ) ) {
			submit_button( __( 'Empty Trash' ), 'apply', 'delete_all', false );
		}
		echo '</div>';
	}

	/**
	 * Get current action
	 *
	 * @since  0.15
	 * @return string
	 */
	public function current_action() {
		if ( isset( $_REQUEST['delete_all'] ) || isset( $_REQUEST['delete_all2'] ) )
			return 'delete_all';

		return parent::current_action();
	}

	/**
	 * Get columns
	 *
	 * @since  0.15
	 * @return array
	 */
	public function get_columns() {
		$posts_columns = array();
		$posts_columns['cb'] = '<input type="checkbox" />';
		$posts_columns['title'] = _x( 'Title', 'column name' );
		$posts_columns['name'] = __( 'Name', 'restrict-user-access' );
		$posts_columns['role'] = __( 'Members', 'restrict-user-access' );
		$posts_columns['duration'] = __( 'Duration', 'restrict-user-access' );
		$posts_columns['caps'] = __( 'Capabilities', 'restrict-user-access' );
		$posts_columns['handle'] = __( 'Action', 'restrict-user-access' );

		return apply_filters('rua/admin/columns', $posts_columns);
	}

	/**
	 * Get sortable columns
	 *
	 * @since  0.15
	 * @return array
	 */
	public function get_sortable_columns() {
		$columns = array(
			'title'    => array('title', true),
			'role'     => 'meta_handle',
			'handle'   => 'meta_handle'
		);
		return $columns;
	}

	/**
	 * Get default column name
	 *
	 * @since  0.15
	 * @return string
	 */
	protected function get_default_primary_column_name() {
		return 'title';
	}

	/**
	 * Get classes for rows
	 * Older WP versions do not add striped
	 *
	 * @since  0.15
	 * @return array
	 */
	public function get_table_classes() {
		return array( 'widefat', 'fixed', 'striped', $this->_args['plural'] );
	}

	/**
	 * Render checkbox column
	 *
	 * @since  0.15
	 * @param  WP_Post  $post
	 * @return void
	 */
	public function column_cb( $post ) {
		if ( current_user_can( 'edit_post', $post->ID ) ): ?>
			<label class="screen-reader-text" for="cb-select-<?php echo $post->ID; ?>"><?php
				printf( __( 'Select %s' ), _draft_or_post_title($post) );
			?></label>
			<input id="cb-select-<?php echo $post->ID; ?>" type="checkbox" name="post[]" value="<?php echo $post->ID; ?>" />
			<div class="locked-indicator"></div>
		<?php endif;
	}

	/**
	 * Render title column wrapper
	 *
	 * @since  0.15
	 * @param  WP_Post  $post
	 * @param  array    $classes
	 * @param  array    $data
	 * @param  string   $primary
	 * @return void
	 */
	protected function _column_title( $post, $classes, $data, $primary) {
		echo '<td class="' . $classes . ' page-title" ', $data, '>';
		echo $this->column_title( $post );
		echo '</td>';
	}

	/**
	 * Render title column
	 *
	 * @since  0.15
	 * @param  WP_Post  $post
	 * @return void
	 */
	public function column_title( $post ) {

		echo "<b>";

		$can_edit_post = current_user_can( 'edit_post', $post->ID );
		$title = _draft_or_post_title($post);

		if ( $can_edit_post && $post->post_status != 'trash' ) {
			printf(
				'<a class="" href="%s" aria-label="%s">%s</a>',
				get_edit_post_link( $post->ID ),
				/* translators: %s: post title */
				esc_attr( sprintf( __( '&#8220;%s&#8221; (Edit)' ), $title ) ),
				$title
			);
		} else {
			echo $title;
		}

		echo "</b>\n";

		if($post->post_parent && isset($this->extended_levels[$post->post_parent])) {
			echo '<em>'.sprintf('extends %s',$this->extended_levels[$post->post_parent]->post_title).'</em>';
		}

		if ( $can_edit_post && $post->post_status != 'trash' ) {
			$lock_holder = wp_check_post_lock( $post->ID );

			if ( $lock_holder ) {
				$lock_holder = get_userdata( $lock_holder );
				$locked_avatar = get_avatar( $lock_holder->ID, 18 );
				$locked_text = esc_html( sprintf( __( '%s is currently editing' ), $lock_holder->display_name ) );
			} else {
				$locked_avatar = $locked_text = '';
			}

			echo '<div class="locked-info"><span class="locked-avatar">' . $locked_avatar . '</span> <span class="locked-text">' . $locked_text . "</span></div>\n";
		}

		echo $this->handle_row_actions( $post, 'title', 'title' );
	}

	/**
	 * Render slug column
	 *
	 * @since  0.15
	 * @param  WP_Post  $post
	 * @return void
	 */
	public function column_name($post) {
		echo "<code>".$post->post_name."</code>";
	}

	/**
	 * Display role column
	 *
	 * @since  0.5
	 * @param  string  $column_name
	 * @param  int     $post_id
	 * @return string
	 */
	
	/**
	 * Render role column
	 *
	 * @since  0.15
	 * @param  WP_Post  $post
	 * @return void
	 */
	public function column_role($post) {
		$metadata = RUA_App::instance()->level_manager->metadata()->get('role');
		$retval = '';
		if($metadata) {
			$data = $metadata->get_data($post->ID);
			if($data === '') {
				$users = get_users(array(
					'meta_key' => RUA_App::META_PREFIX.'level',
					'meta_value' => $post->ID,
					'fields' => 'ID'
				));
				$retval = '<a href="'.get_edit_post_link($post->ID).'#top#section-members">'.count($users).'</a>';
			} else {
				$retval = $metadata->get_list_data($post->ID,false);
			}
		}
		echo $retval;
	}

	/**
	 * Render handle column
	 *
	 * @since  0.15
	 * @param  WP_Post  $post
	 * @return void
	 */
	public function column_handle($post) {
		$metadata = RUA_App::instance()->level_manager->metadata()->get('handle');
		$retval = '';
		if($metadata) {
			$data = $metadata->get_data($post->ID);
			$retval = $metadata->get_list_data($post->ID);
			// if ($data != 2) {
			// 	$page = RUA_App::instance()->level_manager->metadata()->get('page')->get_data($post->ID);
			// 	if(is_numeric($page)) {
			// 		$page = get_post($page);
			// 		$page = $page->post_title;
			// 	}
			// 	$retval .= ": " . ($page ? $page : '<span style="color:red;">' . __('Please update Page', 'restrict-user-access') . '</span>');
			// }
		}
		echo $retval;
	}

	/**
	 * Render duration column
	 *
	 * @since  0.15
	 * @param  WP_Post  $post
	 * @return void
	 */
	protected function column_duration($post) {
		$metadata = RUA_App::instance()->level_manager->metadata()->get('duration');
		$retval = '';
		
		if($metadata) {
			$data = $metadata->get_data($post->ID);
			if(isset($data["count"],$data["unit"]) && $data["count"]) {
				$retval = $this->_get_duration_text($data["count"],$data["unit"]);
			} else {
				$retval = __('Unlimited','restrict-user-access');
			}
		}
		echo esc_html($retval);
	}

	/**
	 * Render capabilities column
	 *
	 * @since  0.15
	 * @param  WP_Post  $post
	 * @return void
	 */
	public function column_caps($post) {
		$counts = array(
			0 => 0,
			1 => 0
		);
		$metadata = RUA_App::instance()->level_manager->metadata()->get('caps');
		$caps = $metadata->get_data($post->ID);
		if($caps) {
			foreach ($caps as $cap) {
				$counts[$cap]++;
			}
		}
		echo __('Permit:').' '.$counts[1].'<br>'.__('Deny:').' '.$counts[0];
	}

	/**
	 * Get localized duration
	 *
	 * @since  0.11
	 * @param  int     $duration
	 * @param  string  $unit
	 * @return string
	 */
	protected function _get_duration_text($duration,$unit) {
		$units = array(
			'day'   => _n_noop('%d day', '%d days'),
			'week'  => _n_noop('%d week', '%d weeks'),
			'month' => _n_noop('%d month', '%d months'),
			'year'  => _n_noop('%d year', '%d years')
		);
		return sprintf(translate_nooped_plural( $units[$unit], $duration, 'restrict-user-access'),$duration);
	}

	/**
	 * Render arbitrary column
	 *
	 * @since  0.15
	 * @param  WP_post  $post
	 * @param  string   $column_name
	 * @return void
	 */
	public function column_default( $post, $column_name ) {
		do_action('rua/admin/columns/render',$post,$column_name);
	}

	/**
	 * Render row
	 *
	 * @since  0.15
	 * @param  WP_Post  $item
	 * @return void
	 */
	public function single_row( $item ) {
		$class = '';
		if($item->post_status == 'publish') {
			$class = ' class="active"';
		}
		echo '<tr'.$class.'>';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Get row actions
	 *
	 * @since  0.15
	 * @param  WP_Post  $post
	 * @param  string  $column_name
	 * @param  string  $primary
	 * @return string
	 */
	protected function handle_row_actions( $post, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		$post_type_object = get_post_type_object( $post->post_type );
		$actions = array();
		$title = _draft_or_post_title();

		if (current_user_can( 'edit_post', $post->ID ) && $post->post_status != 'trash') {
			$actions['edit'] = sprintf(
				'<a href="%s" aria-label="%s">%s</a>',
				get_edit_post_link( $post->ID ),
				/* translators: %s: level title */
				esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), $title ) ),
				__( 'Edit' )
			);
		}

		if ( current_user_can( 'delete_post', $post->ID ) ) {
			if ($post->post_status == 'trash') {
				$actions['untrash'] = sprintf(
					'<a href="%s" aria-label="%s">%s</a>',
					wp_nonce_url( get_edit_post_link( $post->ID, 'display' ).'&amp;action=untrash', 'untrash-post_' . $post->ID ),
					/* translators: %s: level title */
					esc_attr( sprintf( __( 'Restore &#8220;%s&#8221; from the Trash' ), $title ) ),
					__( 'Restore' )
				);
			} elseif ( EMPTY_TRASH_DAYS ) {
				$actions['trash'] = sprintf(
					'<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
					get_delete_post_link( $post->ID ),
					/* translators: %s: post title */
					esc_attr( sprintf( __( 'Move &#8220;%s&#8221; to the Trash' ), $title ) ),
					_x( 'Trash', 'verb' )
				);
			}
			if ($post->post_status == 'trash' || ! EMPTY_TRASH_DAYS ) {
				$actions['delete'] = sprintf(
					'<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
					get_delete_post_link( $post->ID, '', true ),
					/* translators: %s: post title */
					esc_attr( sprintf( __( 'Delete &#8220;%s&#8221; permanently' ), $title ) ),
					__( 'Delete Permanently' )
				);
			}
		}

		return $this->row_actions(
			apply_filters( 'rua/admin/row_actions', $actions, $post )
		);
	}

}
