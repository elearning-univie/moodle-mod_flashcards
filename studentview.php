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
 * Flashcards Student view
 *
 * @package    mod_flashcards
 * @copyright  2020 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once(__DIR__ . '/locallib.php');

global $PAGE, $OUTPUT, $USER, $DB;

$id = required_param('id', PARAM_INT);
list ($course, $cm) = get_course_and_cm_from_cmid($id, 'flashcards');
$context = context_module::instance($cm->id);

require_login($course, false, $cm);

$PAGE->set_url(new moodle_url("/mod/flashcards/studentview.php", ['id' => $id]));
$node = $PAGE->settingsnav->find('mod_flashcards', navigation_node::TYPE_SETTING);

if ($node) {
    $node->make_active();
}

$pagetitle = get_string('pagetitle', 'flashcards');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

if (has_capability('mod/flashcards:teacherview', $context) ) {
    redirect(new moodle_url('/mod/flashcards/teacherview.php', array('id' => $id)));
}

echo $OUTPUT->header();

if (has_capability('mod/flashcards:studentview', $context)) {

    mod_flashcards_check_for_orphan_or_hidden_questions();
    $PAGE->requires->js_call_amd('mod_flashcards/studentcontroller', 'init');
    $PAGE->requires->js_call_amd('mod_flashcards/studentrangeslider', 'init');
    $flashcards = $DB->get_record('flashcards', array('id' => $cm->instance));
    echo $OUTPUT->heading($flashcards->name);

    $templatestablecontext['icons'] = [
            'deck' => $OUTPUT->image_url('deckicon', 'mod_flashcards'),
            'phone' => $OUTPUT->image_url('phone', 'mod_flashcards'),
            'start' => $OUTPUT->image_url('start', 'mod_flashcards')
    ];

    $templatestablecontext['stores'] = array();

    $applestoreurl = get_config('flashcards', 'applestoreapp');
    $googlestoreurl = get_config('flashcards', 'googlestoreapp');
    if (!empty($applestoreurl)) {
        $templatestablecontext['stores'][] = [
                'badge' => $OUTPUT->image_url('storeapple', 'mod_flashcards'),
                'redirecturl' => $applestoreurl,
                'badgealt' => get_string('appstoreapplealt', 'mod_flashcards')
        ];
    }
    if (!empty($googlestoreurl)) {
        $templatestablecontext['stores'][] = [
                'badge' => $OUTPUT->image_url('storegoogle', 'mod_flashcards'),
                'redirecturl' => $googlestoreurl,
                'badgealt' => get_string('appstoregooglealt', 'mod_flashcards')
        ];
    }
    $templatestablecontext['displaymobileapps'] = !empty($templatestablecontext['stores']);

    $boxzeroquestioncount = get_box_zero_count_record($USER->id, $flashcards);
    $totalquestioncount = get_total_card_count_record($USER->id, $flashcards);
    $usedquestioncount = $totalquestioncount - $boxzeroquestioncount;

    $templatestablecontext['stats'] = [
            'totalquestioncount' => $totalquestioncount,
            'cardsavailable' => $totalquestioncount > 0,
            'boxzeroquestioncount' => $boxzeroquestioncount,
            'unselectedcardsavailable' => $boxzeroquestioncount > 0,
            'usedquestioncount' => $usedquestioncount,
            'halfusedquestioncount' => ceil($usedquestioncount / 2),
            'selectedcardsavailable' => $usedquestioncount > 0,
            'usedquestionspercentage' => $totalquestioncount <= 0 ? 1 : (1 - $boxzeroquestioncount / $totalquestioncount) * 100
    ];

    $templatestablecontext['enablelearnnow'] = $usedquestioncount > 0;
    $templatestablecontext['enablestudentscreatequestions'] = $flashcards->addfcstudent == 1;

    $boxrecords = get_regular_box_count_records($USER->id, $flashcards->id);
    $boxarray = create_regular_boxvalue_array($boxrecords, $id, $usedquestioncount);

    $templatestablecontext['boxes'] = $boxarray;
    $templatestablecontext['selectquestionsurl'] = new moodle_url('/mod/flashcards/studentquestioninit.php', ['id' => $id]);

    $templatestablecontext['learnnowurl'] = new moodle_url("/mod/flashcards/studentlearnnow.php", ['id' => $id]);
    $templatestablecontext['flashcardsid'] = $flashcards->id;

    $templatestablecontext['createfcurl'] = new moodle_url('/mod/flashcards/simplequestion.php',
            ['cmid' => $cm->id, 'origin' => $templatestablecontext['selectquestionsurl']]);

    $renderer = $PAGE->get_renderer('core');
    echo $renderer->render_from_template('mod_flashcards/studentview', $templatestablecontext);
    echo $OUTPUT->footer();
} else {
    echo $OUTPUT->heading(get_string('errornotallowedonpage', 'flashcards'));
    echo $OUTPUT->footer();
    die();
}

