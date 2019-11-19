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
        $mform->hideIf('newcategoryname', 'neworexistingcategory', 'eq','existing');
        
        
        $mform->addElement('questioncategory', 'category', get_string('category', 'question'), array('contexts' => $context));

        $mform->addElement('checkbox', 'includesubcategories', get_string('includesubcategories', 'flashcards'));
        $mform->hideIf('includesubcategories', 'neworexistingcategory', 'eq', 'new');
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
