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

final class RUA_Level_Edit extends RUA_Admin {

	/**
	 * Add filters and actions for admin dashboard
	 * e.g. AJAX calls
	 *
	 * @since  0.15
	 * @return void
	 */
	public function admin_hooks() {

		add_action('save_post_'.RUA_App::TYPE_RESTRICT,
			array($this,'save_post'));
		add_action('rua/admin/add_meta_boxes',
			array($this,'create_meta_boxes'));
		add_action('wp_ajax_rua/user/suggest',
			array($this,'ajax_get_users'));
		add_action('wp_ajax_rua/page/suggest',
			array($this,'ajax_get_pages'));

		add_filter('wpca/condition/meta',
			array($this,'register_level_meta'),10,2);
		add_filter( 'get_edit_post_link',
			array($this,'get_edit_post_link'), 10, 3 );
		add_filter( 'get_delete_post_link',
			array($this,'get_delete_post_link'), 10, 3 );

	}

	/**
	 * Add filters and actions for frontend
	 *
	 * @since  0.15
	 * @return void
	 */
	public function frontend_hooks() {

	}


	/**
	 * Register meta data for conditions
	 *
	 * @since  0.15
	 * @param  array   $meta
	 * @param  string  $post_type
	 * @return array
	 */
	public function register_level_meta($meta,$post_type) {
		if($post_type == RUA_App::TYPE_RESTRICT) {
			$meta['_ca_opt_drip'] = 0;
		}
		return $meta;
	}

	/**
	 * Get available users for level
	 *
	 * @since  0.15
	 * @return void
	 */
	public function ajax_get_users() {
		$results = array();
		if(current_user_can(RUA_App::CAPABILITY)) {
			$user_query = new WP_User_Query(array(
				'search'         => '*'.$_REQUEST['q'].'*',
				'search_columns' => array('user_login','user_email','user_nicename'),
				'fields'         => array('ID','user_login','user_email'),
				'number'         => 10,
				'offset'         => 0
			));
			foreach($user_query->get_results() as $user) {
				$levels = (array) get_user_meta($user->ID, RUA_App::META_PREFIX.'level', false);
				if(!in_array($_REQUEST['post_id'], $levels)) {
					$results[] = $user;
				}
			}
		}
		wp_send_json($results);
	}

	/**
	 * Get redirect/include pages for level
	 *
	 * @since  0.17
	 * @return void
	 */
	public function ajax_get_pages() {
		$posts_list = array();
		if(current_user_can(RUA_App::CAPABILITY)) {
			foreach(get_posts(array(
				'posts_per_page'         => 20,
				'orderby'                => 'post_title',
				'order'                  => 'ASC',
				'post_type'              => 'page',
				'post_status'            => 'publish',
				's'                      => $_REQUEST['search'],
				'paged'                  => $_REQUEST['paged'],
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false
			)) as $post) {
				$posts_list[] = array(
					'id'   => $post->ID,
					'text' => $post->post_title ? $post->post_title : __('(no title)')
				);
			}
		}
		wp_send_json($posts_list);
	}

