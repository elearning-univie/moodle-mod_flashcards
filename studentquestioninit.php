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
require_once(__DIR__ . '/../../config.php');

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

if (!has_capability('mod/flashcards:studentview', $context)) {
    echo $OUTPUT->heading(get_string('errornotallowedonpage', 'flashcards'));
    echo $OUTPUT->footer();
    die();
}

$PAGE->requires->js_call_amd('mod_flashcards/questioninit', 'init');
$flashcards = $DB->get_record('flashcards', array('id' => $cm->instance));
echo $OUTPUT->heading($flashcards->name);

if ($flashcards->inclsubcats) {
    require_once($CFG->dirroot . '/lib/questionlib.php');
    $qcategories = question_categorylist($flashcards->categoryid);
} else {
    $qcategories = $flashcards->categoryid;
}

list($sqlwhere, $qcategories) = $DB->get_in_or_equal($qcategories, SQL_PARAMS_NAMED);
$sql = "SELECT id, questiontext
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
    $qurl = new moodle_url('/mod/flashcards/studentquestionpreview.php',
        array('id' => $question->id, 'courseid' => $course->id));

    $questiontext =
        file_rewrite_pluginfile_urls($question->questiontext, 'pluginfile.php', $context->id, 'question', 'questiontext',
            $question->id);
    $questiontext = format_text($questiontext, FORMAT_HTML);

    preg_match_all('/<img[^>]+>/i', $questiontext, $images);

    if (!empty($images)) {
        foreach ($images[0] as $image) {
            preg_match('/alt="(.*?)"/', $image, $imagealt);
            if (!empty($imagealt[1])) {
                $questiontext = str_replace($image, $imagealt[1], $questiontext);
            } else {
                $questiontext = str_replace($image, get_string('noimagetext', 'mod_flashcards'), $questiontext);
            }
        }
    }

    $questiontext = html_to_text($questiontext, 0);

    if (strlen($questiontext) > 30) {
        $questiontext = substr($questiontext, 0, 30) . '...';
    }

    $questions[] = ['text' => $questiontext,
        'qurl' => html_entity_decode($qurl->__toString()),
        'qid' => $question->id
    ];
}
$createbuttonvisibility = 'flashcards_add_btn_invisi';
if ($flashcards->addfcstudent == 1) {
    $createbuttonvisibility = 'flashcards_add_btn_visi';
}
$createflashcardurl = new moodle_url('/mod/flashcards/studentcreatequestion.php', ['cmid' => $cm->id, 'courseid' => $course->id]);
$templateinfo = ['questions' => $questions, 'aid' => $flashcards->id, 'cmid' => $cm->id, 'createfcurl' => $createflashcardurl,
    'cbvis' => $createbuttonvisibility];
$renderer = $PAGE->get_renderer('core');

echo $renderer->render_from_template('mod_flashcards/studentinitboxview', $templateinfo);
echo $OUTPUT->footer();