/**
 * Calculates the number of questions in box zero
 *
 * @param int $userid
 * @param object $flashcards
 * @return int
 * @throws coding_exception
 * @throws dml_exception
 */
function get_box_zero_count_record($userid, $flashcards) {
    global $DB;

    if ($flashcards->inclsubcats) {
        $qcategories = question_categorylist($flashcards->categoryid);
    } else {
        $qcategories = $flashcards->categoryid;
    }

    list($inids, $categorieids) = $DB->get_in_or_equal($qcategories, SQL_PARAMS_NAMED);

    $sql = "SELECT count(id)
              FROM {question}
             WHERE category $inids
               AND qtype = 'flashcard'
               AND id NOT IN (SELECT questionid
                                FROM {flashcards_q_stud_rel}
                               WHERE studentid = :userid
                                 AND flashcardsid = :fid)";

    return $DB->count_records_sql($sql, $categorieids + ['userid' => $userid, 'fid' => $flashcards->id]);
}

/**
 * Calculates the number of cards available for a given flashcard deck and user combination
 *
 * @param int $userid To query available cards for
 * @param object $flashcards Activity configuration
 * @return int number of totally available cards
 * @throws coding_exception
 * @throws dml_exception
 */
function get_total_card_count_record($userid, $flashcards) {
    global $DB;

    if ($flashcards->inclsubcats) {
        $qcategories = question_categorylist($flashcards->categoryid);
    } else {
        $qcategories = $flashcards->categoryid;
    }

    list($inids, $categorieids) = $DB->get_in_or_equal($qcategories, SQL_PARAMS_NAMED);

    $sql = "SELECT count(id)
              FROM {question}
             WHERE category $inids
               AND qtype = 'flashcard'";

    return $DB->count_records_sql($sql, $categorieids);
}

/**
 * Calculates the number of questions in each box, ordered by box id
 *
 * @param int $userid Id of the user we count selected the questions for.
 * @param int $flashcardsid Activity id
 * @return moodle_recordset holding the id of a box and the number of questions it contains
 * @throws dml_exception
 */
function get_regular_box_count_records($userid, $flashcardsid) {
    global $DB;

    $sql = "SELECT currentbox AS boxid,
                   count(id) AS questioncount
            FROM {flashcards_q_stud_rel}
            WHERE studentid = :userid
              AND flashcardsid = :flashcardsid
              AND currentbox != 0
            GROUP BY boxid
            ORDER BY boxid ASC";

    return $DB->get_recordset_sql($sql,
            [
                    'userid' => $userid,
                    'flashcardsid' => $flashcardsid,
                    'useridsub' => $userid,
                    'flashcardsidsub' => $flashcardsid
            ]);
}

/**
 * Creates an array containing the values for the box overview. Only playable boxes are includes, i.e., box 0 is ignored.
 *
 * @param array $records Contains the box number and the question count to display, ordered by box id
 * @param int $courseid Course id
 * @param int $usedtotalquestioncount number of questions taken out of box 0 for this user
 * @return array of boxes
 */
