<?php
require('../../config.php');
require_once('lib.php');

global $PAGE, $OUTPUT, $COURSE, $USER;

//$context = context_course::instance($COURSE->id);

$id = required_param('id', PARAM_INT);
list ($course, $cm) = get_course_and_cm_from_cmid($id, 'flashcards');

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

require_login($course, false, $cm);

$flashcards = $DB->get_record('flashcards', array('id'=> $cm->instance));

$PAGE->set_url(new moodle_url("/mod /flashcards/view.php", ['id' => $id]));
$node = $PAGE->settingsnav->find('mod_flashcards', navigation_node::TYPE_SETTING);
if ($node) {
    $node->make_active();
}

$pagetitle = get_string('pagetitle', 'flashcards');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($flashcards->name);

print("THIS IS TEACHER VIEW.");

echo $OUTPUT->footer();