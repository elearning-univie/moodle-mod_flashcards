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
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

global $PAGE, $OUTPUT, $COURSE, $USER;

$id = required_param('id', PARAM_INT);
list ($course, $cm) = get_course_and_cm_from_cmid($id, 'flashcards');

$context = context_module::instance($cm->id);

require_login($course, false, $cm);

$flashcards = $DB->get_record('flashcards', array('id'=> $cm->instance));

$PAGE->set_url(new moodle_url("/mod/flashcards/view.php", ['id' => $id]));
$node = $PAGE->settingsnav->find('mod_flashcards', navigation_node::TYPE_SETTING);
if ($node) {
  $node->make_active();
}

$pagetitle = get_string('pagetitle', 'flashcards');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($flashcards->name);

print("questionview");

//$options = question_engine::get_behaviour_options(null);
//print_object($options);

$quba = question_engine::make_questions_usage_by_activity('mod_flashcards', $context);
$quba->set_preferred_behaviour('immediatefeedback');
$question = question_bank::load_question(1);
$quba->add_question($question, 10);
$quba->start_all_questions();

$options = new question_display_options();
$options->marks = question_display_options::MAX_ONLY;
$options->markdp = 2; // Display marks to 2 decimal places.
$options->feedback = question_display_options::VISIBLE;
$options->generalfeedback = question_display_options::HIDDEN;

echo $quba->render_question(1, $options, null);

echo $OUTPUT->footer();

echo $OUTPUT->footer();
