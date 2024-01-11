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
 * @copyright  2021 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once('locallib.php');

use qbank_previewquestion\question_preview_options;

global $PAGE, $DB, $OUTPUT, $USER;

$id = required_param('id', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$flashcardsid = required_param('flashcardsid', PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_cmid($cmid, 'flashcards');
$context = context_module::instance($cm->id);
require_login($course, false, $cm);

$question = question_bank::load_question($id);
// $PAGE->set_pagelayout('popup');

// Get and validate display options.
$maxvariant = 1;
$options = new question_preview_options($question);
$options->load_user_defaults();
$options->set_from_request();

$params = array('id' => $question->id);
$params['cmid'] = $context->instanceid;
$params['flashcardsid'] = $flashcardsid;

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
        throw new \moodle_exception('submissionoutofsequencefriendlymessage', 'question',
                $prevurl, null, $e);
    }

    if ($quba->get_owning_context()->instanceid != $context->instanceid) {
        throw new \moodle_exception('notyourpreview', 'question');
    }

    $slot = $quba->get_first_question_number();
    $usedquestion = $quba->get_question($slot);
    if ($usedquestion->id != $question->id) {
        throw new \moodle_exception('questionidmismatch', 'question');
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
        'flashcardsid' => $flashcardsid
);
$params['courseid'] = $context->instanceid;

$actionurl = new moodle_url('/mod/flashcards/flashcardpreview.php', $params);
$nostatus = false;

$statusrec = $DB->get_record('flashcards_q_status', ['questionid' => $question->id, 'fcid' => $flashcardsid]);
if ($statusrec === false) {
    $nostatus = true;
    $statusval = 0;
    $fqid = 0;
} else {
    $statusval = $statusrec->teachercheck;
    $fqid = $statusrec->id;
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
                    $fqid = $DB->insert_record('flashcards_q_status',
                        ['questionid' => $question->id, 'fcid' => $flashcardsid, 'teachercheck' => $teachercheck, 'addedby' => $USER->id]);
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
            throw new \moodle_exception('submissionoutofsequencefriendlymessage', 'question', $actionurl);

        } catch (Exception $e) {
            // This sucks, if we display our own custom error message, there is no way
            // to display the original stack trace.
            $debuginfo = '';
            if (!empty($e->debuginfo)) {
                $debuginfo = $e->debuginfo;
            }
            throw new \moodle_exception('errorprocessingresponses', 'question', $actionurl,
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
$activityheader = $PAGE->activityheader;
$activityheader->set_attrs([
        'description' => '',
        'hidecompletion' => true
]);

$node = $PAGE->settingsnav->find('mod_flashcards', navigation_node::TYPE_SETTING);
if ($node) {
    $node->make_active();
}

$PAGE->add_body_class('limitedwidth');
echo $OUTPUT->header();

$votes = mod_flashcard_get_peer_review_votes($fqid);
$peerreviewvote = mod_flashcard_get_peer_review_vote_user($fqid);
$helppeerreview = new \help_icon('peerreview', 'mod_flashcards');
$helpteachercheck = new \help_icon('teachercheck', 'mod_flashcards');

$templatecontent = [
    'actionurl' => $actionurl,
    'sesskey' => sesskey(),
    'slot' => $slot,
    'question' => $quba->render_question($slot, $options, $displaynumber),
    'upvotes' => $votes['upvotes'],
    'downvotes' => $votes['downvotes'],
    'fqid' => $fqid,
    'questiontitle' => $question->name,
    'prbtncolorinfoup' => mod_flashcard_get_peer_review_info($peerreviewvote, true),
    'prbtncolorinfodown' => mod_flashcard_get_peer_review_info($peerreviewvote, false),
    'statval' => $statusval,
    'upvote' => FLASHCARDS_PEER_REVIEW_UP,
    'downvote' => FLASHCARDS_PEER_REVIEW_DOWN,
    'helppeerreview' => $helppeerreview->export_for_template($OUTPUT),
    'helpteachercheck' => $helpteachercheck->export_for_template($OUTPUT),
];

if ($canedit) {
    for ($i = 0; $i < 3; $i++) {
        $checkinfo = mod_flashcard_get_teacher_check_info($i);
        $templatecontent['checkicon' . $i] = $checkinfo['icon'];
        $templatecontent['checkicon' . $i]['title'] = get_string('statusval' . $i, 'mod_flashcards');
        $templatecontent['teachercheckcolor' . $i] = $checkinfo['color'];
    }

    $templatecontent['canedit'] = $canedit;
    $templatecontent['selected' . $statusval] = true;
    $templatecontent['icon' . $statusval] = 1;
} else {
    $checkinfo = mod_flashcard_get_teacher_check_info($statusval);
    $templatecontent['checkicon'] = $checkinfo['icon'];
    $templatecontent['checkicon']['title'] = get_string('statusval' . $statusval, 'mod_flashcards');
    $templatecontent['teachercheckcolor'] = $checkinfo['color'];
}

// Edit button.
$fcobj = $DB->get_record('flashcards', ['id' => $flashcardsid]);
$eurl = new moodle_url('/mod/flashcards/simplequestion.php',
    array('action' => 'edit', 'id' => $question->id, 'cmid' => $cmid, 'origin' => $prevurl, 'fcid' => $flashcardsid));
$templatecontent['fceditlink'] = $eurl;
if (mod_flashcards_has_delete_rights($context, $fcobj, $id) ||
    has_capability('mod/flashcards:editcardwithouttcreset', $context)) {
    $templatecontent['showfceditlink'] = true;
}

foreach ($question->answers as $answer) {
    $ans = $answer;
}

$templatecontent['questiontext'] = $question->format_questiontext($qa);

$templatecontent['answer'] = $question->format_text(
     $ans->answer, $ans->answerformat,
     $qa, 'question', 'answer', $ans->id);
$templatecontent['qaid'] = $question->id;

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
$PAGE->requires->js_call_amd('mod_flashcards/previewevents', 'init');

echo $OUTPUT->footer();