	/**
	 * Meta boxes for restriction edit
	 *
	 * @since  0.1
	 * @return void
	 */
	public function create_meta_boxes($post) {

		RUA_App::instance()->level_manager->populate_metadata();

		$path = plugin_dir_path( __FILE__ ).'../view/';

		$boxes = array();
		$boxes[] = array(
			'id'       => 'submitdiv',
			'title'    => __('Publish'),
			'view'     => 'publish',
			'context'  => 'side',
			'priority' => 'high'
		);
		$boxes[] = array(
			'id'       => 'rua-plugin-links',
			'title'    => __('Helpful Links', 'restrict-user-access'),
			'view'     => 'support',
			'context'  => 'side',
			'priority' => 'default'
		);
		$boxes[] = array(
			'id'       => 'rua-options',
			'title'    => __('Options', 'restrict-user-access'),
			'callback' => 'meta_box_options',
			'context'  => 'section-options',
			'priority' => 'default'
		);
		$boxes[] = array(
			'id'       => 'rua-members',
			'title'    => __('Members', 'restrict-user-access'),
			'callback' => 'meta_box_members',
			'context'  => 'section-members',
			'priority' => 'default'
		);
		$boxes[] = array(
			'id'       => 'rua-capabilities',
			'title'    => __('Capabilities', 'restrict-user-access'),
			'view'     => 'caps',
			'context'  => 'section-capabilities',
			'priority' => 'default'
		);

		//Add meta boxes
		foreach($boxes as $box) {
			if(isset($box['view'])) {
				$view = WPCAView::make($path.'meta_box_'.$box['view'].'.php',array(
					'post'=> $post
				));
				$callback = array($view,'render');
			} else {
				$callback = array($this, $box['callback']);
			}
			add_meta_box(
				$box['id'],
				$box['title'],
				$callback,
				RUA_App::BASE_SCREEN.'-edit',
				$box['context'],
				$box['priority']
			);
		}

		add_action('wpca/meta_box/before',
			array($this,'show_description'));
		add_action('wpca/meta_box/after',
			array($this,'show_review_link'));

		add_action('wpca/group/settings',
			array($this,'render_condition_options'));

		//todo: refactor add of meta box
		//with new bootstrapper, legacy core might be loaded
		if(method_exists('WPCACore', 'render_group_meta_box')) {
			WPCACore::render_group_meta_box($post,RUA_App::BASE_SCREEN.'-edit','section-conditions','default');
		}

	}

	/**
	 * Render description for level conditions
	 *
	 * @since  0.12
	 * @param  string  $post_type
	 * @return void
	 */
	public function show_description($post_type) {
		if($post_type == RUA_App::TYPE_RESTRICT) {
			_e('Content that meets the following conditions will only be accessible by this level or higher:');
			echo '<p></p>';
		}
	}

	/**
	 * Render support description
	 *
	 * @since  0.15
	 * @param  string  $post_type
	 * @return void
	 */
	public function show_review_link($post_type) {
		if($post_type == RUA_App::TYPE_RESTRICT) {
			echo '<div style="overflow: hidden; padding: 2px 0px;">';
			echo '<div style="line-height:24px;">';
			echo '<span style="color:rgb(172, 23, 10);">❤</span> ';
			printf(__('Like this plugin? %1$sPlease help make it better with a %2$s rating%3$s. Thank you.','restrict-user-access'),'<b><a target="_blank" href="https://wordpress.org/support/plugin/restrict-user-access/reviews/?rate=5#new-post">','5★','</a></b>');
			echo '</div>';
			echo '</div>';
		}
	}

	/**
	 * Display extra options for condition group
	 *
	 * @since  0.15
	 * @param  string  $post_type
	 * @return void
	 */
	public function render_condition_options($post_type) {
		if($post_type == RUA_App::TYPE_RESTRICT) {
			echo '<li class="js-rua-drip-option">';
			echo '<label>'.__('Unlock Time for new members','restrict-user-access');
			echo '<div class="wpca-pull-right"><input class="small-text" data-vm="value:integer(_ca_opt_drip)" type="number" />'.__("days");
			echo '</div></label>';
			echo '</li>';
		}
	}

