<?php  // $Id: signup_form.php,v 1.35.2.6 2008/07/23 05:21:21 nicolasconnault Exp $

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
 * implements a form for signing up.
 */

if (!defined('MOODLE_INTERNAL')) die('You cannot accesss this script directly');

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');

class login_signup_form extends moodleform {

    function definition() {
        global $USER, $CFG, $SESSION;

        $mform =& $this->_form;

        $mform->addElement('header', '', get_string('createuserandpass'), '');

        $mform->addElement('text', 'username', get_string('username'), 'maxlength="100" size="12"');
        $mform->setType('username', PARAM_NOTAGS);
        $mform->addRule('username', get_string('missingusername'), 'required', null, 'server');
        $mform->setHelpButton('username', array('username', get_string('username'), 'block_ext_signup'));

        if(!empty($CFG->passwordpolicy)){
            $passwordpolicy = print_password_policy();
            $mform->addElement('html', $passwordpolicy);
        }

        $mform->addElement('passwordunmask', 'password', get_string('password'), 'maxlength="32" size="12"');
        $mform->setType('password', PARAM_RAW);
        $mform->addRule('password', get_string('missingpassword'), 'required', null, 'server');

        $mform->addElement('header', '', get_string('supplyinfo'),'');

        $mform->addElement('text', 'email', get_string('email'), 'maxlength="100" size="25"');
        $mform->setType('email', PARAM_NOTAGS);
        $mform->addRule('email', get_string('missingemail'), 'required', null, 'server');

        $mform->addElement('text', 'email2', get_string('emailagain'), 'maxlength="100" size="25"');
        $mform->setType('email2', PARAM_NOTAGS);
        $mform->addRule('email2', get_string('missingemail'), 'required', null, 'server');

        $nameordercheck = new object();
        $nameordercheck->firstname = 'a';
        $nameordercheck->lastname  = 'b';
        if (fullname($nameordercheck) == 'b a' ) {  // See MDL-4325
            $mform->addElement('text', 'lastname',  get_string('lastname'),  'maxlength="100" size="30"');
            $mform->addElement('text', 'firstname', get_string('firstname'), 'maxlength="100" size="30"');
        } else {
            $mform->addElement('text', 'firstname', get_string('firstname'), 'maxlength="100" size="30"');
            $mform->addElement('text', 'lastname',  get_string('lastname'),  'maxlength="100" size="30"');
        }

        $mform->setType('firstname', PARAM_TEXT);
        $mform->addRule('firstname', get_string('missingfirstname'), 'required', null, 'server');

        $mform->setType('lastname', PARAM_TEXT);
        $mform->addRule('lastname', get_string('missinglastname'), 'required', null, 'server');

        $mform->addElement('text', 'city', get_string('city'), 'maxlength="20" size="20"');
        $mform->setType('city', PARAM_TEXT);
        $mform->addRule('city', get_string('missingcity'), 'required', null, 'server');

        $country = get_list_of_countries();
        $default_country[''] = get_string('selectacountry');
        $country = array_merge($default_country, $country);
        $mform->addElement('select', 'country', get_string('country'), $country);
        $mform->addRule('country', get_string('missingcountry'), 'required', null, 'server');

        if( !empty($CFG->country) ){
            $mform->setDefault('country', $CFG->country);
        } else {
            $mform->setDefault('country', '');
        }

        $mform->addElement('textarea', 'description', get_string('descriptionform', 'block_ext_signup'), 'cols="60" rows="15"');
        $mform->addRule('description', get_string('missingdescription'), 'required', null, 'server');

        $this->set_upload_manager(new upload_manager());

        $mform->addElement('file', 'imagefile', get_string('imagefile', 'block_ext_signup'));
        $mform->addRule('imagefile', get_string('missingimage'), 'required', null, 'server');

        $mform->addElement('text', 'address', get_string('address'), 'maxlength="255" size="50"');
        $mform->addElement('text', 'phone1', get_string('phone'), 'maxlength="15" size="15"');
        $mform->addElement('text', 'phone2', get_string('mobile', 'block_ext_signup'), 'maxlength="15" size="15"');
        $mform->addElement('text', 'institution', get_string('institutionform', 'block_ext_signup'), 'maxlength="255" size="50"');
        $mform->addRule('institution', get_string('missing', 'block_ext_sigup'), 'required', null, 'server');
        $mform->addElement('text', 'department', get_string('departmentform', 'block_ext_signup'), 'maxlength="255" size="50"');
        $mform->addRule('department', get_string('missing', 'block_ext_sigup'), 'required', null, 'server');

        $mform->addElement('file', 'cv', get_string('yourcv', 'block_ext_signup'));

        $SESSION->extsignup->captcha->length = 3;
        $captcha_generator = $CFG->wwwroot.'/blocks/ext_signup/print_captcha.php';
        $captchaaltstr = get_string('captchagenerated', 'block_ext_signup');
        $captcha_html = "<img alt=\"{$captchaaltstr}\" src=\"{$captcha_generator}\" align=\"middle\" />";
            
        $mform->addElement('static', 'captcha_elm', get_string('captcha', 'block_ext_signup'),$captcha_html);
        $captchaoptions['maxlength'] = $SESSION->extsignup->captcha->length;
        $captchaoptions['size'] = $SESSION->extsignup->captcha->length;
        $mform->addElement('text', 'captcha', '', $captchaoptions);

        profile_signup_fields($mform);

        $mform->addElement('hidden', 'userid');
        $mform->addElement('hidden', 'id');

        if (!empty($CFG->sitepolicy)) {
            $mform->addElement('header', '', get_string('policyagreement'), '');
            $mform->addElement('static', 'policylink', '', '<a href="'.$CFG->sitepolicy.'" onclick="this.target=\'_blank\'">'.get_String('policyagreementclick').'</a>');
            $mform->addElement('checkbox', 'policyagreed', get_string('policyaccept'));
            $mform->addRule('policyagreed', get_string('policyagree'), 'required', null, 'server');
        }

        // buttons
        $group = array();
        $group[] = & $mform->createElement('submit', 'createaccount', get_string('createaccount'));
        $group[] = & $mform->createElement('cancel', 'cancelbtn', get_string('cancel', 'block_ext_signup'));
        $mform->addGroup($group);
    }

