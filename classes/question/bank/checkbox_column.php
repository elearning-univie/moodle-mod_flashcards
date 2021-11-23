<?php
// This file is part of mod_offlinequiz for Moodle - http://moodle.org/
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
 * A column with a checkbox for each question with name q{questionid}.
 *
 * @package   mod_flashcards
 * @copyright 2021 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checkbox_column extends \core_question\bank\checkbox_column {
    /** @var string caches a lang string used repeatedly. */
    protected $strselect;

    /**
     * display_content
     * @param object $question
     * @param string $rowclasses
     */
    protected function display_content($question, $rowclasses) {
        if ($this->qbank->flashcards_contains($question->id)) {
            echo '<input title="' . $this->strselect . '" type="checkbox" name="q' .
                $question->id . '" id="checkq' . $question->id . '" value="1" ' .
                'disabled="disabled" class="select-multiple-checkbox" />';
        } else {
            parent::display_content($question, $rowclasses);
        }
    }
}
