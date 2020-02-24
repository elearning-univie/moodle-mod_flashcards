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
 * Interface implementation of the external Webservices
 *
 * @package    mod_flashcards
 * @copyright  2020 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/mod/flashcards/renderer.php');
require_once($CFG->dirroot . '/mod/flashcards/locallib.php');

/**
 * Class mod_flashcards_external
 *
 * @copyright  2020 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_flashcards_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function update_progress_parameters() {
        return new external_function_parameters(
                array(
                        'fid' => new external_value(PARAM_INT, 'id of flashcard activity'),
                        'questionid' => new external_value(PARAM_INT, 'question id'),
                        'qanswervalue' => new external_value(PARAM_INT, 'int value of the answer')
                )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function load_next_question_parameters() {
        return new external_function_parameters(
            array(
                'fid' => new external_value(PARAM_INT, 'id of flashcard activity'),
                'boxid' => new external_value(PARAM_INT, 'id of current box')
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function load_questions_parameters() {
        return new external_function_parameters(
                array(
                        'flashcardsid' => new external_value(PARAM_INT, 'id of activity'),
                        'qids' => new external_multiple_structure(
                                new external_value(PARAM_INT, 'id array of questions')
                        ),
                )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function start_learn_now_parameters() {
        return new external_function_parameters(
            array(
                'flashcardsid' => new external_value(PARAM_INT, 'id of activity'),
                'qcount' => new external_value(PARAM_INT, 'number of questions to learn')
            )
        );
    }

    /**
     * Moves the question into the next box if the answer was correct, otherwise to box 1
     *
     * @param int $courseid
     * @param int $fid
     * @param int $questionid
     * @param int $qanswervalue
     * @return string|null
     * @throws dml_exception
     */
    public static function update_progress($fid, $questionid, $qanswervalue) {
        global $DB, $USER;

        $record = $DB->get_record('flashcards_q_stud_rel',
                ['studentid' => $USER->id, 'flashcardsid' => $fid, 'questionid' => $questionid], $fields = '*',
                $strictness = MUST_EXIST);

        $currentbox = $record->currentbox;

        $record->lastanswered = time();
        $record->tries++;

        if ($qanswervalue == 1) {
            if ($currentbox < 5) {
                $record->currentbox++;
            }
        } else {
            $record->currentbox = 1;
            $record->wronganswercount++;
        }

        $DB->update_record('flashcards_q_stud_rel', $record);
        return 1;
    }

    public static function load_next_question($fid, $boxid) {
        global $USER;

        $qid = mod_flashcards_get_next_question($fid, $boxid);
        $questionrenderer = new renderer($USER->id, $boxid, $fid, $qid);
        return $questionrenderer->render_question();
    }

    /**
     * Moves all selected questions from box 0 to box 1 for the activity
     *
     * @param int $flashcardsid
     * @param array $qids
     * @return int
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function load_questions($flashcardsid, $qids) {
        global $DB, $USER;

        $record = $DB->get_record('flashcards', ['id' => $flashcardsid]);
        $categories = question_categorylist($record->categoryid);
        list($inids, $questionids) = $DB->get_in_or_equal($qids, SQL_PARAMS_NAMED);
        list($inids2, $categorieids) = $DB->get_in_or_equal($categories, SQL_PARAMS_NAMED);

        $sql = "SELECT id
                  FROM {question}
                 WHERE id $inids
                   AND category $inids2
                   AND id NOT IN (SELECT questionid
                                    FROM {flashcards_q_stud_rel}
                                   WHERE studentid = :userid
                                     AND flashcardsid = :fid)";

        $questionids = $DB->get_fieldset_sql($sql, $questionids + $categorieids + ['userid' => $USER->id, 'fid' => $flashcardsid]);
        $questionarray = [];

        foreach ($questionids as $question) {
            $questionentry =
                    array('flashcardsid' => $record->id, 'questionid' => $question, 'studentid' => $USER->id, 'active' => 1,
                            'currentbox' => 1, 'lastanswered' => 0, 'tries' => 0, 'wronganswercount' => 0);

            $questionarray[] = $questionentry;
        }
        $DB->insert_records('flashcards_q_stud_rel', $questionarray);
    }

    /**
     * Moves all selected questions from box 0 to box 1 for the activity
     *
     * @param int $flashcardsid
     * @param int $qcount
     * @return url
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function start_learn_now($flashcardsid, $qcount) {
        global $DB, $USER, $_SESSION;
        list($context, $course, $cm) = mod_flashcards_check_student_rights($flashcardsid);

        $sql = "SELECT questionid
                  FROM {flashcards_q_stud_rel}
                 WHERE studentid = :userid
                   AND flashcardsid = :fid
              ORDER BY currentbox, lastanswered";

        $questionids = $DB->get_fieldset_sql($sql, ['userid' => $USER->id, 'fid' => $flashcardsid]);

        $_SESSION[FLASHCARDS_LN . $flashcardsid] = array_slice($questionids, 0, $qcount);

        $newmoodleurl = new moodle_url('/mod/flashcards/studentquiz.php', ['id' => $cm->id, 'box' => '-1']);

        return html_entity_decode($newmoodleurl->__toString());
    }

    /**
     * Returns return value description
     *
     * @return external_value
     */
    public static function update_progress_returns() {
        return new external_value(PARAM_INT, 'new question');
    }

    /**
     * Returns return value description
     *
     * @return external_value
     */
    public static function load_next_question_returns() {
        return new external_value(PARAM_RAW, 'new question');
    }

    /**
     * Returns return value description
     *
     * @return external_value
     */
    public static function load_questions_returns() {
        return null;
    }

    /**
     * Returns return value description
     *
     * @return external_value
     */
    public static function start_learn_now_returns() {
        return new external_value(PARAM_RAW, 'learn now url');
    }
}