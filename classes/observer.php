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


defined('MOODLE_INTERNAL') || die();

/**
 * Event observer for mod_flashcards.
 */
class mod_flashcards_observer {

    /**
     * Triggered via question_updated event. Resets teachercheck and PeerReview after update of question.
     *
     * @param \core\event\question_updated $event
     */
    public static function question_updated(\core\event\question_updated $event) {
        global $DB;

        $tc = 0;
        $record = $DB->get_record('flashcards_q_status', array('questionid' => $event->objectid));
        if (!$record) {
            return;
        }

        // reset teachercheck only when a student-authored card is changed.
        if (has_capability('mod/flashcards:studentview', $event->get_context(), $event->userid)) {
            $DB->update_record('flashcards_q_status', array('id' => $record->id, 'teachercheck' => $tc));
        }

        // reset peer review for all roles
    }

}
