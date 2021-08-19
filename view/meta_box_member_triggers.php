<?php
/**
 * @package Restrict User Access
 * @author Joachim Jensen <joachim@dev.institute>
 * @license GPLv3
 * @copyright 2021 by Joachim Jensen
 */

 $automators = RUA_App::instance()->get_level_automators();

 $automators_by_type = [];
 foreach ($automators as $automator) {
     $automators_by_type[$automator->get_type()][] = $automator;
 }

 $types = [
     'trigger' => __('Triggers'),
     'trait'   => __('Visitor Traits')
 ];

$automatorsData = RUA_App::instance()->level_manager->metadata()->get('member_automations')->get_data($post->ID, true);

echo '<div class="js-rua-member-automations rua-member-automations">';
$i = 0;
foreach($automatorsData as $automatorData) {
    if(!isset($automatorData['value'],$automatorData['name'])) {
        continue;
    }

    if(!$automators->has($automatorData['name'])) {
        continue;
    }

    $automator = $automators->get($automatorData['name']);

    $content = $automator->get_content();
    echo '<div data-no="'.$i.'" class="rua-member-trigger">';
    echo $automator->get_description().' ';
    echo '<input type="hidden" name="member_automations['.$i.'][name]" value="'.$automatorData['name'].'" />';
    echo '<input type="hidden" name="member_automations['.$i.'][value]" value="'.$automatorData['value'].'" />';
    echo '<span class="rua-member-trigger-value">'.(isset($content[$automatorData['value']]) ? $content[$automatorData['value']] : '').'</span>';
    echo '<span class="js-rua-member-trigger-remove wpca-condition-remove wpca-pull-right dashicons dashicons-trash"></span>';
    echo '</div>';
    $i++;
}
echo '</div>';
echo '<select class="js-rua-add-member-automator">';
echo '<option value="">Add</option>';
foreach ($automators_by_type as $type => $automators) {
    echo '<optgroup label="'.$types[$type].'">';
    foreach ($automators as $automator) {  
        echo '<option data-sentence="'.$automator->get_description().'" data-content=\''.json_encode($automator->get_content()).'\' value="'.$automator->get_name().'">'.$automator->get_title().'</option>';
    }
    echo '</optgroup>';
}
echo '</select>';