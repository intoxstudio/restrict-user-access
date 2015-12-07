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

final class RUA_Level_Edit {

	/**
	 * Members list table
	 * 
	 * @var RUA_Members_List
	 */
	protected $list_members;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->add_actions();
	}

	/**
	 * Add callbacks to actions queue
	 *
	 * @since 0.5
	 */
	protected function add_actions() {
		add_action('save_post_'.RUA_App::TYPE_RESTRICT,
			array($this,'save_post'));
		add_action('add_meta_boxes_'.RUA_App::TYPE_RESTRICT,
			array($this,'create_meta_boxes'));
		add_action('in_admin_header',
			array($this,'clear_admin_menu'),99);
		add_action("edit_form_after_title",
			array($this,"render_tab_navigation"));
		add_action('load-post.php' ,
			array($this, "process_requests"));
		add_action('edit_form_advanced',
			array($this,'render_screen_members'),999);
		add_action('wp_ajax_rua/user/suggest',
			array($this,'ajax_get_users'));

		add_action("wpca/modules/save-data",
			array($this,"save_condition_options"));
		add_action("wpca/group/settings",
			array($this,"render_condition_options"));
	}

	/**
	 * Get available users for level
	 *
	 * @since  0.6
	 * @return void
	 */
	public function ajax_get_users() {
		$user_query = new WP_User_Query(array(
			'search'         => '*'.$_REQUEST["q"].'*',
			'search_columns' => array('user_login','user_email','user_nicename'),
			'fields'         => array('ID','user_login','user_email'),
			'number'         => 10,
			'offset'         => 0,
			'meta_query'     => array(
		 		array(
					array(
						'key'     => WPCACore::PREFIX."level",
						'value'   => $_REQUEST["post_id"],
			 			'compare' => 'NOT EXISTS'
					)
				)
			)
		));
		wp_send_json($user_query->get_results());
	}

	/**
	 * Process custom requests
	 *
	 * @since  0.5
	 * @return void
	 */
	public function process_requests() {

		$screen = get_current_screen();

		if($screen->post_type == RUA_App::TYPE_RESTRICT) {

			$this->list_members = new RUA_Members_List();

			$action = $this->list_members->current_action();

			if($action) {

				//todo: check nonce

				if(isset($_REQUEST["_wp_http_referer"])) {
					$current_page = $_REQUEST["_wp_http_referer"];
				} else {
					$current_page = remove_query_arg(array('_wpnonce','action','action2','_wp_http_referer','user'));
					$current_page = add_query_arg("post",$_REQUEST['post'],$current_page);
					$current_page = add_query_arg("action","edit",$current_page);
				}
				switch($action) {
					case "add_users":
						$users = explode(",", $_REQUEST["users"]);
						foreach ($users as $user) {
							RUA_App::instance()->level_manager->_add_user_level((int)$user,$_REQUEST["post_ID"]);
						}
						wp_safe_redirect($current_page."#top#rua-members");
						exit;
					case "remove":
						$users = is_array($_REQUEST['user']) ? $_REQUEST['user'] : array($_REQUEST['user']);
						$post_id = isset($_REQUEST['post']) ? $_REQUEST['post'] : $_REQUEST['post_ID'];
						foreach ($users as $user_id) {
							RUA_App::instance()->level_manager->_remove_user_level($user_id,$post_id);
						}
						wp_safe_redirect($current_page."#top#rua-members");
						exit;
				}
			}
		}
	}

	/**
	 * Remove unwanted meta boxes
	 *
	 * @since  0.1
	 * @global array $wp_meta_boxes
	 * @return void 
	 */
	public function clear_admin_menu() {
		global $wp_meta_boxes;

		$screen = get_current_screen();

		// Post type not set on all pages in WP3.1
		if(!(isset($screen->post_type) && $screen->post_type == RUA_App::TYPE_RESTRICT & $screen->base == 'post'))
			return;

		// Names of whitelisted meta boxes
		$whitelist = array(
			'rua-plugin-links' => 'rua-plugin-links',
			'cas-groups'       => 'cas-groups',
			'cas-rules'        => 'cas-rules',
			'rua-options'      => 'rua-options',
			'submitdiv'        => 'submitdiv',
			'slugdiv'          => 'slugdiv'
		);

		// Loop through context (normal,advanced,side)
		foreach($wp_meta_boxes[RUA_App::TYPE_RESTRICT]as $context_k => $context_v) {
			// Loop through priority (high,core,default,low)
			foreach($context_v as $priority_k => $priority_v) {
				// Loop through boxes
				foreach($priority_v as $box_k => $box_v) {
					// If box is not whitelisted, remove it
					if(!isset($whitelist[$box_k])) {
						$wp_meta_boxes[RUA_App::TYPE_RESTRICT][$context_k][$priority_k][$box_k] = false;
						//unset($whitelist[$box_k]);
					}
				}
			}
		}
	}

	/**
	 * Meta boxes for restriction edit
	 *
	 * @since  0.1
	 * @return void 
	 */
	public function create_meta_boxes() {

		$boxes = array(
			//About
			array(
				'id'       => 'rua-plugin-links',
				'title'    => __('Restrict User Access', RUA_App::DOMAIN),
				'callback' => 'meta_box_support',
				'context'  => 'side',
				'priority' => 'high'
			),
			//Options
			array(
				'id'       => 'rua-options',
				'title'    => __('Options', RUA_App::DOMAIN),
				'callback' => 'meta_box_options',
				'context'  => 'side',
				'priority' => 'default'
			),
		);

		//Add meta boxes
		foreach($boxes as $box) {
			add_meta_box(
				$box['id'],
				$box['title'],
				array(&$this, $box['callback']),
				RUA_App::TYPE_RESTRICT,
				$box['context'],
				$box['priority']
			);
		}

		$screen = get_current_screen();

		$screen->add_help_tab( array( 
			'id'      => WPCACore::PREFIX.'help',
			'title'   => __('Condition Groups',RUA_App::DOMAIN),
			'content' => '<p>'.__('Each created condition group describe some specific content (conditions) that can be restricted for a selected role.',RUA_App::DOMAIN).'</p>'.
				'<p>'.__('Content added to a condition group uses logical conjunction, while condition groups themselves use logical disjunction. '.
				'This means that content added to a group should be associated, as they are treated as such, and that the groups do not interfere with each other. Thus it is possible to have both extremely focused and at the same time distinct conditions.',RUA_App::DOMAIN).'</p>',
		) );
		$screen->set_help_sidebar( '<h4>'.__('More Information').'</h4>'.
			'<p><a href="http://wordpress.org/support/plugin/restrict-user-access" target="_blank">'.__('Get Support',RUA_App::DOMAIN).'</a></p>'
		);

	}

	/**
	 * Display extra options for condition group
	 *
	 * @since  0.7
	 * @param  string  $post_type
	 * @return void
	 */
	public function render_condition_options($post_type) {
		if($post_type == RUA_App::TYPE_RESTRICT) {
			echo "<div><label>Drip content:";
			echo '<input class="js-rua-drip-option" type="number" value="<%= _.has(options,"_ca_opt_drip") ? options._ca_opt_drip : 0 %>" name="'.WPCACore::PREFIX.'opt_drip" /> '.__("days");
			echo "</label></div>";
		}
	}

	/**
	 * Meta box for options
	 *
	 * @since  0.1
	 * @return void
	 */
	public function meta_box_options($post) {

		RUA_App::instance()->level_manager->populate_metadata();

		$pages = wp_dropdown_pages(array(
			'post_type'        => $post->post_type,
			'exclude_tree'     => $post->ID,
			'selected'         => $post->post_parent,
			'name'             => 'parent_id',
			'show_option_none' => __('Do not extend',RUA_App::DOMAIN),
			'sort_column'      => 'menu_order, post_title',
			'echo'             => 0,
		));
		if ( ! empty($pages) ) {
?>
<span class="extend"><strong><?php _e('Extend',RUA_App::DOMAIN) ?></strong>
<label class="screen-reader-text" for="parent_id"><?php _e('Extend',RUA_App::DOMAIN) ?></label>
<p><?php echo $pages; ?></p>
</span>
<?php
		}

		$columns = array(
			'role',
			'handle',
			'page',
			'exposure'
		);

		foreach ($columns as $key => $value) {

			$id = is_numeric($key) ? $value : $key;

			echo '<span class="'.$id.'"><strong>' . RUA_App::instance()->level_manager->metadata()->get($id)->get_title() . '</strong>';
			echo '<p>';
			$values = explode(',', $value);
			foreach ($values as $val) {
				$this->_form_field($val);
			}
			echo '</p></span>';
		}

		$duration =  RUA_App::instance()->level_manager->metadata()->get("duration");
		$duration_val = "day";

		$duration_no = 0;
		$duration_arr = $duration->get_data($post->ID);
		if($duration_arr) {
			$duration_no = $duration_arr["count"];
			$duration_val = $duration_arr["unit"];
		}

		echo '<span class="duration"><strong>' . $duration->get_title() . '</strong>';
		echo '<p>';
		echo '<input type="number" min="0" name="duration[count]" value="'.$duration_no.'" style="width:60px;" />';
		echo '<select style="width:190px;" name="' . $duration->get_id() . '[unit]">' . "\n";
		foreach ($duration->get_input_list() as $key => $value) {
			echo '<option value="' . $key . '"' . selected($duration_val,$key,false) . '>' . $value . '</option>' . "\n";
		}
		echo '</select>' . "\n";
		echo '</p></span>';
	}
		
	/**
	 * Meta box for support
	 *
	 * @since  0.1
	 * @return void 
	 */
	public function meta_box_support() {
?>
			<div style="overflow:hidden;">
				<ul>
					<li><a href="https://wordpress.org/support/view/plugin-reviews/restict-user-access?rate=5#postform" target="_blank"><?php _e('Give a review on WordPress.org',RUA_App::DOMAIN); ?></a></li>
					<li><a href="https://wordpress.org/plugins/restrict-user-access/faq/" target="_blank"><?php _e('Read the FAQ',RUA_App::DOMAIN); ?></a></li>
					<li><a href="https://wordpress.org/support/plugin/restrict-user-access/" target="_blank"><?php _e('Get Support',RUA_App::DOMAIN); ?></a></li>
				</ul>
			</div>
		<?php
	}

	/**
	 * Render tabs and members list table
	 * on level edit screen
	 *
	 * @since  0.4
	 * @param  string  $post
	 * @return void
	 */
	public function render_tab_navigation($post) {
		if(get_post_type($post) == RUA_App::TYPE_RESTRICT) :

?>
	<h2 class="nav-tab-wrapper js-rua-tabs hide-if-no-js ">
		<a href="#top#normal-sortables" class="nav-tab nav-tab-active"><?php _e("Restrictions",RUA_App::DOMAIN); ?></a>
		<a href="#top#rua-members" class="nav-tab"><?php _e("Members",RUA_App::DOMAIN); ?></a>
		<a href="#top#rua-caps" class="nav-tab"><?php _e("Capabilities",RUA_App::DOMAIN); ?></a>
	</h2>
<?php
		endif;
	}

	/**
	 * Render members screen
	 *
	 * @since  0.6
	 * @param  WP_Post  $post
	 * @return void
	 */
	public function render_screen_members($post) {
		if(get_post_type($post) == RUA_App::TYPE_RESTRICT) :

			if(!$this->list_members)
				$this->list_members = new RUA_Members_List();

			$this->list_members->prepare_items();

			$list_caps = new RUA_Capabilities_List();
			$list_caps->prepare_items();

			echo '<div id="rua-members" style="display:none;">';
			echo '<input type="hidden" name="users" class="js-rua-user-suggest" value="" /> ';
			echo '<input type="submit" name="add_users" class="button button-primary" value="'.__("Add Members",RUA_App::DOMAIN).'" />';
			$this->list_members->display();

			echo "</div>";

			echo '<div id="rua-caps" style="display:none;">';
			echo '<input type="hidden" name="caps" value="" />';

			$list_caps->display();

			echo "</div>";
		endif;
	}

	/**
	 * Create form field for metadata
	 *
	 * @since  0.1
	 * @param  string $setting 
	 * @return void 
	 */
	private function _form_field($setting) {

		$setting = RUA_App::instance()->level_manager->metadata()->get($setting);
		$current = $setting->get_data(get_the_ID(),true);

		switch ($setting->get_input_type()) {
			case 'select' :
				echo '<select style="width:250px;" name="' . $setting->get_id() . '">' . "\n";
				foreach ($setting->get_input_list() as $key => $value) {
					echo '<option value="' . $key . '"' . selected($current,$key,false) . '>' . $value . '</option>' . "\n";
				}
				echo '</select>' . "\n";
				break;
			case 'checkbox' :
				echo '<ul>' . "\n";
				foreach ($setting->get_input_list() as $key => $value) {
					echo '<li><label><input type="checkbox" name="' . $setting->get_id() . '[]" value="' . $key . '"' . (in_array($key, $current) ? ' checked="checked"' : '') . ' /> ' . $value . '</label></li>' . "\n";
				}
				echo '</ul>' . "\n";
				break;
			case 'text' :
			default :
				echo '<input style="width:200px;" type="text" name="' . $setting->get_id() . '" value="' . $current . '" />' . "\n";
				break;
		}
	}
		
	/**
	 * Save metadata values for restriction
	 *
	 * @since  0.1
	 * @param  int  $post_id 
	 * @return void 
	 */
	public function save_post($post_id) {

		// Save button pressed
		if (!isset($_POST['original_publish']) && !isset($_POST['save_post']))
			return;

		// Check permissions
		if (!current_user_can(RUA_App::CAPABILITY, $post_id))
			return;

		// Check autosave
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return;

		// Update metadata
		foreach (RUA_App::instance()->level_manager->metadata()->get_all() as $field) {
			$new = isset($_POST[$field->get_id()]) ? $_POST[$field->get_id()] : false;
			$old = $field->get_data($post_id);

			if($new !== false) {
				if ($new != '' && $new != $old) {
					$field->update($post_id,$new);
				} elseif ($new == '' && $old != '') {
					$field->delete($post_id,$old);
				}
			}
		}
	}

	/**
	 * Save extra options for condition group
	 *
	 * @since  0.7
	 * @param  int  $group_id
	 * @return void
	 */
	public function save_condition_options($group_id) {
		$key = WPCACore::PREFIX."opt_drip";
		$value = isset($_POST[$key]) ? (int)$_POST[$key] : 0;
		if($value > 0) {
			update_post_meta($group_id,$key,$value);
		} else if(get_post_meta($group_id,$key,true)) {
			delete_post_meta($group_id,$key);
		}
	}
}

//eol