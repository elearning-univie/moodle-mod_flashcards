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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot."/question/category_class.php");

function flashcards_add_instance($flashcards) {
    global $DB;
    $object = new stdClass();
    $object->timecreated = time();

    if (property_exists($flashcards, 'intro') || $flashcards -> intro == null) {
        $flashcards -> intro = '';
    } else {
        $flashcards -> intro = $flashcards;
    }

    $catids = explode(",", $flashcards->category);

    if ($flashcards->newcategory) {
        $newcat = new stdClass();
        $newcat -> name = $flashcards -> newcategoryname;
        $newcat->contextid = $catids[1];
        $newcat->info = 'Created via Flashcard Activity';
        $qcid = $DB->insert_record('question_categories', $newcat);
        $flashcards->categoryid = $qcid;
        
    } else {
        $flashcards -> categoryid = $catids[0];
    }

    $id = $DB -> insert_record('flashcards', $flashcards);
    
    return $id;
}
function flashcards_update_instance($flashcards) {
    
}
function flashcards_delete_instance($id) {
    global $DB;

    $DB->delete_records('flashcards', ['id' => $id]);
}