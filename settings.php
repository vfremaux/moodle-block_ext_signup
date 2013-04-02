<?php

if ($choices = get_records_menu('course', 'visible', 1, 'fullname ASC', 'id, fullname')){
    $settings->add(new admin_setting_configmulticheckbox('block_ext_signup_courses', get_string('extenabledcourses', 'block_ext_signup'), get_string('configextcourses', 'block_ext_signup'), array(), $choices));
}

// get available languages + no force option
$langoptions = get_list_of_languages();
$langoptions[0] = get_string('userlanguage', 'block_ext_signup');
$settings->add(new admin_setting_configselect('block_ext_signup_submitternotifylang', get_string('submitternotifylang', 'block_ext_signup'), get_string('configsubmitternotifylang', 'block_ext_signup'), '', $langoptions));

$settings->add(new admin_setting_configcheckbox('block_ext_signup_publicregistering', get_string('publicregistering', 'block_ext_signup'), get_string('configpublicregistering', 'block_ext_signup'), true));

global $CFG;
include_once($CFG->dirroot.'/blocks/ext_signup/locallib.php');

$handlers = ext_signup_get_handlers();
foreach($handlers as $handler){
    include_once $CFG->dirroot."/blocks/ext_signup/handlers/$handler.php";
    $handler = new $handler();
    $handler->add_setting($settings);
}

$clearall = get_string('clearall', 'block_ext_signup');
$settings->add(new admin_setting_heading('clear', get_string('clear', 'block_ext_signup'), "<a href=\"{$CFG->wwwroot}/blocks/ext_signup/clear.php\">$clearall</a>"));

?>