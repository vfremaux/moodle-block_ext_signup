<?php

function xmldb_block_ext_signup_install(){
	global $DB;
	
    if (!$DB->record_exists('user_info_field', array('shortname' => 'externalsignup'))){
        // create hidden fields externalsignup and cv and surrounding category
        $maxsort = $DB->get_field('user_info_category', 'MAX(sortorder', array());
        $infocat->name = get_string('extsignupuserinfocat', 'block_ext_signup');
        $infocat->sortorder = $maxsort + 1;
        $catid = $DB->insert_record('user_info_category', $infocat);
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
        $DB->insert_record('user_info_field', $infofield);

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
        $DB->insert_record('user_info_field', $infofield);
    }
}