	/**
	 * Meta box for options
	 *
	 * @since  0.1
	 * @return void
	 */
	public function meta_box_options($post) {

		$metadata = RUA_App::instance()->level_manager->metadata();

		$pages = wp_dropdown_pages(array(
			'post_type'        => $post->post_type,
			'exclude_tree'     => $post->ID,
			'selected'         => $post->post_parent,
			'name'             => 'parent_id',
			'show_option_none' => __('Do not extend','restrict-user-access'),
			'sort_column'      => 'menu_order, post_title',
			'echo'             => 0,
		));
		if ( ! empty($pages) ) {
?>
<div class="extend"><strong><?php _e('Extend','restrict-user-access') ?></strong>
<label class="screen-reader-text" for="parent_id"><?php _e('Extend','restrict-user-access') ?></label>
<p><?php echo $pages; ?></p>
</div>
<?php
		}

		$this->_form_field('handle');

		$val = $metadata->get('page')->get_data($post->ID);

		echo '<div><p><select name="page" class="js-rua-page" data-tags="1" data-rua-url="'.get_site_url().'">';
		if(is_numeric($val)) {
			$page = get_post($val);
			echo '<option value="'.$page->ID.'" selected="selected">'.$page->post_title.'</option>';
		} elseif($val) {
			echo '<option value="'.$val.'" selected="selected">'.$val.'</option>';
		}
		echo '</select></p></div>';

		$duration =  $metadata->get('duration');
		$duration_val = 'day';

		$duration_no = 0;
		$duration_arr = $duration->get_data($post->ID);
		if($duration_arr) {
			$duration_no = $duration_arr['count'];
			$duration_val = $duration_arr['unit'];
		}

		echo '<div class="duration"><strong>' . $duration->get_title() . '</strong>';
		echo '<p>';
		echo '<input type="number" min="0" name="duration[count]" value="'.$duration_no.'" style="width:60px;vertical-align:top;" />';
		echo '<select style="width:190px;" name="' . $duration->get_id() . '[unit]">' . "\n";
		foreach ($duration->get_input_list() as $key => $value) {
			echo '<option value="' . $key . '"' . selected($duration_val,$key,false) . '>' . $value . '</option>' . "\n";
		}
		echo '</select>' . "\n";
		echo '</p></div>';

		//ability to change name on update
		if ( $post->post_status != 'auto-draft' ) {
			echo '<strong>' . __('Level Name','restrict-user-access') . '</strong>';
			echo '<p>';
			echo '<input type="text" name="post_name" value="'.$post->post_name.'" />';
			echo '</p>';
		}
	}

	/**
	 * Render members screen
	 *
	 * @since  0.15
	 * @param  WP_Post  $post
	 * @return void
	 */
	public function meta_box_members($post) {

		$list_members = new RUA_Members_List();
		$list_members->prepare_items();

		$this->_form_field('role');

		echo '<div class="js-rua-members">';

		echo '<select class="js-rua-user-suggest" multiple="multiple" name="users[]"></select>';
		$list_members->display();

		echo '</div>';

	}

	/**
	 * Create form field for metadata
	 * @global object $post
	 * @param  array $setting 
	 * @return void 
	 */
	private function _form_field($id,$class = '') {

		$setting = RUA_App::instance()->level_manager->metadata()->get($id);
		$current = $setting->get_data(get_the_ID(),true,$setting->get_input_type() != 'multi');

		echo '<div class="'.$class.'"><strong>' . $setting->get_title() . '</strong>';
		echo '<p>';
		switch ($setting->get_input_type()) {
			case 'select' :
				echo '<select style="width:250px;" name="' . $id . '" class="js-rua-'.$id.'">' . "\n";
				foreach ($setting->get_input_list() as $key => $value) {
					echo '<option value="' . $key . '"' . selected($current,$key,false) . '>' . $value . '</option>' . "\n";
				}
				echo '</select>' . "\n";
				break;
			case 'checkbox' :
				echo '<ul>' . "\n";
				foreach ($setting->get_input_list() as $key => $value) {
					echo '<li><label><input type="checkbox" name="' . $id . '[]" class="js-rua-'.$id.'" value="' . $key . '"' . (in_array($key, $current) ? ' checked="checked"' : '') . ' /> ' . $value . '</label></li>' . "\n";
				}
				echo '</ul>' . "\n";
				break;
			case 'multi' :
				echo '<div><select style="width:250px;" class="js-rua-'.$id.'" multiple="multiple"  name="' . $id . '[]" data-value="'.implode(",", $current).'"></select></div>';
				break;
			case 'text' :
			default :
				echo '<input style="width:200px;" type="text" name="' . $id . '" value="' . $current . '" />' . "\n";
				break;
		}
		echo '</p></div>';
	}

