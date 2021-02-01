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
 * Form for creating flashcards with less information to fill out
 *
 * @package    mod_flashcards
 * @copyright  2020 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_flashcards\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Flashcard form definition with less information.
 *
 * @copyright  2020 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class simplequestionform extends moodleform {

    /**
     * Question object with options and answers
     * @var object
     */
    protected $question;
    /** @var string question category */
    protected $category;

    /** @var object current context */
    public $context;
    /** @var array html editor options */
    public $editoroptions;
    /** @var array options to preapre draft area */
    public $fileoptions;
    /** @var object instance of question type */
    public $instance;

    /**
     * simplequestionform constructor.
     *
     * @param string $submiturl
     * @param object $question
     * @param string $category
     * @param bool $formeditable
     * @throws coding_exception
     * @throws dml_exception
     */
    public function __construct($submiturl, $question, $category, $formeditable = true) {
        global $DB;

        $this->question = $question;

        $record = $DB->get_record('question_categories',
                array('id' => $question->category), 'contextid');
        $this->context = context::instance_by_id($record->contextid);

        $this->editoroptions = array('subdirs' => 1, 'maxfiles' => EDITOR_UNLIMITED_FILES,
                'context' => $this->context);
        $this->fileoptions = array('subdirs' => 1, 'maxfiles' => -1, 'maxbytes' => -1);

        $this->category = $category;

        parent::__construct($submiturl, null, 'post', '', null, $formeditable);
    }

    /**
     * form definition
     *
     * @throws coding_exception
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'generalheader', get_string("general", 'form'));

        $mform->addElement('hidden', 'category', get_string('category', 'question'),
                array('size' => 512));
        $mform->setType('category', PARAM_RAW);

        $mform->addElement('text', 'name', get_string('questionname', 'question'),
                array('size' => 50, 'maxlength' => 255));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('editor', 'questiontext', get_string('questiontext', 'question'),
                array('rows' => 15), $this->editoroptions);
        $mform->setType('questiontext', PARAM_RAW);
        $mform->addRule('questiontext', null, 'required', null, 'client');

        $mform->addElement('header', 'answerhdr', get_string('answers', 'question'), '');
        $mform->setExpanded('answerhdr', 1);

        $mform->addElement('editor', 'answer',
                get_string('correctanswer', 'qtype_flashcard'), array('rows' => 15), $this->editoroptions);
        $mform->setType('answer', PARAM_RAW);
        $mform->addRule('answer', null, 'required', null, 'client');

        $this->add_hidden_fields();
        $this->add_action_buttons(true, get_string('savechanges'));

        if ((!empty($this->question->id)) && (!($this->question->formoptions->canedit ||
                        $this->question->formoptions->cansaveasnew))) {
            //$mform->hardFreezeAllVisibleExcept(array('categorymoveto', 'buttonar', 'currentgrp'));
        }
    }

    /**
     * form validation
     *
     * @param array $fromform
     * @param array $files
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
    public function validation($fromform, $files) {
        global $DB;

        $errors = parent::validation($fromform, $files);
        if (empty($fromform['makecopy']) && isset($this->question->id)
                && ($this->question->formoptions->canedit ||
                        $this->question->formoptions->cansaveasnew)
                && empty($fromform['usecurrentcat']) && !$this->question->formoptions->canmove) {
            $errors['currentgrp'] = get_string('nopermissionmove', 'question');
        }
        // Category.
        if (empty($fromform['category'])) {
            // User has provided an invalid category.
            $errors['category'] = get_string('required');
        }
        // Default mark.
        if (array_key_exists('defaultmark', $fromform) && $fromform['defaultmark'] < 0) {
            $errors['defaultmark'] = get_string('defaultmarkmustbepositive', 'question');
        }
        // Can only have one idnumber per category.
        if (strpos($fromform['category'], ',') !== false) {
            list($category, $categorycontextid) = explode(',', $fromform['category']);
        } else {
            $category = $fromform['category'];
        }
        if (isset($fromform['idnumber']) && ((string) $fromform['idnumber'] !== '')) {
            if (empty($fromform['usecurrentcat']) && !empty($fromform['categorymoveto'])) {
                $categoryinfo = $fromform['categorymoveto'];
            } else {
                $categoryinfo = $fromform['category'];
            }
            list($categoryid, $notused) = explode(',', $categoryinfo);
            $conditions = 'category = ? AND idnumber = ?';
            $params = [$categoryid, $fromform['idnumber']];
            if (!empty($this->question->id)) {
                $conditions .= ' AND id <> ?';
                $params[] = $this->question->id;
            }
            if ($DB->record_exists_select('question', $conditions, $params)) {
                $errors['idnumber'] = get_string('idnumbertaken', 'error');
            }
        }

        return $errors;
    }

    /**
     * Add all the hidden form fields used by question/question.php
     */
    protected function add_hidden_fields() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'defaultmark');
        $mform->setDefault('defaultmark', 1);
        $mform->setType('defaultmark', PARAM_FLOAT);

        $mform->addElement('hidden', 'generalfeedback');
        $mform->setType('generalfeedback', PARAM_RAW);

        $mform->addElement('hidden', 'idnumber');
        $mform->setType('idnumber', PARAM_RAW);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'inpopup');
        $mform->setType('inpopup', PARAM_INT);

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'returnurl');
        $mform->setType('returnurl', PARAM_LOCALURL);

        $mform->addElement('hidden', 'scrollpos');
        $mform->setType('scrollpos', PARAM_INT);

        $mform->addElement('hidden', 'appendqnumstring');
        $mform->setType('appendqnumstring', PARAM_ALPHA);

        $mform->addElement('hidden', 'qtype');
        $mform->setType('qtype', PARAM_ALPHA);

        $mform->addElement('hidden', 'makecopy');
        $mform->setType('makecopy', PARAM_INT);
    }
}