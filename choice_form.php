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

include $CFG->libdir.'/formslib.php';


class Course_Choice_Form extends moodleform{
    var $from;
    var $page;
    var $max;
    function __construct($from, $page, $max){
        $this->from = $from;
        $this->page = $page;
        $this->max = $max;
        parent::__construct();
    }
    function definition(){
        global $CFG, $DB;

        $form = $this->_form;
        if (!empty($CFG->block_ext_signup_courses)){
            $available_courses = $DB->get_records_select('course', " visible = 1 AND id IN({$CFG->block_ext_signup_courses}) ", array(), 'fullname ASC', 'id, fullname, shortname, summary', $this->from, $this->page);
        } else {
            $available_courses = array();
        }

        $choosecoursestr = get_string('choosecourses', 'block_ext_signup');
        $form->addElement('hidden', 'from', $this->from);
        $form->addElement('hidden', 'id');
        $form->addElement('hidden', 'userid');

        $form->addElement('html', "<h2>$choosecoursestr</h2>");

        foreach($available_courses as $courseid => $course){
            $form->addElement('checkbox', 'choice_'.$courseid, $course->shortname, ' '.$course->fullname.'<p class="smalltext">'.$course->summary.'</p>');
        }

        $group = array();
        $group[] = & $form->createElement('submit', 'iscomplete', get_string('iscomplete', 'block_ext_signup'));
        if ($this->max > $this->page){
            $group[] = & $form->createElement('submit', 'store', get_string('store', 'block_ext_signup'));
        }
        $group[] = & $form->createElement('submit', 'reset', get_string('reset', 'block_ext_signup'));
        $group[] = & $form->createElement('submit', 'cancel', get_string('cancel', 'block_ext_signup'));
        $form->addGroup($group);
    }
    function validation(&$data, &$files){
    }    
}


?>