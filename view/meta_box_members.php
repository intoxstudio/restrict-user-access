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
