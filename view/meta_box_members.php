<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2021 by Joachim Jensen
 */

$list_members = new RUA_Members_List();
$list_members->prepare_items();

$role = RUA_App::instance()->level_manager->metadata()->get('role')->get_data($post->ID, true);

if($role !== '') {
    echo '<div style="background-color:#fcf0f1;padding: 8px 20px;border: 1px solid #c3c4c7;border-left-color: #d63638;border-left-width: 4px;">Synchronized Role option will be removed in a future version. Please use the Login State automation instead.</div>';
    RUA_Level_Edit::form_field('role');
}

echo '<select class="js-rua-user-suggest" multiple="multiple" name="users[]"></select>';
$list_members->display();