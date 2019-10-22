<?php 
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
global $DB, $PAGE, $OUTPUT;
//TODO: This has to be module-level
$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);
$url = new moodle_url('/mod/flashcards/question.php', array('courseid' => $courseid));
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->force_settings_menu(true);
$PAGE->set_context($context);
//TODO heading;
$PAGE->set_heading("heading");
echo $OUTPUT->header();


$quba = question_engine::make_questions_usage_by_activity('mod_flashcards', $context);
$quba->set_preferred_behaviour('adaptivenopenalty');
$sql = 'SELECT q.id 
        FROM   {question} q,
               {question_categories} qc
        WHERE  q.category = qc.id
        AND    qc.contextid= :contextid';
$questionids = $DB->get_fieldset_sql($sql, ['contextid' => $context->id]);
if(!$questionids) {
    print_error('noquestion', 'mod_flashcards');
}
foreach($questionids as $questionid) {
    print_object($questionid);
    $question = question_bank::load_question($questionid);
    $quba->add_question($question, 1);
}
$quba->start_all_questions();
// $id = question_engine::save_questions_usage_by_activity($quba);
// question_engine::load_questions_usage_by_activity();
// print_object($quba);
$renderer = $PAGE->get_renderer('core');
$params = [];

$slotsonpage = [1];
$options = new question_display_options();
$options->clearwrong = question_display_options::HIDDEN;
$options->context = $context->id;
$options->extrainfocontent = $renderer->render_from_template('mod_flashcards/decide', $params);
$options->rightanswer = question_display_options::VISIBLE;
$options->flags = question_display_options::HIDDEN;
$options->generalfeedback = question_display_options::HIDDEN;
$options->correctness = question_display_options::VISIBLE;

$options->marks = question_display_options::HIDDEN;
$options->numpartscorrect = question_display_options::HIDDEN;
echo $question->questiontext;

echo $quba->render_question(1, $options);


echo $OUTPUT->footer();
