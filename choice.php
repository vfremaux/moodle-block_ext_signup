<?php

    /**
     * Moodle - Modular Object-Oriented Dynamic Learning Environment
     *          http://moodle.org
     * Copyright (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
     *
     * This program is free software: you can redistribute it and/or modify
     * it under the terms of the GNU General Public License as published by
     * the Free Software Foundation, either version 2 of the License, or
     * (at your option) any later version.
     *
     * This program is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     * GNU General Public License for more details.
     *
     * You should have received a copy of the GNU General Public License
     * along with this program.  If not, see <http://www.gnu.org/licenses/>.
     *
     * @package block-ext_signup
     * @category block
     * @author     Valery Fremaux <valery@valeisti.fr>
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
     * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
     * @copyright  (C) 2010 ValEISTI (http://www.valeisti.fr)
     *
     */

    require_once('../../config.php');
    require_once("../../course/lib.php");
    require_once("locallib.php");
    include 'choice_form.php';

    $id = required_param('id', PARAM_INT); // block instance id
    $from = optional_param('from', 0, PARAM_INT);
    $reset = optional_param('reset', 0, PARAM_INT);
    $sitecontext = context_system::instance();

    if ($reset){
        unset($SESSION->prechoice);
    }

	$url = $CFG->wwwroot.'/blocks/ext_sighup/choice.php?id='.$id;
    $PAGE->set_url($url);
    $PAGE->set_context($sitecontext);
    $PAGE->set_title($SITE->fullname);
    $PAGE->set_heading($SITE->fullname .' : '.get_string('step1choose', 'block_ext_signup'));
    $PAGE->set_focuscontrol('');
    $PAGE->set_cacheable(true);
    $PAGE->set_button('' /*);
    $PAGE->set_headingmenu(user_login_string($SITE).$langmenu*/);
    echo $OUTPUT->header();
    $page = 5;
    $max = count(explode(',', @$CFG->block_ext_signup_courses));

	echo '<br/>';    
    echo $OUTPUT->box(get_string('alreadyusersadvice', 'block_ext_signup'));
    $mform = new Course_Choice_Form($from, $page, $max);

    $data = @$SESSION->prechoice[$from]->choiceset;
    // setup a temporary userid untill we get account confirmation
    if (empty($data->userid)){
        $data->userid = md5(time());
    }
    $data->id = $id;
    $mform->set_data($data);
    if ($mform->is_cancelled()){
        redirect($CFG->wwwroot);
    } elseif ($data = $mform->get_data()) {
        if (!empty($data->reset)){
            unset($SESSION->prechoice);
            echo '<br/>';
            echo $OUTPUT->box(get_string('formreset', 'block_ext_signup'));
            redirect($CFG->wwwroot."/blocks/ext_signup/choice.php?id=$id");
        } 
        if (!empty($data->cancel)){
        	redirect($CFG->wwwroot);
        } 
        if (!empty($data->iscomplete)){
            $SESSION->prechoice[$from]->choiceset = $data;
            // test if there are courses 
            $choicekeys = (array)($SESSION->prechoice[$from]->choiceset);
            $choices = array_keys($choicekeys);
            if (!preg_match('/choice_/', implode(' ', $choices))){
            	echo '<br/>';
	            echo $OUTPUT->box(get_string('emptychoice', 'block_ext_signup'));
	            redirect($CFG->wwwroot."/blocks/ext_signup/choice.php?id=$id");
            }
            $DB->delete_records('block_ext_signup', array('userid' => $data->userid));
            foreach($SESSION->prechoice as $prechoice){
                foreach($prechoice->choiceset as $key => $choice){
                    if (preg_match("/choice_(\\d+)/", $key, $matches)){
                        $preenroll = new StdClass;
                        $preenroll->userid = $data->userid;
                        $preenroll->courseid = $matches[1];
                        $preenroll->accepted = EXT_PENDING;
                        $preenroll->timecreated = time();
                        $DB->insert_record('block_ext_signup', $preenroll);
                    }
                }
            }
            echo '<br/>';
            echo $OUTPUT->box(get_string('processhelp1', 'block_ext_signup'));
            echo '<br/>';
            echo $OUTPUT->continue_button($CFG->wwwroot."/blocks/ext_signup/signup.php?id={$id}&amp;userid={$data->userid}");
        } else {
        	echo '<br/>';
            echo $OUTPUT->box(get_string('prechoicerecorded', 'block_ext_signup'));
            $SESSION->prechoice[$from]->choiceset = $data;
            echo '<center>';
            ext_signup_courses_print_pager($from, $page, $max, $CFG->wwwroot."/blocks/ext_signup/choice.php?id=$id");
            echo '</center>';

            $mform->display();
        }
    } else {
        echo '<center><br/>';
        echo '<p class="ext-signup-search">';
		print_string('searchinpages', 'block_ext_signup');
        echo '</p>';
        ext_signup_courses_print_pager($from, $page, $max, $CFG->wwwroot."/blocks/ext_signup/choice.php?id=$id");
        echo '</center>';

        $mform->display();
    }
    echo $OUTPUT->footer('empty');
?>
