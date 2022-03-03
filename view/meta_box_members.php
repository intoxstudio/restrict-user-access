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

RUA_Level_Edit::form_field('role');

echo '<select class="js-rua-user-suggest" multiple="multiple" name="users[]"></select>';
$list_members->display();