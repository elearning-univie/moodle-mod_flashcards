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
 * @copyright  2021 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_flashcards\event;

/**
 * Event observer for mod_flashcards.
 */
class simplequestionform_observer {
    /**
     * Triggered via simplequestion_updated event. Resets teachercheck and PeerReview after update of question.
     *
     * @param \mod_flashcards\event\simplequestion_updated $event
     */
    public static function simplequestion_updated(\mod_flashcards\event\simplequestion_updated $event) {
        global $DB, $USER;

        $data = $event->other;
        $qbe = get_question_bank_entry($event->objectid);

        if ($data['changeextent']) {
            $sql = "SELECT fcqstatus.id AS id,
                           cm.id AS coursemodule
                      FROM {flashcards_question} fcqstatus
                      JOIN {modules} m ON m.name = 'flashcards'
                      JOIN {course_modules} cm ON cm.instance = fcqstatus.fcid AND m.id = cm.module
                     WHERE fcqstatus.qbankentryid = :qbankentryid";
            $records = $DB->get_records_sql($sql, ['qbankentryid' => $qbe->id]);

            foreach ($records as $record) {
                // Reset teachercheck only when the editor doesn't have the right to (normally students).
                $context = \context_module::instance($record->coursemodule);
                if (!has_capability('mod/flashcards:editcardwithouttcreset', $context, $data['userid'])) {
                    $DB->set_field('flashcards_question', 'teachercheck', 0, ['id' => $record->id]);
                }
                if ($DB->record_exists('flashcards_q_stud_rel', ['fqid' => $record->id])) {
                    $DB->delete_records('flashcards_q_stud_rel', ['fqid' => $record->id]);
                }
            }
        }

        $DB->set_field('flashcards_question', 'questionid', $event->objectid, ['qbankentryid' => $qbe->id]);
    }
    /**
     * Triggered via question_created event. Resets teachercheck and PeerReview after creation of question.
     *
     * @param \mod_flashcards\event\simplequestion_created $event
     */
    public static function simplequestion_created(\mod_flashcards\event\simplequestion_created $event) {
        global $DB, $USER;

        $data = $event->other;
        $tc = has_capability('mod/flashcards:editallquestions', $event->get_context()) ? 1 : 0;

        if ($qbe = get_question_bank_entry($event->objectid)) {
            if (!($record = $DB->get_record('flashcards_question', ['qbankentryid' => $qbe->id, 'fcid' => $data['fcid']]))) {
                $fcqstatusid = $DB->insert_record('flashcards_question', ['qbankentryid' => $qbe->id, 'fcid' => $data['fcid'], 'teachercheck' => $tc,
                    'questionid' => $event->objectid, 'addedby' => $USER->id], true);
                list ($course, $cm) = get_course_and_cm_from_instance($data['fcid'], 'flashcards');
                $context = $event->get_context();
                $questionreferences = new \StdClass();
                $questionreferences->usingcontextid = $context->id;
                $questionreferences->component = 'mod_flashcards';
                $questionreferences->questionarea = 'slot';
                $questionreferences->itemid = $fcqstatusid;
                $questionreferences->questionbankentryid = get_question_bank_entry($event->objectid)->id;
                $DB->insert_record('question_references', $questionreferences);
            } else {
                $DB->set_field('flashcards_question', 'teachercheck', $tc, ['id' => $record->id]);
            }
        }
    }
}
