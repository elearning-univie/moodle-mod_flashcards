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
 * @copyright  2021 University of Vienna
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
    redirect(new moodle_url('/mod/flashcards/teacherview.php', array('cmid' => $id)));
}

echo $OUTPUT->header();

if (has_capability('mod/flashcards:studentview', $context)) {
    $PAGE->requires->js_call_amd('mod_flashcards/studentcontroller', 'init');
    $PAGE->requires->js_call_amd('mod_flashcards/studentrangeslider', 'init');
    $flashcards = $DB->get_record('flashcards', array('id' => $cm->instance));
    echo $OUTPUT->heading($flashcards->name);

    $templatestablecontext['icons'] = [
            'deck' => $OUTPUT->image_url('collection', 'mod_flashcards'),
            'phone' => $OUTPUT->image_url('mobile', 'mod_flashcards'),
            'start' => $OUTPUT->image_url('shuffle', 'mod_flashcards')
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

    $userpref = get_user_preferences('flashcards_showapp');

    if (isset($userpref)) {
        $templatestablecontext['displaymobileapps'] = $userpref;
    } else {
        $templatestablecontext['displaymobileapps'] = true;
    }

    $sql = "SELECT count(q.id)
              FROM {question} q,
                   {flashcards_q_status} s
             WHERE q.id = s.questionid
               AND fcid = :fcid
               AND questionid NOT IN (SELECT questionid
                                FROM {flashcards_q_stud_rel}
                               WHERE studentid = :userid
                                 AND currentbox IS NOT NULL
                                 AND flashcardsid = fcid)";
    $boxzeroquestioncount = $DB->count_records_sql($sql, ['fcid' => $flashcards->id, 'userid' => $USER->id]);

    $sql = "SELECT count(q.id)
              FROM {question} q,
                   {flashcards_q_status} s
             WHERE q.id = s.questionid
               AND fcid = :fcid";
    $totalquestioncount = $DB->count_records_sql($sql, ['fcid' => $flashcards->id]);

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

    $boxrecords = get_regular_box_count_records($USER->id, $flashcards->id);
    $boxarray = create_regular_boxvalue_array($boxrecords, $id, $usedquestioncount);

    $templatestablecontext['boxes'] = $boxarray;
    $templatestablecontext['selectquestionsurl'] = new moodle_url('/mod/flashcards/studentquestioninit.php', ['id' => $id]);

    $templatestablecontext['learnnowurl'] = new moodle_url("/mod/flashcards/studentlearnnow.php", ['id' => $id]);
    $templatestablecontext['flashcardsid'] = $flashcards->id;

    $renderer = $PAGE->get_renderer('core');

    if (trim(strip_tags($flashcards->intro))) {
        $formatoptions = new stdClass();
        $formatoptions->noclean = true;
        $templatestablecontext['intro'] = $renderer->box(format_text($flashcards->intro, $flashcards->introformat, $formatoptions),
            'generalbox', 'intro');
    }

    echo $renderer->render_from_template('mod_flashcards/studentview', $templatestablecontext);
    echo $OUTPUT->footer();
} else {
    echo $OUTPUT->heading(get_string('errornotallowedonpage', 'flashcards'));
    echo $OUTPUT->footer();
    die();
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
            $boxarray[$key]['boxbackgroundurl'] = $OUTPUT->image_url('deckinprogress', 'mod_flashcards');
        } else {
            $boxarray[$key]['boxbackgroundurl'] = $OUTPUT->image_url('deckempty', 'mod_flashcards');
        }

        $advancedquestioncount += $eachbox['count'];
    }

    return $boxarray;
}
