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
 * @copyright  2020 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once('locallib.php');

global $PAGE, $OUTPUT, $DB, $CFG;

$id = required_param('id', PARAM_INT);
$deleteselected = optional_param('deleteselected', null, PARAM_INT);
$confirm = optional_param('confirm', null, PARAM_ALPHANUM);
$perpage = optional_param('perpage', null, PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_cmid($id, 'flashcards');
$context = context_module::instance($cm->id);
require_login($course, false, $cm);

$baseurl = new moodle_url("/mod/flashcards/teacherview.php", ['id' => $id]);

$PAGE->set_url($baseurl);
$node = $PAGE->settingsnav->find('mod_flashcards', navigation_node::TYPE_SETTING);
if ($node) {
    $node->make_active();
}

$pagetitle = get_string('pagetitle', 'flashcards');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

if (!has_capability('mod/flashcards:teacherview', $context) ) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('errornotallowedonpage', 'flashcards'));
    echo $OUTPUT->footer();
    die();
}

if ($deleteselected) {
    if (!$DB->record_exists('question', ['id' => $deleteselected])) {
        redirect($PAGE->url);
    }

    if ($confirm == md5($deleteselected)) {
        $questionid = $deleteselected;
        question_require_capability_on($questionid, 'edit');

        if (questions_in_use(array($questionid))) {
            $DB->set_field('question', 'hidden', 1, array('id' => $questionid));
        } else {
            question_delete_question($questionid);
        }
        $DB->delete_records('flashcards_q_stud_rel', ['questionid' => $questionid]);
        redirect($PAGE->url);
    } else {
        $deleteurl = new moodle_url('/mod/flashcards/teacherview.php',
                array('id' => $id, 'deleteselected' => $deleteselected, 'sesskey' => sesskey(), 'confirm' => md5($deleteselected)));

        $continue = new \single_button($deleteurl, get_string('delete'), 'post');
        $questionname = $DB->get_field('question', 'name', ['id' => $deleteselected]);

        echo $OUTPUT->header();
        echo $OUTPUT->confirm(get_string('deletequestionscheck', 'question', $questionname), $continue, $PAGE->url);
        echo $OUTPUT->footer();
        die();
    }
}

$flashcards = $DB->get_record('flashcards', array('id' => $cm->instance));

if ($flashcards->inclsubcats) {
    require_once($CFG->dirroot . '/lib/questionlib.php');
    $qcategories = question_categorylist($flashcards->categoryid);
} else {
    $qcategories = $flashcards->categoryid;
}

list($sqlwhere, $qcategories) = $DB->get_in_or_equal($qcategories);
$sqlwhere = "category $sqlwhere AND qtype = 'flashcard'";

$table = new mod_flashcards\output\teacherviewtable('uniqueid', $cm->id, $course->id, $flashcards->id);

$table->set_sql('id, name, createdby', "{question}", $sqlwhere, $qcategories);
$table->define_baseurl($baseurl);

$params = ['cmid' => $cm->id, 'courseid' => $course->id, 'origin' => $PAGE->url];
$link = new moodle_url('/mod/flashcards/simplequestion.php', $params);

$renderer = $PAGE->get_renderer('core');

$templateinfo = ['createbtnlink' => $link->out(false)];
$templateinfo['id'] = $id;
$templateinfo['sesskey'] = sesskey();
$templateinfo['actionurl'] = $baseurl;

if ($perpage !== null) {
    $templateinfo['selected' . $perpage] = true;
} else {
    $templateinfo['selected20'] = true;
}

echo $OUTPUT->header();
echo $OUTPUT->heading($flashcards->name);
echo $renderer->render_from_template('mod_flashcards/teacherview', $templateinfo);
$table->out($perpage, false);
echo $OUTPUT->footer();







/*list($sqlwhere, $qcategories) = $DB->get_in_or_equal($qcategories);
$sqlwhere = "category $sqlwhere";
$sql = "SELECT id, name, createdby
          FROM {question}
         WHERE $sqlwhere
           AND qtype = 'flashcard'";

$questionstemp = $DB->get_records_sql($sql, $qcategories);
$authors = mod_flashcards_get_question_authors($questionstemp, $course->id, FLASHCARDS_AUTHOR_NAME);

$returnurl = '/mod/flashcards/teacherview.php?id=' . $id;
$questions = array();
foreach ($questionstemp as $question) {
    $qurl = new moodle_url('/mod/flashcards/flashcardpreview.php', array('id' => $question->id, 'cmid' => $cm->id, 'fcid' => $flashcards->id));
    $eurl = new moodle_url('/question/question.php',
        array('returnurl' => $returnurl, 'courseid' => $course->id, 'id' => $question->id ));
    $durl = new moodle_url('/mod/flashcards/teacherview.php',
            array('id' => $id, 'deleteselected' => $question->id, 'sesskey' => sesskey()));
    $row = [];
    $row['name'] = $question->name;
    $row['qurl'] = html_entity_decode($qurl->__toString());
    $row['editurl'] = html_entity_decode($eurl->__toString());

    $row['deleteurl'] = html_entity_decode($durl->__toString());
    $row['author'] = $authors[$question->createdby];

    $teachercheckresult = mod_flashcard_get_teacher_check_result($question->id, $flashcards->id, $course->id);
    $checkinfo = mod_flashcard_get_teacher_check_info($teachercheckresult);

    $row['teachercheckcolor'] = $checkinfo['color'];
    $row['teachercheck'] = $checkinfo['icon'];
    $row['peerreview'] = mod_flashcard_peer_review_info_overview($question->id, $flashcards->id);
    $questions[] = $row;
}

$params = ['cmid' => $cm->id, 'courseid' => $course->id, 'origin' => $PAGE->url];
$link = new moodle_url('/mod/flashcards/simplequestion.php', $params);

echo $OUTPUT->header();
echo $OUTPUT->heading($flashcards->name);

$templateinfo = ['createbtnlink' => $link->out(false),
    'qlabel' => get_string('question', 'flashcards'),
    'questions' => $questions];

$renderer = $PAGE->get_renderer('core');

echo $renderer->render_from_template('mod_flashcards/teacherview', $templateinfo);
echo $OUTPUT->footer();*/
