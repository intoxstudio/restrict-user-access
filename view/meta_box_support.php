<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <jv@intox.dk>
 * @license GPLv3
 * @copyright 2018 by Joachim Jensen
 */

$rua_fs = rua_fs();

?>
<div style="overflow:hidden;">
	<ul>
		<li><a href="https://dev.institute/docs/restrict-user-access/getting-started/?utm_source=plugin&amp;utm_medium=referral&amp;utm_content=info-box&amp;utm_campaign=rua" target="_blank"><?php _e('Get Started','restrict-user-access'); ?></a></li>
		<li><a href="https://dev.institute/docs/restrict-user-access/?utm_source=plugin&amp;utm_medium=referral&amp;utm_content=info-box&amp;utm_campaign=rua" target="_blank"><?php _e('Documentation & FAQ','restrict-user-access'); ?></a></li>
		<li><a href="https://wordpress.org/support/plugin/restrict-user-access/" target="_blank"><?php _e('Forum Support','restrict-user-access'); ?></a></li>
		<li><a href="<?php echo esc_url($rua_fs->contact_url('feature_request')); ?>"><?php _e('Feature Requests','restrict-user-access'); ?></a></li>
	</ul>
</div>