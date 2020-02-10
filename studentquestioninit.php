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
require('../../config.php');

global $PAGE, $OUTPUT, $DB, $CFG, $USER;

$id = required_param('id', PARAM_INT);
list ($course, $cm) = get_course_and_cm_from_cmid($id, 'flashcards');
$context = context_module::instance($cm->id);

require_login($course, false, $cm);

$PAGE->set_url(new moodle_url("/mod/flashcards/studentquestioninit.php", ['id' => $id]));
$node = $PAGE->settingsnav->find('mod_flashcards', navigation_node::TYPE_SETTING);
if ($node) {
    $node->make_active();
}
$pagetitle = get_string('pagetitle', 'flashcards');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

if (has_capability('mod/flashcards:studentview', $context) ) {
    $PAGE->requires->js_call_amd('mod_flashcards/questioninit', 'init');
    $flashcards = $DB->get_record('flashcards', array('id' => $cm->instance));
    echo $OUTPUT->heading($flashcards->name);

    if ($flashcards->inclsubcats) {
        require_once($CFG->dirroot."/lib/questionlib.php");
        $qcategories = question_categorylist($flashcards->categoryid);
    } else {
        $qcategories = $flashcards->categoryid;
    }

    list($sqlwhere, $qcategories) = $DB->get_in_or_equal($qcategories, SQL_PARAMS_NAMED);
    $sql = "SELECT id, name
              FROM {question} q
             WHERE category $sqlwhere
               AND qtype = 'flashcard'
               AND id NOT IN (SELECT questionid
                                FROM {flashcards_q_stud_rel}
                               WHERE studentid = :userid
                                 AND flashcardsid = :fid)";

    $questionstemp = $DB->get_records_sql($sql, $qcategories + ['userid' => $USER->id, 'fid' => $flashcards->id]);
    $questions = array();

    foreach ($questionstemp as $question) {
        $qurl = new moodle_url('/question/preview.php', array('id' => $question->id, 'courseid' => $course->id ));

        $questions[] = ['name' => $question->name,
                'qurl' => html_entity_decode($qurl->__toString()),
                'qid' => $question->id
        ];
    }

    $templateinfo = ['questions' => $questions, 'aid' => $flashcards->id, 'cmid' => $cm->id];
    $renderer = $PAGE->get_renderer('core');

    echo $renderer->render_from_template('mod_flashcards/studentinitboxview', $templateinfo);
    echo $OUTPUT->footer();
} else {
    echo $OUTPUT->heading(get_string('errornotallowedonpage', 'flashcards'));
    echo $OUTPUT->footer();
    die();
}