	/**
	 * Save metadata values for restriction
	 *
	 * @since  0.1
	 * @param  int  $post_id
	 * @return void
	 */
	public function save_post($post_id) {

		//TODO: check other nonce instead
		if(!(isset($_POST[WPCACore::NONCE])
			&& wp_verify_nonce($_POST[WPCACore::NONCE], WPCACore::PREFIX.$post_id)))
			return;

		// Check permissions
		if (!current_user_can(RUA_App::CAPABILITY, $post_id))
			return;

		// Check autosave
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return;

		// Update metadata
		foreach (RUA_App::instance()->level_manager->metadata() as $field) {
			$field->save($post_id);
		}
	}

	/**
	 * Set up admin menu and get current screen
	 *
	 * @since  0.15
	 * @return string
	 */
	public function get_screen() {
		$post_type_object = get_post_type_object(RUA_App::TYPE_RESTRICT);
		return add_submenu_page(
			RUA_App::BASE_SCREEN,
			$post_type_object->labels->add_new_item,
			$post_type_object->labels->add_new,
			$post_type_object->cap->edit_posts,
			RUA_App::BASE_SCREEN.'-edit',
			array($this,'render_screen')
		);
	}

	/**
	 * Authorize user for screen
	 *
	 * @since  0.15
	 * @return boolean
	 */
	public function authorize_user() {
		return true;
	}

	/**
	 * Prepare screen load
	 *
	 * @since  0.15
	 * @return void
	 */
	public function prepare_screen() {

		global $nav_tabs, $post, $title, $active_post_lock;

		$post_type = RUA_App::TYPE_RESTRICT;
		$post_type_object = get_post_type_object( $post_type );
		$post_id = isset($_REQUEST['level_id']) ? $_REQUEST['level_id'] : 0;

		//process actions
		$this->process_actions($post_id);

		if ( is_multisite() ) {
			add_action( 'admin_footer', '_admin_notice_post_locked' );
		} else {
			$check_users = get_users( array( 'fields' => 'ID', 'number' => 2 ) );
			if ( count( $check_users ) > 1 )
				add_action( 'admin_footer', '_admin_notice_post_locked' );
			unset( $check_users );
		}

		wp_enqueue_script('post');

		if ( wp_is_mobile() ) {
			wp_enqueue_script( 'jquery-touch-punch' );
		}

		// Add the local autosave notice HTML
		//add_action( 'admin_footer', '_local_storage_notice' );

		/**
		 * Edit mode
		 */
		if($post_id) {
			$post = get_post($post_id, OBJECT, 'edit');

			if ( ! $post )
				wp_die( __( 'The level no longer exists.' ) );
			if ( ! current_user_can( 'edit_post', $post_id ) )
				wp_die( __( 'You are not allowed to edit this level.' ) );
			if ( 'trash' == $post->post_status )
				wp_die( __( 'You cannot edit this level because it is in the Trash. Please restore it and try again.' ) );

			if ( ! empty( $_GET['get-post-lock'] ) ) {
				check_admin_referer( 'lock-post_' . $post_id );
				wp_set_post_lock( $post_id );
				wp_redirect( get_edit_post_link( $post_id, 'url' ) );
				exit();
			}

			if ( ! wp_check_post_lock( $post->ID ) ) {
				$active_post_lock = wp_set_post_lock( $post->ID );
				//wp_enqueue_script('autosave');
			}

			$title = $post_type_object->labels->edit_item;

		/**
		 * New Mode
		 */
		} else {

			if ( ! current_user_can( $post_type_object->cap->edit_posts ) || ! current_user_can( $post_type_object->cap->create_posts ) ) {
				wp_die(
					'<p>' . __( 'You are not allowed to create levels.', 'restrict-user-access' ) . '</p>',
					403
				);
			}

			//wp_enqueue_script( 'autosave' );

			$post = get_default_post_to_edit( $post_type, true );

			$title = $post_type_object->labels->add_new_item;
		}

		$nav_tabs = array(
			'conditions'   => __('Restrictions','restrict-user-access'),
			'options'      => __('Options','restrict-user-access'),
			'members'      => __('Members','restrict-user-access'),
			'capabilities' => __('Capabilities','restrict-user-access')
		);
		$nav_tabs = apply_filters('rua/admin/nav-tabs', $nav_tabs);

		do_action( 'rua/admin/add_meta_boxes', $post );

	}

