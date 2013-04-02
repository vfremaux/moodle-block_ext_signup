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
    $PAGE->requires->js('/blocks/ext_signup/js/process.js');
    $id = required_param('id', PARAM_INT); // The block ID    
    $userid = required_param('userid', PARAM_INT);    
    $action = optional_param('what', '', PARAM_ALPHA);
    $blockcontext = context_block::instance($id);

	// Security check
    if (!has_capability('block/ext_signup:process', $blockcontext)) {
        print_error('errornopermissiontoprocess', 'block_ext_signup');
    }

	$url = $CFG->wwwroot.'/blocks/ext_signup/process.php?id='.$id;
	$PAGE->set_url($url);
    $PAGE->set_title($SITE->fullname);
    $PAGE->set_heading($SITE->fullname .' : '.get_string('process', 'block_ext_signup'));
    $PAGE->set_focuscontrol('');
    $PAGE->set_cacheable(true);
    $PAGE->set_button('' /*);
    $PAGE->set_headingmenu(user_login_string($SITE).$langmenu*/);
    echo $OUTPUT->header();

    $confirmstr = get_string('confirmdelete', 'block_ext_signup');
    echo "<script type=\"text/javascript\">
    var confirmtext = '$confirmstr';
    </script>";

    echo $OUTPUT->heading(get_string('externalprocessing', 'block_ext_signup'));
    if (!$user = $DB->get_record('user', array('id' => $userid))){
    	print_error('errorgoneaway', 'block_ext_signup');
    }
    echo $OUTPUT->box_start();
    $course->id = SITEID;
    
    print_user($user, $course);
    
    // print_string('username', 'block_ext_signup', "<a href=\"mailto:{$user->email}\">".fullname($user)."</a>");
    $cv = get_cv_record($userid);
    $cvfiltered = preg_replace("/^user\\/0\\/{$userid}\\/[a-f0-9]{32}_/", '', $cv);
    if (!empty($cvfiltered)){
    	print_string('cv', 'block_ext_signup', "<a target=\"_blank\" href=\"{$CFG->wwwroot}/blocks/ext_signup/file.php?id=$id&amp;file=/{$cv}\">$cvfiltered</a>");
    }
    print_string('description', 'block_ext_signup', $user->description);
    // print_string('address', 'block_ext_signup', $user->address);
    // print_string('city', 'block_ext_signup', $user->city);
    // print_string('country', 'block_ext_signup', $user->country);
    print_string('institution', 'block_ext_signup', $user->institution);
    print_string('department', 'block_ext_signup', $user->department);
    print_string('phone1', 'block_ext_signup', $user->phone1);
    print_string('phone2', 'block_ext_signup', $user->phone2);
    echo $OUTPUT->box_end();
    $coursestr = get_string('course');
    $cancelstr = get_string('cancel');
    $acceptstr = get_string('accept', 'block_ext_signup');
    $acceptallstr = get_string('acceptall', 'block_ext_signup');
    $rejectstr = get_string('reject');
    $rejectallstr = get_string('rejectall', 'block_ext_signup');
    $deletestr = get_string('delete');
    $deleteallstr = get_string('deleteall', 'block_ext_signup');
    $sendallstr = get_string('sendall', 'block_ext_signup');
    $reasonstr = get_string('reason', 'block_ext_signup');
    $quicklinksstr = get_string('quicklinks', 'block_ext_signup');
    $accepthelp = $OUTPUT->help_icon('accept', 'block_ext_signup', false);
    $rejecthelp = $OUTPUT->help_icon('reject', 'block_ext_signup', false);
    $deletehelp = $OUTPUT->help_icon('delete', 'block_ext_signup', false);
    $sendallhelp = $OUTPUT->help_icon('send', 'block_ext_signup', false);

    $table->head = array("<b>$coursestr</b>", "<b>$acceptstr</b>  $accepthelp", "<b>$rejectstr</b>  $rejecthelp", "<b>$deletestr</b> $deletehelp");
    $table->width = "100%";
    $table->align = array('left', 'center', 'center', 'center');
    $table->size = array('40%', '10%', '40%', '10%');
    $exts = $DB->get_records_select('block_ext_signup', " userid = $userid AND accepted = ".EXT_PENDING);
    foreach($exts as $ext){
        $course = $DB->get_record('course', array('id' => $ext->courseid));
        $acceptradio = "<input type=\"radio\" name=\"c{$ext->courseid}\" value=\"accept\" />";
        $rejectradio = "<input type=\"radio\" name=\"c{$ext->courseid}\"  value=\"reject\" /><br/>$reasonstr : <textarea name=\"reason{$ext->courseid}\" cols=\"35\" rows=\"3\"></textarea>";
        $deleteradio = "<input type=\"radio\" name=\"c{$ext->courseid}\" value=\"delete\" />";
        $table->data[] = array($course->fullname, $acceptradio, $rejectradio, $deleteradio);        
    }
    echo "<form name=\"processform\" action=\"{$CFG->wwwroot}/blocks/ext_signup/view.php\" method=\"post\" >";
    echo "<input type=\"hidden\" name=\"what\" value=\"\" />";
    echo "<input type=\"hidden\" name=\"id\" value=\"{$id}\" />";
    echo "<input type=\"hidden\" name=\"view\" value=\"pending\" />";
    echo "<input type=\"hidden\" name=\"userid\" value=\"{$userid}\" />";
    echo html_writer::table($table);

    $cancel = "<input type=\"submit\" name=\"cancel\" value=\"$cancelstr\" />";
    $sendall = "<input type=\"button\" name=\"sendall\" value=\"$sendallstr\"  onclick=\"document.forms['processform'].what.value='sendall'; document.forms['processform'].submit();\" />";
    $acceptall = "<input type=\"button\" name=\"acceptall\" value=\"$acceptallstr\" onclick=\"document.forms['processform'].what.value='acceptall'; document.forms['processform'].submit();\" />";
    $rejectall = "<input type=\"button\" name=\"rejectall\" value=\"$rejectallstr\" onclick=\"document.forms['processform'].what.value='rejectall'; document.forms['processform'].submit();\" /><br/>$reasonstr : <textarea name=\"reason\" cols=\"35\" rows=\"3\"></textarea>";
    $deleteall = "<input type=\"button\" name=\"deleteall\" value=\"$deleteallstr\" onclick=\"confirm_delete();\" />";

    echo "<table width=\"100%\" class=\"generaltable\" >
            <tr>
                <td colspan=\"4\" align=\"right\">$cancel $sendall $sendallhelp</td>
            </tr>
    </table>
    ";
    echo "<table width=\"100%\" class=\"generaltable\" >
            <tr valign=\"top\">
                <td>$quicklinksstr</td>
                <td align=\"center\">$acceptall</td>
                <td align=\"center\">$rejectall</td>
                <td align=\"center\">$deleteall</td>
            </tr>
    </table>
    ";
    echo "</form>";

    echo $OUTPUT->footer();
?>