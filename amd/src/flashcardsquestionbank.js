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
 * Initialise the question bank modal on the quiz page.
 *
 * @module    mod_flashcards/flashcardsquestionbank
 * @copyright 2021 University of Vienna
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import AddQuestionModalLauncher from 'mod_flashcards/addquestionmodallauncher';
import ModalFlashcardsQuestionBank from 'mod_flashcards/modalflashcardsquestionbank';

export const init = contextId => {
    AddQuestionModalLauncher.init(
        ModalFlashcardsQuestionBank.TYPE,
        '.menu [data-action="questionbank"]',
        contextId
    );
};
