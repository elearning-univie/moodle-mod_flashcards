<?php
use core\context;

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
 * Flashcards overview for teachers
 *
 * @package    mod_flashcards
 * @copyright  2023 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');

global $PAGE, $OUTPUT, $DB, $CFG, $COURSE;

$cmid = required_param('cmid', PARAM_INT);

$params = array();
$params['cmid'] = $cmid;

list ($course, $cm) = get_course_and_cm_from_cmid($cmid, 'flashcards');
$context = context_module::instance($cm->id);

require_login($course, false, $cm);

$pageurl = new moodle_url("/mod/flashcards/overview.php", $params);

$PAGE->set_url($pageurl);
$node = $PAGE->settingsnav->find('mod_flashcards', navigation_node::TYPE_SETTING);
if ($node) {
    $node->make_active();
}

$pagetitle = get_string('pagetitle', 'flashcards');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);
$PAGE->add_body_class('limitedwidth');

if (!has_capability('mod/flashcards:teacherview', $context)) {
    if (has_capability('mod/flashcards:view', $context) ) {
        redirect(new moodle_url('/mod/flashcards/studentview.php', ['id' => $cmid]));
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('errornotallowedonpage', 'flashcards'));
    echo $OUTPUT->footer();
    die();
}

$flashcards = $DB->get_record('flashcards', ['id' => $cm->instance]);

$sql = "SELECT count(q.id)
              FROM {question} q,
                   {flashcards_q_status} s
             WHERE q.id = s.questionid
               AND fcid = :fcid";
$totalquestioncount = $DB->count_records_sql($sql, ['fcid' => $flashcards->id]);

$sql = "SELECT count(q.id)
              FROM {question} q,
                   {flashcards_q_status} s
             WHERE q.id = s.questionid
               AND fcid = :fcid
               AND teachercheck = 0";
$newquestioncount = $DB->count_records_sql($sql, ['fcid' => $flashcards->id]);


$teacherarchs = explode(',', get_config('flashcards', 'authordisplay_group_teacherroles'));

list($stsql, $stparams) = $DB->get_in_or_equal($teacherarchs, SQL_PARAMS_NAMED, 'atid');
list($stsql2, $stparams2) = $DB->get_in_or_equal($teacherarchs, SQL_PARAMS_NAMED, 'atid2');
$stparams['fcid'] = $flashcards->id;
$stparams['courseid'] = $course->id;
$stparams['courseid2'] = $course->id;
$stparams += $stparams2;

$sql = "SELECT COUNT(q.id)
             FROM {question} q
             JOIN {question_versions} v ON v.questionid = q.id
             JOIN {flashcards_q_status} fcs ON v.questionbankentryid = fcs.qbankentryid
            WHERE v.questionbankentryid  = fcs.qbankentryid
              AND fcs.fcid = :fcid
              AND v.version = (SELECT MIN(v.version) FROM {question_versions} v WHERE v.questionbankentryid = v.questionbankentryid)
              AND q.createdby IN (SELECT u.id
                                    FROM {user} u
                                    JOIN {role_assignments} ra ON ra.userid = u.id
                                    JOIN {context} mc ON mc.id = ra.contextid
                                    JOIN {course} mc2 ON mc2.id = mc.instanceid
                                   WHERE mc2.id = :courseid
                                     AND ra.roleid NOT " . $stsql . ")". "
             AND q.createdby NOT IN (SELECT u.id
                                 FROM {user} u
                                 JOIN {role_assignments} ra ON ra.userid = u.id
                                 JOIN {context} mc ON mc.id = ra.contextid
                                 JOIN {course} mc2 ON mc2.id = mc.instanceid
                                 WHERE mc2.id = :courseid2
                                 AND ra.roleid " . $stsql2 . ")";

$studentquestioncount = $DB->count_records_sql($sql, $stparams);

$sql = "SELECT COUNT(q.id)
             FROM {question} q
             JOIN {question_versions} v ON v.questionid = q.id
             JOIN {flashcards_q_status} fcs ON v.questionbankentryid = fcs.qbankentryid
            WHERE v.questionbankentryid  = fcs.qbankentryid
              AND fcs.fcid = :fcid
              AND v.version = (SELECT MIN(v.version) FROM {question_versions} v WHERE v.questionbankentryid = v.questionbankentryid)
              AND q.createdby IN (SELECT u.id
                                    FROM {user} u
                                    JOIN {role_assignments} ra ON ra.userid = u.id
                                    JOIN {context} mc ON mc.id = ra.contextid
                                    JOIN {course} mc2 ON mc2.id = mc.instanceid
                                   WHERE mc2.id = :courseid
                                     AND ra.roleid " . $stsql . ")";
$teacherquestioncount = $DB->count_records_sql($sql, $stparams);

$filterlink1 = new moodle_url('/mod/flashcards/teacherview.php', ['cmid' => $cm->id, 'filter' => 1]);
$filterlink2 = new moodle_url('/mod/flashcards/teacherview.php', ['cmid' => $cm->id, 'filter' => 2]);
$filterlink3 = new moodle_url('/mod/flashcards/teacherview.php', ['cmid' => $cm->id, 'filter' => 3]);
$filterlink4 = new moodle_url('/mod/flashcards/teacherview.php', ['cmid' => $cm->id, 'filter' => 4]);
$filterlink5 = new moodle_url('/mod/flashcards/teacherview.php', ['cmid' => $cm->id, 'filter' => 5]);
$filterlink6 = new moodle_url('/mod/flashcards/teacherview.php', ['cmid' => $cm->id, 'filter' => 6]);

