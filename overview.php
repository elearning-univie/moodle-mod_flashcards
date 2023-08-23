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
 * Flashcards overview for teachers
 *
 * @package    mod_flashcards
 * @copyright  2023 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');

global $PAGE, $OUTPUT, $DB, $CFG;

$cmid = required_param('cmid', PARAM_INT);

$params = array();
$params['cmid'] = $cmid;

list ($course, $cm) = get_course_and_cm_from_cmid($cmid, 'flashcards');
$context = context_module::instance($cm->id);
require_login($course, false, $cm);

$pageurl = new moodle_url("/mod/flashcards/overview.php", $params);

$PAGE->set_url($pageurl);
$node = $PAGE->settingsnav->find('mod_flashcards', navigation_node::TYPE_SETTING);
if ($node) {
    $node->make_active();
}

$pagetitle = get_string('pagetitle', 'flashcards');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);
$PAGE->add_body_class('limitedwidth');

if (!has_capability('mod/flashcards:teacherview', $context)) {
    if (has_capability('mod/flashcards:view', $context) ) {
        redirect(new moodle_url('/mod/flashcards/studentview.php', ['id' => $cmid]));
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('errornotallowedonpage', 'flashcards'));
    echo $OUTPUT->footer();
    die();
}

$flashcards = $DB->get_record('flashcards', ['id' => $cm->instance]);

$sql = "SELECT count(q.id)
              FROM {question} q,
                   {flashcards_q_status} s
             WHERE q.id = s.questionid
               AND fcid = :fcid";
$totalquestioncount = $DB->count_records_sql($sql, ['fcid' => $flashcards->id]);

$sql = "SELECT count(q.id)
              FROM {question} q,
                   {flashcards_q_status} s
             WHERE q.id = s.questionid
               AND fcid = :fcid
               AND teachercheck = 0";
$newquestioncount = $DB->count_records_sql($sql, ['fcid' => $flashcards->id]);

$sql = "SELECT count(fqs.id)
              FROM {flashcards} fc,
                   {question_categories} qc,
                   {question_bank_entries} qbe,
                   {flashcards_q_status} fqs,
                   {question} q
             WHERE fc.id = :fcid
               AND qc.parent = fc.categoryid
               AND qc.name = :qcname
               AND qc.id = qbe.questioncategoryid
               AND qbe.id = fqs.qbankentryid
               AND q.id = fqs.questionid";
$studentquestioncount = $DB->count_records_sql($sql, ['fcid' => $flashcards->id, 'qcname' => get_string('createdbystudents', 'mod_flashcards')]);


$filterlink1 = new moodle_url('/mod/flashcards/teacherview.php', ['cmid' => $cm->id, 'filter' => 1]);
$filterlink2 = new moodle_url('/mod/flashcards/teacherview.php', ['cmid' => $cm->id, 'filter' => 2]);
$filterlink3 = new moodle_url('/mod/flashcards/teacherview.php', ['cmid' => $cm->id, 'filter' => 3]);
$filterlink4 = new moodle_url('/mod/flashcards/teacherview.php', ['cmid' => $cm->id, 'filter' => 4]);

$tabledata = [
  ['text' => get_string('overviewall', 'flashcards'), 'link' => $filterlink1->out(false), 'value' => $totalquestioncount],
    ['text' => get_string('overviewtq', 'flashcards'), 'link' => $filterlink2->out(false), 'value' => $totalquestioncount - $studentquestioncount],
    ['text' => get_string('overviewsq', 'flashcards'), 'link' => $filterlink3->out(false), 'value' => $studentquestioncount],
    ['text' => get_string('overviewnq', 'flashcards'), 'link' => $filterlink4->out(false), 'value' => $newquestioncount]
];

$params = ['action' => 'create', 'cmid' => $cm->id, 'courseid' => $course->id, 'origin' => $PAGE->url, 'fcid' => $flashcards->id];
$createbtnlink = new moodle_url('/mod/flashcards/simplequestion.php', $params);
$teacherviewlink = new moodle_url('/mod/flashcards/teacherview.php', ['cmid' => $cm->id]);
$studentviewlink = new moodle_url('/mod/flashcards/studentview.php', ['id' => $cm->id]);

$templateinfo = ['createbtnlink' => $createbtnlink->out(false),
    'teacherviewlink' => $teacherviewlink->out(false),
    'studentviewlink' => $studentviewlink->out(false),
    'listentries' => array_values($tabledata)
];

$renderer = $PAGE->get_renderer('core');

echo $OUTPUT->header();
echo $renderer->render_from_template('mod_flashcards/overview', $templateinfo);
echo $OUTPUT->footer();
