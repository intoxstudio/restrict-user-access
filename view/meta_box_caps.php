<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2020 by Joachim Jensen
 */

$list_caps = new RUA_Capabilities_List();
$list_caps->prepare_items();
echo '<input type="hidden" name="caps" value="" />';
$list_caps->display();
