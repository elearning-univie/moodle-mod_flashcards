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
 * Page where students can create flashcards
 *
 * @package    mod_flashcards
 * @copyright  2021 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/formslib.php');
require_once('locallib.php');

global $USER, $DB, $PAGE, $COURSE, $OUTPUT;

$id = optional_param('id', 0, PARAM_INT); // Question id.
$cmid = required_param('cmid', PARAM_INT);
$categoryid = optional_param('category', 0, PARAM_INT);
$origin = required_param('origin', PARAM_URL);
$action = required_param('action', PARAM_ALPHA);
$fcid = required_param('fcid', PARAM_INT);

$url = new moodle_url('/mod/flashcards/simplequestion.php',
    ['cmid' => $cmid, 'origin' => $origin, 'action' => $action, 'fcid' => $fcid]);
if ($id) {
    $url->param('id', $id);
}
$PAGE->set_url($url);

list($module, $cm) = get_module_from_cmid($cmid);
require_login($cm->course, false, $cm);
$context = context_module::instance($cmid);

$PAGE->set_pagelayout('admin');
$context = context_module::instance($cm->id);

if (has_capability('mod/flashcards:editallquestions', $context)) {
    if ($categoryid === 0) {
        $categoryid = $module->categoryid;
    }
} else if (has_capability('mod/flashcards:editownquestion', $context)) {
    if ($module->addfcstudent == 0) {
        $PAGE->set_title('Errorrrr');
        $PAGE->set_heading($COURSE->fullname);
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('errornotallowedonpage', 'flashcards'));
        echo $OUTPUT->footer();
        die();
    }

    $categoryid = $module->studentsubcat;
}

$qtype = 'flashcard';

if ($id) {
    if (!$question = $DB->get_record('question', ['id' => $id])) {
        throw new \moodle_exception('questiondoesnotexist', 'question', $origin);
    }
    // We can use $COURSE here because it's been initialised as part of the
    // require_login above. Passing it as the third parameter tells the function
    // to filter the course tags by that course.
    get_question_options($question, true, [$COURSE]);
} else if ($categoryid) {
    $question = new stdClass();
    $question->category = $categoryid;
    $question->qtype = $qtype;
    $question->createdby = $USER->id;
    if (!question_bank::qtype_enabled($qtype)) {
        throw new \moodle_exception('cannotenable', 'question', $origin, $qtype);
    }
    $question->options = new stdClass();
}

$qtypeobj = question_bank::get_qtype($question->qtype);

if (isset($question->categoryobject)) {
    $category = $question->categoryobject;
} else {
    if (!$category = $DB->get_record('question_categories', ['id' => $question->category])) {
        throw new \moodle_exception('categorydoesnotexist', 'question', $origin);
    }
}

$question->formoptions = new stdClass();
$question->contextid = $category->contextid;
$question->formoptions->canaddwithcat = question_has_capability_on($question, 'add');

$formeditable = true;

$PAGE->set_pagetype('question-type-flashcard');
$mform = new \mod_flashcards\form\simplequestionform($url, $question, $category, $action, $formeditable);

$questioncopy = fullclone($question);
$questioncopy->category = "{$category->id},{$category->contextid}";
$questioncopy->cmid = $cm->id;

$mform->set_data($questioncopy);

if ($mform->is_cancelled()) {
    redirect($origin);
} else if ($fromform = $mform->get_data()) {
    // Because we only have certain fields wie completely ignore the form object and ony save the ones in the form.
    $questioncopy->name = $fromform->name;
    $questioncopy->questiontext = $fromform->questiontext;
    $questioncopy->answer = $fromform->answer;

    $question = $qtypeobj->save_question($question, $questioncopy);

    $params = [
        'objectid' => $question->id,
        'context' => context_module::instance( $cm->id ),
    ];
    if ($action == 'create') {
        $params['other'] = [
            'changeextent' => 0,
            'fcid' => $fcid,
        ];
        $event = \mod_flashcards\event\simplequestion_created::create($params);
    } else {
        $params['other'] = [
            'changeextent' => $fromform->changeextent,
            'fcid' => $fcid,
            'userid' => $USER->id,
        ];
        $event = \mod_flashcards\event\simplequestion_updated::create($params);
    }
    $event->trigger();
    question_bank::notify_question_edited($question->id);

    if (strpos($origin, '?id=')) {
        $origin = new moodle_url('/mod/flashcards/flashcardpreview.php',
            ['id' => $question->id, 'cmid' => $context->instanceid, 'flashcardsid' => $fcid]);
    }
    if ($qtypeobj->finished_edit_wizard($fromform)) {
        redirect($origin);
    }
}

$streditingquestion = $qtypeobj->get_heading();
$PAGE->set_title($streditingquestion);
$PAGE->set_heading($COURSE->fullname);
$PAGE->navbar->add($streditingquestion);
$activityheader = $PAGE->activityheader;
$activityheader->set_attrs([
    'description' => '',
    'hidecompletion' => true,
]);

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
