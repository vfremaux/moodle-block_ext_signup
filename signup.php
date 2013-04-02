<?php  // $Id: signup.php,v 1.56.2.2 2008/09/25 07:40:54 skodak Exp $

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
     * @author     Valery Fremaux <valery.fremaux@gmail.com>
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
     * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
     * @copyright  (C) 2010 ValEISTI (http://www.valeisti.fr)
     *
     */

    require_once('../../config.php');
    /**
     * Returns whether or not the captcha element is enabled, and the admin settings fulfil its requirements.
     * @return bool
     */
    require_once('signup_form.php');
    
    $sitecontext = context_system::instance();

    $usertmpid = optional_param('userid', 0, PARAM_TEXT);
    $id = required_param('id', PARAM_INT);

    $authplugin = get_auth_plugin('ext');

	echo isloggedin();
    if (isguestuser() && isloggedin()) {
        print_error('noguests', 'block_ext_signup');
    }

    if (isloggedin()) {
        print_error('nologgedusers', 'block_ext_signup');
    }


    //HTTPS is potentially required in this page
    httpsrequired();

	$url = $CFG->wwwroot.'/blocks/ext_signup/signup.php?id='.$id;
    $PAGE->set_url($url);
	$PAGE->set_context($sitecontext);

	// We need desactivate debug notices as using deliberately a deprecated form element.
	// file form element is the inly way to get some files from an unlogged pre-user.
	$debug = $CFG->debug;
	$CFG->debug = 0;
    $mform_signup = new login_signup_form();
    $CFG->debug = $debug;

    if ($mform_signup->is_cancelled()) {
        redirect($CFG->httpswwwroot);

    } else if ($user = $mform_signup->get_data()) {
        $user->confirmed   = 0;
        $user->lang        = current_language();
        $user->firstaccess = 0;
        $user->mnethostid  = $CFG->mnet_localhost_id;
        $user->secret      = random_string(15);
        $user->auth        = 'ext';
        // check user is not a registered user before doing anything else
        $exists = $DB->count_records('user', array('username' => $user->username));
        if ($exists){
        	print_error('errorusernameexists', 'block_ext_signup');
        }

        $mform_signup->save_files($CFG->dataroot . '/user/0/ext_signup');

		// quickly remove temp files off the way        
        $cvfilename = @$_FILES['cv']['name'];
        $picturefilename = @$_FILES['imagefile']['name'];

        if (!empty($cvfilename)){
        	$cvfilename = clean_filename($cvfilename); // remove all harmfull chars consistently with save_files
            $parts = pathinfo($cvfilename);
            $securedfilename = md5($user->secret.@$CFG->passwordsaltmain).'_cv.'.$parts['extension'];
            $securedfilenamefull = $CFG->dataroot . '/user/0/ext_signup/'.md5($user->secret.@$CFG->passwordsaltmain).'_cv.'.$parts['extension'];
            rename($CFG->dataroot . '/user/0/ext_signup/'.$cvfilename, $securedfilenamefull);
            $user->profile_field_cv = $securedfilename;
        }

        if (!empty($picturefilename)){
        	$picturefilename = clean_filename($picturefilename); // remove all harmfull chars consistently with save_files
            $parts = pathinfo($picturefilename);
            $securedfilename = md5($user->secret.@$CFG->passwordsaltmain).'_picture.'.$parts['extension'];
            $securedfilenamefull = $CFG->dataroot . '/user/0/ext_signup/'.md5($user->secret.@$CFG->passwordsaltmain).'_picture.'.$parts['extension'];
            rename($CFG->dataroot . '/user/0/ext_signup/'.$picturefilename, $securedfilenamefull);
            $user->picture_image = $securedfilename;
        }

        $user->profile_field_externalsignup = 1;
    	add_to_log(0, 'ext_signup', 'submit', "/blocks/ext_signup/view.php?id={$id}", $id, 0, $user->id);
        $authplugin->user_signup($user, true, $id); // prints notice and link to login/index.php
        exit; // never reached
    }

    $newaccount = get_string('newaccount', 'block_ext_signup');
    $login      = get_string('login');

    if (empty($CFG->langmenu)) {
        $langmenu = '';
    } else {
        $currlang = current_language();
        $langs    = get_string_manager()->get_list_of_translations();
        $langmenu = $OUTPUT->single_select("$CFG->wwwroot/login/signup.php?lang=", "chooselang", $langs, $currlang);
    }

	$PAGE->navigation->add($login, $CFG->wwwroot.'/login/index.php');
    $PAGE->set_title($newaccount);
    $PAGE->set_heading($newaccount);
    /* SCANMSG: may be additional work required for $navigation variable */
    $PAGE->set_focuscontrol($mform_signup->focus());
    $PAGE->set_cacheable(true);
    $PAGE->set_button("<div class=\"langmenu\">$langmenu</div>");

    echo $OUTPUT->header();

    if ($selection = $DB->get_records_menu('block_ext_signup', array('userid' => $usertmpid), 'id', 'courseid,userid')){
        $selectedids = implode(',', array_keys($selection));
        $coursesselected = $DB->get_records_select('course', "id IN ($selectedids)", array(), 'fullname ASC', 'id, fullname');
    } else {
        print_error('errornocourse', 'block_ext_signup');
    }
	add_to_log(0, 'ext_signup', 'signup', "/blocks/ext_signup/view.php?id={$id}", $id, 0);

    $data->userid = $usertmpid;
    $data->id = $id;
    $mform_signup->set_data($data);
    $mform_signup->display();
    
    echo $OUTPUT->footer();

?>
