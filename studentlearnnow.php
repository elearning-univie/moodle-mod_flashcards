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

global $PAGE, $OUTPUT, $USER;

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

if (has_capability('mod/flashcards:studentview', $context)) {
    $flashcards = $DB->get_record('flashcards', array('id' => $cm->instance));
    echo $OUTPUT->heading($flashcards->name);

    $sql = "SELECT count(id)
              FROM {flashcards_q_stud_rel}
             WHERE studentid = :userid
               AND flashcardsid = :fid";

    $qcount = $DB->count_records_sql($sql, ['userid' => $USER->id, 'fid' => $flashcards->id]);

    if ($qcount > 0) {
        $renderer = $PAGE->get_renderer('core');
        echo $renderer->render_from_template('mod_flashcards/studentlearnnow',
            ['qcount' => $qcount, 'flashcardsid' => $flashcards->id]);
        $PAGE->requires->js_call_amd('mod_flashcards/studentrangeslider', 'init');
    } else {
        echo 'Fragen importieren!';
    }

    echo $OUTPUT->footer();
} else {
    echo $OUTPUT->heading(get_string('errornotallowedonpage', 'flashcards'));
    echo $OUTPUT->footer();
    die();
}