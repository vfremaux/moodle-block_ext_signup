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
    require_once($CFG->dirroot.'/blocks/ext_signup/locallib.php');

	require_capability('moodle/site:doanything', context_system::instance());

    $PAGE->set_title($SITE->fullname);
    $PAGE->set_heading($SITE->fullname .' : '.get_string('clear', 'block_ext_signup'));
    $PAGE->set_focuscontrol('');
    $PAGE->set_cacheable(true);
    $PAGE->set_button('' /*);
    $PAGE->set_headingmenu(user_login_string($SITE).$langmenu*/);
    echo $OUTPUT->header();
    $DB->delete_records('block_ext_signup', null);
    echo $OUTPUT->continue_button($CFG->wwwroot);

    echo $OUTPUT->box(get_string('cleared','block_ext_signup'));

	echo $OUTPUT->footer();    
    ?>
