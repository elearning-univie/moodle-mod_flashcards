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
 * Calls the question engine to render a question
 *
 * @package   mod_flashcards
 * @copyright 2020 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once('locallib.php');

/**
 * Class renderer
 * @copyright 2020 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer {
    /**
     * @var int
     */
    private $userid;
    /**
     * @var int
     */
    private $box;
    /**
     * @var int
     */
    private $flashcardsid;
    /**
     * @var int
     */
    private $courseid;

    /**
     * renderer constructor.
     *
     * @param int $userid
     * @param int $box
     * @param int $flashcardsid
     * @param int $courseid
     */
    public function __construct($userid, $box, $flashcardsid, $courseid) {
        $this->userid = $userid;
        $this->box = $box;
        $this->courseid = $courseid;
        $this->flashcardsid = $flashcardsid;
    }

    /**
     * Get the next question for the given student and box
     * @return mixed
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function get_question_for_student_course_box() {
        global $DB;
        $i = 0;

        // TODO active abfragen
        $sql = "SELECT min(questionid) AS questionid FROM {flashcards_q_stud_rel} q " .
                "WHERE q.studentid = :userid AND q.currentbox = :box AND q.flashcardsid = :flashcardsid AND q.lastanswered = " .
                "(SELECT min(lastanswered) FROM {flashcards_q_stud_rel} subq " .
                "WHERE subq.studentid = q.studentid AND subq.currentbox = q.currentbox AND subq.active = q.active AND subq.flashcardsid = q.flashcardsid)";

        $records = $DB->get_recordset_sql($sql, ['userid' => $this->userid, 'box' => $this->box, 'flashcardsid' => $this->flashcardsid]);

        foreach ($records as $record) {
            $questionid = $record->questionid;
            $i++;
        }

        if ($i != 1) {
            print_error('noquestion', 'mod_flashcards');
        };

        return $questionid;
    }

    /**
     * renders the question
     * @return string|null
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function render_question() {
        global $PAGE;

        $cm = get_coursemodule_from_instance("flashcards", $this->flashcardsid);
        $context = context_module::instance($cm->id);
        $PAGE->set_context($context);

        $PAGE->requires->js_call_amd('mod_flashcards/studentcontroller', 'init');
        $jsmodule = array(
                'name' => 'core_question_engine',
                'fullpath' => '/question/qengine.js'
        );
        $PAGE->requires->js_init_call('M.core_question_engine.init_form',
                array('#mod-flashcards-responseform'), false, $jsmodule);

        $quba = question_engine::make_questions_usage_by_activity('flashcards', $context);
        $quba->set_preferred_behaviour('immediatefeedback');
        $questionid = $this->get_question_for_student_course_box();

        if ($questionid == null) {
            return null;
        }

        $question = question_bank::load_question($questionid);
        $quba->add_question($question, 1);
        $quba->start_all_questions();
        question_engine::save_questions_usage_by_activity($quba);
        $qaid = $quba->get_question_attempt(1)->get_database_id();

        $result =
                '<form id="mod-flashcards-responseform" method="post" action="javascript:;" onsubmit="$.mod_flashcards_call_update(' .
                $this->courseid . ',' . $questionid . ',' . $qaid . ',' . $cm->id . ')" enctype="multipart/form-data" accept-charset="utf-8">';
        $result .= "\n<div>\n";

        $options = new question_display_options();
        $options->marks = question_display_options::MAX_ONLY;
        $options->markdp = 2;
        $options->feedback = question_display_options::VISIBLE;
        $options->generalfeedback = question_display_options::HIDDEN;

        $result .= $quba->render_question(1, $options);

        return $result;
    }
}