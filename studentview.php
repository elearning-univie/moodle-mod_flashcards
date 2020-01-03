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
 * Flashcards Student view
 *
 * @package    mod_flashcards
 * @copyright  2019 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require_once($CFG->libdir . '/questionlib.php');

global $PAGE, $OUTPUT, $USER, $DB;

$id = required_param('id', PARAM_INT);
list ($course, $cm) = get_course_and_cm_from_cmid($id, 'flashcards');
$context = context_module::instance($cm->id);

require_login($course, false, $cm);

$PAGE->set_url(new moodle_url("/mod/flashcards/studentview.php", ['id' => $id]));
$node = $PAGE->settingsnav->find('mod_flashcards', navigation_node::TYPE_SETTING);

if ($node) {
    $node->make_active();
}

$pagetitle = get_string('pagetitle', 'flashcards');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

if (has_capability('mod/flashcards:studentview', $context)) {
    $PAGE->requires->js_call_amd('mod_flashcards/studentcontroller', 'init');

    $flashcards = $DB->get_record('flashcards', array('id' => $cm->instance));
    echo $OUTPUT->heading($flashcards->name);

    $boxrecords = get_box_count_records($USER->id, $flashcards->id);
    $questioncount = get_box_zero_count_record($USER->id, $flashcards->id);

    $boxarray = create_boxvalue_array($boxrecords, $id, $questioncount);
    $templatestablecontext['boxes'] = $boxarray;

    $renderer = $PAGE->get_renderer('core');
    echo $renderer->render_from_template('mod_flashcards/student_view', $templatestablecontext);
    echo $OUTPUT->footer();
} else {
    echo $OUTPUT->heading(get_string('errornotallowedonpage', 'flashcards'));
    echo $OUTPUT->footer();
    die();
}

function get_box_zero_count_record($userid, $flashcardsid) {
    global $DB;

    $categoryid = $DB->get_record_sql('SELECT categoryid FROM {flashcards} WHERE id = :flashcardsid', ['flashcardsid' => $flashcardsid]);
    $categories = question_categorylist($categoryid->categoryid);
    list($inids, $categorieids) = $DB->get_in_or_equal($categories);

    $sql = "SELECT count(q.id) FROM {question} q " .
                    "WHERE category $inids AND q.id NOT IN " .
                    "(SELECT questionid FROM {flashcards_q_stud_rel} WHERE studentid = $userid and flashcardsid = $flashcardsid)";

    return $DB->count_records_sql($sql, $categorieids);
}

function get_box_count_records($userid, $flashcardsid) {
    global $DB;

    $sql = "SELECT currentbox, count(id) FROM {flashcards_q_stud_rel} " .
            "WHERE studentid = :userid AND flashcardsid = :flashcardsid " .
            "GROUP BY currentbox ORDER BY currentbox";

    return $DB->get_recordset_sql($sql, ['userid' => $userid, 'flashcardsid' => $flashcardsid]);
}

/**
 * Creates an array containing the values for the box overview.
 *
 * @param array $records Contains the box number and the question count to display
 * @param int $id Course id
 * @param int $boxzerocount Number of new questions for box 0
 * @return array
 */
function create_boxvalue_array($records, $id, $boxzerocount) {
    $boxindex = 0;
    $boxvalues['currentbox'] = $boxindex;
    $boxvalues['count'] = $boxzerocount;
    $boxvalues['redirecturl'] = null;

    if ($boxzerocount != 0) {
        $boxvalues['loadquestions'] = true;
    } else {
        $boxvalues['loadquestions'] = false;
    }

    $boxarray[] = $boxvalues;

    $boxvalues['loadquestions'] = false;
    $boxindex++;

    foreach ($records as $record) {

        while ($record->currentbox != $boxindex) {
            $boxvalues['currentbox'] = $boxindex;
            $boxvalues['count'] = 0;
            $boxvalues['redirecturl'] = null;

            $boxarray[] = $boxvalues;
            $boxindex++;
        }

        if ($record->currentbox = $boxindex) {
            $boxvalues['currentbox'] = $boxindex;
            $boxvalues['count'] = $record->count;
            $boxvalues['redirecturl'] = new moodle_url('/mod/flashcards/studentquiz.php', ['id' => $id, 'box' => $boxindex]);

            $boxarray[] = $boxvalues;
            $boxindex++;
        }
    }

    while ($boxindex <= 5) {
        $boxvalues['currentbox'] = $boxindex;
        $boxvalues['count'] = 0;
        $boxvalues['redirecturl'] = null;

        $boxarray[] = $boxvalues;
        $boxindex++;
    }

    return $boxarray;
}