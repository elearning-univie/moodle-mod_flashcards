<?php

defined('MOODLE_INTERNAL') || die();

require_once('locallib.php');

class renderer {
    var $userid;
    var $box;
    var $context;
    var $flashcardsid;
    var $courseid;

    function __construct($userid, $box, $flashcardsid, $courseid) {
        $this->userid = $userid;
        $this->box = $box;
        $this->courseid = $courseid;
        $this->flashcardsid = $flashcardsid;
    }

    function get_question_for_student_course_box($userid, $box) {
        global $DB;
        $i = 0;

        #TODO active abfragen
        $sql = "SELECT min(questionid) AS questionid FROM {flashcards_q_stud_rel} q " .
                "WHERE q.studentid = :userid AND q.currentbox = :box AND q.lastanswered = " .
                "(SELECT min(lastanswered) FROM {flashcards_q_stud_rel} subq WHERE subq.studentid = q.studentid AND subq.currentbox = q.currentbox AND subq.active = q.active)";

        $records = $DB->get_recordset_sql($sql, ['userid' => $userid, 'box' => $box]);

        foreach ($records as $record) {
            $questionid = $record->questionid;
            $i++;
        }

        if ($i != 1) {
            print_error('noquestion', 'mod_flashcards');
        };

        return $questionid;
    }

    function render_question() {
        global $PAGE;

        $cm = get_coursemodule_from_instance("flashcards", 1);
        $context = context_module::instance($cm->id);
        $PAGE->set_context($context);

        $PAGE->requires->js_call_amd('mod_flashcards/studentcontroller', 'init');
        $jsmodule = array(
                'name' => 'core_question_engine',
                'fullpath' => '/question/qengine.js'
        );
        $PAGE->requires->js_init_call('M.core_question_engine.init_form',
                array('#mod-flashcards-responseform'), false, $jsmodule);

        $quba = question_engine::make_questions_usage_by_activity('flashcards', $context);
        $quba->set_preferred_behaviour('immediatefeedback');
        $questionid = $this->get_question_for_student_course_box($this->userid, $this->box);
        $question = question_bank::load_question($questionid);
        $quba->add_question($question, 1);
        $quba->start_all_questions();
        question_engine::save_questions_usage_by_activity($quba);

        $result =
                '<form id="mod-flashcards-responseform" method="post" action="javascript:;" onsubmit="$.mod_flashcards_call_update(' .
                $this->flashcardsid .
                ',' . $questionid . ')" enctype="multipart/form-data" accept-charset="utf-8">';
        $result .= "\n<div>\n";

        $options = new question_display_options();
        $options->marks = question_display_options::MAX_ONLY;
        $options->markdp = 2;
        $options->feedback = question_display_options::VISIBLE;
        $options->generalfeedback = question_display_options::HIDDEN;

        $result .= $quba->render_question(1, $options);

        return $result;
    }
}