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

print("THIS IS STUDENT VIEW.");

$sql = "SELECT currentbox, count(id) FROM {flashcards_q_stud_rel} WHERE studentid = :userid GROUP BY currentbox ORDER BY currentbox";
$records = $DB->get_recordset_sql($sql, ['userid' => 1]);

$boxindex = 0;

foreach ($records as $record) {

  while ($record->currentbox != $boxindex) {
    $boxvalues['currentbox'] = $boxindex;
    $boxvalues['count'] = 0;

    $boxarray[$boxindex] = $boxvalues;
    $boxindex++;
  }

  if ($record->currentbox = $boxindex) {
    $boxvalues['currentbox'] = $boxindex;
    $boxvalues['count'] = $record->count;

    $boxarray[$boxindex] = $boxvalues;
    $boxindex++;
  }
}

while ($boxindex <= 5) {
  $boxvalues['currentbox'] = $boxindex;
  $boxvalues['count'] = 0;

  $boxarray[$boxindex] = $boxvalues;
  $boxindex++;
}

$renderer = $PAGE->get_renderer('core');
$templatestablecontext['boxes'] = $boxarray;
echo $renderer->render_from_template('mod_flashcards/student_view', $templatestablecontext);

echo $OUTPUT->footer();