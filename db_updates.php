<?php
/**
 * @package Restrict User Access
 * @copyright Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 */

if(is_admin()) {
	$db_updater = new WP_DB_Updater("rua_plugin_version",RUA_App::PLUGIN_VERSION);
	$db_updater->register_version_update("0.4","rua_db_userlevel_date");

	/**
	 * Store userlevel dates with level_id
	 * instead of level umeta_id
	 *
	 * @since  0.4
	 * @return boolean
	 */
	function rua_db_userlevel_date() {
		global $wpdb;

		//Get levels by umeta id and level id
		$levels_by_metaid = $wpdb->get_results("SELECT umeta_id,meta_value FROM $wpdb->usermeta WHERE meta_key = '_ca_level'",OBJECT_K);
		$levels_by_id = array();
		foreach ($levels_by_metaid as $meta_id => $level) {
			$levels_by_id[$level->meta_value] = $meta_id;
		}

		$level_dates = $wpdb->get_results("SELECT user_id,meta_key,meta_value FROM $wpdb->usermeta WHERE meta_key LIKE '_ca_level_%'");
		foreach($level_dates as $level_date) {
			$level_date_level_id = str_replace("_ca_level_", "", $level_date->meta_key);
			//Check if date exists by level umeta id (old store)
			//If so, move it to new
			if(isset($levels_by_metaid[$level_date_level_id])) {
				update_user_meta($level_date->user_id,"_ca_level_".$levels_by_metaid[$level_date_level_id]->meta_value,$level_date->meta_value,true);
			}
			//Check if date exists by level id (new store)
			//If not, delete it
			if(!isset($levels_by_id[$level_date_level_id])) {
				delete_user_meta($level_date->user_id,$level_date->meta_key);
			}
		
		}

		return true;
	}
}

//eol