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
require_once(__DIR__ . '/locallib.php');

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
                        'boxid' => new external_value(PARAM_INT, 'id of box'),
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
    public static function load_learn_progress_parameters() {
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
    public static function init_questions_parameters() {
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
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function set_preview_status_parameters() {
        return new external_function_parameters(
                array(
                        'flashcardsid' => new external_value(PARAM_INT, 'id of activity'),
                        'questionid' => new external_value(PARAM_INT, 'id of question'),
                        'status' => new external_value(PARAM_INT, 'number of questions to learn')
                )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function set_peer_review_vote_parameters() {
        return new external_function_parameters(
            array(
                'flashcardsid' => new external_value(PARAM_INT, 'id of activity'),
                'questionid' => new external_value(PARAM_INT, 'id of question'),
                'vote' => new external_value(PARAM_INT, 'peer review vote')
            )
        );
    }

    /**
     * Moves the question into the next box if the answer was correct, otherwise to box 1
     *
     * @param int $fid
     * @param int $boxid id of the related box, -1 indicates learn now
     * @param int $questionid
     * @param int $qanswervalue
     * @return string|null
     * @throws dml_exception
     */
    public static function update_progress($fid, $boxid, $questionid, $qanswervalue) {
        global $DB, $USER, $_SESSION;

        $params = self::validate_parameters(self::update_progress_parameters(),
            array(
            'fid' => $fid,
            'boxid' => $boxid,
            'questionid' => $questionid,
            'qanswervalue' => $qanswervalue)
        );

        $record = $DB->get_record('flashcards_q_stud_rel',
                ['studentid' => $USER->id, 'flashcardsid' => $params['fid'], 'questionid' => $params['questionid']], '*',
                MUST_EXIST);

        $currentbox = $record->currentbox;

        $record->lastanswered = time();
        $record->tries++;

        if ($params['qanswervalue'] == 1) {
            if ($currentbox < 5) {
                $record->currentbox++;
            }

            if ($params['boxid'] == -1) {
                $_SESSION[FLASHCARDS_LN_KNOWN . $params['fid']] += 1;
            }
        } else {
            $record->currentbox = 1;
            $record->wronganswercount++;

            if ($params['boxid'] == -1) {
                $_SESSION[FLASHCARDS_LN_UNKNOWN . $params['fid']] += 1;
            }
        }

        $DB->update_record('flashcards_q_stud_rel', $record);
        return 1;
    }

    /**
     * Get a representation of the student learning progress
     *
     * @param int $fid
     * @param int $boxid
     * @return string|null
     */
    public static function load_learn_progress($fid, $boxid) {
        global $USER, $PAGE, $_SESSION;
        list ($course, $cm) = get_course_and_cm_from_instance($fid, 'flashcards');
        require_login($course, false, $cm);

        $params = self::validate_parameters(self::load_learn_progress_parameters(), array('fid' => $fid, 'boxid' => $boxid));

        if ($params['boxid'] >= 0) {
            return null;
        }

        $lncount = $_SESSION[FLASHCARDS_LN_COUNT . $params['fid']];
        $lnknown = $_SESSION[FLASHCARDS_LN_KNOWN . $params['fid']];
        $lnunknown = $_SESSION[FLASHCARDS_LN_UNKNOWN . $params['fid']];

        $questionrenderer = $PAGE->get_renderer('mod_flashcards');
        return $questionrenderer->render_learn_progress($lncount, $lnknown, $lnunknown);
    }

    /**
     * Get the next question
     *
     * @param int $fid
     * @param int $boxid
     * @return string|null
     */
    public static function load_next_question($fid, $boxid) {
        global $USER, $PAGE;
        list ($course, $cm) = get_course_and_cm_from_instance($fid, 'flashcards');
        require_login($course, false, $cm);

        $params = self::validate_parameters(self::load_next_question_parameters(), array('fid' => $fid, 'boxid' => $boxid));

        $qid = mod_flashcards_get_next_question($params['fid'], $params['boxid']);
        $questionrenderer = $PAGE->get_renderer('mod_flashcards');
        return $questionrenderer->render_flashcard($params['fid'], $USER->id, $params['boxid'], $qid);
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
    public static function init_questions($flashcardsid, $qids) {
        global $DB, $USER;

        $params = self::validate_parameters(self::init_questions_parameters(),
            array('flashcardsid' => $flashcardsid, 'qids' => $qids));

        $record = $DB->get_record('flashcards', ['id' => $params['flashcardsid']]);
        $categories = question_categorylist($record->categoryid);
        list($inids, $questionids) = $DB->get_in_or_equal($params['qids'], SQL_PARAMS_NAMED);
        list($inids2, $categorieids) = $DB->get_in_or_equal($categories, SQL_PARAMS_NAMED);

        $sql = "SELECT id
                  FROM {question}
                 WHERE id $inids
                   AND category $inids2";

        $questionids = $DB->get_fieldset_sql($sql, $questionids + $categorieids +
            ['userid' => $USER->id, 'fid' => $params['flashcardsid']]);
        $questionarray = [];
        foreach ($questionids as $question) {
            $recid = $DB->get_record('flashcards_q_stud_rel', ['flashcardsid' => $record->id, 'questionid' => $question, 'studentid' => $USER->id]);
            if ($recid) {
                $DB->update_record('flashcards_q_stud_rel', ['id' => $recid->id, 'currentbox' => 1]);
            } else {
                $questionentry =
                array('flashcardsid' => $record->id, 'questionid' => $question, 'studentid' => $USER->id, 'active' => 1,
                      'currentbox' => 1, 'lastanswered' => 0, 'tries' => 0, 'wronganswercount' => 0);
                $questionarray[] = $questionentry;
            }
        }
        $DB->insert_records('flashcards_q_stud_rel', $questionarray);
    }

    /**
     * Load questions for learn now into the session
     *
     * @param int $flashcardsid
     * @param int $qcount
     * @return url
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function start_learn_now($flashcardsid, $qcount) {
        global $DB, $USER, $_SESSION;

        $params = self::validate_parameters(self::start_learn_now_parameters(),
            array('flashcardsid' => $flashcardsid, 'qcount' => $qcount));

        list($context, $course, $cm) = mod_flashcards_check_student_rights($params['flashcardsid']);

        $sql = "SELECT questionid
                  FROM {flashcards_q_stud_rel}
                 WHERE studentid = :userid
                   AND flashcardsid = :fid
              ORDER BY currentbox, lastanswered";

        $questionids = $DB->get_fieldset_sql($sql, ['userid' => $USER->id, 'fid' => $params['flashcardsid']]);

        $_SESSION[FLASHCARDS_LN . $params['flashcardsid']] = array_slice($questionids, 0, $params['qcount']);
        $_SESSION[FLASHCARDS_LN_COUNT . $params['flashcardsid']] = $params['qcount'];
        $_SESSION[FLASHCARDS_LN_KNOWN . $params['flashcardsid']] = 0;
        $_SESSION[FLASHCARDS_LN_UNKNOWN . $params['flashcardsid']] = 0;

        $newmoodleurl = new moodle_url('/mod/flashcards/studentquiz.php', ['id' => $cm->id, 'box' => '-1']);

        return html_entity_decode($newmoodleurl->__toString());
    }

    /**
     * Sets the review status of a flashcard
     *
     * @param int $flashcardsid
     * @param int $questionid
     * @param int $status
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function set_preview_status($flashcardsid, $questionid, $status) {
        global $DB;

        $params = self::validate_parameters(self::set_preview_status_parameters(),
                array('flashcardsid' => $flashcardsid, 'questionid' => $questionid, 'status' => $status));

        if ($params['status'] != FLASHCARDS_CHECK_NONE && $params['status'] != FLASHCARDS_CHECK_POS && $params['status'] != FLASHCARDS_CHECK_NEG) {
            return;
        }

        $statusrec = $DB->get_record('flashcards_q_status', ['questionid' => $params['questionid'], 'fcid' => $params['flashcardsid']]);

        if ($statusrec === false) {
            $DB->insert_record('flashcards_q_status', ['questionid' => $params['questionid'], 'fcid' => $params['flashcardsid'], 'teachercheck' => $params['status']]);
        } else {
            $statusrec->teachercheck = $params['status'];
            $DB->update_record('flashcards_q_status', $statusrec);
        }
    }

    /**
     * Sets the users peer review vote of a flashcard
     *
     * @param int $flashcardsid
     * @param int $questionid
     * @param int $vote
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function set_peer_review_vote($flashcardsid, $questionid, $vote) {
        global $DB, $USER;

        $params = self::validate_parameters(self::set_peer_review_vote_parameters(),
            array('flashcardsid' => $flashcardsid, 'questionid' => $questionid, 'vote' => $vote));

        if ($params['vote'] != FLASHCARDS_PEER_REVIEW_NONE && $params['vote'] != FLASHCARDS_PEER_REVIEW_UP && $params['vote'] != FLASHCARDS_PEER_REVIEW_DOWN) {
            return;
        }

        $statusrec = $DB->get_record('flashcards_q_stud_rel', ['questionid' => $params['questionid'], 'flashcardsid' => $params['flashcardsid'], 'studentid' => $USER->id]);

        if ($statusrec === false) {
            $DB->insert_record('flashcards_q_stud_rel', ['questionid' => $params['questionid'],
                'flashcardsid' => $params['flashcardsid'],
                'studentid' => $USER->id,
                'active' => 0,
                'peerreview' => $params['vote']]);
        } else {
            $statusrec->peerreview = $params['vote'];
            $DB->update_record('flashcards_q_stud_rel', $statusrec);
        }
    }

    /**
     * Returns return value description
     *
     * @return external_value
     */
    public static function update_progress_returns() {
        return new external_value(PARAM_INT, '1 if update was successful');
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
    public static function load_learn_progress_returns() {
        return new external_value(PARAM_RAW, 'current learning progress');
    }

    /**
     * Returns return value description
     *
     * @return external_value
     */
    public static function init_questions_returns() {
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

    /**
     * Returns return value description
     *
     * @return external_value
     */
    public static function set_preview_status_returns() {
        return null;
    }

    /**
     * Returns return value description
     *
     * @return external_value
     */
    public static function set_peer_review_vote_returns() {
        return null;
    }
}
