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
 * @copyright  2020 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/mod/flashcards/locallib.php');

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
    mod_flashcards_check_for_orphan_or_hidden_questions();
    $PAGE->requires->js_call_amd('mod_flashcards/studentcontroller', 'init');
    $PAGE->requires->js_call_amd('mod_flashcards/studentrangeslider', 'init');
    $flashcards = $DB->get_record('flashcards', array('id' => $cm->instance));
    echo $OUTPUT->heading($flashcards->name);

    $boxrecords = get_box_count_records($USER->id, $flashcards->id);
    $questioncount = get_box_zero_count_record($USER->id, $flashcards);

    $boxarray = create_boxvalue_array($boxrecords, $id, $questioncount, $flashcards->id);
    $templatestablecontext['boxes'] = $boxarray;
    $templatestablecontext['learnnowurl'] = new moodle_url("/mod/flashcards/studentlearnnow.php", ['id' => $id]);
    $templatestablecontext['flashcardsid'] = $flashcards->id;

    $renderer = $PAGE->get_renderer('core');

    $qcount = get_learn_now_question_count($USER->id, $flashcards->id);

    if ($qcount > 0) {
        $templatestablecontext['learnnowqcount'] = $qcount;
        $templatestablecontext['enablelearnnow'] = true;
    } else {
        $templatestablecontext['enablelearnnow'] = false;
    }

    echo $renderer->render_from_template('mod_flashcards/studentview', $templatestablecontext);
    echo $OUTPUT->footer();
} else {
    echo $OUTPUT->heading(get_string('errornotallowedonpage', 'flashcards'));
    echo $OUTPUT->footer();
    die();
}

/**
 * Returns the number of questions available to be learned
 * @param int $userid
 * @param int $flashcardsid
 * @return int
 * @throws dml_exception
 */
function get_learn_now_question_count($userid, $flashcardsid) {
    global $DB;

    $sql = "SELECT count(id)
              FROM {flashcards_q_stud_rel}
             WHERE studentid = :userid
               AND flashcardsid = :fid";

    return $DB->count_records_sql($sql, ['userid' => $userid, 'fid' => $flashcardsid]);
}

/**
 * Calculates the number of questions in box zero
 * @param int $userid
 * @param object $flashcards
 * @return int
 * @throws coding_exception
 * @throws dml_exception
 */
function get_box_zero_count_record($userid, $flashcards) {
    global $DB;

    if ($flashcards->inclsubcats) {
        $qcategories = question_categorylist($flashcards->categoryid);
    } else {
        $qcategories = $flashcards->categoryid;
    }

    list($inids, $categorieids) = $DB->get_in_or_equal($qcategories, SQL_PARAMS_NAMED);

    $sql = "SELECT count(id)
              FROM {question}
             WHERE category $inids
               AND qtype = 'flashcard'
               AND id NOT IN (SELECT questionid
                                FROM {flashcards_q_stud_rel}
                               WHERE studentid = :userid
                                 AND flashcardsid = :fid)";

    return $DB->count_records_sql($sql, $categorieids + ['userid' => $userid, 'fid' => $flashcards->id]);
}

/**
 * Calculates the number of questions in each box
 * @param int $userid
 * @param int $flashcardsid
 * @return moodle_recordset
 * @throws dml_exception
 */
function get_box_count_records($userid, $flashcardsid) {
    global $DB;

    $sql = "SELECT currentbox, count(id) countid FROM {flashcards_q_stud_rel} " .
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
 * @param int $flashcardsid activity number
 * @return array
 */
function create_boxvalue_array($records, $id, $boxzerocount, $flashcardsid) {
    $boxtext = get_string('box', 'mod_flashcards');
    $boxindex = 1;

    $boxvalues['boxtext'] = get_string('boxzero', 'mod_flashcards');
    $boxvalues['count'] = $boxzerocount;
    $boxvalues['redirecturl'] = new moodle_url('/mod/flashcards/studentquestioninit.php', ['id' => $id]);
    $boxvalues['flashcardsid'] = $flashcardsid;

    $boxarray[] = $boxvalues;

    foreach ($records as $record) {

        while ($record->currentbox != $boxindex) {
            $boxvalues['boxtext'] = $boxtext . $boxindex;
            $boxvalues['count'] = 0;
            $boxvalues['redirecturl'] = null;

            $boxarray[] = $boxvalues;
            $boxindex++;
        }

        if ($record->currentbox = $boxindex) {
            $boxvalues['boxtext'] = $boxtext . $boxindex;
            $boxvalues['count'] = $record->countid;
            $boxvalues['redirecturl'] = new moodle_url('/mod/flashcards/studentquiz.php', ['id' => $id, 'box' => $boxindex]);

            $boxarray[] = $boxvalues;
            $boxindex++;
        }
    }

    while ($boxindex <= 5) {
        $boxvalues['boxtext'] = $boxtext . $boxindex;
        $boxvalues['count'] = 0;
        $boxvalues['redirecturl'] = null;

        $boxarray[] = $boxvalues;
        $boxindex++;
    }

    return $boxarray;
}