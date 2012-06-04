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
 * master controller for main view.
 * 
 * @usecase deleteall
 * @usecase sendall
 * @usecase acceptall
 * @usecase rejectall
 */

if (!defined('MOODLE_INTERNAL')) die('You cannot accesss this script directly');

require_once $CFG->dirroot.'/auth/ticket/lib.php';

/************************ full delete all reference of user ************************/
if ($action == 'deleteall'){
    $userid = required_param('userid', PARAM_INT);
    delete_records_select('block_ext_signup', " userid = $userid AND accepted = ".EXT_PENDING);
    if (record_exists('user', 'id', $userid, 'confirmed', 0)){
        delete_records('user', 'id', $userid);
    }
}
/************************ send all information collected and send a report ************************/
elseif ($action == 'sendall'){
    $userid = required_param('userid', PARAM_INT);
    $studentrole = get_record('role', 'shortname', 'student');
    $inputs = preg_grep("/^c\\d+$/", array_keys($_POST));
    if($inputs){
        $onewasaccepted = false;
        foreach($inputs as $input){
            $courseid = str_replace('c', '', $input);
            $resolve = optional_param('c'.$courseid, '', PARAM_TEXT);
            switch($resolve){
                case 'accept' :
                    if (!$exts = get_records_select('block_ext_signup', " userid = $userid AND courseid = $courseid AND accepted = ".EXT_PENDING)){
                        debugging("skipping $userid on accept for $courseid ");
                        continue;
                    }
                    $extvalues = array_values($exts);
                    $ext = $extvalues[0];
                    $ext->accepted = EXT_ACCEPTED;
                    $ext->timeaccepted = time();
                    $ext->acceptedby = $USER->id;
                    $ext->reason = '';
                    update_record('block_ext_signup', $ext);
                    $coursecontext = get_context_instance(CONTEXT_COURSE, $courseid);
                    role_assign($studentrole->id, $userid, 0, $coursecontext->id, time(), 0, false, 'ext', time());
                    $processed[] = $ext;
                    $onewasaccepted = true;
                    break;
                case 'reject' :
                    $reason = optional_param('reason'.$courseid, '', PARAM_TEXT);
                    if (!$exts = get_records_select('block_ext_signup', " userid = $userid AND courseid = $courseid AND accepted = ".EXT_PENDING)){
                        debugging("skipping $userid on reject");
                        continue;
                    }
                    $extvalues = array_values($exts);
                    $ext = $extvalues[0];
                    $ext->accepted = EXT_REJECTED;
                    $ext->timeaccepted = time();
                    $ext->acceptedby = $USER->id;
                    $ext->reason = $reason;
                    update_record('block_ext_signup', $ext);
                    $processed[] = $ext;
                    break;
                case 'delete' :
                    delete_records_select('block_ext_signup', " userid = $userid AND courseid = $courseid AND accepted = ".EXT_PENDING);
                    break;
            }            
        }
    }

    if (!empty($onewasaccepted)){
        // confirm user if it has not been yet.
        set_field('user', 'confirmed', 1, 'id', $ext->userid);

        // call an external hanlder in which integrators can perform other processing with user 
        $handle->userid = $userid;
        $handle->block = $theblock;
        $handlers = ext_signup_get_handlers();
        foreach($handlers as $handler){
            include_once "handlers/$handler.php";
            $handler = new $handler($userid, $theblock);
            $handler->process();
        }
    }

    if (!empty($processed)){

        // notify the student and the teachers of accepted courses

        include_once($CFG->dirroot."/blocks/ext_signup/mailtemplatelib.php");

        $user = get_record('user', 'id', $userid);
        $vars = array('SITE' => $SITE->shortname, 
                      'FIRSTNAME' => $user->firstname, 
                      'LASTNAME' => $user->lastname, 
                      'CITY' => $user->city, 
                      'MAIL' => $user->email, 
                      'COUNTRY' => $user->country, 
                      'URL' => $CFG->wwwroot.'/login/index.php?ticket=<%%TICKET%%>');
        $acceptedcourses = array();
        $rejectedcourses = array();
        foreach($processed as $extenroll){
            if ($extenroll->accepted == EXT_ACCEPTED){
                $coursename = get_field('course', 'fullname', 'id', $extenroll->courseid);
                $vars['COURSE'] = $coursename;
                $coursecontext = get_context_instance(CONTEXT_COURSE, $extenroll->courseid);
                $studentrole = get_record('role', 'shortname', 'student');
                $wheretogo = "/user/index.php?contextid={$coursecontext->id}&roleid={$studentrole->id}";
    
                if($teachers = get_users_by_capability($coursecontext, 'moodle:legacy:teacher', 'u.id, username, firstname, lastname, email, emailstop, lang, mailformat')){
                    foreach($teachers as $teacher){
                        // compile in teacher own's language
                        $notification = compile_mail_template('enroll', $vars, 'block_ext_signup', $teacher->lang);
                        $notification_html = compile_mail_template('enroll_html', $vars, 'block_ext_signup', $teacher->lang);
                        // if ($CFG->debugsmtp) echo "Sending New Enroll Mail Notification to " . fullname($teacher) . ' at '.$coursename.'<br/>';
                        ticket_notify($teacher, $USER, get_string('newsignup', 'block_ext_signup', $coursename), $notification, $notification_html, $wheretogo, 'Ext signup');
                    }
                }
                $acceptedcourses[] = $coursename;
            } elseif ($extenroll->accepted == EXT_REJECTED){
                $coursename = get_field('course', 'fullname', 'id', $extenroll->courseid);
                $vars['COURSE'] = $coursename;
                $rejectedcourses[] = $coursename." ($extenroll->reason)";
            }
        }      

        $vars = array('SITE' => $SITE->shortname, 
                      'FIRSTNAME' => $user->firstname, 
                      'LASTNAME' => $user->lastname, 
                      'CITY' => $user->city, 
                      'MAIL' => $user->email, 
                      'COUNTRY' => $user->country, 
                      'URL' => $CFG->wwwroot.'/login/index.php?ticket=<%%TICKET%%>', 
                      'ACCEPTEDCOURSELIST' => implode(",\n", $acceptedcourses),
                      'REJECTEDCOURSELIST' => implode(",\n", $rejectedcourses),
                      );
        $vars_html = array('SITE' => $SITE->shortname, 
                      'FIRSTNAME' => $user->firstname, 
                      'LASTNAME' => $user->lastname, 
                      'CITY' => $user->city, 
                      'MAIL' => $user->email, 
                      'COUNTRY' => $user->country, 
                      'URL' => $CFG->wwwroot.'/login/index.php?ticket=<%%TICKET%%>', 
                      'ACCEPTEDCOURSELIST' => implode(",<br/>\n", $acceptedcourses),
                      'REJECTEDCOURSELIST' => implode(",<br/>\n", $rejectedcourses));
        $userlang = (empty($CFG->block_ext_signup_submitternotifylang)) ? $user->lang : $CFG->block_ext_signup_submitternotifylang ;
        if ($user->confirmed){
	        $notification = compile_mail_template('process', $vars, 'block_ext_signup', $userlang);
	        $notification_html = compile_mail_template('process_html', $vars_html, 'block_ext_signup', $userlang);
	    } else {
	        $notification = compile_mail_template('process_unconfirmed', $vars, 'block_ext_signup', $userlang);
	        $notification_html = compile_mail_template('process_unconfirmed_html', $vars_html, 'block_ext_signup', $userlang);
	    }
        // if ($CFG->debugsmtp) echo "Sending Process Mail Notification to " . fullname($user) . '<br/>'.$notification_html.'<br/>';
        $wheretogo = $CFG->wwwroot;
        ticket_notify($user, $USER, get_string('signupresults', 'block_ext_signup', $SITE->shortname), $notification, $notification_html, $wheretogo, 'Ext Accept');
    }
}
/************************ reject all demands and notify student ************************/
elseif ($action == 'rejectall'){
    $userid = required_param('userid', PARAM_INT);
    $reason = required_param('reason', PARAM_TEXT);
    $exts = get_records('block_ext_signup', 'userid', $userid);
    foreach($exts as $ext){
        $ext->accepted = EXT_REJECTED;
        $ext->timeaccepted = time();
        $ext->acceptedby = $USER->id;
        $ext->reason = $reason;
        update_record('block_ext_signup', $ext);
        $rejectedcourses[] = get_field('course', 'fullname', 'id', $ext->courseid). " ($reason)";
    }
    
    // notify student
    $vars = array('SITE' => $SITE->shortname, 
                  'COURSELIST' => implode(",\n", $courses),
                  'REASON', $reason);
    $vars_html = array('SITE' => $SITE->shortname, 
                  'COURSELIST' => implode(",<br/>\n", $courses),
                  'REASON', $reason);
    $userlang = (empty($CFG->block_ext_signup_submitternotifylang)) ? $user->lang : $CFG->block_ext_signup_submitternotifylang ;
    $notification = compile_mail_template('reject', $vars, 'block_ext_signup', $userlang);
    $notification_html = compile_mail_template('reject_html', $vars_html, 'block_ext_signup', $userlang);
    // if ($CFG->debugsmtp) echo "Sending Rejection Mail Notification to " . fullname($user) . '<br/>'.$notification_html.'<br/>';
    email_to_user($user, $USER, get_string('rejectsignup', 'block_ext_signup', $SITE->shortname), $notification, $notification_html);
}
/************************ accept all demands and notify student and teachers ************************/
elseif ($action == 'acceptall'){
    $userid = required_param('userid', PARAM_INT);
    if ($exts = get_records_select('block_ext_signup', " userid = $userid AND accepted = ".EXT_PENDING)){
        foreach($exts as $ext){
            $ext->accepted = EXT_ACCEPTED;
            $ext->timeaccepted = time();
            $ext->acceptedby = $USER->id;
            update_record('block_ext_signup', $ext);
            $studentrole = get_record('role', 'shortname', 'student');
            $coursecontext = get_context_instance(CONTEXT_COURSE, $ext->courseid);
            role_assign($studentrole->id, $userid, 0, $coursecontext->id, time(), 0, false, 'ext', time());
            $processed[] = $ext;
        }
    }
    
    // confirm user if necessary (anyaway) and bind him to general additional assignations
    set_field('user', 'confirmed', 1, 'id', $userid);
    
    // call an external hanlder in which integrators can perform other processing with user 
    $handle->userid = $userid;
    $handle->block = $theblock;
    if($handlers = ext_signup_get_handlers()){
        foreach($handlers as $handler){
            include_once "handlers/$handler.php";
            $handler = new $handler($userid, $theblock);
            $handler->process();
        }
    }

    if (!empty($processed)){
        // notify the student and the teachers of accepted courses

        include_once($CFG->dirroot."/auth/ext/mailtemplatelib.php");

        $user = get_record('user', 'id', $userid);
        $vars = array('SITE' => $SITE->shortname, 
                      'FIRSTNAME' => $user->firstname, 
                      'LASTNAME' => $user->lastname, 
                      'CITY' => $user->city, 
                      'MAIL' => $user->email, 
                      'COUNTRY' => $user->country, 
                      'URL' => $CFG->wwwroot.'/login/index.php?ticket=<%%TICKET%%>');

        foreach($processed as $extenroll){
            $coursename = get_field('course', 'fullname', 'id', $extenroll->courseid);
            $vars['COURSE'] = $coursename;
            $coursecontext = get_context_instance(CONTEXT_COURSE, $extenroll->courseid);
            $studentrole = get_record('role', 'shortname', 'student');
            $wheretogo = "/user/index.php?contextid={$coursecontext->id}&roleid={$studentrole->id}";

            if($teachers = get_users_by_capability($coursecontext, 'moodle:legacy:teacher', 'u.id, username, firstname, lastname, email, emailstop, lang, mailformat')){
                foreach($teachers as $teacher){
                    $notification = compile_mail_template('enroll', $vars, 'block_ext_signup', $teacher->lang);
                    $notification_html = compile_mail_template('enroll_html', $vars, 'block_ext_signup', $teacher->lang);
                    // if ($CFG->debugsmtp) echo "Sending New Enroll Mail Notification to " . fullname($teacher) . ' at '.$coursename.'<br/>';
                    ticket_notify($teacher, $USER, get_string('newsignup', 'block_ext_signup', $coursename), $notification, $notification_html, $wheretogo, 'Ext signup');
                }
            }
            $acceptedcourses[] = $coursename;
        }      

        $vars = array('SITE' => $SITE->shortname, 
                      'URL' => $CFG->wwwroot.'/login/index.php?ticket=<%%TICKET%%>', 
                      'ACCEPTEDCOURSELIST' => implode(",\n", $acceptedcourses));
        $vars_html = array('SITE' => $SITE->shortname, 
                      'URL' => $CFG->wwwroot.'/login/index.php?ticket=<%%TICKET%%>', 
                      'ACCEPTEDCOURSELIST' => implode(",<br/>\n", $acceptedcourses));
        $userlang = (empty($CFG->block_ext_signup_submitternotifylang)) ? $user->lang : $CFG->block_ext_signup_submitternotifylang ;
        $notification = compile_mail_template('accept', $vars, 'block_ext_signup', $userlang);
        $notification_html = compile_mail_template('accept_html', $vars_html, 'block_ext_signup', $userlang);
        // if ($CFG->debugsmtp) echo "Sending Acceptation Mail Notification to " . fullname($user) . '<br/>'.$notification_html.'<br/>';
        $wheretogo = $CFG->wwwroot;
        ticket_notify($user, $USER, get_string('newsignup', 'block_ext_signup', $SITE->shortname), $notification, $notification_html, $wheretogo, 'Ext Accept');
    }
}

?>