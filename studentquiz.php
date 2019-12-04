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

require('../../config.php');

global $PAGE, $OUTPUT, $COURSE, $USER;

$id = required_param('id', PARAM_INT);
$box = required_param('box', PARAM_INT);
list ($course, $cm) = get_course_and_cm_from_cmid($id, 'flashcards');

$context = context_module::instance($cm->id);

require_login($course, false, $cm);

$flashcards = $DB->get_record('flashcards', array('id' => $cm->instance));

$PAGE->set_url(new moodle_url("/mod/flashcards/studentquiz.php", ['id' => $id, 'box' => $box]));
$node = $PAGE->settingsnav->find('mod_flashcards', navigation_node::TYPE_SETTING);

if ($node) {
    $node->make_active();
}

$pagetitle = get_string('pagetitle', 'flashcards');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

$quba = question_engine::make_questions_usage_by_activity('mod_flashcards', $context);
$quba->set_preferred_behaviour('immediatefeedback');
$questionid = get_question_for_student_course_box($USER->id, $box);
$question = question_bank::load_question($questionid);
$quba->add_question($question, 1);
$quba->start_all_questions();
question_engine::save_questions_usage_by_activity($quba);
#echo '<input type="hidden" name="slots" value="' . implode(',', 1) . "\" />\n";
#echo '<input type="hidden" name="scrollpos" value="" />';

echo $OUTPUT->header();
echo $OUTPUT->heading($flashcards->name);
#echo '<form id="responseform" method="post" action="' . $PAGE->url .
#        '" enctype="multipart/form-data" accept-charset="utf-8">', "\n<div>\n";
$PAGE->requires->js_call_amd('mod_flashcards/studentcontroller','init');
echo '<form id="responseform" method="post" action="javascript:;" onsubmit="$.mod_flashcards_call_update()" enctype="multipart/form-data" accept-charset="utf-8">', "\n<div>\n";

$jsmodule = array(
        'name' => 'core_question_engine',
        'fullpath' => '/question/qengine.js'
);

$PAGE->requires->js_init_call('M.core_question_engine.init_form',
        array('#responseform'), false, $jsmodule);

$options = new question_display_options();
$options->marks = question_display_options::MAX_ONLY;
$options->markdp = 2;
$options->feedback = question_display_options::VISIBLE;
$options->generalfeedback = question_display_options::HIDDEN;

echo $quba->render_question(1, $options);
echo $OUTPUT->footer();

function get_question_for_student_course_box($userid, $box) {
    global $DB;
    $i = 0;

    #TODO active alse SMALLINT auf der DB einstellen und im SELECT als 1 abfragen
    $sql =
            "SELECT min(questionid) AS questionid FROM {flashcards_q_stud_rel} q WHERE q.studentid = :userid AND q.currentbox = :box AND q.lastanswered = (SELECT min(lastanswered) FROM {flashcards_q_stud_rel} subq WHERE subq.studentid = q.studentid AND subq.currentbox = q.currentbox AND subq.active = q.active)";
    $records = $DB->get_recordset_sql($sql, ['userid' => $userid, 'box' => $box]);

    foreach ($records as $record) {
        $questionid = $record->questionid;
        $i++;
    }

    if ($i != 1) {
        print_error('noquestion', 'mod_flashcards');
    };

    return $questionid;
}