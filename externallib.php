<?php
require_once("$CFG->libdir/externallib.php");

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
        global $DB;
        return 1;
    }

    public static function update_progress_returns() {
        return new external_value(PARAM_INT, '1 if success, 0 if failed');
    }
}