// if flashcardsversion is older than 2023042401, dont show added by counts and filters, since field values are NULL
$addedbyisnull = $DB->count_records_sql("SELECT COUNT(s.id) FROM {flashcards_q_status} s WHERE s.fcid = :fcid AND s.addedby IS NULL ", ['fcid' => $flashcards->id]);

if ($addedbyisnull > 0) {
    $tabledata = [
        ['text' => get_string('overviewall', 'flashcards'),
            'link' => get_string('overviewttotallink', 'flashcards', ['linktotal' => $filterlink1->out(false), 'valuetotal' => $totalquestioncount])], 
        ['text' => get_string('overviewtq', 'flashcards'),
            'link' => get_string('overviewtlink', 'flashcards', ['linkt' => $filterlink2->out(false), 'valuet' => $teacherquestioncount])],
        ['text' => get_string('overviewsq', 'flashcards'),
            'link' => get_string('overviewslink', 'flashcards', ['linkst' => $filterlink3->out(false), 'valuest' => $studentquestioncount])],
        ['text' => get_string('overviewnq', 'flashcards'),
            'link' => get_string('overviewtnqlink', 'flashcards', ['linknq' => $filterlink4->out(false), 'valuenq' => $newquestioncount])]
    ];  
} else {    
    $sql = "SELECT COUNT(q.id)
             FROM {question} q 
             JOIN {question_versions} v ON v.questionid = q.id 
             JOIN {flashcards_q_status} fcs ON v.questionbankentryid = fcs.qbankentryid
            WHERE v.questionbankentryid  = fcs.qbankentryid
              AND fcs.fcid = :fcid
              AND v.version = (SELECT MIN(v.version) FROM {question_versions} v WHERE v.questionbankentryid = v.questionbankentryid)
              AND fcs.addedby IN (SELECT u.id
                                    FROM {user} u
                                    JOIN {role_assignments} ra ON ra.userid = u.id 
                                    JOIN {context} mc ON mc.id = ra.contextid 
                                    JOIN {course} mc2 ON mc2.id = mc.instanceid
                                   WHERE mc2.id = :courseid
                                     AND ra.roleid " . $stsql . ")";
    $teacherquestioncountadd =  $DB->count_records_sql($sql, $stparams);
    $sql = "SELECT COUNT(q.id)
             FROM {question} q 
             JOIN {question_versions} v ON v.questionid = q.id 
             JOIN {flashcards_q_status} fcs ON v.questionbankentryid = fcs.qbankentryid
            WHERE v.questionbankentryid  = fcs.qbankentryid
              AND fcs.fcid = :fcid
              AND v.version = (SELECT MIN(v.version) FROM {question_versions} v WHERE v.questionbankentryid = v.questionbankentryid)
              AND fcs.addedby IN (SELECT u.id
                                    FROM {user} u
                                    JOIN {role_assignments} ra ON ra.userid = u.id 
                                    JOIN {context} mc ON mc.id = ra.contextid 
                                    JOIN {course} mc2 ON mc2.id = mc.instanceid
                                   WHERE mc2.id = :courseid
                                     AND ra.roleid NOT " . $stsql . ")". "
             AND q.createdby NOT IN (SELECT u.id
                                 FROM {user} u
                                 JOIN {role_assignments} ra ON ra.userid = u.id
                                 JOIN {context} mc ON mc.id = ra.contextid
                                 JOIN {course} mc2 ON mc2.id = mc.instanceid
                                 WHERE mc2.id = :courseid2
                                 AND ra.roleid " . $stsql2 . ")";
    
    $studentquestioncountadd = $DB->count_records_sql($sql, $stparams);
    
    $tabledata = [
        ['text' => get_string('overviewall', 'flashcards'),
            'link' => get_string('overviewttotallink', 'flashcards', ['linktotal' => $filterlink1->out(false), 'valuetotal' => $totalquestioncount])],        
        ['text' => get_string('overviewtqadd', 'flashcards'),
            'link' => get_string('overviewtaddlinks', 'flashcards', ['linkcreated' => $filterlink2->out(false), 'valuecreated' => $teacherquestioncount,
                'linkadded' => $filterlink5->out(false), 'valueadded' => $teacherquestioncountadd])],
        ['text' => get_string('overviewsqadd', 'flashcards'),
            'link' => get_string('overviewsaddlinks', 'flashcards', ['linkcreated' => $filterlink3->out(false), 'valuecreated' => $studentquestioncount,
                'linkadded' => $filterlink6->out(false), 'valueadded' => $studentquestioncountadd])],
        ['text' => get_string('overviewnq', 'flashcards'),
            'link' => get_string('overviewtnqlink', 'flashcards', ['linknq' => $filterlink4->out(false), 'valuenq' => $newquestioncount])]
        
    ];
}
$params = ['action' => 'create', 'cmid' => $cm->id, 'courseid' => $course->id, 'origin' => $PAGE->url, 'fcid' => $flashcards->id];
$createbtnlink = new moodle_url('/mod/flashcards/simplequestion.php', $params);
$teacherviewlink = new moodle_url('/mod/flashcards/teacherview.php', ['cmid' => $cm->id]);
$studentviewlink = new moodle_url('/mod/flashcards/studentview.php', ['id' => $cm->id]);

$templateinfo = ['createbtnlink' => $createbtnlink->out(false),
    'teacherviewlink' => $teacherviewlink->out(false),
    'studentviewlink' => $studentviewlink->out(false),
    'listentries' => array_values($tabledata)
];

$renderer = $PAGE->get_renderer('core');

echo $OUTPUT->header();
echo $renderer->render_from_template('mod_flashcards/overview', $templateinfo);
echo $OUTPUT->footer();
