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
 * @copyright  2020 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once('locallib.php');

global $PAGE, $OUTPUT, $DB, $CFG, $USER;

$id = required_param('id', PARAM_INT);
$action = optional_param('action', null, PARAM_ALPHA);
$questionid = optional_param('questionid', null, PARAM_INT);
list ($course, $cm) = get_course_and_cm_from_cmid($id, 'flashcards');
$context = context_module::instance($cm->id);
require_login($course, false, $cm);

$PAGE->set_url(new moodle_url("/mod/flashcards/studentquestioninit.php", ['id' => $id]));
$node = $PAGE->settingsnav->find('mod_flashcards', navigation_node::TYPE_SETTING);
if ($node) {
    $node->make_active();
}

if (!has_capability('mod/flashcards:studentview', $context)) {
    echo $OUTPUT->heading(get_string('errornotallowedonpage', 'flashcards'));
    echo $OUTPUT->footer();
    die();
}

$flashcards = $DB->get_record('flashcards', array('id' => $cm->instance));

if ($action == 'delete') {
    mod_flashcards_delete_student_question($questionid, $flashcards, $context);
    $redirecturl = new moodle_url('/mod/flashcards/studentquestioninit.php', array('id' => $id));
    redirect($redirecturl);
    die();
}

$pagetitle = get_string('pagetitle', 'flashcards');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

$PAGE->requires->js_call_amd('mod_flashcards/questioninit', 'init');
echo $OUTPUT->heading($flashcards->name);

if ($flashcards->inclsubcats) {
    require_once($CFG->dirroot . '/lib/questionlib.php');
    $qcategories = question_categorylist($flashcards->categoryid);
} else {
    $qcategories = $flashcards->categoryid;
}

list($sqlwhere, $qcategories) = $DB->get_in_or_equal($qcategories, SQL_PARAMS_NAMED);
$authordisplay = get_config('flashcards', 'authordisplay');
$sql = "SELECT id,
               questiontext,
               createdby,
               category,
               qtype
          FROM {question} q
         WHERE category $sqlwhere
           AND qtype = 'flashcard'
           AND q.hidden <> 1
           AND id NOT IN (SELECT questionid
                            FROM {flashcards_q_stud_rel}
                           WHERE studentid = :userid
                             AND flashcardsid = :fid)";

$questionstemp = $DB->get_records_sql($sql, $qcategories + ['userid' => $USER->id, 'fid' => $flashcards->id]);
$questions = [];
$authors = mod_flashcards_get_question_authors($questionstemp, $course->id);
foreach ($questionstemp as $question) {
    $row = [];
    $row['qid'] = $question->id;
    $qurl = new moodle_url('/mod/flashcards/studentquestionpreview.php',
            array('id' => $question->id, 'courseid' => $course->id));
    $row['qurl'] = html_entity_decode($qurl->__toString());
    $row['text'] = mod_flashcards_get_preview_questiontext($context, $question);
    $row['deletequestionurl'] = mod_flashcards_get_question_delete_url($id, $context, $flashcards, $question);
    $row['editquestionurl'] = mod_flashcards_get_question_edit_url($id, $context, $flashcards, $question, $cm->id, $course->id, $PAGE->url);
    // Display author group.
    if ($authordisplay) {
        if ($question->createdby) {
            $row['author'] = $authors[$question->createdby];
        } else {
            $row['author'] = get_string('author_unknown');
        }
    }
    $questions[] = $row;
}
$createbuttonvisibility = 'flashcards_add_btn_invisi';
if ($flashcards->addfcstudent == 1) {
    $createbuttonvisibility = 'flashcards_add_btn_visi';
}
$createflashcardurl = new moodle_url('/mod/flashcards/simplequestion.php',
        ['cmid' => $cm->id, 'courseid' => $course->id, 'origin' => $PAGE->url]);
$templateinfo = ['questions' => $questions, 'aid' => $flashcards->id, 'cmid' => $cm->id, 'createfcurl' => $createflashcardurl,
        'cbvis' => $createbuttonvisibility, 'displayauthorcolumn' => $authordisplay];
$renderer = $PAGE->get_renderer('core');

echo $renderer->render_from_template('mod_flashcards/studentinitboxview', $templateinfo);
echo $OUTPUT->footer();
