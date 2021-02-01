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

$makecopy = optional_param('makecopy', 0, PARAM_BOOL);
$qtype = 'flashcard';
$categoryid = optional_param('category', 0, PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$wizardnow = optional_param('wizardnow', '', PARAM_ALPHA);
$appendqnumstring = optional_param('appendqnumstring', '', PARAM_ALPHA);
$inpopup = optional_param('inpopup', 0, PARAM_BOOL);

$url = new moodle_url('/mod/flashcards/teachercreatequestion.php');
if ($cmid !== 0) {
    $url->param('cmid', $cmid);
}
if ($courseid !== 0) {
    $url->param('courseid', $courseid);
}
$PAGE->set_url($url);

if ($cmid) {
    $initboxurl = new moodle_url('/mod/flashcards/teacherview.php', array('id' => $cmid));
} else {
    $initboxurl = new moodle_url('/mod/flashcards/teacherview.php', array('courseid' => $courseid));
}
navigation_node::override_active_url($initboxurl);

$returnurl = $initboxurl;

$sql = 'SELECT *
          FROM {flashcards}
         WHERE id = (SELECT instance
                       FROM {course_modules}
                      WHERE id = :cmid)';

$rec = $DB->get_record_sql($sql, ['cmid' => $cmid]);

$categoryid = $rec->categoryid;

if ($cmid) {
    list($module, $cm) = get_module_from_cmid($cmid);
    require_login($cm->course, false, $cm);
    $thiscontext = context_module::instance($cmid);
} else if ($courseid) {
    require_login($courseid, false);
    $thiscontext = context_course::instance($courseid);
    $module = null;
    $cm = null;
} else {
    print_error('missingcourseorcmid', 'question');
}

$contexts = new question_edit_contexts($thiscontext);
$PAGE->set_pagelayout('admin');

if (optional_param('addcancel', false, PARAM_BOOL)) {
    redirect($returnurl);
}

if ($categoryid && $qtype) {
    $question = new stdClass();
    $question->category = $categoryid;
    $question->qtype = $qtype;
    $question->createdby = $USER->id;

    if (!question_bank::qtype_enabled($qtype)) {
        print_error('cannotenable', 'question', $returnurl, $qtype);
    }

} else if ($categoryid) {
    $addurl = new moodle_url('/question/addquestion.php', $url->params());
    $addurl->param('validationerror', 1);
    redirect($addurl);

} else {
    print_error('notenoughdatatoeditaquestion', 'question', $returnurl);
}

$qtypeobj = question_bank::get_qtype($question->qtype);

if (isset($question->categoryobject)) {
    $category = $question->categoryobject;
} else {
    if (!$category = $DB->get_record('question_categories', array('id' => $question->category))) {
        print_error('categorydoesnotexist', 'question', $returnurl);
    }
}

$question->formoptions = new stdClass();

$categorycontext = context::instance_by_id($category->contextid);
$question->contextid = $category->contextid;
$addpermission = has_capability('moodle/question:add', $categorycontext);

$question->formoptions->canedit = question_has_capability_on($question, 'edit');
$question->formoptions->canmove = (question_has_capability_on($question, 'move') && $addpermission);
$question->formoptions->cansaveasnew = false;
$question->formoptions->repeatelements = true;
$formeditable = true;

$question->formoptions->mustbeusable = (bool) $appendqnumstring;

$PAGE->set_pagetype('question-type-' . $question->qtype);
$mform = new fastcreatequestionform('teachercreatequestion.php', $question, $category, $formeditable);

$toform = fullclone($question);
$toform->category = "{$category->id},{$category->contextid}";

$toform->appendqnumstring = $appendqnumstring;
$toform->makecopy = $makecopy;
if ($cm !== null) {
    $toform->cmid = $cm->id;
    $toform->courseid = $cm->course;
} else {
    $toform->courseid = $COURSE->id;
}

$toform->inpopup = $inpopup;

$mform->set_data($toform);

if ($mform->is_cancelled()) {
    if ($inpopup) {
        close_window();
    } else {
        redirect($returnurl);
    }

} else if ($fromform = $mform->get_data()) {
    list($newcatid, $newcontextid) = explode(',', $fromform->category);
    if (!empty($question->id) && $newcatid != $question->category) {
        $contextid = $newcontextid;
    } else {
        $contextid = $category->contextid;
    }

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
        if ($inpopup) {
            echo $OUTPUT->notification(get_string('changessaved'), '');
            close_window(3);
        } else {
            redirect($returnurl);
        }
    }
}

$streditingquestion = $qtypeobj->get_heading();
$PAGE->set_title($streditingquestion);
$PAGE->set_heading($COURSE->fullname);
$PAGE->navbar->add($streditingquestion);

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
