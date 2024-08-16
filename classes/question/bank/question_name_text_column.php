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
 * A column type for the name followed by the start of the question text.
 *
 * @package   mod_flashcards
 * @copyright 2021 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_flashcards\question\bank;

/**
 * A column type for the name followed by the start of the question text.
 *
 * @package   mod_flashcards
 * @copyright 2021 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_name_text_column extends question_name_column {
    /**
     * get_name
     * @return string
     */
    public function get_name(): string {
        return 'questionnametext';
    }

    /**
     * get_default_width
     * @return int
     */
    public function get_default_width(): int {
        return 800;
    }

    /**
     * display_content
     * @param object $question
     * @param string $rowclasses
     */
    protected function display_content($question, $rowclasses): void {
        if ($this->qbank->flashcards_contains($question->id)) {
            $class = 'greyed';
        }
        echo '<div class="'. $class . '">';
        $labelfor = $this->label_for($question);
        if ($labelfor) {
            echo '<label for="' . $labelfor . '">';
        }
        echo mod_flashcards_question_tostring($question, false, true, true, $question->tags);
        if ($labelfor) {
            echo '</label>';
        }
        echo '</div>';
    }
    /**
     * get_required_fields
     * @return string[]
     */
    public function get_required_fields(): array {
        $fields = parent::get_required_fields();
        $fields[] = 'q.questiontext';
        $fields[] = 'q.questiontextformat';
        $fields[] = 'qbe.idnumber';
        return $fields;
    }

    /**
     * load_additional_data
     * @param array $questions
     */
    public function load_additional_data(array $questions) {
        parent::load_additional_data($questions);
        parent::load_question_tags($questions);
    }
}
