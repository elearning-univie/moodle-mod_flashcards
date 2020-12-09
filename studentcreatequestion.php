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
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/formslib.php');

global $USER, $DB, $PAGE, $COURSE, $OUTPUT;

// Read URL parameters telling us which question to edit.
$id = optional_param('id', 0, PARAM_INT); // question id
$makecopy = optional_param('makecopy', 0, PARAM_BOOL);
$qtype = 'flashcard';
$categoryid = optional_param('category', 0, PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$appendqnumstring = optional_param('appendqnumstring', '', PARAM_ALPHA);
$inpopup = optional_param('inpopup', 0, PARAM_BOOL);
$scrollpos = optional_param('scrollpos', 0, PARAM_INT);

$url = new moodle_url('/mod/flashcards/studentcreatequestion.php');
if ($id !== 0) {
    $url->param('id', $id);
}
if ($makecopy) {
    $url->param('makecopy', $makecopy);
}
if ($qtype !== '') {
    $url->param('qtype', $qtype);
}
if ($categoryid !== 0) {
    $url->param('category', $categoryid);
}
if ($cmid !== 0) {
    $url->param('cmid', $cmid);
}
if ($appendqnumstring !== '') {
    $url->param('appendqnumstring', $appendqnumstring);
}
if ($inpopup !== 0) {
    $url->param('inpopup', $inpopup);
}
if ($scrollpos) {
    $url->param('scrollpos', $scrollpos);
}
$PAGE->set_url($url);

$questionbankurl = new moodle_url('/question/edit.php', array('cmid' => $cmid));

navigation_node::override_active_url($questionbankurl);

$returnurl = $questionbankurl;
if ($scrollpos) {
    $returnurl->param('scrollpos', $scrollpos);
}

list($module, $cm) = get_module_from_cmid($cmid);
require_login($cm->course, false, $cm);
$thiscontext = context_module::instance($cmid);

$contexts = new question_edit_contexts($thiscontext);
//$PAGE->set_pagelayout('admin');

if (optional_param('addcancel', false, PARAM_BOOL)) {
    redirect($returnurl);
}

if ($id) {
    if (!$question = $DB->get_record('question', array('id' => $id))) {
        print_error('questiondoesnotexist', 'question', $returnurl);
    }
    get_question_options($question, true, [$COURSE]);

} else if ($categoryid && $qtype) { // only for creating new questions
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

if ($id) {
    $question->formoptions->canedit = question_has_capability_on($question, 'edit');
    $question->formoptions->canmove = $addpermission && question_has_capability_on($question, 'move');
    $question->formoptions->cansaveasnew = $addpermission &&
            (question_has_capability_on($question, 'view') || $question->formoptions->canedit);
    $question->formoptions->repeatelements = $question->formoptions->canedit || $question->formoptions->cansaveasnew;
    $formeditable =  $question->formoptions->canedit || $question->formoptions->cansaveasnew || $question->formoptions->canmove;
    if (!$formeditable) {
        question_require_capability_on($question, 'view');
    }
    if ($makecopy) {
        $question->name = get_string('questionnamecopy', 'question', $question->name);
        $question->idnumber = core_question_find_next_unused_idnumber($question->idnumber, $category->id);
        $question->beingcopied = true;
    }

} else  {
    $question->formoptions->canedit = question_has_capability_on($question, 'edit');
    $question->formoptions->canmove = (question_has_capability_on($question, 'move') && $addpermission);
    $question->formoptions->cansaveasnew = false;
    $question->formoptions->repeatelements = true;
    $formeditable = true;
    //require_capability('moodle/question:add', $categorycontext);
}
$question->formoptions->mustbeusable = (bool) $appendqnumstring;

$PAGE->set_pagetype('question-type-' . $question->qtype);

$mform = $qtypeobj->create_editing_form('question.php', $question, $category, $contexts, $formeditable);
$mform->removeFormElement('category');
// TODO set category automatically
$mform->removeFormElement('generalfeedback');
$mform->removeFormElement('idnumber');

/*$toform = fullclone($question);
$toform->category = "{$category->id},{$category->contextid}";
$toform->scrollpos = $scrollpos;
if ($formeditable && $id){
    $toform->categorymoveto = $toform->category;
}

$toform->appendqnumstring = $appendqnumstring;
$toform->returnurl = $originalreturnurl;
$toform->makecopy = $makecopy;
if ($cm !== null){
    $toform->cmid = $cm->id;
    $toform->courseid = $cm->course;
} else {
    $toform->courseid = $COURSE->id;
}

$toform->inpopup = $inpopup;

$mform->set_data($toform);*/

if ($mform->is_cancelled()) {
    if ($inpopup) {
        close_window();
    } else {
        redirect($returnurl);
    }

} else if ($fromform = $mform->get_data()) {
    if ($makecopy) {
        $question->id = 0;
        $question->hidden = 0; // Copies should not be hidden.
    }

    if (!empty($fromform->usecurrentcat)) {
        // $fromform->category is the right category to save in.
    } else {
        if (!empty($fromform->categorymoveto)) {
            $fromform->category = $fromform->categorymoveto;
        } else {
            // $fromform->category is the right category to save in.
        }
    }

    list($newcatid, $newcontextid) = explode(',', $fromform->category);
    if (!empty($question->id) && $newcatid != $question->category) {
        $contextid = $newcontextid;
        question_require_capability_on($question, 'move');
    } else {
        $contextid = $category->contextid;
    }

    $returnurl->param('category', $fromform->category);

    if (!empty($question->id)) {
        question_require_capability_on($question, 'edit');
    } else {
        require_capability('moodle/question:add', context::instance_by_id($contextid));
        if (!empty($fromform->makecopy) && !$question->formoptions->cansaveasnew) {
            print_error('nopermissions', '', '', 'edit');
        }
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

    if (!empty($fromform->updatebutton)) {
        $url->param('id', $question->id);
        $url->remove_params('makecopy');
        redirect($url);
    }

    if ($qtypeobj->finished_edit_wizard($fromform)) {
        if ($inpopup) {
            echo $OUTPUT->notification(get_string('changessaved'), '');
            close_window(3);
        } else {
            $returnurl->param('lastchanged', $question->id);
            if ($appendqnumstring) {
                $returnurl->param($appendqnumstring, $question->id);
                $returnurl->param('sesskey', sesskey());
                $returnurl->param('cmid', $cmid);
            }
            redirect($returnurl);
        }

    } else {
        $nexturlparams = array(
                'returnurl' => $originalreturnurl,
                'appendqnumstring' => $appendqnumstring,
                'scrollpos' => $scrollpos);
        if (isset($fromform->nextpageparam) && is_array($fromform->nextpageparam)){
            $nexturlparams += $fromform->nextpageparam;
        }
        $nexturlparams['id'] = $question->id;
        $nexturlparams['wizardnow'] = $fromform->wizard;
        $nexturl = new moodle_url('/question/question.php', $nexturlparams);
        if ($cmid) {
            $nexturl->param('cmid', $cmid);
        } else {
            $nexturl->param('courseid', $COURSE->id);
        }
        redirect($nexturl);
    }

}

$streditingquestion = $qtypeobj->get_heading();
$PAGE->set_title($streditingquestion);
$PAGE->set_heading($COURSE->fullname);
$PAGE->navbar->add($streditingquestion);

echo $OUTPUT->header();
$qtypeobj->display_question_editing_page($mform, $question, null);
echo $OUTPUT->footer();
