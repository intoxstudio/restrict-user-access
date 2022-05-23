<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

$list_caps = new RUA_Capabilities_List();
$list_caps->prepare_items();
$list_caps->display();
