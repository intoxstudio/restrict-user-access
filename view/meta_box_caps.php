<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2018 by Joachim Jensen
 */

$list_caps = new RUA_Capabilities_List();
$list_caps->prepare_items();
echo '<input type="hidden" name="caps" value="" />';
$list_caps->display();

//