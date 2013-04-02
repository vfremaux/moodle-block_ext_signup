<?php

    /**
    *
    *
    * emulates a course selection form when only choosing the news letter.
    */

    require_once('../../config.php');
    require_once("../../course/lib.php");
    require_once("locallib.php");
    include 'choice_form.php';

    $PAGE->set_title($SITE->fullname);
    $PAGE->set_heading($SITE->fullname .' : '.get_string('step2signup', 'block_ext_signup'));
    $PAGE->set_focuscontrol('');
    $PAGE->set_cacheable(true);
    $PAGE->set_button('' /*);
    $PAGE->set_headingmenu(user_login_string($SITE).$langmenu*/);
    echo $OUTPUT->header();

    $id = required_param('id', PARAM_INT); // block instance id
    $forumcourseid = required_param('courseid', PARAM_INT);
    $userid = required_param('userid', PARAM_TEXT);
    $iscomplete = required_param('iscomplete', PARAM_TEXT);

    if (!empty($iscomplete)){
        $preenroll = new StdClass;
        $preenroll->userid = $userid;
        $preenroll->courseid = $forumcourseid;
        $preenroll->timecreated = time();
        $DB->insert_record('block_ext_signup', $preenroll);
    }
    echo '<br/>';
    echo $OUTPUT->box(get_string('processhelp1', 'block_ext_signup'));
    echo $OUTPUT->continue_button($CFG->wwwroot."/blocks/ext_signup/signup.php?id={$id}&amp;userid={$userid}");

    echo $OUTPUT->footer();
?>