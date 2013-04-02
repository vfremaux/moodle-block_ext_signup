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
 * an additional pluggable handler for subscribing incoming users to a news forum
 */

require_once $CFG->dirroot.'/blocks/ext_signup/ext_process_handler.class.php';
require_once $CFG->dirroot.'/mod/forum/lib.php';

class common_forum_subscribe_handler extends ext_process_handler{

    function process(){
    	global $DB;
    	
        if (!empty($CFG->block_ext_signup_commonforumid)){
            $studentrole = $DB->get_record('role', array('shortame' => 'student'));
            $forum = $DB->get_record('forum', array('id' => $CFG->block_ext_signup_commonforumid));
            $coursecontext = context_course::instance($forum->course);
            role_assign($role->id, $this->userid, 0, $coursecontext);
            forum_subscribe($this->userid, $forum->id);
        }
    }
    function add_setting(&$settings){
    	global $DB;

        if ($forums = $DB->get_records('forum', null)){
	        $forumopts[0] = get_string('defaultlanguage', 'block_ext_signup') ;
	        foreach($forums as $forum){
	            $forumopts[$forum->id] = $DB->get_field('course', 'shortname', array('id' => $forum->course)) . ' : '.$forum->name;
	        }
	        $settings->add(new admin_setting_configselect('block_ext_signup_commonforumid', get_string('extcommonforum', 'block_ext_signup'), get_string('configextcommonforum', 'block_ext_signup'), '', $forumopts));
	    }
    }

    function block_content(&$content){
    }

    function block_footer(&$footer){
        global $CFG, $DB;
        
        if (!empty($CFG->block_ext_signup_commonforumid)){
            $forum = $DB->get_record('forum', array('id' => $CFG->block_ext_signup_commonforumid));
            $footer .= '<div class="ext_signup">';
            $newuserid = md5(time());
            $forcelang = ($CFG->block_ext_signup_submitternotifylang) ? "&lang={$CFG->block_ext_signup_submitternotifylang}" : '&lang='.current_language() ;
            $footer .= '<a href="'.$CFG->wwwroot."/blocks/ext_signup/common_forum_receiver.php?id={$this->blockinstance}&amp;courseid={$forum->course}&amp;userid=$newuserid&amp;iscomplete=iscomplete{$forcelang}\">".get_string('applyfornewsletter', 'block_ext_signup').'</a>';
            $footer .= '</div>';
        }            
    }        
}

?>