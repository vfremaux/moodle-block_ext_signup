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
    require_once($CFG->libdir.'/tablelib.php');
    require_once($CFG->dirroot.'/blocks/ext_signup/locallib.php');
    $id = required_param('id', PARAM_INT); // The block ID    
    $userid = required_param('userid', PARAM_INT);    
    $view = optional_param('view', '', PARAM_ALPHA);
    $blockcontext = context_block::instance($id);

    if (!has_capability('block/ext_signup:view', $blockcontext)) {
        print_error('errornopermissiontoview', 'block_ext_signup');
    }

	$url = $CFG->wwwroot.'/blocks/ext_signup/display.php?id='.$id);
    $PAGE->set_url($url);
    $PAGE->set_title($SITE->fullname);
    $PAGE->set_heading($SITE->fullname .' : '.get_string('display', 'block_ext_signup'));
    $PAGE->set_focuscontrol('');
    $PAGE->set_cacheable(true);
    echo $OUTPUT->header();

    echo $OUTPUT->heading(get_string('externalstudent', 'block_ext_signup'));
    $user = $DB->get_record('user', array('id' => $userid));
    echo $OUTPUT->box_start();
    $course->id = SITEID;
    print_user($user, $course);
    $cv = get_cv_record($userid);
    $cvfiltered = preg_replace('/^user\/0\/cv\/[a-f0-9]{32}_/', '', $cv);
    print_string('cv', 'block_ext_signup', "<a target=\"_blank\" href=\"{$CFG->wwwroot}/blocks/ext_signup/file.php?id=$id&amp;file=/{$cv}\">$cvfiltered</a>");
    print_string('description', 'block_ext_signup', $user->description);
    print_string('address', 'block_ext_signup', $user->address);
    print_string('institution', 'block_ext_signup', $user->institution);
    print_string('department', 'block_ext_signup', $user->department);
    print_string('phone1', 'block_ext_signup', $user->phone1);
    print_string('phone2', 'block_ext_signup', $user->phone2);
    echo $OUTPUT->box_end();
    $requests = $DB->get_records('block_ext_signup', array('userid' => $user->id));
    $accepts = array();
    $rejects = array();
    $pendings = array();
    foreach($requests as $request){
    	if ($request->accepted == EXT_ACCEPTED){
    		$accepts[] = $request;
    	} else if ($request->accepted == EXT_REJECTED) {
    		$rejects[] = $request;
    	} else {
    		$pendings[] = $request;
    	}
    }

    echo $OUTPUT->box_start();
    echo $OUTPUT->heading(get_string('pendings', 'block_ext_signup'));
    if (empty($pendings)){
    	print_string('nopendings', 'block_ext_signup');
    } else {
    	foreach($pendings as $pending){
    		$course = $DB->get_record('course', array('id' => $pending->courseid));
    		echo "<li> <a href=\"{$CFG->wwwroot}/blocks/ext_signup/process.php?id={$id}&amp;userid={$userid}\">".$course->fullname.'</a> ('.userdate($pending->timecreated).')</li>';
    	}
    }

    echo $OUTPUT->box_end();

    echo $OUTPUT->box_start();
    echo $OUTPUT->heading(get_string('accepts', 'block_ext_signup'));
    if (empty($accepts)){
    	print_string('noaccepts', 'block_ext_signup');
    } else {
    	foreach($accepts as $accept){
    		$course = $DB->get_record('course', array('id' => $accept->courseid));
    		echo '<li> '.$course->fullname.' ('.userdate($accept->timecreated).')</li>';
    	}
    }
    echo $OUTPUT->box_end();

    echo $OUTPUT->box_start();
    echo $OUTPUT->heading(get_string('rejects', 'block_ext_signup'));
    if (empty($rejects)){
    	print_string('norejects', 'block_ext_signup');
    } else {
    	foreach($rejects as $reject){
    		$course = $DB->get_record('course', array('id' => $reject->courseid));
    		echo '<li> '.$course->fullname.' ('.userdate($reject->timeaccepted).')<br/><span class="smalltext">[ '.$reject->reason.' ]</span></li>';
    	}
    }
    echo $OUTPUT->box_end();
    echo $OUTPUT->continue_button($CFG->wwwroot."/blocks/ext_signup/view.php?id={$id}&amp;view={$view}");    

    echo $OUTPUT->footer();
?>