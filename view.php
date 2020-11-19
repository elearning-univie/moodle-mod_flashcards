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
 * Flashcards view
 *
 * @package    mod_flashcards
 * @copyright  2019 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require_once(__DIR__ . '/lib.php');

global $PAGE, $OUTPUT, $DB;

$id = required_param('id', PARAM_INT);
list ($course, $cm) = get_course_and_cm_from_cmid($id, 'flashcards');

$context = context_module::instance($cm->id);

require_login($course, false, $cm);

$flashcards = $DB->get_record('flashcards', array('id' => $cm->instance));

$PAGE->set_url(new moodle_url("/mod/flashcards/view.php", ['id' => $id]));

if (has_capability('mod/flashcards:studentview', $context) ) {
    $redirecturl = new moodle_url('/mod/flashcards/studentview.php', array('id' => $id));
    redirect($redirecturl);
} elseif (has_capability('mod/flashcards:teacherview', $context) ) {
    $redirecturl = new moodle_url('/mod/flashcards/teacherview.php', array('id' => $id));
    redirect($redirecturl);
} else {
    $node = $PAGE->settingsnav->find('mod_flashcards', navigation_node::TYPE_SETTING);
    if ($node) {
        $node->make_active();
    }

    $pagetitle = get_string('pagetitle', 'flashcards');
    $PAGE->set_title($pagetitle);
    $PAGE->set_heading($course->fullname);

    echo $OUTPUT->header();
    echo $OUTPUT->heading($flashcards->name);
    echo $OUTPUT->footer();
}

