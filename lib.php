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
 * @copyright  2021 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Returns the information on whether the module supports a feature
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
use core\context;
/**
 *
 * @param string $feature
 * @return boolean|string|NULL
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
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_COLLABORATION;
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

    $categorylist = [];
    $coursecontext = context_course::instance($courseid);
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
            return;
        }
        $newparent = $flashcards->category;
    } else {
        $newparent = $defaultcategoryobj->id . ',' . $defaultcategoryobj->contextid;
    }

    if ($flashcards->newcategory) {
        $newcategoryname = get_string('modulenameplural', 'flashcards') . '_' . $flashcards->name;
        $qcontext = new \core_question\local\bank\question_edit_contexts($coursecontext);
        $qcobject = new \qbank_managecategories\question_category_object(null,
            new moodle_url("/mod/flashcards/view.php", ['id' => $courseid]),
            $qcontext->having_one_edit_tab_cap('categories'), 0, $defaultcategoryobj->id, 0,
            $qcontext->having_cap('moodle/question:add'));
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

    if (!$DB->record_exists('flashcards', ['id' => $id])) {
        return false;
    }

    $DB->delete_records('flashcards', ['id' => $id]);
    $DB->delete_records('flashcards_q_status', ['fcid' => $id]);
    $DB->delete_records('flashcards_q_stud_rel', ['flashcardsid' => $id]);

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

    if (!isset($flashcardsdb->categoryid)) {
        throw new \moodle_exception('invalidcategoryid');
        return;
    }

    if (!property_exists($flashcards, 'inclsubcats') || !$flashcards->inclsubcats) {
        $flashcardsdb->inclsubcats = 0;
    } else {
        $flashcardsdb->inclsubcats = 1;
    }

    if (!property_exists($flashcards, 'intro') || $flashcards->intro == null) {
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
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) || $file->is_directory()) {
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

/**
 * Generates the question bank in a fragment output. This allows
 * the question bank to be displayed in a modal.
 *
 * The only expected argument provided in the $args array is
 * 'querystring'. The value should be the list of parameters
 * URL encoded and used to build the question bank page.
 *
 * The individual list of parameters expected can be found in
 * question_build_edit_resources.
 *
 * @param array $args The fragment arguments.
 * @return string The rendered mform fragment.
 */
function mod_flashcards_output_fragment_flashcards_question_bank($args): string {
    global $PAGE;

    // Retrieve params.
    $params = [];
    $extraparams = [];
    $querystring = parse_url($args['querystring'], PHP_URL_QUERY);
    parse_str($querystring, $params);

    $viewclass = \mod_flashcards\question\bank\custom_view::class;
    $extraparams['view'] = $viewclass;

    // Build required parameters.
    [$contexts, $thispageurl, $cm, $flashcards, $pagevars, $extraparams] =
    mod_flashcards_build_required_params_for_custom_view($params, $extraparams);

    $course = get_course($cm->course);
    require_capability('mod/flashcards:editallquestions', $contexts->lowest());

    // Custom View.
    $questionbank = new $viewclass($contexts, $thispageurl, $course, $cm, $pagevars, $extraparams, $flashcards);

    // Output.
    $renderer = $PAGE->get_renderer('mod_flashcards', 'edit');
    return $renderer->question_bank_contents($questionbank, $pagevars);
}
/**
 * Build required parameters for question bank custom view
 *
 * @param array $params the page parameters
 * @param array $extraparams additional parameters
 * @return array
 */
function mod_flashcards_build_required_params_for_custom_view(array $params, array $extraparams): array {
    // Retrieve questions per page.
    $viewclass = $extraparams['view'] ?? null;
    $defaultpagesize = $viewclass ? $viewclass::DEFAULT_PAGE_SIZE : DEFAULT_QUESTIONS_PER_PAGE;
    // Build the required params.
    [$thispageurl, $contexts, $cmid, $cm, $module, $pagevars] = question_build_edit_resources(
        'editq',
        '/mod/flashcards/teacherview.php',
        array_merge($params, $extraparams),
        $defaultpagesize);

    // Add cmid so we can retrieve later in extra params.
    $extraparams['cmid'] = $cmid;

    return [$contexts, $thispageurl, $cm, $module, $pagevars, $extraparams];
}

/**
 * Question data fragment to get the question html via ajax call.
 *
 * @param array $args
 * @return string
 */
function mod_flashcards_output_fragment_question_data(array $args): string {
    // Return if there is no args.
    if (empty($args)) {
        return '';
    }

    // Retrieve params from query string.
    [$params, $extraparams] = \core_question\local\bank\filter_condition_manager::extract_parameters_from_fragment_args($args);

    // Build required parameters.
    $cmid = clean_param($args['cmid'], PARAM_INT);
    $thispageurl = new \moodle_url('/mod/flashcards/teacherview.php', ['cmid' => $cmid]);
    $thiscontext = context::instance($cmid);
    $contexts = new \core_question\local\bank\question_edit_contexts($thiscontext);
    $defaultcategory = question_make_default_categories($contexts->all());
    $params['cat'] = implode(',', [$defaultcategory->id, $defaultcategory->contextid]);

    $course = get_course($params['courseid']);
    [, $cm] = get_module_from_cmid($cmid);
    $params['tabname'] = 'questions';

    // Custom question bank View.
    $viewclass = clean_param($args['view'], PARAM_NOTAGS);
    $questionbank = new $viewclass($contexts, $thispageurl, $course, $cm, $params, $extraparams);

    // Question table.
    $questionbank->add_standard_search_conditions();
    ob_start();
    $questionbank->display_question_list();
    return ob_get_clean();
}
/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settingsnav The settings navigation object
 * @param navigation_node $wordcloudnode The node to add module settings to
 */
function flashcards_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $wordcloudnode) {
    if (has_capability('mod/flashcards:teacherview', $settingsnav->get_page()->context)) {
        $url = new moodle_url('/mod/flashcards/teacherview.php', ['cmid' => $settingsnav->get_page()->cm->id]);
        $wordcloudnode->add(get_string('teacherview', 'mod_flashcards'), $url, navigation_node::TYPE_SETTING, null, 'mod_flashcards_teacherview');

        $url = new moodle_url('/question/edit.php', ['courseid' => $settingsnav->get_page()->cm->course]);
        $wordcloudnode->add(get_string('qbank', 'mod_flashcards'), $url, navigation_node::TYPE_SETTING, null, 'mod_flashcards_qbank')->set_force_into_more_menu(true);
    }
}

