<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2021 by Joachim Jensen
 */

$metadata = RUA_App::instance()->level_manager->metadata();

$pages = wp_dropdown_pages([
    'post_type'        => $post->post_type,
    'exclude_tree'     => $post->ID,
    'selected'         => $post->post_parent,
    'name'             => 'parent_id',
    'show_option_none' => __('Do not extend', 'restrict-user-access'),
    'sort_column'      => 'menu_order, post_title',
    'echo'             => 0,
    'class'            => 'rua-input-md'
]);

$action_page = $metadata->get('page')->get_data($post->ID);

$duration = $metadata->get('duration');
$duration_arr = $duration->get_data($post->ID);
$duration_no = $duration_arr ? $duration_arr['count'] : 0;
$duration_val = $duration_arr ? $duration_arr['unit'] : 'day';

?>

<table class="form-table rua-form-table" width="100%"><tbody>
	<tr>
		<td scope="row"><?php _e('Extend Level', 'restrict-user-access') ?></td>
		<td>
			<?php echo $pages; ?>
		</td>
	</tr>
	<tr>
		<td scope="row"><?php _e('Non-Member Action', 'restrict-user-access') ?></td>
		<td>
<?php
echo RUA_Level_Edit::form_field('handle', '', false);
echo '<div><select name="page" class="js-rua-page" data-tags="1" data-rua-url="'.get_site_url().'">';
if (is_numeric($action_page)) {
    $page = get_post($action_page);
    echo '<option value="'.$page->ID.'" selected="selected">'.$page->post_title.'</option>';
} elseif ($action_page) {
    echo '<option value="'.$action_page.'" selected="selected">'.$action_page.'</option>';
}
echo '</select></div>';
?>
		</td>
	</tr>
	<tr>
		<td scope="row"><?php echo $duration->get_title(); ?></td>
		<td>
<?php
echo '<input type="number" min="0" name="duration[count]" value="'.$duration_no.'" class="rua-input-sm" style="vertical-align:top;" />';
echo '<select name="' . $duration->get_id() . '[unit]">' . "\n";
foreach ($duration->get_input_list() as $key => $value) {
    echo '<option value="' . $key . '"' . selected($duration_val, $key, false) . '>' . $value . '</option>' . "\n";
}
echo '</select>' . "\n";
?>
		</td>
	</tr>
	<tr>
		<td scope="row"><?php _e('Can Access Unrestricted Content', 'restrict-user-access'); ?></td>
		<td>
			<?php echo RUA_Level_Edit::form_field('default_access', '', false); ?>
		</td>
	</tr>
	<tr>
		<td scope="row"><?php _e('Hide Admin Toolbar', 'restrict-user-access') ?></td>
		<td>
			<?php echo RUA_Level_Edit::form_field('hide_admin_bar', '', false); ?>
		</td>
	</tr>
<?php if ($post->post_status != 'auto-draft') : ?>
	<tr>
		<td scope="row"><?php _e('Level Name', 'restrict-user-access') ?></td>
		<td>
			<input type="text" class="rua-input-md" name="post_name" value="<?php echo $post->post_name; ?>" />
		</td>
	</tr>
<?php endif; ?>
</tbody></table>