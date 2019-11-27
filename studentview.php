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

print("THIS IS STUDENT VIEW.2");

$renderer = $PAGE->get_renderer('core');
$templatestablecontext["wwwroot"] = $CFG->wwwroot;
echo $renderer->render_from_template('mod_flashcards/student_view', $templatestablecontext);

$sql = "SELECT g.id, l.content, g.sticky FROM {tool_gnotify_tpl_ins} g, {tool_gnotify_tpl_lang} l " .
  "WHERE :time between fromdate AND todate AND l.lang = 'en' AND l.tplid = g.tplid AND NOT EXISTS " .
  "(SELECT 1 FROM {tool_gnotify_tpl_ins_ack} a WHERE g.id=a.insid AND a.userid = :userid)";

$records = $DB->get_records_sql($sql, ['time' => time(), 'userid' => $USER->id]);
$htmlcontent = format_text($record->content, FORMAT_HTML, $formatoptions);
$htmlcontent = $renderer->render_direct($htmlcontent, $varray);

echo $OUTPUT->footer();