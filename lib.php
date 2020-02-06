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
    global $DB;

    $flashcardsdb = flashcards_get_database_object($flashcards);
    $id = $DB->insert_record('flashcards', $flashcardsdb);

    return $id;
}

/**
 *
 * flashcards_get_database_object
 *
 * @param stdClass $flashcards
 * @return stdClass
 */
function flashcards_get_database_object($flashcards) {
    global $COURSE;
    require_once('locallib.php');

    $courseid = $COURSE->id;

    $flashcardsdb = new stdClass();

    $flashcardsdb->course = $courseid;
    $flashcardsdb->name = $flashcards->name;

    $flashcardsdb->categoryid = flashcards_check_category($flashcards, $courseid);

    if (!property_exists($flashcards, 'inclsubcats') || !$flashcards->inclsubcats) {
        $flashcardsdb->inclsubcats = 0;
    } else {
        $flashcardsdb->inclsubcats = 1;
    }

    if (property_exists($flashcards, 'intro') || $flashcards->intro == null) {
        $flashcardsdb->intro = '';
    } else {
        $flashcardsdb->intro = $flashcards->intro;
    }

    if (property_exists($flashcards, 'introformat') || is_integer($flashcards->introformat)) {
        $flashcardsdb->introformat = 1;
    } else {
        $flashcardsdb->introformat = $flashcards->introformat;
    }
    $flashcardsdb->timemodified = time();

    return $flashcardsdb;
}

/**
 *
 * flashcards_check_category
 *
 * @param stdClass $flashcards
 * @param int $courseid
 * @return number
 */
function flashcards_check_category($flashcards, $courseid) {

    $context = [];
    $context[] = context_course::instance($courseid);
    $coursecontext = context_course::instance($courseid);
    $contexts = [$coursecontext->id => $coursecontext];

    $defaultcategoryobj = question_make_default_categories($contexts);
    $coursecategorylist = question_get_top_categories_for_contexts([$coursecontext->id]);

    $categorylist = [];

    foreach ($coursecategorylist as $category) {
        list($catid, $catcontextid) = explode(",", $category);
        $categorylist = array_merge(question_categorylist($catid), $categorylist);
    }

    list($catid, $catcontextid) = explode(",", $flashcards->category);

    if (!in_array($catid, $categorylist)) {
        print_error('invalidcategoryid');
        return;
    }

    if ($flashcards->newcategory) {
        $defaultcategory = $defaultcategoryobj->id . ',' . $defaultcategoryobj->contextid;
        $qcobject = new question_category_object(0, new moodle_url("/mod/flashcards/view.php", ['id' => $courseid]),
            $context, $defaultcategoryobj->id, $defaultcategory, null, null);
        $categoryid = $qcobject->add_category($flashcards->category, $flashcards->newcategoryname, '', true);
        return $categoryid;
    } else {
        return $catid;
    }
}

/**
 * flashcards_update_instance
 *
 * @param array $flashcards
 * @return bool
 */
function flashcards_update_instance($flashcards) {
    global $DB;
    require_once('locallib.php');

    $flashcardsdb = flashcards_get_database_object($flashcards);
    $flashcardsdb->id = $flashcards->instance;
    $DB->update_record('flashcards', $flashcardsdb);

    return true;
}
/**
 * flashcards_delete_instance
 *
 * @param int $id
 * @return bool
 */
function flashcards_delete_instance(int $id) {
    global $DB;

    $DB->delete_records('flashcards', ['id' => $id]);

    return true;
}