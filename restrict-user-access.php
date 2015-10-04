<?php
/**
 * @package Restrict User Access
 * @copyright Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 */
/*
Plugin Name: Restrict User Access
Plugin URI: 
Description: Easily restrict content and contexts to provide premium access for specific User Roles.
Version: 0.3.2
Author: Joachim Jensen, Intox Studio
Author URI: http://www.intox.dk/
Text Domain: restrict-user-access
Domain Path: /lang/
License: GPLv3

	Restrict User Access Plugin
	Copyright (C) 2015 Joachim Jensen - jv@intox.dk

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if (!defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit;
}

final class RestrictUserAccess {

	/**
	 * Plugin version
	 */
	const PLUGIN_VERSION       = '0.3.2';

	/**
	 * Post Type for restriction
	 */
	const TYPE_RESTRICT        = 'restriction';

	/**
	 * Language domain
	 */
	const DOMAIN               = 'restrict-user-access';

	/**
	 * Capability to manage restrictions
	 */
	const CAPABILITY           = 'edit_users';

	/**
	 * Metadata
	 * 
	 * @var WPCAObjectManager
	 */
	private $metadata;

	/**
	 * Access Levels
	 * 
	 * @var array
	 */
	private $levels            = array();

	/**
	 * Instance of class
	 * 
	 * @var RestrictUserAccess
	 */
	private static $_instance;

	/**
	 * Constructor
	 */
	public function __construct() {
		
		$this->_load_dependencies();
		
		//For administration
		if(is_admin()) {
			
			add_action('admin_enqueue_scripts',
				array(&$this,'load_admin_scripts'));
			add_action('save_post',
				array(&$this,'save_post'));
			add_action('add_meta_boxes_'.self::TYPE_RESTRICT,
				array(&$this,'create_meta_boxes'));
			add_action('in_admin_header',
				array(&$this,'clear_admin_menu'),99);
			add_action('manage_'.self::TYPE_RESTRICT.'_posts_custom_column',
				array(&$this,'admin_column_rows'),10,2);
			add_action( 'show_user_profile',
				array(&$this,'add_field_access_level'));
			add_action( 'edit_user_profile',
				array(&$this,'add_field_access_level'));
			add_action( 'personal_options_update',
				array(&$this,'save_user_profile'));
			add_action( 'edit_user_profile_update',
				array(&$this,'save_user_profile'));
			add_action('edit_form_top',
				array($this,'render_members_list'));

			add_filter('request',
				array(&$this,'admin_column_orderby'));
			add_filter('manage_'.self::TYPE_RESTRICT.'_posts_columns',
				array(&$this,'admin_column_headers'),99);
			add_filter('manage_edit-'.self::TYPE_RESTRICT.'_sortable_columns',
				array(&$this,'admin_column_sortable_headers'));
			add_filter('post_updated_messages',
				array(&$this,'restriction_updated_messages'));
			add_filter( 'manage_users_columns',
				array(&$this,'add_user_column_headers'));
			add_filter( 'manage_users_custom_column',
				array(&$this,'add_user_columns'), 10, 3 );
		}

		add_action('template_redirect',
			array(&$this,'authorize_access'));
		add_action('init',
			array(&$this,'load_textdomain'));
		add_action('init',
			array(&$this,'create_restrict_type'),99);

		add_shortcode( 'restrict', array($this,'shortcode_restrict'));
	}

	/**
	 * Restrict content in shortcode
	 * 
	 * @version 0.1
	 * @param   array     $atts
	 * @param   string    $content
	 * @return  string
	 */
	public function shortcode_restrict( $atts, $content = null ) {
		$a = shortcode_atts( array(
			'role' => 0,
			'page' => -1
		), $atts );

		if(!array_intersect(explode(",", $a['role']), $this->_get_user_roles())) {
			$content = "";
			$page = get_post($a["page"]);
			if($page) {
				setup_postdata($page);
				$content = get_the_content();
				wp_reset_postdata();
			}
		}

		return do_shortcode($content);
	}

	/**
	 * Instantiates and returns class singleton
	 *
	 * @since  0.1
	 * @return RestrictUserAccess 
	 */
	public static function instance() {
		if(!self::$_instance) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	/**
	 * Load plugin textdomain for languages
	 *
	 * @since  0.1
	 * @return void 
	 */
	public function load_textdomain() {
		load_plugin_textdomain(self::DOMAIN, false, dirname(plugin_basename(__FILE__)).'/lang/');
	}

	/**
	 * Get instance of metadata manager
	 *
	 * @since  1.0
	 * @return WPCAObjectManager
	 */
	private function metadata() {
		if(!$this->metadata) {
			$this->metadata = new WPCAObjectManager();
		}
		return $this->metadata;
	}
	
	/**
	 * Create and populate metadata fields
	 *
	 * @since  0.1
	 * @return void 
	 */
	private function _init_metadata() {

		$role_list = array(
			-1 => __("Do not synchronize",self::DOMAIN),
			0 => __('Not logged-in',self::DOMAIN)
		);
		$posts_list = array();
		if(is_admin()) {
			foreach(get_editable_roles() as $id => $role) {
				$role_list[$id] = $role['name'];
			}

			//TODO: autocomplete instead of getting all pages
			foreach(get_posts(array(
				'posts_per_page' => -1,
				'orderby'        => 'post_title',
				'order'          => 'ASC',
				'post_type'      => 'page'
			)) as $post) {
				$posts_list[$post->ID] = $post->post_title;
			}
		}


		$this->metadata()
		->add(new WPCAMeta(
			'exposure',
			__('Exposure', self::DOMAIN),
			1,
			'select',
			array(
				__('Singular', self::DOMAIN),
				__('Singular & Archive', self::DOMAIN),
				__('Archive', self::DOMAIN)
			)
		),'exposure')
		->add(new WPCAMeta(
			'role',
			__('Synchronized Role'),
			-1,
			'select',
			$role_list
		),'role')
		->add(new WPCAMeta(
			'handle',
			_x('Action','option', self::DOMAIN),
			0,
			'select',
			array(
				0 => __('Redirect', self::DOMAIN),
				1 => __('Tease', self::DOMAIN)
			),
			__('Redirect to another page or show teaser.', self::DOMAIN)
		),'handle')
		->add(new WPCAMeta(
			'page',
			__('Page'),
			0,
			'select',
			$posts_list,
			__('Page to redirect to or display content from under teaser.', self::DOMAIN)
		),'page');
		// ->add(new WPCAMeta(
		// 	'duration',
		// 	__('Duration'),
		// 	0,
		// 	'text',
		// 	$posts_list,
		// 	__('Page to redirect to or display content from under teaser.', self::DOMAIN)
		// ),'duration');
	}
	
	/**
	 * Create restrict post type and add it to WPCACore
	 *
	 * @since  0.1
	 * @return void 
	 */
	public function create_restrict_type() {

		$capabilities = array(
			'edit_post'          => self::CAPABILITY,
			'read_post'          => self::CAPABILITY,
			'delete_post'        => self::CAPABILITY,
			'edit_posts'         => self::CAPABILITY,
			'delete_posts'       => self::CAPABILITY,
			'edit_others_posts'  => self::CAPABILITY,
			'publish_posts'      => self::CAPABILITY,
			'read_private_posts' => self::CAPABILITY
		);
		
		// Register the sidebar type
		register_post_type(self::TYPE_RESTRICT,array(
			'labels'        => array(
				'name'               => __('Access Levels', self::DOMAIN),
				'singular_name'      => __('Access Level', self::DOMAIN),
				'add_new'            => _x('Add New', 'level', self::DOMAIN),
				'add_new_item'       => __('Add New Access Level', self::DOMAIN),
				'edit_item'          => __('Edit Access Level', self::DOMAIN),
				'new_item'           => __('New Access Level', self::DOMAIN),
				'all_items'          => __('Access Levels', self::DOMAIN),
				'view_item'          => __('View Access Level', self::DOMAIN),
				'search_items'       => __('Search Access Levels', self::DOMAIN),
				'not_found'          => __('No Access Levels found', self::DOMAIN),
				'not_found_in_trash' => __('No Access Levels found in Trash', self::DOMAIN),
				'parent_item_colon'  => __('Extend Level', self::DOMAIN),
				//wp-content-aware-engine specific
				'ca_title'           => __('Grant explicit access to',self::DOMAIN),
				'ca_not_found'       => __('No content. Please add at least one condition group to restrict content.',self::DOMAIN)
			),
			'capabilities'  => $capabilities,
			'show_ui'       => true,
			'show_in_menu'  => 'users.php',
			'query_var'     => false,
			'rewrite'       => false,
			'hierarchical'  => true,
			'menu_position' => 26.099, //less probable to be overwritten
			'supports'      => array('title','page-attributes'),
			'menu_icon'     => ''
		));

		WPCACore::post_types()->add(self::TYPE_RESTRICT);
	}
	
	/**
	 * Create update messages
	 *
	 * @since  0.1
	 * @param  array  $messages 
	 * @return array           
	 */
	public function restriction_updated_messages( $messages ) {
		$messages[self::TYPE_RESTRICT]= array(
			0 => '',
			1 => __('Restriction updated.',self::DOMAIN),
			2 => '',
			3 => '',
			4 => __('Restriction updated.',self::DOMAIN),
			5 => '',
			6 => __('Restriction published.',self::DOMAIN),
			7 => __('Restriction saved.',self::DOMAIN),
			8 => __('Restriction submitted.',self::DOMAIN),
			9 => sprintf(__('Restriction scheduled for: <strong>%1$s</strong>.',self::DOMAIN),
				// translators: Publish box date format, see http://php.net/date
				date_i18n(__('M j, Y @ G:i'),strtotime(get_the_ID()))),
			10 => __('Restriction draft updated.',self::DOMAIN),
		);
		return $messages;
	}

	/**
	 * Add admin column headers
	 *
	 * @since  0.1
	 * @param  array $columns 
	 * @return array          
	 */
	public function admin_column_headers($columns) {

		// Load metadata
		if (!$this->metadata)
			$this->_init_metadata();
		// Totally discard current columns and rebuild
		return array(
			'cb'        => $columns['cb'],
			'title'     => $columns['title'],
			'role'      => __("Members",self::DOMAIN),
			'handle'    => $this->metadata()->get('handle')->get_title(),
			'date'      => $columns['date']
		);
	}
		
	/**
	 * Make some columns sortable
	 *
	 * @since  0.1
	 * @param  array $columns 
	 * @return array
	 */
	public function admin_column_sortable_headers($columns) {
		return array_merge(
			array(
				'handle' => 'handle',
				'role'   => 'role'
			), $columns
		);
	}
	
	/**
	 * Manage custom column sorting
	 *
	 * @since  0.1
	 * @param  array $vars 
	 * @return array 
	 */
	public function admin_column_orderby($vars) {
		if (isset($vars['orderby']) && in_array($vars['orderby'], array('role', 'handle'))) {
			$vars = array_merge($vars, array(
				'meta_key' => WPCACore::PREFIX . $vars['orderby'],
				'orderby'  => 'meta_value'
			));
		}
		return $vars;
	}
	
	/**
	 * Add admin column rows
	 *
	 * @since  0.1
	 * @param  string $column_name 
	 * @param  int    $post_id
	 * @return void
	 */
	public function admin_column_rows($column_name, $post_id) {

		$retval = $this->metadata()->get($column_name);

		if($retval) {

			$retval = $retval->get_list_data($post_id);

			$data = $this->metadata()->get($column_name)->get_data($post_id);
			
			if ($column_name == 'handle' && $data != 2) {
				//TODO: with autocomplete, only fetch needed pages
				$page = $this->metadata()->get('page')->get_list_data($post_id);
				$retval .= ": " . ($page ? $page : '<span style="color:red;">' . __('Please update Page', self::DOMAIN) . '</span>');
			} else if($column_name == "role" && $data == "-1") {
				$retval = count(get_users(array('meta_key' => WPCACore::PREFIX."level", 'meta_value' => $post_id)));
				$retval = '<a href="post.php?post='.$post_id.'&action=edit#top#rua-members">'.$retval.'</a>';
			}
		}
		
		echo $retval;
	}

	/**
	 * Add Access Level to user profile
	 *
	 * @since 0.3
	 * @param WP_User  $user
	 */
	public function add_field_access_level( $user ) {
		if(current_user_can(self::CAPABILITY)) {
			$levels = $this->_get_levels();
			$user_levels = $this->_get_user_levels($user,false);
?>
			<h3>Access</h3>
			<table class="form-table">
				<tr>
					<th><label for="_ca_level">Access Levels</label></th>
					<td>
						<p><label>
							<input type="radio" name="_ca_level" value="0" <?php checked(empty($user_levels),true); ?> />
							<?php _e("No Access Level",self::DOMAIN); ?>
						</label></p>
					<?php foreach($levels as $level) :
					 ?>
						<p><label>
							<input type="radio" name="_ca_level" value="<?php echo esc_attr($level->ID); ?>" <?php checked( in_array($level->ID,$user_levels),true); ?> />
							<?php echo $level->post_title; ?>
						</label></p>
					<?php endforeach; ?>
					</td>
				</tr>
			</table>
<?php
		}
	}

	/**
	 * Save additional data for
	 * user profile
	 *
	 * @since  0.3
	 * @param  int  $user_id
	 * @return void
	 */
	public function save_user_profile( $user_id ) {
		if ( !current_user_can(self::CAPABILITY) )
			return false;

		$level = isset($_POST[WPCACore::PREFIX.'level']) ? $_POST[WPCACore::PREFIX.'level'] : null;

		if($level) {
			$this->_add_user_level($user_id,$level);
		} else {
			$user = get_userdata($user_id);
			$level = $this->_get_user_levels($user,false);
			if($level) {
				$this->_remove_user_level($user_id,$level[0]);
			}
		}
	}

	/**
	 * Add column headers on
	 * User overview
	 *
	 * @since 0.3
	 * @param array  $column
	 */
	public function add_user_column_headers( $column ) {
		$column['level'] = __('Access Level',self::DOMAIN);
		return $column;
	}

	/**
	 * Add columns on user overview
	 *
	 * @since 0.3
	 * @param [type]  $val
	 * @param string  $column_name
	 * @param int     $user_id
	 */
	public function add_user_columns( $val, $column_name, $user_id ) {
		$user = get_userdata( $user_id );
		switch ($column_name) {
			case 'level' :
				$levels = $this->_get_levels();
				$level_links = array();
				foreach ($this->_get_user_levels($user,false) as $user_level) {
					$user_level = isset($levels[$user_level]) ? $levels[$user_level] : null;
					if($user_level) {
						$level_links[] = '<a href="'.admin_url( 'post.php?post='.$user_level->ID.'&action=edit').'">'.$user_level->post_title.'</a>';
					}
				}
				return implode(", ", $level_links);
				break;
			default:
		}
		return $return;
	}

	/**
	 * Add level to user
	 *
	 * @since  0.3
	 * @param  int           $user_id
	 * @param  int           $level_id
	 * @return int|boolean
	 */
	private function _add_user_level($user_id,$level_id) {
		$user_level = update_user_meta( $user_id, WPCACore::PREFIX."level", $level_id);
		if($user_level) {
			add_user_meta($user_id,WPCACore::PREFIX."level_".$level_id,time(),true);
		}
		return $level_id;
	}

	/**
	 * Remove level from user
	 *
	 * @since  0.3
	 * @param  $int    $user_id
	 * @param  $int    $level_id
	 * @return boolean
	 */
	private function _remove_user_level($user_id,$level_id) {
		return delete_user_meta($user_id,WPCACore::PREFIX."level",$level_id) &&
			delete_user_meta($user_id,WPCACore::PREFIX."level_".$level_id);
	}

	// private function _is_user_level_expired($user_id,$level) {
	// 	$level_created = get_user_meta($user_id,WPCACore::PREFIX."level_".$level->ID,true);
	// 	if($level_created) {
	// 		$this->metadata()->get('duration')->get_data($level->ID);
	// 		//todo: compare dates.
	// 		//duration should be stored/parsed correctly and human readable
	// 	}
	// 	return false;
	// }

	/**
	 * Get roles from specific user
	 * or 0 if not logged in
	 *
	 * @since  0.1
	 * @param  WP_User  $user
	 * @return array
	 */
	private function _get_user_roles($user = null) {
		$roles = array();
		if(is_user_logged_in()) {
			if(!$user) {
				$user = wp_get_current_user();
			}
			$roles = $user->roles;
		} else {
			$roles[] = '0';
		}
		return $roles;
	}

	/**
	 * Get user levels traversed to their base
	 *
	 * @since  0.3
	 * @param  WP_User  $user
	 * @return array
	 */
	private function _get_user_levels($user = null,$hierarchical = true) {
		$levels = array();
		if($user || is_user_logged_in()) {
			if(!$user) {
				$user = wp_get_current_user();
			}
			$levels = get_user_meta($user->ID, WPCACore::PREFIX."level", false);
			if($hierarchical) {
				$extended_levels = array();
				foreach($levels as $level) {
					//todo: check for expired here and exclude
					$levels = array_merge($levels,get_ancestors((int)$level,self::TYPE_RESTRICT));
				}
			}
		} else {
			$levels[] = '0';
		}
		return $levels;
	}

	/**
	 * Get all levels not synced with roles
	 *
	 * @since  0.3
	 * @return array
	 */
	private function _get_levels() {
		if(!$this->levels) {
			$levels = get_posts(array(
				'numberposts' => -1,
				'post_type'   => self::TYPE_RESTRICT,
				'post_status' => array('publish','private','future'),
				'meta_query'  => array(
					array(
						'key' => WPCACore::PREFIX.'role',
						'value' => '-1',
					)
				)
			));
			foreach ($levels as $level) {
				$this->levels[$level->ID] = $level;
			}
		}
		return $this->levels;
	}

	/**
	 * Get conditional restrictions 
	 * and authorize access for user
	 * 
	 * @since  0.1
	 * @return void
	 */
	public function authorize_access() {

		$posts = WPCACore::get_posts(self::TYPE_RESTRICT);

		if ($posts) {
			$kick = 0;
			$roles = $this->_get_user_roles();
			$levels = $this->_get_user_levels();
			$this->_init_metadata();
			foreach ($posts as $post) {
				$role = $this->metadata()->get('role')->get_data($post->ID);
				if($role != '-1') {
					if(!in_array($role, $roles)) {
						$kick = $post->ID;
					} else {
						$kick = 0;
						break;
					}
				}// else {
					if(!in_array($post->ID, $levels)) {
						$kick = $post->ID;
					} else {
						$kick = 0;
						break;
					}
				//}
			}
			if($kick) {
				$action = $this->metadata()->get('handle')->get_data($kick);
				self::$page = $this->metadata()->get('page')->get_data($kick);
				switch($action) {
					case 0:
						if(self::$page != get_the_ID()) {
							wp_safe_redirect(get_permalink(self::$page));
							exit;
						}
						break;
					case 1:
						add_filter( 'the_content', array($this,'content_tease'), 8);
						break;
					default: break;
				}
				return;
			}
		}
	}

	/**
	 * Carry over page from restriction metadata
	 * @var integer
	 */
	public static $page = 0;

	/**
	 * Limit content to only show teaser and
	 * page content from restriction metadata
	 *
	 * @since   0.1
	 * @param   string    $content
	 * @return  string
	 */
	public function content_tease( $content ) {
		if ( preg_match( '/(<span id="more-[0-9]*"><\/span>)/', $content, $matches ) ) {
			$teaser = explode($matches[0], $content, 2);
			$content = $teaser[0];
		} else {
			$content = "";
		}

		if(self::$page) {
			setup_postdata(get_post(self::$page));
			$content .= get_the_content();
			wp_reset_postdata();
		}

		return $content;
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
		if(!(isset($screen->post_type) && $screen->post_type == self::TYPE_RESTRICT & $screen->base == 'post'))
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
		foreach($wp_meta_boxes[self::TYPE_RESTRICT]as $context_k => $context_v) {
			// Loop through priority (high,core,default,low)
			foreach($context_v as $priority_k => $priority_v) {
				// Loop through boxes
				foreach($priority_v as $box_k => $box_v) {
					// If box is not whitelisted, remove it
					if(!isset($whitelist[$box_k])) {
						$wp_meta_boxes[self::TYPE_RESTRICT][$context_k][$priority_k][$box_k] = false;
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

		$this->_init_metadata();

		$boxes = array(
			//About
			array(
				'id'       => 'rua-plugin-links',
				'title'    => __('Restrict User Access', self::DOMAIN),
				'callback' => 'meta_box_support',
				'context'  => 'side',
				'priority' => 'high'
			),
			//Options
			array(
				'id'       => 'rua-options',
				'title'    => __('Options', self::DOMAIN),
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
				self::TYPE_RESTRICT,
				$box['context'],
				$box['priority']
			);
		}

		$screen = get_current_screen();

		$screen->add_help_tab( array( 
			'id'      => WPCACore::PREFIX.'help',
			'title'   => __('Condition Groups',self::DOMAIN),
			'content' => '<p>'.__('Each created condition group describe some specific content (conditions) that can be restricted for a selected role.',self::DOMAIN).'</p>'.
				'<p>'.__('Content added to a condition group uses logical conjunction, while condition groups themselves use logical disjunction. '.
				'This means that content added to a group should be associated, as they are treated as such, and that the groups do not interfere with each other. Thus it is possible to have both extremely focused and at the same time distinct conditions.',self::DOMAIN).'</p>',
		) );
		$screen->set_help_sidebar( '<h4>'.__('More Information').'</h4>'.
			'<p><a href="http://wordpress.org/support/plugin/restrict-user-access" target="_blank">'.__('Get Support',self::DOMAIN).'</a></p>'
		);

	}

	/**
	 * Meta box for options
	 *
	 * @since  0.1
	 * @return void
	 */
	public function meta_box_options($post) {

		$pages = wp_dropdown_pages(array(
			'post_type'        => $post->post_type,
			'exclude_tree'     => $post->ID,
			'selected'         => $post->post_parent,
			'name'             => 'parent_id',
			'show_option_none' => __('Do not extend',self::DOMAIN),
			'sort_column'      => 'menu_order, post_title',
			'echo'             => 0,
		));
		if ( ! empty($pages) ) {
?>
<span class="extend"><strong><?php _e('Extend',self::DOMAIN) ?></strong>
<label class="screen-reader-text" for="parent_id"><?php _e('Extend',self::DOMAIN) ?></label>
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

			echo '<span class="'.$id.'"><strong>' . $this->metadata()->get($id)->get_title() . '</strong>';
			echo '<p>';
			$values = explode(',', $value);
			foreach ($values as $val) {
				$this->_form_field($val);
			}
			echo '</p></span>';
		}
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
					<li><a href="https://wordpress.org/support/view/plugin-reviews/restict-user-access?rate=5#postform" target="_blank"><?php _e('Give a review on WordPress.org',self::DOMAIN); ?></a></li>
					<li><a href="https://wordpress.org/plugins/restrict-user-access/faq/" target="_blank"><?php _e('Read the FAQ',self::DOMAIN); ?></a></li>
					<li><a href="https://wordpress.org/support/plugin/restrict-user-access/" target="_blank"><?php _e('Get Support',self::DOMAIN); ?></a></li>
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
	public function render_members_list($post) {
		if(get_post_type($post) == self::TYPE_RESTRICT) :

			require_once(plugin_dir_path( __FILE__ )."list-members.php");
			$members_list_table = new RUA_Members_List();
			$members_list_table->prepare_items();
?>
	<h2 class="nav-tab-wrapper js-rua-tabs hide-if-no-js ">
		<a href="#top#poststuff" class="nav-tab nav-tab-active"><?php _e("Restrictions",self::DOMAIN); ?></a>
		<a href="#top#rua-members" class="nav-tab"><?php _e("Members",self::DOMAIN); ?></a>
	</h2>
	<div id="rua-members" style="display:none;">
		<?php $members_list_table->display(); ?>
	</div>
<?php
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

		$setting = $this->metadata()->get($setting);
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

		// Only sidebar type
		if (get_post_type($post_id) != self::TYPE_RESTRICT)
			return;

		// Verify nonce
		if (!check_admin_referer(WPCACore::PREFIX.$post_id, WPCACore::NONCE))
			return;

		// Check permissions
		if (!current_user_can(self::CAPABILITY, $post_id))
			return;

		// Check autosave
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return;

		// Load metadata
		$this->_init_metadata();

		// Update metadata
		foreach ($this->metadata()->get_all() as $field) {
			$new = isset($_POST[$field->get_id()]) ? $_POST[$field->get_id()] : '';
			$old = $field->get_data($post_id);

			if ($new != '' && $new != $old) {
				$field->update($post_id,$new);
			} elseif ($new == '' && $old != '') {
				$field->delete($post_id,$old);
			}
		}
	}

	/**
	 * Load scripts and styles for administration
	 * 
	 * @since  0.1
	 * @param  string  $hook
	 * @return void
	 */
	public function load_admin_scripts($hook) {

		$current_screen = get_current_screen();

		if($current_screen->post_type == self::TYPE_RESTRICT){

			wp_register_script('rua/admin/edit', plugins_url('/js/edit.js', __FILE__), array(), self::PLUGIN_VERSION);

			wp_register_style('rua/style', plugins_url('/css/style.css', __FILE__), array(), self::PLUGIN_VERSION);

			//Sidebar editor
			if ($current_screen->base == 'post') {
				wp_enqueue_script('rua/admin/edit');
				wp_enqueue_style('rua/style');
			//Sidebar overview
			} else if ($hook == 'edit.php') {
				wp_enqueue_style('rua/style');
			}
		}

	}
	
	/**
	 * Load dependencies
	 *
	 * @since  0.1
	 * @return void
	 */
	private function _load_dependencies() {
		$path = plugin_dir_path( __FILE__ );
		require($path.'/lib/wp-content-aware-engine/core.php');
	}

}

// Launch plugin
RestrictUserAccess::instance();

//eol