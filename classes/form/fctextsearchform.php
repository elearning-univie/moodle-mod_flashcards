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

namespace mod_flashcards\form;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Oerhub
 *
 * @package    mod_flashcards
 * @copyright  2024 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fctextsearchform extends \moodleform {
    /** @var url */
    protected $actionurl;
    /** @var string */
    protected $fctextfilter;
    /**
     *
     * @param url $actionurl
     * @param string $fctextfilter
     * @param boolean $formeditable
     */
    public function __construct($actionurl, $fctextfilter, $formeditable = true) {
        global $DB, $OUTPUT;

        $this->actionurl = $actionurl;
        $this->fctextfilter = $fctextfilter;

        parent::__construct($actionurl, $fctextfilter, $formeditable);
    }

    /**
     *
     * {@inheritDoc}
     * @see moodleform::definition()
     */
    public function definition() {
        global $DB, $OUTPUT;
        // A reference to the form is stored in $this->form.
        // A common convention is to store it in a variable, such as `$mform`.
        $mform = $this->_form; // Don't forget the underscore!

        $templateinfo = [
            'actionurl2' => $this->actionurl,
            'fctxtfilter' => $this->fctextfilter,
        ];
        $mform->addElement('html', $OUTPUT->render_from_template('mod_flashcards/fctextsearchform', $templateinfo));
    }

    /**
     * Add all the hidden form fields used by question/question.php
     */
    protected function add_hidden_fields() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'actionurl2');
        $mform->setType('actionurl2', PARAM_URL);

        $mform->addElement('hidden', 'fctxtfilter');
        $mform->setType('fctxtfilter', PARAM_TEXT);

        $mform->addElement('hidden', 'questioncount');
        $mform->setType('questioncount', PARAM_INT);
    }
    /**
     *
     * {@inheritDoc}
     * @see moodleform::validation()
     */
    public function validation($data, $files) {
        return [];
    }
}
