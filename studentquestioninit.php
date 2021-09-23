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
require_once('locallib.php');

global $PAGE, $OUTPUT, $DB, $CFG, $USER;

$id = required_param('id', PARAM_INT);
$deleteselected = optional_param('deleteselected', null, PARAM_INT);
$confirm = optional_param('confirm', null, PARAM_ALPHANUM);
$perpage = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT);
$tab = optional_param('tab', 'notadded', PARAM_ALPHAEXT);

$params = array();
$params['id'] = $id;
$params['tab'] = $tab;

if (!in_array($perpage, [10, 20, 50, 100], true)) {
    $perpage = DEFAULT_PAGE_SIZE;
}
$params['perpage'] = $perpage;

list ($course, $cm) = get_course_and_cm_from_cmid($id, 'flashcards');
$context = context_module::instance($cm->id);
require_login($course, false, $cm);

$PAGE->set_url(new moodle_url("/mod/flashcards/studentquestioninit.php", $params));
$node = $PAGE->settingsnav->find('mod_flashcards', navigation_node::TYPE_SETTING);
if ($node) {
    $node->make_active();
}

$pagetitle = get_string('pagetitle', 'flashcards');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

if (!has_capability('mod/flashcards:studentview', $context)) {
    echo $OUTPUT->heading(get_string('errornotallowedonpage', 'flashcards'));
    echo $OUTPUT->footer();
    die();
}

$flashcards = $DB->get_record('flashcards', array('id' => $cm->instance));

if ($deleteselected) {
    if (!$DB->record_exists('question', ['id' => $deleteselected])) {
        redirect($PAGE->url);
    }

    if ($confirm == md5($deleteselected)) {
        $questionid = $deleteselected;
        mod_flashcards_delete_student_question($questionid, $flashcards, $context);
        redirect($PAGE->url);
    } else {
        $deleteurl = new moodle_url('/mod/flashcards/studentquestioninit.php',
                array('id' => $id, 'deleteselected' => $deleteselected, 'sesskey' => sesskey(), 'confirm' => md5($deleteselected)));

        $continue = new \single_button($deleteurl, get_string('delete'), 'post');
        $questionname = $DB->get_field('question', 'name', ['id' => $deleteselected]);

        echo $OUTPUT->header();
        echo $OUTPUT->confirm(get_string('deletequestionscheck', 'question', $questionname), $continue, $PAGE->url);
        echo $OUTPUT->footer();
        die();
    }
}

$PAGE->requires->js_call_amd('mod_flashcards/questioninit', 'init');

if ($flashcards->inclsubcats) {
    require_once($CFG->dirroot . '/lib/questionlib.php');
    $qcategories = question_categorylist($flashcards->categoryid);
} else {
    $qcategories = $flashcards->categoryid;
}
$equalparam = ($tab == 'added') ? true : false;

$importedfcs = $DB->get_fieldset_sql('SELECT questionid
                            FROM {flashcards_q_stud_rel}
                           WHERE studentid = :userid
                             AND flashcardsid = :fid
                             AND currentbox IS NOT NULL', ['userid' => $USER->id, 'fid' => $flashcards->id]);

list($sqlwherecat, $qcategories) = $DB->get_in_or_equal($qcategories, SQL_PARAMS_NAMED, 'p');
list($sqlwhereifcs, $importedfcids) = $DB->get_in_or_equal($importedfcs, SQL_PARAMS_NAMED, 'p', $equalparam, true);
$sqlwhere = "category $sqlwherecat AND qtype = 'flashcard' AND q.hidden <> 1 AND q.id $sqlwhereifcs";

$table = new mod_flashcards\output\studentviewtable('uniqueid', $cm->id, $course->id, $flashcards, $PAGE->url, $tab);
$table->set_sql('q.id, name, fsr.currentbox, q.questiontext, q.createdby, q.timemodified, teachercheck',
        "{question} q LEFT JOIN {flashcards_q_status} fcs ON q.id = fcs.questionid
                      LEFT JOIN mdl_flashcards_q_stud_rel fsr ON fsr.questionid = q.id AND fsr.studentid = $USER->id",
                      $sqlwhere, $qcategories + $importedfcids);
$table->define_baseurl($PAGE->url);

list($notadded, $added) = mod_flashcards_count_added_and_not_added_cards($importedfcs, $qcategories);

$params = ['action' => 'create', 'cmid' => $cm->id, 'courseid' => $course->id, 'origin' => $PAGE->url];
$link = new moodle_url('/mod/flashcards/simplequestion.php', $params);

$renderer = $PAGE->get_renderer('core');

$templateinfo = ['createbtnlink' => $link->out(false),
        'id' => $id,
        'sesskey' => sesskey(),
        'actionurl' => $PAGE->url,
        'aid' => $flashcards->id,
        'cmid' => $cm->id,
        'tab' => $tab];
$templateinfo['selected' . $perpage] = true;

if ($flashcards->addfcstudent == 1) {
    $templateinfo['cbvis'] = 1;
}

$tabs = [];
$tabs['notadded'] = new tabobject('notadded',
    new moodle_url('/mod/flashcards/studentquestioninit.php', ['id' => $id, 'tab' => 'notadded']),
    get_string('tabflashcardsnotadded', 'flashcards', ['nonotadded' => $notadded]),
    get_string('tabflashcardsnotaddedtip', 'flashcards'),
    false);
$tabs['added'] = new tabobject('added',
    new moodle_url('/mod/flashcards/studentquestioninit.php', ['id' => $id, 'tab' => 'added']),
    get_string('tabflashcardsadded', 'flashcards', ['noadded' => $added]),
    get_string('tabflashcardsaddedtip', 'flashcards'),
    false);

echo $OUTPUT->header();
echo $OUTPUT->heading($flashcards->name);
echo $renderer->render_from_template('mod_flashcards/studentinitboxview', $templateinfo);
echo $OUTPUT->tabtree($tabs, $tab);

if ($equalparam) {
    $addlink = '$.mod_flashcards_remove_questions('.$flashcards->id .', "'.get_string('removeflashcardsdonemessage', 'mod_flashcards').'")';
    echo html_writer::start_tag('button', ['class' => 'btn btn-primary add_remove_btn_margins', 'onClick' => $addlink]);
    echo get_string('removeflashcardbutton', 'mod_flashcards');
} else {
    $addlink = '$.mod_flashcards_init_questions('.$flashcards->id .', "'.get_string('addflashcardsdonemessage', 'mod_flashcards').'")';
    echo html_writer::start_tag('button', ['class' => 'btn btn-primary add_remove_btn_margins', 'onClick' => $addlink]);
    echo get_string('addflashcardbutton', 'mod_flashcards');
}
echo html_writer::end_tag('button');
$table->out($perpage, false);
echo $OUTPUT->footer();
