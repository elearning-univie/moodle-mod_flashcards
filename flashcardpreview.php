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
 * This page displays a preview of a question
 *
 * The preview uses the option settings from the activity within which the question
 * is previewed or the default settings if no activity is specified. The question session
 * information is stored in the session as an array of subsequent states rather
 * than in the database.
 *
 * @package    mod_flashcards
 * @copyright  2020 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/previewlib.php');
require_once('locallib.php');

global $PAGE, $DB, $OUTPUT;

$id = required_param('id', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$flashcardsid = required_param('fcid', PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_cmid($cmid, 'flashcards');
$context = context_module::instance($cm->id);
require_login($course, false, $cm);

$question = question_bank::load_question($id);
$PAGE->set_pagelayout('popup');

// Get and validate display options.
$maxvariant = 1;
$options = new question_preview_options($question);
$options->load_user_defaults();
$options->set_from_request();

$params = array('id' => $question->id);
$params['cmid'] = $context->instanceid;

$prevurl = new moodle_url('/mod/flashcards/flashcardpreview.php', $params);
$PAGE->set_url($prevurl);

// Get and validate existing preview, or start a new one.
$previewid = optional_param('previewid', 0, PARAM_INT);

if ($previewid) {
    try {
        $quba = question_engine::load_questions_usage_by_activity($previewid);
    } catch (Exception $e) {
        // This may not seem like the right error message to display, but
        // actually from the user point of view, it makes sense.
        print_error('submissionoutofsequencefriendlymessage', 'question',
                $prevurl, null, $e);
    }

    if ($quba->get_owning_context()->instanceid != $context->instanceid) {
        print_error('notyourpreview', 'question');
    }

    $slot = $quba->get_first_question_number();
    $usedquestion = $quba->get_question($slot);
    if ($usedquestion->id != $question->id) {
        print_error('questionidmismatch', 'question');
    }
    $question = $usedquestion;
    $options->variant = $quba->get_variant($slot);

} else {
    $quba = question_engine::make_questions_usage_by_activity(
            'mod_flashcards', $context);
    $quba->set_preferred_behaviour($options->behaviour);
    $slot = $quba->add_question($question, $options->maxmark);

    if ($options->variant) {
        $options->variant = min($maxvariant, max(1, $options->variant));
    } else {
        $options->variant = 1;
    }

    $quba->start_question($slot, $options->variant);

    $transaction = $DB->start_delegated_transaction();
    question_engine::save_questions_usage_by_activity($quba);
    $transaction->allow_commit();
}
$options->behaviour = $quba->get_preferred_behaviour();
$options->maxmark = $quba->get_question_max_mark($slot);

$params = array(
        'id' => $question->id,
        'cmid' => $cmid,
        'previewid' => $quba->get_id(),
        'fcid' => $flashcardsid
);
$params['courseid'] = $context->instanceid;
$actionurl = new moodle_url('/mod/flashcards/flashcardpreview.php', $params);
$nostatus = false;

$statusrec = $DB->get_record('flashcards_q_status', ['questionid' => $question->id, 'fcid' => $flashcardsid]);
if ($statusrec === false) {
    $nostatus = true;
    $statusval = 0;
} else {
    $statusval = $statusrec->teachercheck;
}

if (!has_capability('mod/flashcards:editreview', $context)) {
    $canedit = false;
} else {
    $canedit = true;

    // Process any actions from the buttons at the bottom of the form.
    if (data_submitted() && confirm_sesskey()) {
        try {
            if (optional_param('finish', null, PARAM_BOOL)) {
                $teachercheck = optional_param('teachercheck', 0, PARAM_INT);
                if ($nostatus) {
                    $DB->insert_record('flashcards_q_status', ['questionid' => $question->id, 'fcid' => $flashcardsid, 'teachercheck' => $teachercheck]);
                } else if ($statusrec->teachercheck != $teachercheck) {
                    $statusrec->teachercheck = $teachercheck;
                    $DB->update_record('flashcards_q_status', $statusrec);
                }

                $quba->process_all_actions();
                $quba->finish_all_questions();

                $transaction = $DB->start_delegated_transaction();
                question_engine::save_questions_usage_by_activity($quba);
                $transaction->allow_commit();
                redirect($actionurl);
            } else {
                $quba->process_all_actions();

                $transaction = $DB->start_delegated_transaction();
                question_engine::save_questions_usage_by_activity($quba);
                $transaction->allow_commit();

                $scrollpos = optional_param('scrollpos', '', PARAM_RAW);
                if ($scrollpos !== '') {
                    $actionurl->param('scrollpos', (int) $scrollpos);
                }
                redirect($actionurl);
            }

        } catch (question_out_of_sequence_exception $e) {
            print_error('submissionoutofsequencefriendlymessage', 'question', $actionurl);

        } catch (Exception $e) {
            // This sucks, if we display our own custom error message, there is no way
            // to display the original stack trace.
            $debuginfo = '';
            if (!empty($e->debuginfo)) {
                $debuginfo = $e->debuginfo;
            }
            print_error('errorprocessingresponses', 'question', $actionurl,
                    $e->getMessage(), $debuginfo);
        }
    }
}

if ($question->length) {
    $displaynumber = '1';
} else {
    $displaynumber = 'i';
}

// Prepare technical info to be output.
$qa = $quba->get_question_attempt($slot);

// Start output.
$title = get_string('previewquestion', 'question', format_string($question->name));
$headtags = question_engine::initialise_js() . $quba->render_question_head_html($slot);
$PAGE->set_title($title);
$PAGE->set_heading($title);
echo $OUTPUT->header();

$templatecontent['actionurl'] = $actionurl;
$templatecontent['sesskey'] = sesskey();
$templatecontent['slot'] = $slot;
$templatecontent['question'] = $quba->render_question($slot, $options, $displaynumber);
$templatecontent['upvotes'] = 'XX';
$templatecontent['downvotes'] = 'XX';

if ($canedit) {
    $templatecontent['canedit'] = $canedit;
    $templatecontent['selected' . $statusval] = true;

    $checkinfo = mod_flashcard_get_teacher_check_info(1);
    $templatecontent['checkicon1'] = $checkinfo['icon'];
    $templatecontent['teachercheckcolor1'] = $checkinfo['color'];

    $checkinfo = mod_flashcard_get_teacher_check_info(2);
    $templatecontent['checkicon2'] = $checkinfo['icon'];
    $templatecontent['teachercheckcolor2'] = $checkinfo['color'];

    $checkinfo = mod_flashcard_get_teacher_check_info(0);
    $templatecontent['checkicon0'] = $checkinfo['icon'];
    $templatecontent['teachercheckcolor0'] = $checkinfo['color'];

    $templatecontent['icon' . $statusval] = 1;
} else {
    $checkinfo = mod_flashcard_get_teacher_check_info($statusval);
    $templatecontent['checkicon'] = $checkinfo['icon'];
    $templatecontent['teachercheckcolor'] = $checkinfo['color'];
}

$renderer = $PAGE->get_renderer('core');
echo $renderer->render_from_template('mod_flashcards/flashcardpreview', $templatecontent);

// Log the preview of this question.
$event = \core\event\question_viewed::create_from_question_instance($question, $context);
$event->trigger();

$PAGE->requires->js_module('core_question_engine');
$PAGE->requires->strings_for_js(array(
        'closepreview'
), 'question');
$PAGE->requires->yui_module('moodle-question-preview', 'M.question.preview.init');
if ($canedit) {
    $PAGE->requires->js_call_amd('mod_flashcards/previewevents', 'init', [$question->id, $flashcardsid]);
}
echo $OUTPUT->footer();
