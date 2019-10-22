<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/flashcards/lib.php');

class mod_flashcards_mod_form extends moodleform_mod {
    function definition() {
        $mform =& $this->_form;

        $mform->addElement('text', 'name', get_string('flashcardname', 'flashcards'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
