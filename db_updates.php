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

if(is_admin()) {
	$rua_db_updater = new WP_DB_Updater('rua_plugin_version',RUA_App::PLUGIN_VERSION);
	$rua_db_updater->register_version_update('0.4','rua_update_to_04');
	$rua_db_updater->register_version_update('0.13','rua_update_to_013');
	$rua_db_updater->register_version_update('0.14','rua_update_to_014');
	$rua_db_updater->register_version_update('0.15','rua_update_to_015');
	$rua_db_updater->register_version_update('0.17','rua_update_to_017');

	/**
	 * Update to version 0.17
	 * Remove role meta for unsynced levels
	 *
	 * @since  0.17
	 * @return boolean
	 */
	function rua_update_to_017() {
		global $wpdb;

		$wpdb->query("
			DELETE FROM $wpdb->postmeta 
			WHERE meta_key = '_ca_role' AND meta_value = '-1'
		");

		return true;
	}

	/**
	 * Update to version 0.15
	 * Remove old condition settings
	 *
	 * @since  0.15
	 * @return boolean
	 */
	function rua_update_to_015() {
		global $wpdb;

		$wpdb->query("
			DELETE FROM $wpdb->postmeta 
			WHERE meta_value LIKE '_ca_sub_%'
		");

		return true;
	}

	/**
	 * Update to version 0.14
	 * Simplify auto select option
	 *
	 * @since  0.14
	 * @return boolean
	 */
	function rua_update_to_014() {
		global $wpdb;

		$group_ids = $wpdb->get_col("SELECT post_id FROM $wpdb->postmeta WHERE meta_value LIKE '_ca_sub_%'");
		foreach ($group_ids as $group_id) {
			add_post_meta($group_id,'_ca_autoselect',1,true);
		}

		return true;
	}

	/**
	 * Update to version 0.13
	 * Inherit condition exposure from level
	 * Remove level exposure
	 *
	 * @since  0.13
	 * @return boolean
	 */
	function rua_update_to_013() {
		global $wpdb;

		$wpdb->query("
			UPDATE $wpdb->posts AS c
			INNER JOIN $wpdb->posts AS s ON s.ID = c.post_parent
			INNER JOIN $wpdb->postmeta AS e ON e.post_id = s.ID
			SET c.menu_order = e.meta_value
			WHERE c.post_type = 'condition_group'
			AND e.meta_key = '_ca_exposure'
		");

		$wpdb->query("
			DELETE FROM $wpdb->postmeta 
			WHERE meta_key = '_ca_exposure'
		");

		wp_cache_flush();

		return true;
	}

	/**
	 * Store userlevel dates with level_id
	 * instead of level umeta_id
	 *
	 * @since  0.4
	 * @return boolean
	 */
	function rua_update_to_04() {
		global $wpdb;

		//Get levels by umeta id and level id
		$levels_by_metaid = $wpdb->get_results("SELECT umeta_id,meta_value FROM $wpdb->usermeta WHERE meta_key = '_ca_level'",OBJECT_K);
		$levels_by_id = array();
		foreach ($levels_by_metaid as $meta_id => $level) {
			$levels_by_id[$level->meta_value] = $meta_id;
		}

		$level_dates = $wpdb->get_results("SELECT user_id,meta_key,meta_value FROM $wpdb->usermeta WHERE meta_key LIKE '_ca_level_%'");
		foreach($level_dates as $level_date) {
			$level_date_metaid = str_replace('_ca_level_', '', $level_date->meta_key);
			//Check if date exists by level umeta id (old store)
			//If so, move it to new
			if(isset($levels_by_metaid[$level_date_metaid])) {
				update_user_meta($level_date->user_id,'_ca_level_'.$levels_by_metaid[$level_date_metaid]->meta_value,$level_date->meta_value,true);
			}
			//Check if date exists by level id (new store)
			//If not, delete it
			if(!isset($levels_by_id[$level_date_metaid])) {
				delete_user_meta($level_date->user_id,$level_date->meta_key);
			}

		}

		return true;
	}
}

//eol