    function definition_after_data(){
        $mform =& $this->_form;

        $mform->applyFilter('username', 'moodle_strtolower');
        $mform->applyFilter('username', 'trim');
    }

    function validation($data, $files) {
        global $CFG, $SESSION;

        $errors = parent::validation($data, $files);

        $authplugin = get_auth_plugin('ext');

        if (record_exists('user', 'username', $data['username'], 'mnethostid', $CFG->mnet_localhost_id)) {
            $errors['username'] = get_string('usernameexists');
        } else {
            if (empty($CFG->extendedusernamechars)) {
                $string = eregi_replace("[^(-\.[:alnum:])]", '', $data['username']);
                if (strcmp($data['username'], $string)) {
                    $errors['username'] = get_string('alphanumerical');
                }
            }
        }

        //check if user exists in external db
        //TODO: maybe we should check all enabled plugins instead
        if ($authplugin->user_exists($data['username'])) {
            $errors['username'] = get_string('usernameexists');
        }

        if (! validate_email($data['email'])) {
            $errors['email'] = get_string('invalidemail');

        } else if (record_exists('user', 'email', $data['email'])) {
            $errors['email'] = get_string('emailexists')." <a href=\"{$CFG->wwwroot}/login/forgot_password.php\">".get_string('newpassword').'?</a>';
        }
        if (empty($data['email2'])) {
            $errors['email2'] = get_string('missingemail');

        } else if ($data['email2'] != $data['email']) {
            $errors['email2'] = get_string('invalidemail');
        }
        if (!isset($errors['email'])) {
            if ($err = email_is_not_allowed($data['email'])) {
                $errors['email'] = $err;
            }
        }

        $errmsg = '';
        if (!check_password_policy($data['password'], $errmsg)) {
            $errors['password'] = $errmsg;
        }

        if (empty($data['captcha']) || $data['captcha'] != $SESSION->extsignup->captcha->checkchar){
            $errmessage = get_string('captchaerror', 'block_ext_signup');
            $data['captcha'] = '';
            $errors['captcha'] = $errmsg;
        }

        return $errors;
    }
}

?>
