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
 * Flashcards teacher view
 *
 * @package    mod_flashcards
 * @copyright  2021 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once('locallib.php');

global $PAGE, $OUTPUT, $DB, $CFG;

$cmid = required_param('cmid', PARAM_INT);
$fqid = optional_param('fcid', null, PARAM_INT);
$deleteselected = optional_param('deleteselected', null, PARAM_INT);
$confirm = optional_param('confirm', null, PARAM_ALPHANUM);
$perpage = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT);

$params = array();
$params['cmid'] = $cmid;

if (!in_array($perpage, [10, 20, 50, 100], true)) {
    $perpage = DEFAULT_PAGE_SIZE;
}
$params['perpage'] = $perpage;

list ($course, $cm) = get_course_and_cm_from_cmid($cmid, 'flashcards');
$context = context_module::instance($cm->id);
require_login($course, false, $cm);

$pageurl = new moodle_url("/mod/flashcards/teacherview.php", $params);

$PAGE->set_url($pageurl);
$node = $PAGE->settingsnav->find('mod_flashcards', navigation_node::TYPE_SETTING);
if ($node) {
    $node->make_active();
}

$pagetitle = get_string('pagetitle', 'flashcards');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

if (!has_capability('mod/flashcards:teacherview', $context)) {
    if (has_capability('mod/flashcards:studentview', $context) ) {
        redirect(new moodle_url('/mod/flashcards/studentview.php', array('id' => $cmid)));
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('errornotallowedonpage', 'flashcards'));
    echo $OUTPUT->footer();
    die();
}

list($thispageurl, $contexts, $cmid, $cm, $quiz, $pagevars) =
    question_edit_setup('editq', '/mod/flashcards/teacherview.php', true);

if ($deleteselected) {
    if (!$DB->record_exists('question', ['id' => $deleteselected])) {
        redirect($PAGE->url);
    }

    if ($confirm == md5($deleteselected)) {
        $questionid = $deleteselected;
        question_require_capability_on($questionid, 'edit');

        $DB->delete_records('flashcards_q_stud_rel', ['fqid' => $fqid]);
        $DB->delete_records('flashcards_q_status', ['id' => $fqid]);
        redirect($PAGE->url);
    } else {
        $deleteurl = new moodle_url('/mod/flashcards/teacherview.php',
            array('cmid' => $cmid, 'deleteselected' => $deleteselected, 'sesskey' => sesskey(), 'confirm' => md5($deleteselected), 'fcid' => $fqid));

        $continue = new \single_button($deleteurl, get_string('removeflashcard', 'mod_flashcards'), 'post');
        $questionname = $DB->get_field('question', 'name', ['id' => $deleteselected]);

        echo $OUTPUT->header();
        echo $OUTPUT->confirm(get_string('removeflashcadcheck', 'mod_flashcards', $questionname), $continue, $PAGE->url);
        echo $OUTPUT->footer();
        die();
    }
}

$flashcards = $DB->get_record('flashcards', array('id' => $cm->instance));

if (!$DB->record_exists("question_categories", array('id' => $flashcards->categoryid))) {
    $editpage = new moodle_url('/course/modedit.php', array('update' => $cm->id, 'return' => 0, 'sr' => 0, 'missingcategory' => 1));
    redirect($editpage, get_string('categorymissing', 'flashcards'), null, \core\output\notification::NOTIFY_WARNING);
}

$sqlwhere = "fcid = " . $flashcards->id . " AND qtype = 'flashcard'
AND qv.version = (SELECT MAX(v.version)
    FROM {question_versions} v
    WHERE qv.questionbankentryid = v.questionbankentryid) ";

$table = new mod_flashcards\output\teacherviewtable('uniqueid', $cm->id, $flashcards->course, $PAGE->url);

$table->set_sql("q.id, name, q.questiontext, qv.version, q.createdby, q.timemodified, teachercheck, fcs.id fqid, fcs.fcid flashcardsid,
    (SELECT COUNT(sd.id) FROM {flashcards_q_stud_rel} sd WHERE sd.fqid = fcs.id AND sd.peerreview = 1) upvotes,
    (SELECT COUNT(sd.id) FROM {flashcards_q_stud_rel} sd WHERE sd.fqid = fcs.id AND sd.peerreview = 2) downvotes",
        "{question} q
        JOIN {question_versions} qv ON qv.questionid = q.id
        JOIN {flashcards_q_status} fcs on qv.questionbankentryid = fcs.qbankentryid", $sqlwhere);

$table->define_baseurl($PAGE->url);

$params = ['action' => 'create', 'cmid' => $cm->id, 'courseid' => $course->id, 'origin' => $PAGE->url, 'fcid' => $flashcards->id];
$link = new moodle_url('/mod/flashcards/simplequestion.php', $params);

$renderer = $PAGE->get_renderer('core');

$templateinfo = ['createbtnlink' => $link->out(false),
        'cmid' => $cmid,
        'sesskey' => sesskey(),
        'actionurl' => $PAGE->url];
$templateinfo['selected' . $perpage] = true;

if (has_capability('mod/flashcards:editallquestions', $context)) {
    if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
        $rawdata = (array) data_submitted();
        foreach ($rawdata as $key => $value) {
            if (preg_match('!^q([0-9]+)$!', $key, $matches)) {
                $key = $matches[1];
                mod_flashcards_add_question($key, $flashcards->id);
            }
        }
        redirect($pageurl);
    }

    if (optional_param('addsingle', false, PARAM_BOOL) && confirm_sesskey()) {
        $qid = optional_param('addquestion', 0, PARAM_INT);
        mod_flashcards_add_question($qid, $flashcards->id);
        redirect($pageurl);
    }
}

echo $OUTPUT->header();
echo $renderer->render_from_template('mod_flashcards/teacherview', $templateinfo);

$output = $PAGE->get_renderer('mod_flashcards', 'edit');

echo $output->edit_flashcards($pageurl, $contexts, $pagevars);
$table->out($perpage, false);
echo $OUTPUT->footer();
