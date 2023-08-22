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
 * Initial load of questions for flashcards
 *
 * @package    mod_flashcards
 * @copyright  2021 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once('locallib.php');

global $PAGE, $OUTPUT, $DB, $CFG, $USER;

$id = required_param('id', PARAM_INT);
$deleteselected = optional_param('deleteselected', null, PARAM_INT);
$confirm = optional_param('confirm', null, PARAM_ALPHANUM);
$perpage = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT);
$tab = optional_param('tab', 'notadded', PARAM_ALPHAEXT);

if (!in_array($perpage, [10, 20, 50, 100], true)) {
    $perpage = DEFAULT_PAGE_SIZE;
}

$params = array();
$params['id'] = $id;
$params['tab'] = $tab;
$params['perpage'] = $perpage;

list ($course, $cm) = get_course_and_cm_from_cmid($id, 'flashcards');
$context = context_module::instance($cm->id);
require_login($course, false, $cm);

$PAGE->set_url(new moodle_url("/mod/flashcards/studentquestioninit.php", $params));
$node = $PAGE->settingsnav->find('mod_flashcards', navigation_node::TYPE_SETTING);
if ($node) {
    $node->make_active();
}

$PAGE->set_title(get_string('pagetitle', 'flashcards'));
$PAGE->set_heading($course->fullname);
$activityheader = $PAGE->activityheader;
$activityheader->set_attrs([
        'description' => '',
        'hidecompletion' => true
]);

if (!has_capability('mod/flashcards:view', $context)) {
    echo $OUTPUT->heading(get_string('errornotallowedonpage', 'flashcards'));
    echo $OUTPUT->footer();
    die();
}

$flashcards = $DB->get_record('flashcards', array('id' => $cm->instance));

if ($deleteselected) {
    if (!$DB->record_exists('question', ['id' => $deleteselected])) {
        redirect($PAGE->url);
    }

    if ($confirm == md5($deleteselected)) {
        $questionid = $deleteselected;
        mod_flashcards_delete_student_question($questionid, $flashcards, $context);
        redirect($PAGE->url);
    } else {
        $deleteurl = new moodle_url('/mod/flashcards/studentquestioninit.php',
            array('id' => $id, 'deleteselected' => $deleteselected, 'sesskey' => sesskey(), 'confirm' => md5($deleteselected)));

        $continue = new \single_button($deleteurl, get_string('delete'), 'post');
        $questionname = $DB->get_field('question', 'name', ['id' => $deleteselected]);

        echo $OUTPUT->header();
        echo $OUTPUT->confirm(get_string('deletequestionscheck', 'question', $questionname), $continue, $PAGE->url);
        echo $OUTPUT->footer();
        die();
    }
}

$PAGE->requires->js_call_amd('mod_flashcards/questioninit', 'init');

if ($flashcards->inclsubcats) {
    require_once($CFG->dirroot . '/lib/questionlib.php');
    $qcategories = question_categorylist($flashcards->categoryid);
} else {
    $qcategories = $flashcards->categoryid;
}
$equalparam = ($tab == 'added') ? true : false;

$sql = "SELECT q.id
          FROM {question} q,
               {flashcards_q_status} fcs,
               {flashcards_q_stud_rel} fsr
         WHERE q.id = fcs.questionid
           AND fcs.id = fsr.fqid
           AND fcs.fcid = :fcid
           AND fsr.studentid = :userid
           AND qtype = 'flashcard'
           AND currentbox IS NOT NULL";
$importedfcs = $DB->get_fieldset_sql($sql, ['fcid' => $flashcards->id, 'userid' => $USER->id]);
$added = count($importedfcs);

if ($added == 0) {
    $importedfcs[] = -1;
}

list($sqlwhereifcs, $importedfcids) = $DB->get_in_or_equal($importedfcs, SQL_PARAMS_NAMED, 'p', $equalparam, true);
$sqlwhere = "fcid =:fcid AND qtype = 'flashcard' AND q.id $sqlwhereifcs
             AND qv.version = (SELECT MAX(v.version)
             FROM {question_versions} v
             WHERE qv.questionbankentryid = v.questionbankentryid)";

