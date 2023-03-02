<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

defined('ABSPATH') || exit;

$rua_db_updater = RUA_App::instance()->get_updater();
$rua_db_updater->register_version_update('0.4', 'rua_update_to_04');
$rua_db_updater->register_version_update('0.13', 'rua_update_to_013');
$rua_db_updater->register_version_update('0.14', 'rua_update_to_014');
$rua_db_updater->register_version_update('0.15', 'rua_update_to_015');
$rua_db_updater->register_version_update('0.17', 'rua_update_to_017');
$rua_db_updater->register_version_update('1.1', 'rua_update_to_11');
$rua_db_updater->register_version_update('2.2.1', 'rua_update_to_221');
$rua_db_updater->register_version_update('2.4', 'rua_update_to_24');
$rua_db_updater->register_version_update('2.4.2', 'rua_update_to_242');

/**
 * Enable legacy date module and
 * negated conditions if in use
 *
 * @since 2.4.2
 *
 * @return bool
 */
function rua_update_to_242()
{
    global $wpdb;

    $types = WPCACore::types()->get_all();

    $options = [
        'legacy.date_module'        => [],
        'legacy.negated_conditions' => []
    ];

    $options['legacy.date_module'] = array_flip((array)$wpdb->get_col("
        SELECT p.post_type FROM $wpdb->posts p
        INNER JOIN $wpdb->posts c on p.ID = c.post_parent
        INNER JOIN $wpdb->postmeta m on c.ID = m.post_id
        WHERE c.post_type = 'condition_group' AND m.meta_key = '_ca_date'
    "));

    $options['legacy.negated_conditions'] = array_flip((array)$wpdb->get_col("
        SELECT p.post_type FROM $wpdb->posts p
        INNER JOIN $wpdb->posts c on p.ID = c.post_parent
        WHERE c.post_type = 'condition_group' AND c.post_status = 'negated'
    "));

    foreach ($types as $type => $val) {
        foreach ($options as $option => $post_types) {
            if (isset($post_types[$type])) {
                WPCACore::save_option($type, $option, true);
            } elseif (WPCACore::get_option($type, $option, false)) {
                WPCACore::save_option($type, $option, false);
            }
        }
    }

    return true;
}

/**
 * Migrate role sync option to automator
 *
 * @return bool
 */
function rua_update_to_24()
{
    global $wpdb;

    $results = $wpdb->get_results("SELECT post_id,meta_value FROM $wpdb->postmeta WHERE meta_key = '_ca_role'");
    foreach ($results as $result) {
        if (is_numeric($result->meta_value)) {
            $automator = 'login';
            $value = $result->meta_value == -1 ? 'login' : 'logout';
        } else {
            $automator = 'user_role_sync';
            $value = $result->meta_value;
        }

        $metadata = get_post_meta($result->post_id, '_ca_member_automations', true);
        if (!is_array($metadata) || empty($metadata)) {
            $metadata = [];
        }
        $metadata[] = [
            'name'  => $automator,
            'value' => $value
        ];

        update_post_meta($result->post_id, '_ca_member_automations', $metadata);
    }

    return true;
}

/**
 * Add -1 to condition groups with select terms
 * Clear condition type cache
 *
 * @since 2.2.1
 *
 * @return bool
 */
function rua_update_to_221()
{
    update_option('_ca_condition_type_cache', []);

    $taxonomies = array_map(function ($value) {
        return "'" . esc_sql($value) . "'";
    }, get_taxonomies(['public' => true]));

    if (empty($taxonomies)) {
        return true;
    }

    global $wpdb;

    $condition_group_ids = array_unique((array)$wpdb->get_col("
        SELECT p.ID FROM $wpdb->posts p
        INNER JOIN $wpdb->term_relationships r ON r.object_id = p.ID
        INNER JOIN $wpdb->term_taxonomy t ON t.term_taxonomy_id = r.term_taxonomy_id
        WHERE p.post_type = 'condition_group'
        AND t.taxonomy IN (" . implode(',', $taxonomies) . ')
    '));

    foreach ($condition_group_ids as $id) {
        add_post_meta($id, '_ca_taxonomy', '-1');
    }

    return true;
}

/**
 * Migrate rua-toolbar-hide option to levels
 *
 * @since  1.1
 * @return bool
 */
function rua_update_to_11()
{
    $hide_toolbar = get_option('rua-toolbar-hide', false);

    delete_option('rua-toolbar-hide');

    if (!$hide_toolbar) {
        return true;
    }

    $app = RUA_App::instance();
    $levels = $app->get_levels();
    $metadata = $app->level_manager->metadata()->get('hide_admin_bar');

    foreach ($levels as $level) {
        $metadata->update($level->ID, 1);
    }

    return true;
}

/**
 * Update to version 0.17
 * Remove role meta for unsynced levels
 *
 * @since  0.17
 * @return boolean
 */
function rua_update_to_017()
{
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
function rua_update_to_015()
{
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
function rua_update_to_014()
{
    global $wpdb;

    $group_ids = $wpdb->get_col("SELECT post_id FROM $wpdb->postmeta WHERE meta_value LIKE '_ca_sub_%'");
    foreach ($group_ids as $group_id) {
        add_post_meta($group_id, '_ca_autoselect', 1, true);
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
function rua_update_to_013()
{
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
function rua_update_to_04()
{
    global $wpdb;

    //Get levels by umeta id and level id
    $levels_by_metaid = $wpdb->get_results("SELECT umeta_id,meta_value FROM $wpdb->usermeta WHERE meta_key = '_ca_level'", OBJECT_K);
    $levels_by_id = [];
    foreach ($levels_by_metaid as $meta_id => $level) {
        $levels_by_id[$level->meta_value] = $meta_id;
    }

    $level_dates = $wpdb->get_results("SELECT user_id,meta_key,meta_value FROM $wpdb->usermeta WHERE meta_key LIKE '_ca_level_%'");
    foreach ($level_dates as $level_date) {
        $level_date_metaid = str_replace('_ca_level_', '', $level_date->meta_key);
        //Check if date exists by level umeta id (old store)
        //If so, move it to new
        if (isset($levels_by_metaid[$level_date_metaid])) {
            update_user_meta($level_date->user_id, '_ca_level_' . $levels_by_metaid[$level_date_metaid]->meta_value, $level_date->meta_value, true);
        }
        //Check if date exists by level id (new store)
        //If not, delete it
        if (!isset($levels_by_id[$level_date_metaid])) {
            delete_user_meta($level_date->user_id, $level_date->meta_key);
        }
    }

    return true;
}
