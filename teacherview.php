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
 * Flashcards Teacher view
 *
 * @package    mod_flashcards
 * @copyright  2019 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');

global $PAGE, $OUTPUT, $DB, $CFG, $COURSE;

$id = required_param('id', PARAM_INT);
list ($course, $cm) = get_course_and_cm_from_cmid($id, 'flashcards');
$context = context_module::instance($cm->id);

require_login($course, false, $cm);

if (!has_capability('mod/flashcards:teacherview', $context) ) {
    $PAGE->set_url(new moodle_url("/mod/flashcards/teacherview.php", ['id' => $id]));
    $node = $PAGE->settingsnav->find('mod_flashcards', navigation_node::TYPE_SETTING);
    if ($node) {
        $node->make_active();
    }
    $pagetitle = get_string('pagetitle', 'flashcards');
    $PAGE->set_title($pagetitle);
    $PAGE->set_heading($course->fullname);

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('errornotallowedonpage', 'flashcards'));
    echo $OUTPUT->footer();
    die();
}

$flashcards = $DB->get_record('flashcards', array('id' => $cm->instance));

if ($flashcards->inclsubcats) {
    require_once($CFG->dirroot."/lib/questionlib.php");
    $qcategories = question_categorylist($flashcards->categoryid);
} else {
    $qcategories = $flashcards->categoryid;
}

list($sqlwhere, $qcategories) = $DB->get_in_or_equal($qcategories);
$sqlwhere = "category $sqlwhere";
$sql = "SELECT id, name
           FROM   {question}
           WHERE  $sqlwhere";
$questionstemp = $DB->get_records_sql($sql, $qcategories);

$baseurl = $CFG->wwwroot.'/question/question.php';
$returnurl = '/mod/flashcards/teacherview.php?id='.$id;

$questions = array();
foreach ($questionstemp as $question) {
    $qurl = new moodle_url('/question/preview.php', array('id' => $question->id, 'courseid' => $course->id ));
    $editurl = new moodle_url('/question/question.php', array('returnurl' => $returnurl, 'courseid' => $course->id, 'id' => $question->id ));
    $deleteurl = new moodle_url('/question/edit.php', array('returnurl' => $returnurl, 'courseid' => $course->id,
        'deleteselected' => $question->id, 'q'.$question->id => 1, 'sesskey' => sesskey()));

    $questions[] = ['name' => $question->name,
        'qurl' => html_entity_decode($qurl->__toString()),
        'deleteurl' => html_entity_decode($deleteurl->__toString()),
        'editurl' => html_entity_decode($editurl->__toString())
    ];
}
$PAGE->set_url(new moodle_url("/mod/flashcards/teacherview.php", ['id' => $id]));
$node = $PAGE->settingsnav->find('mod_flashcards', navigation_node::TYPE_SETTING);
if ($node) {
    $node->make_active();
}

$pagetitle = get_string('pagetitle', 'flashcards');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($flashcards->name);

$params = array(
    'courseid' => $course->id,
    'category' => $flashcards->categoryid,
    'sesskey' => sesskey(),
    'qtype' => 'flashcard',
    'returnurl' => $returnurl,
);

$link = new moodle_url($baseurl, $params);

$templateinfo = ['btnlabel' => get_string('addflashcardbutton', 'flashcards' ),
    'btnlink' => html_entity_decode($link->__toString()),
    'qlabel' => get_string('question', 'flashcards'),
    'questions' => $questions];

$renderer = $PAGE->get_renderer('core');
echo $renderer->render_from_template('mod_flashcards/teacherview', $templateinfo);

echo $OUTPUT->footer();
