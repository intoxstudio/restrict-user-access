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

final class RUA_App {

	/**
	 * Plugin version
	 */
	const PLUGIN_VERSION       = '0.8';

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
	 * Access Levels
	 * 
	 * @var array
	 */
	private $levels            = array();

	/**
	 * Instance of class
	 * 
	 * @var RUA_App
	 */
	private static $_instance;

	/**
	 * Level manager
	 * @var RUA_Level_Manager
	 */
	public $level_manager;

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->level_manager = new RUA_Level_Manager();
		
		if(is_admin()) {

			add_action('admin_enqueue_scripts',
				array(&$this,'load_admin_scripts'));

			add_action( 'show_user_profile',
				array(&$this,'add_field_access_level'));
			add_action( 'edit_user_profile',
				array(&$this,'add_field_access_level'));
			add_action( 'personal_options_update',
				array(&$this,'save_user_profile'));
			add_action( 'edit_user_profile_update',
				array(&$this,'save_user_profile'));

			add_filter( 'manage_users_columns',
				array(&$this,'add_user_column_headers'));
			add_filter( 'manage_users_custom_column',
				array(&$this,'add_user_columns'), 10, 3 );

		}

		add_action('init',
			array(&$this,'load_textdomain'));
	}

	/**
	 * Instantiates and returns class singleton
	 *
	 * @since  0.1
	 * @return RUA_App 
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
	 * Add Access Level to user profile
	 *
	 * @since 0.3
	 * @param WP_User  $user
	 */
	public function add_field_access_level( $user ) {
		if(current_user_can(self::CAPABILITY)) {
			$levels = $this->_get_levels();
			$user_levels = $this->level_manager->_get_user_levels($user,false,false,true);
?>
			<h3><?php _e("Access",self::DOMAIN); ?></h3>
			<table class="form-table">
				<tr>
					<th><label for="_ca_level[]"><?php _e("Access Levels",self::DOMAIN); ?></label></th>
					<td>
					<?php foreach($levels as $level) :
						if($level->{"_ca_role"} != "-1") continue;
					 ?>
						<p><label>
							<input type="checkbox" name="_ca_level[]" value="<?php echo esc_attr($level->ID); ?>" <?php checked( in_array($level->ID,$user_levels),true); ?> />
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

		$user = get_userdata($user_id);

		$new_levels = isset($_POST[WPCACore::PREFIX.'level']) ? $_POST[WPCACore::PREFIX.'level'] : array();
		$user_levels = array_flip($this->level_manager->_get_user_levels($user,false,false,true));

		foreach ($new_levels as $level) {
			if(isset($user_levels[$level])) {
				unset($user_levels[$level]);
			} else {
				$this->level_manager->_add_user_level($user_id,$level);
			}
		}
		foreach ($user_levels as $level => $value) {
			$this->level_manager->_remove_user_level($user_id,$level);
		}
	}

	/**
	 * Add column headers on
	 * User overview
	 *
	 * @since 0.3
	 * @param array  $column
	 */
	public function add_user_column_headers( $columns ) {
		$new_columns = array();
		foreach($columns as $key => $title) {
			$new_columns[$key] = $title;
			if($key == "role") {
				$new_columns["level"] = __('Access Levels',self::DOMAIN);
			}
		}
		return $new_columns;
	}

	/**
	 * Add columns on user overview
	 *
	 * @since 0.3
	 * @param string  $output
	 * @param string  $column_name
	 * @param int     $user_id
	 */
	public function add_user_columns( $output, $column_name, $user_id ) {
		$user = get_userdata( $user_id );
		switch ($column_name) {
			case 'level' :
				$levels = $this->_get_levels();
				$level_links = array();
				foreach ($this->level_manager->_get_user_levels($user,false,true,true) as $user_level) {
					$user_level = isset($levels[$user_level]) ? $levels[$user_level] : null;
					if($user_level) {
						$level_links[] = '<a href="'.admin_url( 'post.php?post='.$user_level->ID.'&action=edit').'">'.$user_level->post_title.'</a>';
					}
				}
				$output = implode(", ", $level_links);
				break;
			default:
		}
		return $output;
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
				// 'meta_query'  => array(
				// 	array(
				// 		'key' => WPCACore::PREFIX.'role',
				// 		'value' => '-1',
				// 	)
				// )
			));
			foreach ($levels as $level) {
				$this->levels[$level->ID] = $level;
			}
		}
		return $this->levels;
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

			wp_register_script('rua/admin/edit', plugins_url('/js/edit.js', __FILE__), array('select2','jquery'), self::PLUGIN_VERSION);

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

}

//eol