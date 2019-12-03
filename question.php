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
 * Multiple choice question definition classes.
 *
 * @package    mod_flashcards
 * @copyright  2019 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

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
// TODO heading;
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
if (!$questionids) {
    print_error('noquestion', 'mod_flashcards');
}
foreach ($questionids as $questionid) {
    $question = question_bank::load_question($questionid);
    $quba->add_question($question, 1);
}
$quba->start_all_questions();

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
