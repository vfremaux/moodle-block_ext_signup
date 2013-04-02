<?php

function xmldb_block_ext_signup_uninstall(){
	global $DB;
		
    $category = $DB->get_record('user_info_category', array('name' => get_string('extsignupuserinfocat', 'block_ext_signup')));
    if ($fields = $DB->get_records_menu('user_info_field', array('categoryid' => $category->id), 'id, shortname')){
        $fieldlist = implode("','", array_keys($fields));
        //Todo encode better IN syntax
        $DB->delete_records_select('user_info_data', " fieldid IN ('$fieldlist') " , array());
        $DB->delete_records_select('user_info_field', " id IN ('$fieldlist') " , array() );
    }
    $DB->delete_records('user_info_cateogry', array('id' => $category->id));
}
