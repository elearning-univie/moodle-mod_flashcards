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
require_once($CFG->libdir.'/formslib.php');

define('FLASHCARDS_EXISTING', get_string('existingcategory', 'flashcards'));
define('FLASHCARDS_NEW', get_string('newcategory', 'flashcards'));

/**
 * mod_flashcards_mod_form
 *
 * mod_flashcards_mod_form...
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
        global $DB, $PAGE, $COURSE;

        $mform =& $this->_form;
        $courseid = $COURSE->id;
        $context = context_course::instance($courseid);

        $mform->addElement('text', 'name', get_string('flashcardname', 'flashcards'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        // Introduction.
        $this->standard_intro_elements();

        $options = array(
            1 => FLASHCARDS_NEW,
            0 => FLASHCARDS_EXISTING
        );

        $mform->addElement('select', 'newcategory', get_string('newexistingcategory', 'flashcards'), $options);
        $mform->setType('newcategory', PARAM_INT);
        $fcstring = get_string('modulename', 'flashcards');
        $mform->addElement('text', 'newcategoryname', get_string('newcategoryname', 'flashcards'), array('size' => '64'));
        $mform->setDefault('newcategoryname', get_string('modulenameplural', 'flashcards'));
        $mform->setType('newcategoryname', PARAM_TEXT);
        $mform->hideIf('newcategoryname', 'newcategory', 'eq', 0);

        $contexts = [];
        $contexts[] = $context;
        $mform->addElement('questioncategory', 'category', get_string('category', 'question'), array('contexts' => $contexts));

        if (optional_param('update', 0, PARAM_INT)) {
            $mform->setDefault('newcategory', 0);
            $flashcards = $DB->get_record('flashcards', array('id' => $this->_instance));
            $catdefault = "$flashcards->categoryid,$context->id";
            $mform->setDefault('category', $catdefault);
        }

        $mform->addElement('advcheckbox', 'inclsubcats', get_string('includesubcategories', 'flashcards'));
        $mform->hideIf('inclsubcats', 'newcategory', 'eq', 1);

        $mform->addElement('select', 'addfcstudent', get_string('addfcstudent', 'flashcards'), array(1 => get_string('yes'), 0 => get_string('no')));
        $mform->addHelpButton('addfcstudent', 'addfcstudent', 'flashcards');
        $mform->hideIf('addfcstudent', 'newcategory', 'eq', 1);
        $mform->disabledIf('inclsubcats', 'addfcstudent', 'eq', 1);
        $PAGE->requires->js_call_amd('mod_flashcards/fcstudentsubcat', 'init');

        $mform->addElement('text', 'studentsubcatname', get_string('studentsubcatname', 'flashcards'));
        $mform->setType('studentsubcatname', PARAM_TEXT);
        $mform->setDefault('studentsubcatname', 'von Studierenden erstellt');
        $mform->hideIf('studentsubcatname', 'addfcstudent', 'eq', 0);
        $mform->hideIf('studentsubcatname', 'inclsubcats', 'eq', 0);
        
        $mform->addElement('text', 'studentsubcat', '');
        $mform->setType('studentsubcat', PARAM_INT);
        $mform->hideIf('studentsubcat', 'addfcstudent', 'lt',3);

        if (optional_param('update', 0, PARAM_INT)) {
            if (!$flashcards->studentsubcat && $flashcards->addfcstudent == 1) {
                $subcatname =  $DB->get_field('question_categories', 'name', ['id' => $flashcards->studentsubcat]);
                $mform->setDefault('studentsubcatname', $subcatname);
            }
        }

        $mform->disabledIf('studentsubcatname', 'addfcstudent', 'lt',3);



        $this->standard_coursemodule_elements();
        $this->add_action_buttons();

        if (empty($this->_instance)) {
            $PAGE->requires->js_call_amd('mod_flashcards/autofillcatname', 'init', ['fcstring' => $fcstring]);
        }
    }
}
