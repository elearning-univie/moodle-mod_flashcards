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

/**
 * Multiple choice question definition classes.
 *
 * @package    mod_flashcards
 * @copyright  2020 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/formslib.php');

/**
 * mod_flashcards_mod_form
 *
 * @package    mod_flashcards
 * @copyright  2020 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_flashcards_mod_form extends moodleform_mod {
    /**
     * definition()
     *
     */
    public function definition() {
        global $DB, $COURSE;

        $mform =& $this->_form;
        $courseid = $COURSE->id;
        $context = context_course::instance($courseid);

        $mform->addElement('text', 'name', get_string('flashcardname', 'flashcards'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        // Introduction.
        $this->standard_intro_elements();
        $this->add_hidden_fields();

        if (optional_param('update', 0, PARAM_INT)) {
            $mform->setDefault('newcategory', 0);
            $flashcards = $DB->get_record('flashcards', array('id' => $this->_instance));
            $catdefault = "$flashcards->categoryid,$context->id";
            $contexts[] = $context;

            if (optional_param('missingcategory', 0, PARAM_INT)) {
                $mform->addElement('questioncategory', 'category', get_string('category', 'question'), array('contexts' => $contexts));
            } else {
                $mform->addElement('hidden', 'category', get_string('category', 'question'), array('contexts' => $contexts));
            }

            $mform->setType('category', PARAM_RAW);
            $mform->setDefault('category', $catdefault);
        } else {
            $mform->setDefault('newcategory', 1);
        }

        $mform->addElement('select', 'addfcstudent', get_string('addfcstudent', 'flashcards'),
            array(1 => get_string('yes'), 0 => get_string('no')));
        $mform->addHelpButton('addfcstudent', 'addfcstudent', 'flashcards');
        $mform->setDefault('addfcstudent', 1);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Add all the hidden form fields
     */
    protected function add_hidden_fields() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'newcategory');
        $mform->setType('newcategory', PARAM_INT);

        $mform->addElement('hidden', 'inclsubcats');
        $mform->setType('inclsubcats', PARAM_INT);

        $mform->addElement('hidden', 'studentsubcat');
        $mform->setType('studentsubcat', PARAM_INT);
    }
}
