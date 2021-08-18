<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2021 by Joachim Jensen
 */

$rua_fs = rua_fs();

?>
<ul>
	<li><a href="https://dev.institute/docs/restrict-user-access/getting-started/?utm_source=plugin&amp;utm_medium=referral&amp;utm_content=info-box&amp;utm_campaign=rua"
			target="_blank" rel="noopener"><?php _e('Getting Started', 'restrict-user-access'); ?></a>
	</li>
	<li><a href="https://dev.institute/docs/restrict-user-access/?utm_source=plugin&amp;utm_medium=referral&amp;utm_content=info-box&amp;utm_campaign=rua"
			target="_blank" rel="noopener"><?php _e('Documentation & FAQ', 'restrict-user-access'); ?></a>
	</li>
	<!--<li><a href="<?php echo esc_url($rua_fs->contact_url('feature_request')); ?>"><?php _e('Feedback & Feature Requests', 'restrict-user-access'); ?></a>
	</li>-->
	<li><a href="https://wordpress.org/support/plugin/restrict-user-access/" target="_blank" rel="noopener noreferrer"><?php _e('Support Forums', 'restrict-user-access'); ?></a>
	</li>

	<li><a class="button button-primary"
			href="<?php echo esc_url($rua_fs->addon_url('')); ?>"><?php _e('Stellar Add-Ons', 'restrict-user-access'); ?></a>
	</li>
</ul>