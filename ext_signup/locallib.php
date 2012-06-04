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
 * local function library
 */

define('EXT_PENDING', -1000);
define('EXT_ACCEPTED', 1);
define('EXT_REJECTED', -1);

function ext_signup_courses_print_pager($offset, $page, $maxobjects, $url){
    global $CFG;
    
    if ($maxobjects <= $page) return;
    
    $current = ceil(($offset + 1) / $page);
    $pages = array();
    $off = 0;    

    for ($p = 1 ; $p <= ceil($maxobjects / $page) ; $p++){
        if ($p == $current){
            $pages[] = $p;
        } else {
            $pages[] = "<a href=\"{$url}&from={$off}\">{$p}</a>";
        }
        $off = $off + $page;    
    }    
    
    echo implode(' - ', $pages);
}

function get_cv_record($userid){
    global $CFG;
    
    $sql = "
        SELECT
            data
        FROM
            {$CFG->prefix}user_info_data ud,
            {$CFG->prefix}user_info_field uf
        WHERE
            uf.id = ud.fieldid AND
            uf.shortname = 'cv' AND
            ud.userid = $userid
    ";
    $cv = get_record_sql($sql);
    if (!empty($cv->data)){
        return "user/0/{$userid}/".$cv->data;
    }
    return '';
}

/**
* get available processing handlers
*/
function ext_signup_get_handlers(){
    global $CFG;
    
    $handlers = glob($CFG->dirroot.'/blocks/ext_signup/handlers/*.php');
    
    $handlers = preg_replace("/^.*\\//", '', $handlers);
    $handlers = preg_replace("/\\.php$/", '', $handlers);
    
    return $handlers;
}

/**
* prints a generic pager using the from param to control offset
*/
function ext_signup_print_pager($offset, $page, $maxobjects, $url){
    global $CFG;
    
    if ($maxobjects <= $page) return;
    
    $current = ceil(($offset + 1) / $page);
    $pages = array();
    $off = 0;    

    for ($p = 1 ; $p <= ceil($maxobjects / $page) ; $p++){
        if ($p == $current){
            $pages[] = $p;
        } else {
            $pages[] = "<a href=\"{$url}&from={$off}\">{$p}</a>";
        }
        $off = $off + $page;    
    }    
    
    echo implode(' - ', $pages);
}

function ext_signup_print_sorted_link($label, $sorting, $sortby, $dir, $url, $return = false){
    global $CFG;
    
    if ($sortby == $sorting){
        if ($dir == 'DESC'){
            $str = "$label <a href=\"{$url}&amp;sortby={$sorting}&amp;dir=ASC\"><img src=\"{$CFG->pixpath}/t/down.gif\" /></a>";
        } else {
            $str = "$label <a href=\"{$url}&amp;sortby={$sorting}&amp;dir=DESC\"><img src=\"{$CFG->pixpath}/t/up.gif\" /></a>";
        }
    } else {
        $str = "<a href=\"{$url}&amp;sortby={$sorting}\">$label</a>";
    }
    
    if ($return) return $str;
    echo $str;
}
?>