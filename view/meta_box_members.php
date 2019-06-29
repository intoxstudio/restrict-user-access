<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2019 by Joachim Jensen
 */

$list_members = new RUA_Members_List();
$list_members->prepare_items();

RUA_Level_Edit::form_field('role');

echo '<div class="js-rua-members">';

echo '<select class="js-rua-user-suggest" multiple="multiple" name="users[]"></select>';
$list_members->display();

echo '</div>';