	/**
	 * Process actions
	 *
	 * @since  0.15
	 * @param  int  $post_id
	 * @return void
	 */
	public function process_actions($post_id) {
		$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : (isset($_REQUEST['action2']) ? $_REQUEST['action2'] : '');

		if ( isset( $_POST['deletepost'] ) )
			$action = 'delete';

		if($action && $post_id) {
			//wp_reset_vars( array( 'action' ) );
			$sendback = wp_get_referer();
			$sendback = remove_query_arg(
				array('action','action2','trashed', 'untrashed', 'deleted', 'ids'),
				$sendback
			);
			if(isset($_REQUEST['_rua_section']) && $_REQUEST['_rua_section']) {
				$sendback .= $_REQUEST['_rua_section'];
			}

			$post = get_post( $post_id );
			if ( ! $post ) {
				wp_die( __( 'The level no longer exists.', 'restrict-user-access' ) );
			}

			switch($action) {
				case 'editpost':
					check_admin_referer('update-post_' . $post_id);

					$post_id = $this->update_level();

					// Session cookie flag that the post was saved
					if ( isset( $_COOKIE['wp-saving-post'] ) && $_COOKIE['wp-saving-post'] === $post_id . '-check' ) {
						setcookie( 'wp-saving-post', $post_id . '-saved', time() + DAY_IN_SECONDS, ADMIN_COOKIE_PATH, COOKIE_DOMAIN, is_ssl() );
					}

					$users = isset($_REQUEST['users']) ? $_REQUEST['users'] : null;
					if($post_id && $users) {
						foreach ($users as $user) {
							RUA_App::instance()->level_manager->add_user_level((int)$user,$post_id);
						}
					}

					if(isset($_POST['original_post_status']) && $_POST['original_post_status'] != 'publish') {
						$message = 2;
					} else {
						$message = 1;
					}

					$sendback = add_query_arg(array(
						'level_id'   => $post_id,
						'message'    => $message,
						'page'       => 'wprua-edit'
					), $sendback);
					wp_safe_redirect($sendback);
					exit();
				case 'trash':
					check_admin_referer('trash-post_' . $post_id);

					if ( ! current_user_can( 'delete_post', $post_id ) )
						wp_die( __( 'You are not allowed to move this level to the Trash.', 'restrict-user-access' ) );

					if ( $user_id = wp_check_post_lock( $post_id ) ) {
						$user = get_userdata( $user_id );
						wp_die( sprintf( __( 'You cannot move this level to the Trash. %s is currently editing.', 'restrict-user-access' ), $user->display_name ) );
					}

					if ( ! wp_trash_post( $post_id ) )
						wp_die( __( 'Error in moving to Trash.' ) );

					$sendback = remove_query_arg('level_id',$sendback);

					wp_safe_redirect(add_query_arg(
						array(
							'page'    => 'wprua',
							'trashed' => 1,
							'ids'     => $post_id
						), $sendback ));
					exit();
				case 'untrash':
					check_admin_referer('untrash-post_' . $post_id);

					if ( ! current_user_can( 'delete_post', $post_id ) )
						wp_die( __( 'You are not allowed to restore this level from the Trash.', 'restrict-user-access' ) );

					if ( ! wp_untrash_post( $post_id ) )
						wp_die( __( 'Error in restoring from Trash.' ) );

					wp_safe_redirect( add_query_arg('untrashed', 1, $sendback) );
					exit();
				case 'delete':
					check_admin_referer('delete-post_' . $post_id);

					if ( ! current_user_can( 'delete_post', $post_id ) )
						wp_die( __( 'You are not allowed to delete this level.', 'restrict-user-access' ) );

					if ( ! wp_delete_post( $post_id, true ) )
						wp_die( __( 'Error in deleting.' ) );

					$sendback = remove_query_arg('level_id',$sendback);
					wp_safe_redirect( add_query_arg(array(
						'page' => 'wprua',
						'deleted' => 1
					), $sendback ));
					exit();
				case 'remove_user':
					check_admin_referer('update-post_' . $post_id);
					$users = is_array($_REQUEST['user']) ? $_REQUEST['user'] : array($_REQUEST['user']);
					$post_id = isset($_REQUEST['level_id']) ? $_REQUEST['level_id'] : $_REQUEST['post_ID'];
					foreach ($users as $user_id) {
						RUA_App::instance()->level_manager->remove_user_level($user_id,$post_id);
					}
					wp_safe_redirect($sendback.'#top#section-members');
					exit;
				default:
					do_action('rua/admin/action', $action, $post);
					break;
			}
		}
	}

