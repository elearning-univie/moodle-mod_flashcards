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
 * @copyright  2020 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/fastcreatequestionform.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/formslib.php');

global $USER, $DB, $PAGE, $COURSE, $OUTPUT;

$id = optional_param('id', 0, PARAM_INT); // question id
$cmid = required_param('cmid', PARAM_INT);
$origin = required_param('origin', PARAM_URL);

$url = new moodle_url('/mod/flashcards/simplequestion.php', ['cmid' => $cmid, 'origin' => $origin]);
if ($cmid) {
    $url->param('cmid', $cmid);
}
if ($origin) {
    $url->param('origin', $origin);
}
if($id) {
    $url->param('id', $id);
}
$PAGE->set_url($url);

list($module, $cm) = get_module_from_cmid($cmid);
require_login($cm->course, false, $cm);
$context = context_module::instance($cmid);

$PAGE->set_pagelayout('admin');
$context = context_module::instance($cm->id);

if (has_capability('mod/flashcards:teacherview', $context)) {
    $categoryid = $module->studentsubcat;
} else if (has_capability('mod/flashcards:studentview', $context)) {
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
    if (!$question = $DB->get_record('question', array('id' => $id))) {
        print_error('questiondoesnotexist', 'question', $origin);
    }
    // We can use $COURSE here because it's been initialised as part of the
    // require_login above. Passing it as the third parameter tells the function
    // to filter the course tags by that course.
    get_question_options($question, true, [$COURSE]);
} elseif ($categoryid) {
    $question = new stdClass();
    $question->category = $categoryid;
    $question->qtype = $qtype;
    $question->createdby = $USER->id;

    if (!question_bank::qtype_enabled($qtype)) {
        print_error('cannotenable', 'question', $origin, $qtype);
    }

}

$qtypeobj = question_bank::get_qtype($question->qtype);

if (isset($question->categoryobject)) {
    $category = $question->categoryobject;
} else {
    if (!$category = $DB->get_record('question_categories', array('id' => $question->category))) {
        print_error('categorydoesnotexist', 'question', $origin);
    }
}

$question->formoptions = new stdClass();

$categorycontext = context::instance_by_id($category->contextid);
$question->contextid = $category->contextid;
$addpermission = has_capability('moodle/question:add', $categorycontext);

$question->formoptions->canedit = true;
$question->formoptions->canmove = false;
$question->formoptions->cansaveasnew = false;
$question->formoptions->repeatelements = true;
$formeditable = true;

$PAGE->set_pagetype('question-type-' . $question->qtype);
$mform = new fastcreatequestionform($url, $question, $category, $formeditable);

$toform = fullclone($question);
$toform->category = "{$category->id},{$category->contextid}";

if ($cm !== null) {
    $toform->cmid = $cm->id;
    $toform->courseid = $cm->course;
} else {
    $toform->courseid = $COURSE->id;
}

$mform->set_data($toform);

if ($mform->is_cancelled()) {
    redirect($origin);
} else if ($fromform = $mform->get_data()) {
    $contextid = $category->contextid;

    $question = $qtypeobj->save_question($question, $fromform);
    if (isset($fromform->tags)) {
        core_tag_tag::set_item_tags('core_question', 'question', $question->id,
                context::instance_by_id($contextid), $fromform->tags, 0);
    }

    if (isset($fromform->coursetags)) {
        core_tag_tag::set_item_tags('core_question', 'question', $question->id,
                context_course::instance($fromform->courseid), $fromform->coursetags, 0);
    }

    question_bank::notify_question_edited($question->id);

    if ($qtypeobj->finished_edit_wizard($fromform)) {
        redirect($origin);
    }
}

$streditingquestion = $qtypeobj->get_heading();
$PAGE->set_title($streditingquestion);
$PAGE->set_heading($COURSE->fullname);
$PAGE->navbar->add($streditingquestion);

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
