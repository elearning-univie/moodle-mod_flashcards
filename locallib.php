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
 * Private page module utility functions
 *
 * @package mod_flashcards
 * @copyright  2019 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
define('FLASHCARDS_LN', 'mod_flashcards_ln_');

/**
 * Checks if the user has the right to view the course
 *
 * @param $flashcardsid
 * @return array
 * @throws coding_exception
 * @throws moodle_exception
 * @throws require_login_exception
 */
function mod_flashcards_check_student_rights($flashcardsid) {
    list ($course, $cm) = get_course_and_cm_from_instance($flashcardsid, 'flashcards');
    $context = context_module::instance($cm->id);

    if (!$course->visible || !$cm->visible) {
        //TODO richtige exception werfen
        throw new require_login_exception();
    }

    require_login($course, false, $cm);
    return array($context, $course, $cm);
}

/**
 * Get the next question for the given student and box
 *
 * @param int $fid
 * @param int $boxid
 * @return int
 */
function mod_flashcards_get_next_question($fid, $boxid) {
    global $DB, $USER;

    if ($boxid > 0) {
        $sql = "SELECT min(questionid) AS questionid FROM {flashcards_q_stud_rel} q " .
            "WHERE q.studentid = :userid AND q.currentbox = :box AND q.flashcardsid = :flashcardsid AND q.lastanswered = " .
            "(SELECT min(lastanswered) FROM {flashcards_q_stud_rel} subq " .
            "WHERE subq.studentid = q.studentid AND subq.currentbox = q.currentbox AND subq.active = q.active AND subq.flashcardsid = q.flashcardsid)";

        $questionid = $DB->get_field_sql($sql,
            ['userid' => $USER->id, 'box' => $boxid, 'flashcardsid' => $fid]);

        return $questionid;
    } else {
        return array_shift($_SESSION[FLASHCARDS_LN . $fid]);
    }
}
