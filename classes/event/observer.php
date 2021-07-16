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
 * The questiontype class for the flashcard question type.
 *
 * @package    mod_flashcards
 * @copyright  2020 University of vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_flashcards\event;

defined('MOODLE_INTERNAL') || die();


/**
 * Event observer for mod_flashcards.
 */
class observer {

    /**
     * Triggered via question_updated event. Resets teachercheck and PeerReview after update of question.
     *
     * @param \core\event\question_updated $event
     */
    public static function question_updated(\core\event\question_updated $event) {
        global $DB;

        $sql = "SELECT fcqstatus.id AS id,
                       cm.id AS coursemodule
                  FROM {flashcards_q_status} fcqstatus
                  JOIN {modules} m ON m.name = 'flashcards'
                  JOIN {course_modules} cm ON cm.instance = fcqstatus.fcid AND m.id = cm.module
                 WHERE fcqstatus.questionid = :questionid";
        $records = $DB->get_records_sql($sql, ['questionid' => $event->objectid]);
        foreach ($records as $record) {
            // Reset teachercheck only when a the editor doesn't have the right to (normally students).
            $context = \context_module::instance($record->coursemodule, MUST_EXIST);
            if (!has_capability('mod/flashcards:editcardwithouttcreset', $context, $event->userid)) {
                $DB->set_field('flashcards_q_status', 'teachercheck', 0, ['id' => $record->id]);
            }
        }
        // Reset peer review for all roles. TODO
    }

}
