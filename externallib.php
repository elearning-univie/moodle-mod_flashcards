<?php
require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/mod/flashcards/renderer.php");
require_once($CFG->libdir . '/questionlib.php');

class mod_flashcards_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function update_progress_parameters() {
        return new external_function_parameters(
                array(
                        'courseid' => new external_value(PARAM_INT, 'id of course'),
                        'questionid' => new external_value(PARAM_INT, 'id of course'),
                        'qanswervalue' => new external_value(PARAM_INT, 'int value of the answer')
                )
        );
    }

    /**
     * @return external_function_parameters
     */
    public static function load_questions_parameters() {
        return new external_function_parameters(
                array(
                        'courseid' => new external_value(PARAM_INT, 'id of course')
                )
        );
    }

    /**
     * @param $courseid
     * @param $questionid
     * @param $qanswervalue
     * @return string|null
     * @throws dml_exception
     */
    public static function update_progress($courseid, $questionid, $qanswervalue) {
        global $DB, $USER;

        $record = $DB->get_record('flashcards_q_stud_rel', ['studentid' => $USER->id, 'questionid' => $questionid], $fields = '*', $strictness = MUST_EXIST);

        $currentbox = $record->currentbox;

        $record->lastanswered = time();
        $record->tries++;

        if ($qanswervalue == 1) {
            $record->currentbox++;
        } else {
            $record->currentbox = 1;
            $record->wronganswercount++;
        }

        $DB->update_record('flashcards_q_stud_rel', $record);
        $questionrenderer = new renderer($USER->id, $currentbox, $record->flashcardsid, $courseid);

        return $questionrenderer->render_question();
    }

    /**
     * @param $courseid
     * @return int
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function load_questions($courseid) {
        global $DB, $USER;

        $record = $DB->get_record('flashcards', ['course' => $courseid]);
        $categories = question_categorylist($record->categoryid);
        list($inids, $categorieids) = $DB->get_in_or_equal($categories);
        $sql =
                "SELECT q.id FROM {question} q WHERE category $inids AND q.id NOT IN (SELECT questionid FROM {flashcards_q_stud_rel} WHERE studentid = $USER->id and flashcardsid = $record->id)";

        $questionids = $DB->get_fieldset_sql($sql, $categorieids);
        $questionarray = [];

        foreach ($questionids as $question) {

            $questionentry =
                    array('flashcardsid' => $record->id, 'questionid' => $question, 'studentid' => $USER->id, 'active' => 't',
                            'currentbox' => 1, 'lastanswered' => 0, 'tries' => 0, 'wronganswercount' => 0);

            $questionarray[] = $questionentry;
        }
        $DB->insert_records('flashcards_q_stud_rel', $questionarray);
        return 1;
    }

    /**
     * @return external_value
     */
    public static function update_progress_returns() {
        return new external_value(PARAM_RAW, 'new question');
    }

    /**
     * @return external_value
     */
    public static function load_questions_returns() {
        return new external_value(PARAM_INT, 'new question');
    }
}