function create_regular_boxvalue_array($records, $courseid, $usedtotalquestioncount) {
    global $OUTPUT;

    $boxtext = get_string('box', 'mod_flashcards');
    $boxindex = 1;
    $maxboxindex = 5;

    // Structure: box id, hex color - transition from university gray to university blue.
    $boxnumbercolor = [
            1 => '#666666',
            2 => '#63666b',
            3 => '#5b6577',
            4 => '#45648d',
            5 => '#1463a3'
    ];

    $boxarray = array();

    foreach ($records as $record) {

        while ($record->boxid != $boxindex) {
            $boxvalues['boxindex'] = $boxindex;
            $boxvalues['boxheader'] = get_string('boxheader_' . $boxindex, 'mod_flashcards');
            $boxvalues['boxdecorationurl'] = $OUTPUT->image_url('box' . $boxindex . 'deco', 'mod_flashcards');

            $boxvalues['count'] = 0;
            $boxvalues['cardsavailable'] = false;
            $boxvalues['redirecturl'] = null;
            $boxvalues['boxnumbercolor'] = $boxnumbercolor[$boxindex];

            $boxarray[] = $boxvalues;
            $boxindex++;
        }

        if ($record->boxid = $boxindex) {
            $boxvalues['boxindex'] = $boxindex;
            $boxvalues['boxheader'] = get_string('boxheader_' . $boxindex, 'mod_flashcards');
            $boxvalues['boxdecorationurl'] = $OUTPUT->image_url('box' . $boxindex . 'deco', 'mod_flashcards');

            $boxvalues['count'] = $record->questioncount;
            $boxvalues['cardsavailable'] = $record->questioncount > 0;
            $boxvalues['redirecturl'] = new moodle_url('/mod/flashcards/studentquiz.php', ['id' => $courseid, 'box' => $boxindex]);
            $boxvalues['boxnumbercolor'] = $boxnumbercolor[$boxindex];

            $boxarray[] = $boxvalues;
            $boxindex++;
        }
    }

    $records->close();

    while ($boxindex <= $maxboxindex) {
        $boxvalues['boxindex'] = $boxindex;
        $boxvalues['boxheader'] = get_string('boxheader_' . $boxindex, 'mod_flashcards');
        $boxvalues['boxdecorationurl'] = $OUTPUT->image_url('box' . $boxindex . 'deco', 'mod_flashcards');

        $boxvalues['count'] = 0;
        $boxvalues['cardsavailable'] = false;
        $boxvalues['redirecturl'] = null;

        $boxvalues['boxnumbercolor'] = $boxnumbercolor[$boxindex];

        $boxarray[] = $boxvalues;
        $boxindex++;
    }

    $advancedquestioncount = 0;
    foreach (array_reverse($boxarray, true) as $key => $eachbox) {
        $relevantquestioncount = $advancedquestioncount;
        if ($eachbox['boxindex'] == $maxboxindex) {
            // Last box: special handling as no cards can be more advanced then this.
            $relevantquestioncount = $eachbox['count'];
        }

        $boxarray[$key]['advancedquestionpercent'] = $usedtotalquestioncount <= 0 ?
                0 : ($relevantquestioncount / $usedtotalquestioncount) * 100;

        if (($eachbox['boxindex'] == $maxboxindex && $eachbox['count'] == $usedtotalquestioncount) ||
                ($eachbox['count'] == 0 && $advancedquestioncount == $usedtotalquestioncount)) {
            $boxarray[$key]['boxbackgroundurl'] = $OUTPUT->image_url('deckdone', 'mod_flashcards');
        } else if ($eachbox['count'] > 0 ||
                ($eachbox['count'] == 0 && $advancedquestioncount > 0)) {
            $boxarray[$key]['boxbackgroundurl'] = $OUTPUT->image_url('deckwork', 'mod_flashcards');
        } else {
            $boxarray[$key]['boxbackgroundurl'] = $OUTPUT->image_url('deckdefault', 'mod_flashcards');
        }

        $advancedquestioncount += $eachbox['count'];
    }

    return $boxarray;
}
