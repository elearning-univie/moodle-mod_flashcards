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
 * Returns the information on whether the module supports a feature
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function flashcards_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_USES_QUESTIONS:
            return true;
        default:
            return null;
    }
}

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
 * flashcards_check_category
 *
 * @param stdClass $flashcards
 * @param int $courseid
 * @return number
 */
function flashcards_check_category($flashcards, $courseid) {
    global $CFG;
    require_once($CFG->dirroot . '/question/editlib.php');
    require_once($CFG->dirroot . '/question/category_class.php');

    $context = [];
    $categorylist = [];
    $coursecontext = context_course::instance($courseid);
    $context[] = $coursecontext;
    $contexts = [$coursecontext->id => $coursecontext];

    $defaultcategoryobj = question_make_default_categories($contexts);
    $coursecategorylist = question_get_top_categories_for_contexts([$coursecontext->id]);

    foreach ($coursecategorylist as $category) {
        list($catid, $catcontextid) = explode(",", $category);
        $categorylist = array_merge(question_categorylist($catid), $categorylist);
    }

    if (isset($flashcards->category)) {
        list($catid, $catcontextid) = explode(",", $flashcards->category);

        if (!in_array($catid, $categorylist)) {
            print_error('invalidcategoryid');
            return;
        }
        $newparent = $flashcards->category;
    } else {
        $newparent = $defaultcategoryobj->id . ',' . $defaultcategoryobj->contextid;
    }

    if ($flashcards->newcategory) {
        $newcategoryname = get_string('modulenameplural', 'flashcards') . '_' . $flashcards->name;
        $qcobject = new question_category_object(0, new moodle_url("/mod/flashcards/view.php", ['id' => $courseid]),
            $context, 0, $defaultcategoryobj->id, null, null);
        $categoryid = $qcobject->add_category($newparent, $newcategoryname, '', true);
        return $categoryid;
    } else {
        return $catid;
    }
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

    $flashcardsdb = new stdClass();

    $flashcardsdb->course = $COURSE->id;
    $flashcardsdb->name = $flashcards->name;

    $flashcardsdb->categoryid = flashcards_check_category($flashcards, $COURSE->id);

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

    if (!property_exists($flashcards, 'addfcstudent') || !$flashcards->addfcstudent) {
        $flashcardsdb->addfcstudent = 0;
    } else {
        $flashcardsdb->addfcstudent = $flashcards->addfcstudent;
    }

    $flashcardsdb->studentsubcat = null;
    if (!property_exists($flashcards, 'studentsubcat') || !$flashcards->studentsubcat) {
        if ($flashcards->addfcstudent == 1) {
            $flashcardsdb->inclsubcats = 1;
            $context = context_course::instance($COURSE->id);
            $contextid = $context->id;
            $subcatid = mod_flashcards_create_student_category_if_not_exists($contextid, $flashcards, $flashcardsdb->categoryid);
            $flashcardsdb->studentsubcat = $subcatid;
        }
    } else {
        if ($flashcardsdb->addfcstudent == 1) {
            $flashcardsdb->studentsubcat = $flashcards->studentsubcat;
        }
    }
    return $flashcardsdb;
}

/**
 * Serves the flashcards files.
 *
 * @package  mod_flashcards
 * @category files
 * @param stdClass $course course settings object
 * @param stdClass $context context object
 * @param string $component the name of the component we are serving files for.
 * @param string $filearea the name of the file area.
 * @param int $qubaid the attempt usage id.
 * @param int $slot the id of a question in this quiz attempt.
 * @param array $args the remaining bits of the file path.
 * @param bool $forcedownload whether the user must be forced to download the file.
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 */
function flashcards_question_pluginfile($course, $context, $component,
        $filearea, $qubaid, $slot, $args, $forcedownload, array $options=array()) {

    list($context, $course, $cm) = get_context_info_array($context->id);
    require_login($course, false, $cm);

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/$component/$filearea/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
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
 * Get icon mapping for font-awesome.
 */
function mod_flashcards_get_fontawesome_icon_map() {
    return [
        'mod_flashcards:viewfc' => 'fa-window-maximize',
    ];
}