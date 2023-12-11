<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

$list_members = new RUA_Members_List();
$list_members->prepare_items();

if (isset($_GET['s']) && strlen($_GET['s'])) {
    /* translators: %s: search keywords */
    printf(' <span class="subtitle">' . __('Search results for &#8220;%s&#8221;') . '</span>', esc_attr($_GET['s']));
}

$list_members->search_box('Search Members', 'post');
$list_members->display();
echo '<p></p><select class="js-rua-user-suggest" multiple="multiple" name="users[]"></select>';

echo '<div id="rua-members-extend" style="display:none;">';
?>
<form>
<table class="form-table rua-form-table js-rua-members-extend">
    <tbody>
    <tr>
        <td><?php _e('Select Date', 'restrict-user-access') ?></td>
        <td>
            <input type="radio" name="rua_extend_type" class="js-rua-extend-type js-rua-extend-type-1" value="1" />
            <input type="datetime-local" class="js-rua-extend-date" min="<?php echo current_time('Y-m-d\T00:00') ?>" />
        </td>
    </tr>
    <tr>
        <td><?php _e('Lifetime', 'restrict-user-access') ?></td>
        <td>
            <input type="radio" name="rua_extend_type" class="js-rua-extend-type js-rua-extend-type-0" value="0" />
        </td>
    </tr>
    </tbody>
</table>
<div class="wpca-pull-right">
    <p><button class="button-primary" id="extend_member"><?php _e('Save') ?></button></p>
</div>
</form>
</div>
