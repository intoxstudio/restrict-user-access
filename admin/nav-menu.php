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

final class RUA_Nav_Menu {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action('wp_update_nav_menu_item',
			array($this,"update_item"),10,3);
		add_action("wp_nav_menu_item_custom_fields",
			array($this,"render_level_option"),99,4);

		add_filter( 'wp_edit_nav_menu_walker',
			array($this,"set_edit_walker"),999);
	}

	/**
	 * Update menu item
	 *
	 * @since  0.11
	 * @param  int    $menu_id
	 * @param  int    $menu_item_db_id
	 * @param  array  $args
	 * @return void
	 */
	public function update_item( $menu_id, $menu_item_db_id, $args ) {
		if ( !current_user_can(RUA_App::CAPABILITY) )
			return false;

		$key = '_menu_item_level';
		$request_key = 'menu-item-access-levels';

		$new_levels = isset($_POST[$request_key][$menu_item_db_id]) ? $_POST[$request_key][$menu_item_db_id] : array();

		//weird empty key.
		//possible bug if WP uses nav-menu-data json to mimic $_POST
		unset($new_levels['']);

		$menu_levels = array_flip(get_post_meta( $menu_item_db_id, $key, false ));

		foreach ($new_levels as $level) {
			if(isset($menu_levels[$level])) {
				unset($menu_levels[$level]);
			} else {
				add_post_meta($menu_item_db_id, $key, $level);
			}
		}
		foreach ($menu_levels as $level => $value) {
			delete_post_meta( $menu_item_db_id, $key, $level );
		}
	}

	/**
	 * Set menu items walker for edit
	 *
	 * @since 0.11
	 */
	public function set_edit_walker() {
		// Guard for plugins using wp_edit_nav_menu_walker wrong
		if(!class_exists("Walker_Nav_Menu_Edit")) {
			require_once( ABSPATH . 'wp-admin/includes/class-walker-nav-menu-edit.php' );
		}
		require_once( dirname( __FILE__ ) . '/walker-nav-menu.php' );
		return "RUA_Walker_Nav_Menu_Edit";
	}

	/**
	 * Render level option on menu item
	 *
	 * @since  0.11
	 * @param  int    $id
	 * @param  [type] $item
	 * @param  int    $depth
	 * @param  array  $args
	 * @return void
	 */
	public function render_level_option($id, $item, $depth, $args ) {
		if ( !current_user_can(RUA_App::CAPABILITY) )
			return false;

		/**
		 * Compat if a similar menu walker from an other plugin is used
		 * @see walder-nav-menu.php
		 */
		if ( empty( $id ) )
			$id = (int) $item->ID;

		$levels = (array) get_post_meta( $id, '_menu_item_level', false );
						?>
		<p class="field-access-levels description description-wide">
		<label for="edit-menu-item-access-levels-<?php echo $id; ?>">
		<?php _e("Access Levels",'restrict-user-access'); ?>:

		<select style="width:100%;" class="js-rua-levels" multiple="multiple" id="edit-menu-item-access-levels-<?php echo $id; ?>" name="menu-item-access-levels[<?php echo $id; ?>][]" data-value="<?php echo esc_html( implode(",", $levels) ); ?>">
		</select>
		<span class="description"><?php _e("Restrict menu item to users with these levels or higher.",'restrict-user-access'); ?></span>
		</label>
		</p>
<?php
	}
}

//