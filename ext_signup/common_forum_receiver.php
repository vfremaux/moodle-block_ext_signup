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

    print_header($SITE->fullname, $SITE->fullname .' : '.get_string('step2signup', 'block_ext_signup'), get_string('step1choose', 'block_ext_signup'), '', '<meta name="description" content="'. strip_tags(format_text($SITE->summary, FORMAT_HTML)) .'" />', true, '' /* , user_login_string($SITE).$langmenu*/);

    $id = required_param('id', PARAM_INT); // block instance id
    $forumcourseid = required_param('courseid', PARAM_INT);
    $userid = required_param('userid', PARAM_TEXT);
    $iscomplete = required_param('iscomplete', PARAM_TEXT);

    if (!empty($iscomplete)){
        $preenroll = new StdClass;
        $preenroll->userid = $userid;
        $preenroll->courseid = $forumcourseid;
        $preenroll->timecreated = time();
        insert_record('block_ext_signup', $preenroll);
    }
    
    echo '<br/>';
    print_box(get_string('processhelp1', 'block_ext_signup'));
    print_continue($CFG->wwwroot."/blocks/ext_signup/signup.php?id={$id}&amp;userid={$userid}");

    print_footer();
?>