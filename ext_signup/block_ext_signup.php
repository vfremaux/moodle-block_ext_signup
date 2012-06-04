<?PHP //$Id: block_ext_signup.php,v 1.2 2009-10-02 13:01:03 cvsprf Exp $

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

class block_ext_signup extends block_base {
    function init() {
        $this->title = get_string('title', 'block_ext_signup');
        $this->version = 2010060501;
        $this->cron = 1; 
    }

    function has_config() {
	    return true;
	  }

    function instance_allow_config() {
        return false;
    }

    function applicable_formats() {
        // Default case: the block can be used in all course types
        return array('all' => false,
                     'site' => true);
    }

    function get_content() {
        global $CFG;


        $this->content = new Object;
        
        $this->content->text = '';
        $this->content->footer = '';
        
        // logged people cannot see content 
        if (isloggedin()){
            $blockcontext = get_context_instance(CONTEXT_BLOCK, $this->instance->id);
            if (has_capability('block/ext_signup:process', $blockcontext)){
            	
            	$sql = "
            		SELECT 
            			COUNT(DISTINCT es.userid) 
            		FROM 
            			{$CFG->prefix}block_ext_signup es,
            			{$CFG->prefix}user u 
            		WHERE 
            			CONCAT('',u.id) = es.userid AND
            			accepted = ".EXT_PENDING."
				";            	
                if ($pendingcount = count_records_sql($sql)){
                    $this->content->text .= '<div class="ext_signup">';
                    $this->content->text .= '<a href="'.$CFG->wwwroot."/blocks/ext_signup/view.php?id={$this->instance->id}&view=pending\">".get_string('youhavetoprocess', 'block_ext_signup', $pendingcount).'</a>';
                    $this->content->text .= '</div>';
                } else {
                    $this->content->footer .= '<div class="ext_signup">';
                    $this->content->footer .= '<a href="'.$CFG->wwwroot."/blocks/ext_signup/view.php?id={$this->instance->id}&view=accepted\">".get_string('seerecords', 'block_ext_signup').'</a>';
                    $this->content->footer .= '</div>';
                }
            }
            return $this->content;
        }
        
        $this->content->text .= '<div class="ext_signup">';
        $this->content->text .= '<p>'.get_string('signuptxt', 'block_ext_signup').'</p>';
        $forcelang = ($CFG->block_ext_signup_submitternotifylang) ? "&lang={$CFG->block_ext_signup_submitternotifylang}" : '&lang='.current_language() ;
        $this->content->text .= '<a href="'.$CFG->wwwroot."/blocks/ext_signup/choice.php?id={$this->instance->id}&reset=1{$forcelang}\">".get_string('signup', 'block_ext_signup').'</a>';
        $this->content->text .= '</div>';

        $handlers = ext_signup_get_handlers();
        foreach($handlers as $handler){
            include_once $CFG->dirroot."/blocks/ext_signup/handlers/$handler.php";
            $handler = new $handler(null, $this->instance->id);
            $handler->block_content($this->content->text);
            $handler->block_footer($this->content->footer);
        }

        return $this->content;
    }
    
    function after_install(){
        if (!record_exists('user_info_field', 'shortname', 'externalsignup')){
            // create hidden fields externalsignup and cv and surrounding category
            $maxsort = get_field('user_info_category', 'MAX(sortorder', '', '');
            $infocat->name = get_string('extsignupuserinfocat', 'block_ext_signup');
            $infocat->sortorder = $maxsort + 1;
            $catid = insert_record('user_info_category', $infocat);
            
            // create fields             
            $infofield->shortname = 'externalsignup';
            $infofield->name = get_string('externalsignupmark', 'block_ext_signup');
            $infofield->categoryid = $catid;
            $infofield->sortorder = 1;
            $infofield->datatype = 'checkbox';
            $infofield->required = 0;
            $infofield->locked = 1;
            $infofield->visible = 0;
            $infofield->forceunique = 0;
            $infofield->signup = 0;
            insert_record('user_info_field', $infofield);

            // create fields             
            $infofield = new StdClass;
            $infofield->shortname = 'cv';
            $infofield->name = get_string('cvfield', 'block_ext_signup');
            $infofield->categoryid = $catid;
            $infofield->sortorder = 1;
            $infofield->datatype = 'text';
            $infofield->required = 0;
            $infofield->locked = 1;
            $infofield->visible = 0;
            $infofield->forceunique = 0;
            $infofield->signup = 0;
            insert_record('user_info_field', $infofield);
        }
    }
    
    function before_remove(){
        $category = get_record('user_info_cateogry', 'name', get_string('extsignupuserinfocat', 'block_ext_signup'));
        
        if ($fields = get_records_menu('user_info_field', 'categoryid', $category->id, 'id, shortname')){
            $fieldlist = implode("','", array_keys($fields));
        
            delete_records_select('user_info_data', " fieldid IN ('$fieldlist') " );
            delete_records_select('user_info_field', " id IN ('$fieldlist') " );
        }
        delete_records('user_info_cateogry', 'id', $category->id);
    }
 
    // the cron cleans up unterminated submissions at least 2 hours after they have been started 
    public function cron(){
    	global $CFG;
    	
    	// get list of unterminated users : they are not created in user list, have a very big ID (md5)
    	$fourhoursago = time() - (HOURSECS * 4);
    	if($unterminated = get_records_select_menu('block_ext_signup', " userid NOT IN (SELECT id FROM {$CFG->prefix}user) AND LENGTH(userid) > 10 AND timecreated < $fourhoursago ", 'id, id')){
    		$deletedcount = count($unterminated);
	    	$idlist = implode("','", array_keys($unterminated));
			delete_records_select('block_ext_signup', "id IN ('$idlist')" );    	
    		mtrace("\n\t\tcleaning $deletedcount unterminated queries");
		}
        return true;
    }
}
?>