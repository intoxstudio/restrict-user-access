<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */
?>

<div class="rua-navbar">
    <img src="<?php echo $freemius->get_local_icon_url(); ?>" width="36" height="36" alt="" />
    <h2><?php _e('Restrict User Access', 'restrict-user-access'); ?></h2>
    <div style="margin:0 auto; font-weight:600; display: inline-block;vertical-align: middle;padding-left: 30px;">
        <a class="button button-small" href="<?php echo esc_url($freemius->addon_url('')); ?>">
            <?php _e('Add-Ons', 'restrict-user-access'); ?>
        </a>
        <a class="button button-small" href="https://dev.institute/wordpress-memberships/bundles/?utm_source=plugin&amp;utm_medium=referral&amp;utm_content=top-bar&amp;utm_campaign=rua"
        target="_blank" rel="noopener">
            <?php _e('Bundle & Save', 'restrict-user-access'); ?>
        </a>
    </div>
</div>