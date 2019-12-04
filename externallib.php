<?php
require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/mod/flashcards/renderer.php");

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
                    'questionid' => new external_value(PARAM_INT, 'id of course')
            )
        );
    }

    public static function update_progress($courseid, $questionid) {
        global $DB, $USER;
        $record = $DB->get_record('flashcards_q_stud_rel', ['studentid' => $USER->id, 'questionid' => $questionid], $fields='*', $strictness=MUST_EXIST);
        //TODO Fehler abfangen und returnen

        $record->lastanswered = time();
        $record->tries++;
        //TODO Box verschieben

        $DB->update_record('flashcards_q_stud_rel', $record);
        $questionrenderer = new renderer($USER->id, $record->currentbox, $record->flashcardsid, $courseid);

        return $questionrenderer->render_question();
    }

    public static function update_progress_returns() {
        return new external_value(PARAM_RAW, 'new question');
    }
}