<?php
require('../../config.php');
require_once('lib.php');

global $PAGE, $OUTPUT, $COURSE, $USER;

$id = required_param('id', PARAM_INT);
list ($course, $cm) = get_course_and_cm_from_cmid($id, 'flashcards');

$context = context_module::instance($cm->id);

require_login($course, false, $cm);

$flashcards = $DB->get_record('flashcards', array('id'=> $cm->instance));

$PAGE->set_url(new moodle_url("/mod/flashcards/view.php", ['id' => $id]));
$node = $PAGE->settingsnav->find('mod_flashcards', navigation_node::TYPE_SETTING);
if ($node) {
    $node->make_active();
}

$pagetitle = get_string('pagetitle', 'flashcards');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

if (has_capability('mod/flashcards:studentview', $context) ) {
    $redirecturl = new moodle_url('/mod/flashcards/studentview.php', array('id' => $id));
    redirect($redirecturl);}
if (has_capability('mod/flashcards:teacherview', $context) ) {
    $redirecturl = new moodle_url('/mod/flashcards/teacherview.php', array('id' => $id));
    redirect($redirecturl);
} else {
    print("hier kommt die Auswahl für beide hin.");
}

echo $OUTPUT->header();
echo $OUTPUT->heading($flashcards->name);

echo $OUTPUT->footer();