	/**
	 * Render screen
	 *
	 * @since  0.15
	 * @return void
	 */
	public function render_screen() {

		global $nav_tabs, $post, $title, $active_post_lock;

		$post_ID = $post->ID;
		$post_type_object = get_post_type_object( $post->post_type );

		$message = false;
		if ( isset($_GET['message']) ) {
			$messages = $this->updated_messages($post);
			$_GET['message'] = absint( $_GET['message'] );
			if ( isset($messages[$_GET['message']]) )
				$message = $messages[$_GET['message']];
		}

		$notice = false;
		$form_extra = '';
		if ( 'auto-draft' == $post->post_status ) {
			if (isset($_REQUEST['level_id']) ) {
				$post->post_title = '';
			}
			//$autosave = false;
			$form_extra .= "<input type='hidden' id='auto_draft' name='auto_draft' value='1' />";
		}

		//Not only for decoration
		//Older wp versions inject updated message after first h2
		if (version_compare(get_bloginfo('version'), '4.3', '<')) {
			$tag = 'h2';
		} else {
			$tag = 'h1';
		}

		echo '<div class="wrap">';
		echo '<'.$tag.'>';
		echo esc_html( $title );
		if ( isset($_REQUEST['level_id']) && current_user_can( $post_type_object->cap->create_posts ) ) {
			echo ' <a href="' . esc_url( admin_url( 'admin.php?page=wprua-edit' ) ) . '" class="page-title-action add-new-h2">' . esc_html( $post_type_object->labels->add_new ) . '</a>';
		}
		echo '</'.$tag.'>';
		if ( $message ) {
			echo '<div id="message" class="updated notice notice-success is-dismissible"><p>'.$message.'</p></div>';
		}
		echo '<form name="post" action="admin.php?page=wprua-edit" method="post" id="post">';
		$referer = wp_get_referer();
		wp_nonce_field('update-post_' . $post_ID);
		echo '<input type="hidden" id="user-id" name="user_ID" value="'.(int)get_current_user_id().'" />';
		echo '<input type="hidden" id="_rua_section" name="_rua_section" value="" />';
		echo '<input type="hidden" id="hiddenaction" name="action" value="editpost" />';
		echo '<input type="hidden" id="post_author" name="post_author" value="'.esc_attr($post->post_author).'" />';
		echo '<input type="hidden" id="original_post_status" name="original_post_status" value="'.esc_attr( $post->post_status).'" />';
		echo '<input type="hidden" id="referredby" name="referredby" value="'.($referer ? esc_url( $referer ) : '').'" />';
		echo '<input type="hidden" id="post_ID" name="level_id" value="'.esc_attr($post_ID).'" />';
		if ( ! empty( $active_post_lock ) ) {
			echo '<input type="hidden" id="active_post_lock" value="'.esc_attr(implode( ':', $active_post_lock )).'" />';
		}
		if ( get_post_status( $post ) != 'draft') {
			wp_original_referer_field(true, 'previous');
		}
		echo $form_extra;

		wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );

