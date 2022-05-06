<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2022 by Joachim Jensen
 */

?>

<div class="cas-save">
	<div class="wpca-pull-right">
<?php
    if ($post->post_status == 'auto-draft') {
        submit_button(__('Create'), 'primary button-large', 'publish', false);
    } else {
        submit_button(__('Save'), 'primary button-large', 'save', false);
    }
?>
	</div>
</div>