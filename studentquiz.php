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
 * Question View
 *
 * @package    mod_flashcards
 * @copyright  2021 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

global $PAGE, $OUTPUT, $USER, $DB;

$id = required_param('id', PARAM_INT);
$box = required_param('box', PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_cmid($id, 'flashcards');
$context = context_module::instance($cm->id);

require_login($course, false, $cm);

$PAGE->set_url(new moodle_url("/mod/flashcards/studentquiz.php", ['id' => $id, 'box' => $box]));
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

    $qid = mod_flashcards_get_next_question($flashcards->id, $box);
    $questionrenderer = $PAGE->get_renderer('mod_flashcards');

    $questionhtml = '<div>';
    if ($box == -1) {
        $lncount = $_SESSION[FLASHCARDS_LN_COUNT . $flashcards->id];
        $lnknown = $_SESSION[FLASHCARDS_LN_KNOWN . $flashcards->id];
        $lnunknown = $_SESSION[FLASHCARDS_LN_UNKNOWN . $flashcards->id];
        $questionhtml .= '<div id="mod-flashcards-learning-progress" class="progress mb-2">';
        $questionhtml .= $questionrenderer->render_learn_progress($lncount, $lnknown, $lnunknown);
        $questionhtml .= '</div>';
    }
    $questionhtml .= '<div id="mod-flashcards-question">';
    $questionhtml .= $questionrenderer->render_flashcard($flashcards->id, $USER->id, $box, $qid);
    $questionhtml .= '</div>';
    $questionhtml .= '</div>';

    echo $questionhtml;
    echo $OUTPUT->footer();
} else {
    echo $OUTPUT->heading(get_string('errornotallowedonpage', 'flashcards'));
    echo $OUTPUT->footer();
    die();
}
