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

list ($course, $cm) = get_course_and_cm_from_cmid($id, 'flashcards');
$context = context_module::instance($cm->id);
require_login($course, false, $cm);

$PAGE->set_url(new moodle_url("/mod/flashcards/teacherview.php", ['id' => $id]));
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
    $qurl = new moodle_url('/question/preview.php', array('id' => $question->id, 'courseid' => $course->id));
    $eurl = new moodle_url('/question/question.php',
        array('returnurl' => $returnurl, 'courseid' => $course->id, 'id' => $question->id ));
    $durl = new moodle_url('/mod/flashcards/teacherview.php',
            array('id' => $id, 'deleteselected' => $question->id, 'qname' => $question->name, 'sesskey' => sesskey()));
    $row = [];
    $row['name'] = $question->name;
    $row['qurl'] = html_entity_decode($qurl->__toString());
    $row['editurl'] = html_entity_decode($eurl->__toString());

    $row['deleteurl'] = html_entity_decode($durl->__toString());
    $row['author'] = $authors[$question->createdby];
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
echo $OUTPUT->footer();
