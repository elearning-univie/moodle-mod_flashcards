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
 * Class for the structure used to backup one flashcards activity.
 *
 * @package   mod_flashcards
 * @copyright 2021 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define the complete flashcards structure for backup, with file and id annotations
 *
 * @package   mod_flashcards
 * @category  backup
 * @copyright 2021 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_flashcards_activity_structure_step extends backup_questions_activity_structure_step {

    /**
     * Defines the backup structure of the module
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        $flashcards = new backup_nested_element('flashcards', ['id'],
                ['course', 'name', 'categoryid', 'inclsubcats', 'intro', 'introformat', 'timemodified', 'addfcstudent', 'studentsubcat']);

        $flashcards->set_source_table('flashcards', ['id' => backup::VAR_ACTIVITYID]);
        $flashcards->annotate_files('mod_flashcards', 'intro', null);

        $qinstance = new backup_nested_element('question_instance', ['id'], [
            'questionid',
        ]);

        $this->add_question_references($qinstance, 'mod_flashcards', 'slot');

        $flashcardsqstatus = new backup_nested_element('flashcards_question', ['id'],
        ['questionid', 'qbankentryid', 'fcid', 'teachercheck']);
        $flashcardsqstatus->add_child($qinstance);

        $flashcards->add_child($flashcardsqstatus);

        $flashcardsqstatus->set_source_table('flashcards_question', ['fcid' => backup::VAR_PARENTID]);
        $flashcardsqstatus->annotate_ids('question', 'questionid');

        return $this->prepare_activity_structure($flashcards);
    }
}
