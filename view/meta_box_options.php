<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2019 by Joachim Jensen
 */

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

RUA_Level_Edit::form_field('handle');

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

RUA_Level_Edit::form_field('hide_admin_bar');

//ability to change name on update
if ( $post->post_status != 'auto-draft' ) {
	echo '<strong>' . __('Level Name','restrict-user-access') . '</strong>';
	echo '<p>';
	echo '<input type="text" name="post_name" value="'.$post->post_name.'" />';
	echo '</p>';
}

//