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


use core_question\category_manager;

/**
 * Event observer for mod_flashcards.
 */
class flashcardcreation_observer {
    /**
     * Triggered via question_created event. Resets teachercheck and PeerReview after creation of question.
     *
     * @param \core\event\course_module_created $event
     */
    public static function flashcard_created(\core\event\course_module_created $event) {
        global $DB, $USER;

        $cmid = $event->objectid;
        $data = $event->other;
        $fcid = $data['instanceid'];
        $modulename = $data['modulename'];

        if (strcmp($modulename, 'flashcards') == 0) {
            $flashcard = $DB->get_record('flashcards', ['id' => $fcid]);
            if ($flashcard->categoryid == 0) {
                $thiscontext = \context_module::instance($cmid);

                $contexts = new \core_question\local\bank\question_edit_contexts($thiscontext);
                $defaultcategoryobj = question_get_default_category($contexts->lowest()->id, true);
                $flashcard->categoryid = $defaultcategoryobj->id;
                $DB->update_record('flashcards', $flashcard);
                if ($flashcard->addfcstudent == 1) {
                    $studentsubcat = $DB->get_record('question_categories', ['id' => $flashcard->studentsubcat]);
                    $studentsubcat->parent = $defaultcategoryobj->id;
                    $studentsubcat->contextid = $defaultcategoryobj->contextid;
                    $DB->update_record('question_categories', $studentsubcat);
                }
            }
        }

    }
}
