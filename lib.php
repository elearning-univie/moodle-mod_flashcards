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
 * Flashcards lib
 *
 * @package    mod_flashcards
 * @copyright  2019 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
/**
 * flashcards_add_instance
 *
 * @param array $flashcards
 * @return bool 
 */
function flashcards_add_instance($flashcards) {
    global $DB, $CFG, $COURSE;
    require_once ('locallib.php');
    
    $object = new stdClass();
    $object->timecreated = time();
    $courseid = $COURSE->id;
    $context = [];
    $context[] = context_course::instance($courseid);
    
    $coursecontext = context_course::instance($courseid);
    $contexts = [$coursecontext->id => $coursecontext]; 

    if (property_exists($flashcards, 'intro') || $flashcards -> intro == null) {
        $flashcards -> intro = '';
    } else {
        $flashcards -> intro = $flashcards;
    }

    list($catid, $catcontextid) = explode(",", $flashcards->category);

    if ($flashcards->newcategory) {

        $defaultcategoryobj = question_make_default_categories($contexts);
        
        $defaultcategory = $defaultcategoryobj->id . ',' . $defaultcategoryobj->contextid;        
        $qcobject = new question_category_object(0, new moodle_url("/mod/flashcards/view.php", ['id' => $courseid]),
           $context, $defaultcategoryobj->id, $defaultcategory, null, null);

        $categoryid = $qcobject->add_category($flashcards->category, $flashcards->newcategoryname, '', true);
        $flashcards -> categoryid = $categoryid;        
    } else {
        $flashcards -> categoryid = $catid;
    }

     $id = $DB -> insert_record('flashcards', $flashcards);
    
    return $id;
}
/**
 * flashcards_update_instance
 *
 * @param array $flashcards
 * @return bool
 */
function flashcards_update_instance($flashcards) {
    global $DB, $CFG;
    require_once ('locallib.php');

    $flashcards->id = $flashcards->instance;
    $DB->update_record('flashcards', $flashcards);

    return true;
}
/**
 * flashcards_delete_instance
 *
 * @param int $id
 * @return bool
 */
function flashcards_delete_instance($id) {
    global $DB;

    $DB->delete_records('flashcards', ['id' => $id]);
    
    return true;
}