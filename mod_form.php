<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/flashcards/lib.php');

class mod_flashcards_mod_form extends moodleform_mod {
    
    function definition() {
        
        $mform =& $this->_form;

        $contexts   = $this->_customdata['contexts'];
       // $currentcat   = $this->_customdata['currentcat'];

        $mform->addElement('text', 'name', get_string('flashcardname', 'flashcards'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $options = array(
            FLASHCARDS_EXISTING => get_string('existingcategory', 'flashcards'),
            FLASHCARDS_NEW => get_string('newcategory', 'flashcards')
        );
        $mform->addElement('select', 'neworexistingcategory', get_string('newexistingcategory', 'flashcards'), $options);
        print_object($contexts);
       // $mform->addElement('questioncategory', 'category', get_string('category', 'question'), array('contexts' => $contexts));
        
        //$mform->addElement('questioncategory', 'parent', get_string('parentcategory', 'question'),
         //  array('context' => $context, 'top' => true, 'currentcat' => $currentcat, 'nochildrenof' => $currentcat));
        
        $mform->addElement('text', 'testtext', 'textitext', array('size'=>'64'));
        $mform->hideIf('testtext', 'neworexistingcategory', 'eq', FLASHCARDS_EXISTING);
        
        $mform->addElement('checkbox', 'includesubcategories', 'include subcategories');
        
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
