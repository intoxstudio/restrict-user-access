<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2024 by Joachim Jensen
 */
?>

<div id="rua-account" class="postbox">
    <h3>
        <a href="https://dev.institute/account/" rel="noreferrer noopener" target="_blank">
            <span class="dashicons dashicons-external"></span> <?php _e('Account Dashboard', 'restrict-user-access'); ?>
        </a>
    </h3>
    <span><?php echo implode(' &bull; ', $list); ?></span>
</div>