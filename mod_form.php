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

define('FLASHCARDS_EXISTING', get_string('existingcategory', 'flashcards'));
define('FLASHCARDS_NEW', get_string('newcategory', 'flashcards'));

class mod_flashcards_mod_form extends moodleform_mod {

    function definition() {

        $mform =& $this->_form;
        $courseid = required_param('course', PARAM_INT);
        $context = [];
        $context[] = context_course::instance($courseid);
        
        $mform->addElement('text', 'name', get_string('flashcardname', 'flashcards'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $options = array(
            1 => FLASHCARDS_NEW,
            0 => FLASHCARDS_EXISTING
        );

        // Introduction.
        $this->standard_intro_elements();


        $mform->addElement('select', 'newcategory', get_string('newexistingcategory', 'flashcards'), $options);
        $mform->setType('newcategory', PARAM_INT);
        $mform->setDefault('newcategory', 1);

        $mform->addElement('text', 'newcategoryname', get_string('newcategoryname','flashcards'),  array('size'=>'64'));
        $mform->setType('newcategoryname', PARAM_TEXT);
        $mform->hideIf('newcategoryname', 'newcategory', 'eq', 0);
        
        
        $mform->addElement('questioncategory', 'category', get_string('category', 'question'), array('contexts' => $context));

        $mform->addElement('checkbox', 'includesubcategories', get_string('includesubcategories', 'flashcards'));
        $mform->hideIf('includesubcategories', 'newcategory', 'eq', 1);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
