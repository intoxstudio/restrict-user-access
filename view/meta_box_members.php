<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

$list_members = new RUA_Members_List();
$list_members->prepare_items();
$list_members->display();
echo '<p></p><select class="js-rua-user-suggest" multiple="multiple" name="users[]"></select>';
