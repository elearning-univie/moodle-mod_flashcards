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
 * @copyright  2021 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once(__DIR__ . '/locallib.php');

/**
 * Class mod_flashcards_external
 *
 * @copyright  2021 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_flashcards_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function update_progress_parameters() {
        return new external_function_parameters([
            'fid' => new external_value(PARAM_INT, 'flashcard activity id'),
            'boxid' => new external_value(PARAM_INT, 'box id'),
            'questionid' => new external_value(PARAM_INT, 'question id'),
            'qanswervalue' => new external_value(PARAM_INT, 'int value of the answer'),
        ]);
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function load_learn_progress_parameters() {
        return new external_function_parameters([
            'fid' => new external_value(PARAM_INT, 'flashcard activity id'),
            'boxid' => new external_value(PARAM_INT, 'id of current box'),
        ]);
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function load_next_question_parameters() {
        return new external_function_parameters([
            'fid' => new external_value(PARAM_INT, 'flashcard activity id'),
            'boxid' => new external_value(PARAM_INT, 'id of current box'),
        ]);
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function init_questions_parameters() {
        return new external_function_parameters([
            'flashcardsid' => new external_value(PARAM_INT, 'flashcard activity id'),
            'qids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'id array of questions')
            ),
        ]);
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function remove_questions_parameters() {
        return new external_function_parameters([
            'flashcardsid' => new external_value(PARAM_INT, 'flashcard activity id'),
            'qids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'id array of questions')
            ),
        ]);
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function start_learn_now_parameters() {
        return new external_function_parameters([
            'flashcardsid' => new external_value(PARAM_INT, 'flashcard activity id'),
            'qcount' => new external_value(PARAM_INT, 'number of questions to learn'),
        ]);
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function set_preview_status_parameters() {
        return new external_function_parameters([
            'fqid' => new external_value(PARAM_INT, 'flashcard question id'),
            'status' => new external_value(PARAM_INT, 'number of questions to learn'),
        ]);
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function set_peer_review_vote_parameters() {
        return new external_function_parameters([
            'fqid' => new external_value(PARAM_INT, 'flashcard question id'),
            'vote' => new external_value(PARAM_INT, 'peer review vote'),
        ]);
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function set_showappinfo_parameters() {
        return new external_function_parameters([
            'prefval' => new external_value(PARAM_BOOL, 'value to set the pref'),
        ]);
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

        $params = self::validate_parameters(self::update_progress_parameters(), [
            'fid' => $fid,
            'boxid' => $boxid,
            'questionid' => $questionid,
            'qanswervalue' => $qanswervalue,
        ]);

        $fqrec = $DB->get_record('flashcards_question', ['questionid' => $params['questionid'], 'fcid' => $params['fid']]);

        $record = $DB->get_record('flashcards_q_stud_rel',
                ['studentid' => $USER->id, 'fqid' => $fqrec->id], '*',
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

        mod_flashcards_load_xp_events($params['fid']);

        return 1;
    }

    /**
     * Get a representation of the student learn progress
     *
     * @param int $fid
     * @param int $boxid
     * @return string|null
     */
    public static function load_learn_progress($fid, $boxid) {
        global $PAGE, $_SESSION;
        list ($course, $cm) = get_course_and_cm_from_instance($fid, 'flashcards');
        require_login($course, false, $cm);

        $params = self::validate_parameters(self::load_learn_progress_parameters(), ['fid' => $fid, 'boxid' => $boxid]);

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

        $params = self::validate_parameters(self::load_next_question_parameters(), ['fid' => $fid, 'boxid' => $boxid]);

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
        $params = self::validate_parameters(self::init_questions_parameters(),
            ['flashcardsid' => $flashcardsid, 'qids' => $qids]);

        mod_flashcards_move_question($params['flashcardsid'], $params['qids'], 1);
    }

    /**
     * Removes all selected questions from box 1 to box 0 for the activity
     *
     * @param int $flashcardsid
     * @param array $qids
     * @return int
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function remove_questions($flashcardsid, $qids) {
        $params = self::validate_parameters(self::remove_questions_parameters(),
            ['flashcardsid' => $flashcardsid, 'qids' => $qids]);

        mod_flashcards_move_question($params['flashcardsid'], $params['qids'], null);
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
            ['flashcardsid' => $flashcardsid, 'qcount' => $qcount]);

        list($context, $course, $cm) = mod_flashcards_check_student_rights($params['flashcardsid']);

        $sql = "SELECT fq.questionid
                  FROM {flashcards_q_stud_rel} fsr
                  JOIN {flashcards_question} fq ON fsr.fqid = fq.id
                 WHERE studentid = :userid
                   AND fcid = :fid
                   AND currentbox <> 0
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
     * Set the review status of a flashcard
     *
     * @param int $fqid
     * @param int $status
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function set_preview_status($fqid, $status) {
        global $DB;

        $params = self::validate_parameters(self::set_preview_status_parameters(),
                ['fqid' => $fqid, 'status' => $status]);

        if ($params['status'] != FLASHCARDS_CHECK_NONE
            && $params['status'] != FLASHCARDS_CHECK_POS
            && $params['status'] != FLASHCARDS_CHECK_NEG) {
            return;
        }

        $statusrec = $DB->get_record('flashcards_question', ['id' => $params['fqid']]);

        if ($statusrec === false) {
            return;
        }

        list ($course, $cm) = get_course_and_cm_from_instance($statusrec->fcid, 'flashcards');
        $context = context_module::instance($cm->id);

        if (!has_capability('mod/flashcards:editreview', $context)) {
            return;
        }

        $statusrec->teachercheck = $params['status'];
        $DB->update_record('flashcards_question', $statusrec);
    }

    /**
     * Set the user peer review vote of a flashcard
     *
     * @param int $fqid
     * @param int $vote
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function set_peer_review_vote($fqid, $vote) {
        global $DB, $USER;

        $params = self::validate_parameters(self::set_peer_review_vote_parameters(),
            ['fqid' => $fqid, 'vote' => $vote]);

        if ($params['vote'] != FLASHCARDS_PEER_REVIEW_NONE
            && $params['vote'] != FLASHCARDS_PEER_REVIEW_UP
            && $params['vote'] != FLASHCARDS_PEER_REVIEW_DOWN) {
            return;
        }

        $statusrec = $DB->get_record('flashcards_q_stud_rel', ['fqid' => $params['fqid'], 'studentid' => $USER->id]);

        if ($statusrec === false) {
            $DB->insert_record('flashcards_q_stud_rel', ['fqid' => $params['fqid'],
                'flashcardsid' => 0, // TOREMOVE!
                'studentid' => $USER->id,
                'active' => 0,
                'peerreview' => $params['vote']]);
        } else {
            $statusrec->peerreview = $params['vote'];
            $DB->update_record('flashcards_q_stud_rel', $statusrec);
        }
    }

    /**
     * Set the preference value to show or hide the mobile app info
     * @param bool $prefval
     * @return external_function_parameters
     */
    public static function set_showappinfo($prefval) {

        $params = self::validate_parameters(self::set_showappinfo_parameters(),
            ['prefval' => $prefval]);

        set_user_preference('flashcards_showapp', $params['prefval']);
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
    public static function remove_questions_returns() {
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

    /**
     * Returns return value description
     *
     * @return external_value
     */
    public static function set_showappinfo_returns() {
        return null;
    }
}
