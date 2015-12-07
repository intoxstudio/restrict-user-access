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

final class RUA_Level_Manager {

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
	 * User level capabilities
	 * @var array
	 */
	private $user_levels_caps  = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		
		if(is_admin()) {
			new RUA_Level_Edit();
			new RUA_Level_Overview();
		}

		$this->add_actions();
		$this->add_filters();

		add_shortcode( 'restrict', array($this,'shortcode_restrict'));
	}

	/**
	 * Add callbacks to actions queue
	 *
	 * @since 0.5
	 */
	protected function add_actions() {
		add_action('template_redirect',
			array($this,'authorize_access'));
		add_action('init',
			array($this,'create_restrict_type'),99);
	}

	/**
	 * Add callbacks to filters queue
	 *
	 * @since 0.5
	 */
	protected function add_filters() {
		if(is_admin()) {
			add_filter('post_updated_messages',
				array($this,'restriction_updated_messages'));
			add_filter( 'bulk_post_updated_messages',
				array($this,'restriction_updated_bulk_messages'), 10, 2 );
		}

		add_filter( 'user_has_cap',
			array($this,"user_level_has_cap"), 99, 3 );
	}

	/**
	 * Get level by name
	 *
	 * @since  0.6
	 * @param  string  $name
	 * @return WP_Post|boolean
	 */
	public function get_level_by_name($name) {
		$levels = get_posts(array(
			'name'           => $name,
			'posts_per_page' => 1,
			'post_type'      => RUA_App::TYPE_RESTRICT,
			'post_status'    => 'publish'
		));
		return $levels ? $levels[0] : false;
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
			'role'  => "",
			'level' => "",
			'page'  => 0
		), $atts );

		if(!$this->_has_global_access()) {
			if($a["level"]) {
				$level = $this->get_level_by_name($a["level"]);
				if($level) {
					$user_levels = array_flip($this->_get_user_levels());
					if(!isset($user_levels[$level->ID])) {
						$content = "";
					}
				}
			}
			else if($a['role'] !== "") {
				if(!array_intersect(explode(",", $a['role']), $this->_get_user_roles())) {
					$content = "";
				}
			}
			if($a["page"]) {
				$page = get_post($a["page"]);
				if($page) {
					setup_postdata($page);
					$content = get_the_content();
					wp_reset_postdata();
				}
			}
		}
		return do_shortcode($content);
	}

	/**
	 * Get instance of metadata manager
	 *
	 * @since  1.0
	 * @return WPCAObjectManager
	 */
	public function metadata() {
		if(!$this->metadata) {
			$this->_init_metadata();
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

		$this->metadata = new WPCAObjectManager();
		$this->metadata
		->add(new WPCAMeta(
			'exposure',
			__('Exposure', RUA_App::DOMAIN),
			1,
			'select',
			array(
				__('Singular', RUA_App::DOMAIN),
				__('Singular & Archive', RUA_App::DOMAIN),
				__('Archive', RUA_App::DOMAIN)
			)
		),'exposure')
		->add(new WPCAMeta(
			'role',
			__('Synchronized Role'),
			-1,
			'select',
			array()
		),'role')
		->add(new WPCAMeta(
			'handle',
			_x('Action','option', RUA_App::DOMAIN),
			0,
			'select',
			array(
				0 => __('Redirect', RUA_App::DOMAIN),
				1 => __('Tease', RUA_App::DOMAIN)
			),
			__('Redirect to another page or show teaser.', RUA_App::DOMAIN)
		),'handle')
		->add(new WPCAMeta(
			'page',
			__('Page'),
			0,
			'select',
			array(),
			__('Page to redirect to or display content from under teaser.', RUA_App::DOMAIN)
		),'page')
		->add(new WPCAMeta(
			'duration',
			__('Duration'),
			"day",
			'select',
			array(
				"day"   => __("Days",RUA_App::DOMAIN),
				"week"  => __("Weeks",RUA_App::DOMAIN),
				"month" => __("Months",RUA_App::DOMAIN),
				"year"  => __("Years",RUA_App::DOMAIN)
			),
			__('Set to 0 for unlimited.', RUA_App::DOMAIN)
		),'duration')
		->add(new WPCAMeta(
			'caps',
			__('Capabilities'),
			"",
			'',
			array(),
			__('Description.', RUA_App::DOMAIN)
		),'caps');

		apply_filters("rua/metadata",$this->metadata);
	}

	/**
	 * Populate input fields for metadata
	 *
	 * @since  0.8
	 * @return void
	 */
	public function populate_metadata() {

		$role_list = array(
			-1 => __("Do not synchronize",RUA_App::DOMAIN),
			0 => __('Not logged-in',RUA_App::DOMAIN)
		);

		foreach(get_editable_roles() as $id => $role) {
			$role_list[$id] = $role['name'];
		}

		$posts_list = array();
		//TODO: autocomplete instead of getting all pages
		foreach(get_posts(array(
			'posts_per_page' => -1,
			'orderby'        => 'post_title',
			'order'          => 'ASC',
			'post_type'      => 'page'
		)) as $post) {
			$posts_list[$post->ID] = $post->post_title;
		}

		$this->metadata()->get("role")->set_input_list($role_list);
		$this->metadata()->get("page")->set_input_list($posts_list);
	}
	
	/**
	 * Create restrict post type and add it to WPCACore
	 *
	 * @since  0.1
	 * @return void 
	 */
	public function create_restrict_type() {
		
		// Register the sidebar type
		register_post_type(RUA_App::TYPE_RESTRICT,array(
			'labels'        => array(
				'name'               => __('Access Levels', RUA_App::DOMAIN),
				'singular_name'      => __('Access Level', RUA_App::DOMAIN),
				'add_new'            => _x('Add New', 'level', RUA_App::DOMAIN),
				'add_new_item'       => __('Add New Access Level', RUA_App::DOMAIN),
				'edit_item'          => __('Edit Access Level', RUA_App::DOMAIN),
				'new_item'           => __('New Access Level', RUA_App::DOMAIN),
				'all_items'          => __('Access Levels', RUA_App::DOMAIN),
				'view_item'          => __('View Access Level', RUA_App::DOMAIN),
				'search_items'       => __('Search Access Levels', RUA_App::DOMAIN),
				'not_found'          => __('No Access Levels found', RUA_App::DOMAIN),
				'not_found_in_trash' => __('No Access Levels found in Trash', RUA_App::DOMAIN),
				'parent_item_colon'  => __('Extend Level', RUA_App::DOMAIN),
				//wp-content-aware-engine specific
				'ca_title'           => __('Members will get exclusive access to',RUA_App::DOMAIN),
				'ca_not_found'       => __('No content. Please add at least one condition group to restrict content.',RUA_App::DOMAIN)
			),
			'capabilities'  => array(
				'edit_post'          => RUA_App::CAPABILITY,
				'read_post'          => RUA_App::CAPABILITY,
				'delete_post'        => RUA_App::CAPABILITY,
				'edit_posts'         => RUA_App::CAPABILITY,
				'delete_posts'       => RUA_App::CAPABILITY,
				'edit_others_posts'  => RUA_App::CAPABILITY,
				'publish_posts'      => RUA_App::CAPABILITY,
				'read_private_posts' => RUA_App::CAPABILITY
			),
			'show_ui'       => true,
			'show_in_menu'  => 'users.php',
			'query_var'     => false,
			'rewrite'       => false,
			'hierarchical'  => true,
			'menu_position' => 26.099, //less probable to be overwritten
			'supports'      => array('title','page-attributes'),
			'menu_icon'     => ''
		));

		WPCACore::post_types()->add(RUA_App::TYPE_RESTRICT);
	}
	
	/**
	 * Create update messages
	 *
	 * @since  0.1
	 * @param  array  $messages 
	 * @return array           
	 */
	public function restriction_updated_messages( $messages ) {
		$messages[RUA_App::TYPE_RESTRICT]= array(
			0 => '',
			1 => __('Access level updated.',RUA_App::DOMAIN),
			2 => '',
			3 => '',
			4 => __('Access level updated.',RUA_App::DOMAIN),
			5 => '',
			6 => __('Access level published.',RUA_App::DOMAIN),
			7 => __('Access level saved.',RUA_App::DOMAIN),
			8 => __('Access level submitted.',RUA_App::DOMAIN),
			9 => sprintf(__('Access level scheduled for: <strong>%1$s</strong>.',RUA_App::DOMAIN),
				// translators: Publish box date format, see http://php.net/date
				date_i18n(__('M j, Y @ G:i'),strtotime(get_the_ID()))),
			10 => __('Access level draft updated.',RUA_App::DOMAIN),
		);
		return $messages;
	}

	/**
	 * Create bulk update messages
	 *
	 * @since  0.4
	 * @param  array  $messages
	 * @param  array  $counts
	 * @return array
	 */
	public function restriction_updated_bulk_messages( $messages, $counts ) {
		$messages[RUA_App::TYPE_RESTRICT] = array(
			'updated'   => _n( '%s access level updated.', '%s access levels updated.', $counts['updated'] ),
			'locked'    => _n( '%s access level not updated, somebody is editing it.', '%s access levels not updated, somebody is editing them.', $counts['locked'] ),
			'deleted'   => _n( '%s access level permanently deleted.', '%s access levels permanently deleted.', $counts['deleted'] ),
			'trashed'   => _n( '%s access level moved to the Trash.', '%s access levels moved to the Trash.', $counts['trashed'] ),
			'untrashed' => _n( '%s access level restored from the Trash.', '%s access levels restored from the Trash.', $counts['untrashed'] ),
		);
		return $messages;
	}

	/**
	 * Add level to user
	 *
	 * @since  0.3
	 * @param  int           $user_id
	 * @param  int           $level_id
	 * @return int|boolean
	 */
	public function _add_user_level($user_id,$level_id) {
		if(!$this->has_user_level($user_id,$level_id)) {
			$user_level = add_user_meta( $user_id, WPCACore::PREFIX."level", $level_id,false);
			if($user_level) {
				add_user_meta($user_id,WPCACore::PREFIX."level_".$level_id,time(),true);
			}
			return $level_id;
		}
		return false;
	}

	/**
	 * Remove level from user
	 *
	 * @since  0.3
	 * @param  $int    $user_id
	 * @param  $int    $level_id
	 * @return boolean
	 */
	public function _remove_user_level($user_id,$level_id) {
		return delete_user_meta($user_id,WPCACore::PREFIX."level",$level_id) &&
			delete_user_meta($user_id,WPCACore::PREFIX."level_".$level_id);
	}

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
	 * Check if user has level
	 *
	 * @since  0.6
	 * @param  int  $user_id
	 * @param  int  $level
	 * @return boolean
	 */
	public function has_user_level($user_id, $level) {
		$user = get_user_by('id',$user_id);
		return in_array($level, $this->_get_user_levels($user,false,false));
	}

	/**
	 * Get user levels
	 * Traversed to their root
	 * Include levels synced with role
	 *
	 * @since  0.3
	 * @param  WP_User $user
	 * @param  boolean $hierarchical
	 * @param  boolean $synced_roles
	 * @param  boolean $include_expired
	 * @return array
	 */
	 public function _get_user_levels(
	 	$user = null,
	 	$hierarchical = true,
	 	$synced_roles = true,
	 	$include_expired = false
	 	) {
		$levels = array();
		if(!$user && is_user_logged_in()) {
			$user = wp_get_current_user();
		}
		if($user) {
			$levels = get_user_meta($user->ID, WPCACore::PREFIX."level", false);
			if(!$include_expired) {
				foreach ($levels as $key => $level) {
					if($this->is_user_level_expired($user,$level)) {
						unset($levels[$key]);
					}
				}
			}
		}
		if($synced_roles) {
			global $wpdb;
			$role = $this->_get_user_roles($user);
			$role_level = $wpdb->get_col("SELECT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id AND m.meta_key = '_ca_role' WHERE m.meta_value = '{$role[0]}'");
			$levels = array_merge($levels,$role_level);
		}
		if($hierarchical) {
			foreach($levels as $key => $level) {
				$levels = array_merge($levels,get_post_ancestors((int)$level));
			}
		}
		update_postmeta_cache($levels);
		return $levels;
	}

	/**
	 * Get time of user level start
	 *
	 * @since  0.7
	 * @param  WP_User  $user
	 * @param  int      $level_id
	 * @return int
	 */
	public function get_user_level_start($user = null, $level_id) {
		if($user || is_user_logged_in()) {
			if(!$user) {
				$user = wp_get_current_user();
			}
			return (int)get_user_meta($user->ID,WPCACore::PREFIX."level_".$level_id,true);
		}
		return 0;
	}

	/**
	 * Get time of user level expiry
	 *
	 * @since  0.5
	 * @param  WP_User  $user
	 * @param  int      $level_id
	 * @return int
	 */
	public function get_user_level_expiry($user = null, $level_id) {
		if($user || is_user_logged_in()) {
			if(!$user) {
				$user = wp_get_current_user();
			}
			$time = $this->get_user_level_start($user,$level_id);
			$duration = $this->metadata()->get("duration")->get_data($level_id);
			if(isset($duration["count"],$duration["unit"]) && $time) {
				$time = strtotime("+".$duration["count"]." ".$duration["unit"]. " 23:59",$time);
				return $time;
			}
		}
		return 0;
	}

	/**
	 * Check if user level is expired
	 *
	 * @since  0.5
	 * @param  WP_User  $user
	 * @param  int      $level_id
	 * @return boolean
	 */
	public function is_user_level_expired($user = null,$level_id) {
		$time_expire = $this->get_user_level_expiry($user,$level_id);
		return $time_expire && time() > $time_expire;
	}

	/**
	 * Check if current user has global access
	 *
	 * @since  0.6
	 * @param  WP_User  $user
	 * @return boolean
	 */
	private function _has_global_access($user = null) {
		if(is_user_logged_in() && !$user) {
			$user = wp_get_current_user();
		}
		$has_access = in_array("administrator",$this->_get_user_roles($user));
		return apply_filters('rua/user/global-access', $has_access, $user);
	}

	/**
	 * Get conditional restrictions 
	 * and authorize access for user
	 * 
	 * @since  0.1
	 * @return void
	 */
	public function authorize_access() {

		if($this->_has_global_access()) {
			return;
		}

		$posts = WPCACore::get_posts(RUA_App::TYPE_RESTRICT);

		if ($posts) {
			$kick = 0;
			$levels = array_flip($this->_get_user_levels());
			foreach ($posts as $post) {
				if(!isset($levels[$post->ID])) {
					$kick = $post->ID;
				} else {
					$kick = 0;
					break;
				}
			}

			if(!$kick && is_user_logged_in()) {
				$conditions = WPCACore::get_conditions();
				foreach ($conditions as $condition => $level) {
					//Check post type
					if(isset($posts[$level])) {
						$drip = get_post_meta($condition,WPCACore::PREFIX."opt_drip",true);
						//Restrict access to dripped content
						if($drip && $this->metadata()->get('role')->get_data($level) == "-1") {
							$start = $this->get_user_level_start(null,$level);
							$drip_time = strtotime("+".$drip." days 00:00",$start);
							if(time() <= $drip_time) {
								$kick = $level;
							} else {
								$kick = 0;
								break;
							}
						} else {
							$kick = 0;
							break;
						}
					}
				}
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
	 * Override user caps with
	 * level caps
	 *
	 * @since  0.8
	 * @param  array  $allcaps
	 * @param  string $cap
	 * @param  array  $args
	 * @return array
	 */
	public function user_level_has_cap( $allcaps, $cap, $args ) {
		if(!$this->_has_global_access()) {
			if(!isset($this->user_levels_caps[$args[1]])) {
				$this->user_levels_caps[$args[1]] = $allcaps;
				$levels = $this->_get_user_levels(get_user_by("id",$args[1]));
				if($levels) {
					//Make sure higher levels have priority
					//Side-effect: synced levels < normal levels
					$levels = array_reverse($levels);
					foreach ($levels as $level) {
						$level_caps = $this->metadata()->get("caps")->get_data($level);
						if($level_caps) {
							foreach ($level_caps as $key => $level_cap) {
								$this->user_levels_caps[$args[1]][$key] = !!$level_cap;
							}
						}
					}
				}
			}
			$allcaps = $this->user_levels_caps[$args[1]];
		}
		return $allcaps;
	}

}

//eol