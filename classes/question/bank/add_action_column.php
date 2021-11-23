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
 * A column with a checkbox for each question with name q{questionid}.
 *
 * @package   mod_flashcards
 * @copyright 2021 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_flashcards\question\bank;
defined('MOODLE_INTERNAL') || die();


/**
 * A column type for the add this question to the quiz action.
 *
 * @package   mod_flashcards
 * @copyright 2021 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_action_column extends \core_question\bank\action_column_base {
    /** @var string caches a lang string used repeatedly. */
    protected $stradd;

    /**
     * init function
     * @throws \coding_exception
     */
    public function init() {
        parent::init();
        $this->stradd = get_string('addtoquiz', 'quiz');
    }

    /**
     * return action name
     * @return string
     */
    public function get_name() {
        return 'addtoflashcardsaction';
    }

    /**
     * disploy column
     * @param object $question
     * @param string $rowclasses
     * @throws \coding_exception
     */
    protected function display_content($question, $rowclasses) {
        if (!question_has_capability_on($question, 'use')) {
            return;
        }
        if ($this->qbank->flashcards_contains($question->id)) {
            return;
        }
        $this->print_icon('t/add', $this->stradd, $this->qbank->add_to_flashcards_url($question->id));
    }
}
