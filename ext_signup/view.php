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
    $from = optional_param('from', 0, PARAM_INT);    
    $view = required_param('view', PARAM_ALPHA);    
    $action = optional_param('what', '', PARAM_ALPHA);    
    $sortby = optional_param('sortby', 'timecreated', PARAM_TEXT);    
    $dir = optional_param('dir', 'DESC', PARAM_ALPHA);    

    $url = $CFG->wwwroot."/blocks/ext_signup/view.php?id={$id}&view={$view}";

    if (!$instance = get_record('block_instance', 'id', $id)){
        error("Block record ID was incorrect");
    }
    if (!$theblock = block_instance('ext_signup', $instance)){
        error("Block instance does'nt exist");
    }

    // if (debugging()) echo "[[$view::$action]]";
    
    $pagesize = 30;

    $blockcontext = get_context_instance(CONTEXT_BLOCK, $id);

    if (!has_capability('block/ext_signup:process', $blockcontext)) {
        error('You do not have the required permission to process external users.');
    }

    print_header($SITE->fullname, $SITE->fullname .' : '.get_string('process', 'block_ext_signup'), get_string('process', 'block_ext_signup'), '', '<meta name="description" content="'. strip_tags(format_text($SITE->summary, FORMAT_HTML)) .'" />', true, '' /* , user_login_string($SITE).$langmenu*/);

    /// Print tabs with options for user
    $sql = "
        SELECT 
            COUNT(DISTINCT es.userid)
        FROM
            {$CFG->prefix}block_ext_signup es,
            {$CFG->prefix}user u
        WHERE
        	es.userid = CONCAT('', u.id) AND
            accepted = ".EXT_PENDING."
            
    ";
    $c->users = count_records_sql($sql);

    $sql = "
        SELECT 
            COUNT(*)
        FROM
            {$CFG->prefix}block_ext_signup es,
            {$CFG->prefix}user u
        WHERE
        	es.userid = CONCAT('', u.id) AND
            accepted = ".EXT_PENDING."
    ";
    $c->rq = count_records_sql($sql);
    
    if (!preg_match("/pending|accepted|rejected/", $view)) $view = 'pending';
    $rows[0][] = new tabobject('pending', "view.php?id={$id}&amp;view=pending", get_string('pending', 'block_ext_signup').' ('.get_string('pendingusers', 'block_ext_signup', $c).')');
    $rows[0][] = new tabobject('accepted', "view.php?id={$id}&amp;view=accepted", get_string('accepted', 'block_ext_signup'));
    $rows[0][] = new tabobject('rejected', "view.php?id={$id}&amp;view=rejected", get_string('rejected', 'block_ext_signup'));
    print_tabs($rows, $view);

    if ($action){
        include 'view.controller.php';
    }

    $sql = "
        SELECT 
            es.*,
            u.lastname
        FROM
            {$CFG->prefix}block_ext_signup es,
            {$CFG->prefix}user u
        WHERE
            CONCAT('', u.id) = es.userid AND
    ";

    $countsql = "
        SELECT
        	COUNT(*)
        FROM
            {$CFG->prefix}block_ext_signup es,
            {$CFG->prefix}user u
        WHERE
            CONCAT('', u.id) = es.userid AND
    ";

    switch($view){
        case 'pending':{
            $allextscount = count_records_sql($countsql." accepted =  ".EXT_PENDING);
            $exts = get_records_sql($sql." accepted = ".EXT_PENDING." ORDER BY $sortby $dir,userid LIMIT $from,$pagesize");
            break;
        }            
        case 'accepted':{
            $allextscount = count_records_sql($countsql." accepted =  ".EXT_ACCEPTED);
            $exts = get_records_sql($sql." accepted = ".EXT_ACCEPTED." ORDER BY $sortby $dir,userid LIMIT $from,$pagesize");
            break;
        }
        case 'rejected':{
            $allextscount = count_records_sql($countsql." accepted =  ".EXT_REJECTED);
            $exts = get_records_sql($sql." accepted = ".EXT_REJECTED." ORDER BY $sortby $dir,userid LIMIT $from,$pagesize");
            break;
        }
    }
    
    /// prepare table
    $studentstr = ext_signup_print_sorted_link(get_string('student', 'block_ext_signup'), 'lastname', $sortby, $dir, $url."&amp;from=$from", true);
    $citystr = get_string('city');
    $datestr = ext_signup_print_sorted_link(get_string('date'), 'timecreated', $sortby, $dir, $url."&amp;from=$from", true); 
    $hasattachmentstr = get_string('hasattachment', 'block_ext_signup'); 
    $coursestr = get_string('course');
    $teachersstr = get_string('teachers');
    $commandsstr = get_string('commands', 'block_ext_signup');
    $processstr = get_string('processthis', 'block_ext_signup');
    $displaystr = get_string('display', 'block_ext_signup');

    $table->head = array("<b>$studentstr</b>", "<b>$citystr</b>", "<b>$datestr</b>", "<b>$coursestr</b>", "<b>$teachersstr</b>", "<b>$commandsstr</b>", "<b>$hasattachmentstr</b>");
    $table->width = '100%';
    $table->size = array('20%', '10%', '10%', '30%', '20%', '10%', '5%');
    $table->align = array('LEFT','LEFT','LEFT','LEFT','LEFT','LEFT', 'CENTER');

    /// print table of results

    $hold = '';
    if (!empty($exts)){
        foreach($exts as $ext){
            $course = get_record('course', 'id', $ext->courseid);
            $courselink = "<a href=\"{$CFG->wwwroot}/course/view.php?id={$course->id}\">{$course->fullname}</a>";
            $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
            $teachers = get_users_by_capability($coursecontext, 'moodle/legacy:editingteacher', 'u.id,firstname, lastname, email', 'lastname,firstname', '', '', '', '', false);
            $teachs = array();
            foreach($teachers as $teacher){
                $teachs[] = fullname($teacher);
            }
            if (count($teachs) > 10){
            	$teachlist = get_string('morethanten', 'block_ext_signup');            	
            } else if (count($teachs) == 0){
            	$teachlist = get_string('noteachers', 'block_ext_signup');
            } else {
            	$teachlist = implode(',<br/>', $teachs);
            }
            $commands = '';
            if ($ext->userid != $hold){
                if ($view == 'pending'){
                    $commands = "<a href=\"$CFG->wwwroot/blocks/ext_signup/process.php?id={$id}&amp;userid={$ext->userid}\">$processstr<a>";
                } else {
                    $commands = "<a href=\"$CFG->wwwroot/blocks/ext_signup/display.php?id={$id}&amp;userid={$ext->userid}&amp;view={$view}\">$displaystr<a>";
                }
                $hold = $ext->userid;
                $user = get_record('user', 'id', $ext->userid);
                if ($cv = get_cv_record($ext->userid)){
                    $curriculumstr = "<img src=\"{$CFG->wwwroot}/blocks/ext_signup/pix/attachment.gif\" />";
                } else {
                    $curriculumstr = '';
                }
                
                $recorddate = userdate($ext->timecreated);
                if (!empty($ext->timeaccepted)){
                    $recorddate = '<br/>('.userdate($ext->timeaccepted).')';
                }
                
                $confirmed = ($user->confirmed) ? '<br/><span class="small">('.get_string('confirmed', 'block_ext_signup').')</span>' : '' ;
                $table->data[] = array(fullname($user).$confirmed, $user->city, $recorddate, $courselink, $teachlist, $commands, $curriculumstr);
            } else {
                $table->data[] = array('', '', '', $courselink, implode(',<br/>', $teachs), '', '');
            }
        }
    } else {
        print_string('nomoreexts', 'block_ext_signup');
    }

    if ($allextscount > $pagesize){
        echo '<p><center>';
        ext_signup_print_pager($from, $pagesize, $allextscount, $url."&amp;sortby=$sortby&amp;dir=$dir");
        echo '</center></p>';
    }
    
    print_table($table);

    if ($allextscount > $pagesize){
        echo '<p><center>';
        ext_signup_print_pager($from, $pagesize, $allextscount, $url."&amp;sortby=$sortby&amp;dir=$dir");
        echo '</center></p>';
    }

    print_footer();
?>