$table = new mod_flashcards\output\studentviewtable('uniqueid', $cm->id, $flashcards, $PAGE->url, $tab);
$table->set_sql("q.id, name, fsr.currentbox, q.questiontext, qv.version, q.createdby, q.timemodified, teachercheck,
    fcs.id fqid, fcs.fcid flashcardsid,
    (SELECT COUNT(sd.id) FROM {flashcards_q_stud_rel} sd WHERE sd.fqid = fcs.id AND sd.peerreview = 1) upvotes,
    (SELECT COUNT(sd.id) FROM {flashcards_q_stud_rel} sd WHERE sd.fqid = fcs.id AND sd.peerreview = 2) downvotes",
    "{question} q
    JOIN {question_versions} qv ON qv.questionid = q.id
    JOIN {flashcards_q_status} fcs on qv.questionbankentryid = fcs.qbankentryid
    LEFT JOIN {flashcards_q_stud_rel} fsr ON fsr.fqid = fcs.qbankentryid AND fsr.studentid = $USER->id",
    $sqlwhere, ['fcid' => $flashcards->id] + $importedfcids);

$table->define_baseurl($PAGE->url);

list($sqlwhereifcs, $importedfcids) = $DB->get_in_or_equal($importedfcs, SQL_PARAMS_NAMED, 'p', false, true);
$sqlwhere = "fcid =:fcid AND qtype = 'flashcard' AND q.id $sqlwhereifcs";
$sql = "SELECT COUNT(q.id)
          FROM {question} q
          JOIN {flashcards_q_status} fcs ON q.id = fcs.questionid
         WHERE $sqlwhere";
$notadded = $DB->count_records_sql($sql, ['fcid' => $flashcards->id] + $importedfcids);

$params = ['action' => 'create', 'cmid' => $cm->id, 'courseid' => $course->id, 'origin' => $PAGE->url, 'fcid' => $flashcards->id];
$createurl = new moodle_url('/mod/flashcards/simplequestion.php', $params);
$returnurl = new moodle_url('/mod/flashcards/studentview.php', ['id' => $id]);

if ($equalparam) {
    $collectionchangefunc = '$.mod_flashcards_remove_questions(' . $flashcards->id . ')';
    $collectionchangetext = get_string('removeflashcardbutton', 'mod_flashcards');
    $tabbtnlink = new moodle_url('/mod/flashcards/studentquestioninit.php', ['id' => $id, 'tab' => 'notadded']);
    $tabbtntext = get_string('tabflashcardsnotaddedtip', 'mod_flashcards');
    $headertext = get_string('tabflashcardsaddedtip', 'mod_flashcards');
} else {
    $collectionchangefunc = '$.mod_flashcards_init_questions(' . $flashcards->id . ')';
    $collectionchangetext = get_string('addflashcardbutton', 'mod_flashcards');
    $tabbtnlink = new moodle_url('/mod/flashcards/studentquestioninit.php', ['id' => $id, 'tab' => 'added']);
    $tabbtntext = get_string('tabflashcardsaddedtip', 'mod_flashcards');
    $headertext = get_string('tabflashcardsnotaddedtip', 'mod_flashcards');
    if ($flashcards->addfcstudent == 1) {
        $cbvis = 1;
    }
}

$templateinfo = ['createbtnlink' => $createurl->out(false),
    'backtooverviewlink' => $returnurl->out(false),
    'aid' => $flashcards->id,
    'cmid' => $cm->id,
    'collectionchangefunc' => $collectionchangefunc,
    'collectionchangetext' => $collectionchangetext,
    'tabbtnlink' => $tabbtnlink->out(false),
    'tabbtntext' => $tabbtntext,
    'headertext' => $headertext,
    'cbvis' => $cbvis ?? 0,
    'flashcardcount' => $added
];

$optionsinfo = [
    'id' => $id,
    'sesskey' => sesskey(),
    'actionurl' => $PAGE->url,
    'tab' => $tab,
    'selected' . $perpage => true
];

$renderer = $PAGE->get_renderer('core');

echo $OUTPUT->header();
echo $renderer->render_from_template('mod_flashcards/studentinitboxview', $templateinfo);
$table->out($perpage, false);
echo $renderer->render_from_template('mod_flashcards/optionssection', $optionsinfo);
echo $OUTPUT->footer();
