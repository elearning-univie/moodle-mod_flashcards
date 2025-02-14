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
require_capability('mod/flashcards:view', $context);

$PAGE->set_url(new moodle_url("/mod/flashcards/studentquiz.php", ['id' => $id, 'box' => $box]));
if ($node = $PAGE->settingsnav->find('mod_flashcards', navigation_node::TYPE_SETTING)) {
    $node->make_active();
}

$pagetitle = get_string('pagetitle', 'flashcards');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

$flashcards = $DB->get_record('flashcards', ['id' => $cm->instance]);
$returnurl = new moodle_url('/mod/flashcards/studentview.php', ['id' => $id]);
$qid = mod_flashcards_get_next_question($flashcards->id, $box);
$questionrenderer = $PAGE->get_renderer('mod_flashcards');

if ($box === -1) {
    $boxheader = get_string('boxheaderlearnnow', 'mod_flashcards');
    $lncount = $_SESSION[FLASHCARDS_LN_COUNT . $flashcards->id];
    $lnknown = $_SESSION[FLASHCARDS_LN_KNOWN . $flashcards->id];
    $lnunknown = $_SESSION[FLASHCARDS_LN_UNKNOWN . $flashcards->id];
    $learnprogress = $questionrenderer->render_learn_progress($lncount, $lnknown, $lnunknown);
    $boxdecorationurl = false;

    mod_flashcards_load_xp_events($flashcards->id, true);
} else {
    $boxheader = get_string('boxheader_' . $box, 'mod_flashcards');
    $learnprogress = false;
    $boxdecorationurl = $OUTPUT->image_url('box' . $box . 'deco', 'mod_flashcards');
}

$templatecontent = [
    'returnurl' => $returnurl->out(true),
    'boxheader' => $boxheader,
    'boxdecorationurl' => $boxdecorationurl,
    'learnprogress' => $learnprogress,
    'renderedquestion' => $questionrenderer->render_flashcard($flashcards->id, $USER->id, $box, $qid),
];

echo $OUTPUT->header();
echo $questionrenderer->render_from_template('mod_flashcards/quizview', $templatecontent);
echo $OUTPUT->footer();