		echo '<div id="poststuff">';
		echo '<div id="post-body" class="metabox-holder columns-'.(1 == get_current_screen()->get_columns() ? '1' : '2').'">';
		echo '<div id="post-body-content">';
		echo '<div id="titlediv">';
		echo '<div id="titlewrap">';
		echo '<label class="screen-reader-text" id="title-prompt-text" for="title">'.__( 'Enter title here' ).'</label>';
		echo '<input type="text" name="post_title" size="30" value="'.esc_attr( $post->post_title ).'" id="title" spellcheck="true" autocomplete="off" />';
		echo '</div></div>';
		$this->render_section_nav($nav_tabs);
		echo '</div>';
		$this->render_sections($nav_tabs,$post,$post->post_type);
		echo '</div>';
		echo '<br class="clear" />';
		echo '</div></form></div>';
	}

	/**
	 * Render tab navigation
	 *
	 * @since  0.15
	 * @param  array  $tabs
	 * @return void
	 */
	public function render_section_nav($tabs) {
		echo '<h2 class="nav-tab-wrapper js-rua-tabs hide-if-no-js " style="padding-bottom:0;">';
		foreach ($tabs as $id => $label) {
			echo '<a class="js-nav-link nav-tab" href="#top#section-'.$id.'">'.$label.'</a>';
		}
		echo '</h2>';
	}

	/**
	 * Render meta box sections
	 *
	 * @since  0.15
	 * @param  array    $tabs
	 * @param  WP_Post  $post
	 * @param  string   $post_type
	 * @return void
	 */
	public function render_sections($tabs, $post, $post_type) {
		echo '<div id="postbox-container-1" class="postbox-container">';
		do_meta_boxes(RUA_App::BASE_SCREEN.'-edit', 'side', $post);
		echo '</div>';
		echo '<div id="postbox-container-2" class="postbox-container">';
		foreach ($tabs as $id => $label) {
			$name = 'section-'.$id;
			echo '<div id="'.$name.'" class="rua-section">';
			do_meta_boxes(RUA_App::BASE_SCREEN.'-edit', $name, $post);
			echo '</div>';
		}
		//boxes across sections
		do_meta_boxes(RUA_App::BASE_SCREEN.'-edit', 'normal', $post);
		echo '</div>';
	}

	/**
	 * Update sidebar post type
	 *
	 * @since  0.15
	 * @return int
	 */
	public function update_level() {
		global $wpdb;

		$post_ID = (int) $_POST['level_id'];
		$post = get_post( $post_ID );
		$post_data['post_type'] = RUA_App::TYPE_RESTRICT;
		$post_data['ID'] = (int) $post_ID;
		$post_data['post_title'] = $_POST['post_title'];
		$post_data['comment_status'] = 'closed';
		$post_data['ping_status'] = 'closed';
		$post_data['post_author'] = get_current_user_id();
		$post_data['post_parent'] = isset($_POST['parent_id']) ? $_POST['parent_id'] : '';
		$post_data['post_status'] = 'publish';
		$post_data['post_name'] = isset($_POST['post_name']) ? $_POST['post_name'] : '';
		//$post_data['menu_order'] = intval($_POST['menu_order']);

		$ptype = get_post_type_object($post_data['post_type']);

		if ( !current_user_can( 'edit_post', $post_ID ) ) {
				wp_die( __('You are not allowed to edit this level.', 'restrict-user-access' ));
		} elseif (! current_user_can( $ptype->cap->create_posts ) ) {
				return new WP_Error( 'edit_others_posts', __( 'You are not allowed to create levels.', 'restrict-user-access' ) );
		} elseif ( $post_data['post_author'] != $_POST['post_author']
			 && ! current_user_can( $ptype->cap->edit_others_posts ) ) {
			return new WP_Error( 'edit_others_posts', __( 'You are not allowed to edit this level.', 'restrict-user-access' ) );
		}

		update_post_meta( $post_ID, '_edit_last', $post_data['post_author'] );
		$success = wp_update_post( $post_data );
		wp_set_post_lock( $post_ID );

		return $post_ID;
	}

	/**
	 * Get update messages
	 *
	 * @since  0.15
	 * @param  WP_Post  $post
	 * @return array
	 */
	public function updated_messages($post) {
		return array(
			1 => __('Access level updated.','restrict-user-access'),
			2 => __('Access level activated.','restrict-user-access'),
			3 => sprintf(__('Access level scheduled for: <strong>%1$s</strong>.','restrict-user-access'),
				// translators: Publish box date format, see http://php.net/date
				date_i18n(__('M j, Y @ G:i'),strtotime($post->post_date))),
			4 => __('Access level draft updated.','restrict-user-access'),
		);
	}

	/**
	 * Get level edit link
	 * TODO: Consider changing post type _edit_link instead
	 *
	 * @since  0.15
	 * @param  string  $link
	 * @param  int     $post_id
	 * @param  string  $context
	 * @return string
	 */
	public function get_edit_post_link($link, $post_id, $context) {
		$post = get_post($post_id);
		if($post->post_type == RUA_App::TYPE_RESTRICT) {
			$sep = '&';
			if($context == 'display') {
				$sep = '&amp;';
			}
			$link = admin_url('admin.php?page=wprua-edit'.$sep.'level_id='.$post_id);

			//load page in all languages for wpml
			if(defined('ICL_SITEPRESS_VERSION') || defined('POLYLANG_VERSION')) {
				$link .= $sep.'lang=all';
			}
		}
		return $link;
	}

	/**
	 * Get level delete link
	 * TODO: Consider changing post type _edit_link instead
	 *
	 * @since  0.15
	 * @param  string   $link
	 * @param  int      $post_id
	 * @param  boolean  $force_delete
	 * @return string
	 */
	public function get_delete_post_link($link, $post_id, $force_delete) {
		$post = get_post($post_id);
		if($post->post_type == RUA_App::TYPE_RESTRICT) {

			$action = ( $force_delete || !EMPTY_TRASH_DAYS ) ? 'delete' : 'trash';

			$link = add_query_arg(
				'action',
				$action,
				admin_url('admin.php?page=wprua-edit&level_id='.$post_id)
			);
			$link = wp_nonce_url( $link, "$action-post_{$post_id}" );
		}
		return $link;
	}

	/**
	 * Register and enqueue scripts styles
	 * for screen
	 *
	 * @since 0.15
	 */
	public function add_scripts_styles() {

		WPCACore::enqueue_scripts_styles('');

		wp_enqueue_script('rua/admin/edit', plugins_url('../js/edit.min.js', __FILE__), array('select2','jquery'), RUA_App::PLUGIN_VERSION);

		wp_enqueue_style('rua/style', plugins_url('../css/style.css', __FILE__), array(), RUA_App::PLUGIN_VERSION);

		//badgeos compat
		//todo: check that developers respond with a fix soon
		wp_register_script('badgeos-select2', '');
		wp_register_style( 'badgeos-select2-css', '');

	}

}

//eol