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
class simplequestionform extends \moodleform {

    /**
     * Question object with options and answers
     * @var object
     */
    protected $question;
    /** @var string question category */
    protected $category;
    /** @var string question category context */
    protected $categorycontext;

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
     * @param string $action
     * @throws coding_exception
     * @throws dml_exception
     */
    public function __construct($submiturl, $question, $category, $formeditable = true, $action) {
        global $DB;

        $this->question = $question;
        $this->action = $action;

        $record = $DB->get_record('question_categories',
                array('id' => $question->category), 'contextid');
        $this->context = \context::instance_by_id($record->contextid);

        $this->editoroptions = array('subdirs' => 1, 'maxfiles' => EDITOR_UNLIMITED_FILES,
                'context' => $this->context);
        $this->fileoptions = array('subdirs' => 1, 'maxfiles' => -1, 'maxbytes' => -1);

        $this->category = $category;
        $this->categorycontext = \context::instance_by_id($category->contextid);

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

        if (!$this->question->formoptions->canaddwithcat) {
            $mform->addElement('hidden', 'category', get_string('category', 'question'),
                    array('size' => 512));
        } else {
            $mform->addElement('questioncategory', 'category', get_string('category', 'question'),
                    array('size' => 512, 'contexts' => array($this->categorycontext)));
        }

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

        if ($this->action == 'edit') {
            $mform->addElement('header', 'radioedithdr',
                get_string('changeextenttitle', 'mod_flashcards'), '');
            $mform->setExpanded('radioedithdr', 1);
            $radioarray = array();
            $radioarray[] = $mform->createElement('radio', 'changeextent', '', get_string('minorchange', 'mod_flashcards'), 0);
            $radioarray[] = $mform->createElement('radio', 'changeextent', '', get_string('majorchange', 'mod_flashcards'), 1);
            $mform->addGroup($radioarray, 'radioar', '', array(' '), false);
            $mform->addRule('radioar', null, 'required', null, 'client');
            $mform->setDefault('changeextent', 0);
        }

        $this->add_hidden_fields();
        $this->add_action_buttons(true, get_string('savechanges'));
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
        $errors = parent::validation($fromform, $files);

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

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('hidden', 'qtype');
        $mform->setType('qtype', PARAM_ALPHA);
    }

    /**
     * Transforms data from the form into the question db form
     * @param array|\stdClass $question
     * @throws \coding_exception
     */
    public function set_data($question) {
        \question_bank::get_qtype($question->qtype)->set_default_options($question);

        // Prepare question text.
        $draftid = file_get_submitted_draft_itemid('questiontext');

        if (!empty($question->questiontext)) {
            $questiontext = $question->questiontext;
        } else {
            $questiontext = $this->_form->getElement('questiontext')->getValue();
            $questiontext = $questiontext['text'];
        }
        $questiontext = file_prepare_draft_area($draftid, $this->context->id,
                'question', 'questiontext', empty($question->id) ? null : (int) $question->id,
                $this->fileoptions, $questiontext);

        $question->questiontext = array();
        $question->questiontext['text'] = $questiontext;
        $question->questiontext['format'] = empty($question->questiontextformat) ?
                editors_get_preferred_format() : $question->questiontextformat;
        $question->questiontext['itemid'] = $draftid;

        $question = $this->data_preprocessing_answers($question, true);
        parent::set_data($question);
    }

    /**
     * Perform the necessary preprocessing for the fields added by
     * {@see add_per_answer_fields()}.
     * @param object $question the data being passed to the form.
     * @param boolean $withanswerfiles
     * @return object $question the modified data.
     */
    protected function data_preprocessing_answers($question, $withanswerfiles = false) {
        if (empty($question->options->answers)) {
            return $question;
        }

        $key = 0;
        foreach ($question->options->answers as $answer) {
            if ($withanswerfiles) {
                // Prepare the feedback editor to display files in draft area.
                $draftitemid = file_get_submitted_draft_itemid('answer['.$key.']');
                $question->answer['text'] = file_prepare_draft_area(
                        $draftitemid,          // Draftid
                        $this->context->id,    // context
                        'question',            // component
                        'answer',              // filarea
                        !empty($answer->id) ? (int) $answer->id : null, // itemid
                        $this->fileoptions,    // options
                        $answer->answer        // text.
                );
                $question->answer['itemid'] = $draftitemid;
                $question->answer['format'] = $answer->answerformat;
            } else {
                $question->answer[$key] = $answer->answer;
            }

            $key++;
        }

        return $question;
    }
}