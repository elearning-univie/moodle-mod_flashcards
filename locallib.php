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
 * Private page module utility functions
 *
 * @package mod_flashcards
 * @copyright  2021 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('FLASHCARDS_LN', 'mod_flashcards_ln_');
define('FLASHCARDS_LN_COUNT', 'mod_flashcards_ln_count_');
define('FLASHCARDS_LN_KNOWN', 'mod_flashcards_ln_known_');
define('FLASHCARDS_LN_UNKNOWN', 'mod_flashcards_ln_unknown_');

define('FLASHCARDS_AUTHOR_NONE', 0);
define('FLASHCARDS_AUTHOR_GROUP', 1);
define('FLASHCARDS_AUTHOR_NAME', 2);

define('FLASHCARDS_CHECK_NONE', 0);
define('FLASHCARDS_CHECK_POS', 1);
define('FLASHCARDS_CHECK_NEG', 2);

define('FLASHCARDS_PEER_REVIEW_NONE', 0);
define('FLASHCARDS_PEER_REVIEW_UP', 1);
define('FLASHCARDS_PEER_REVIEW_DOWN', 2);

define('DEFAULT_PAGE_SIZE', 20);
/**
 * Checks if the user has the right to view the course
 *
 * @param int $flashcardsid
 * @return array
 * @throws coding_exception
 * @throws moodle_exception
 * @throws require_login_exception
 */
function mod_flashcards_check_student_rights($flashcardsid) {
    list ($course, $cm) = get_course_and_cm_from_instance($flashcardsid, 'flashcards');
    $context = context_module::instance($cm->id);

    if (!is_role_switched($course->id) && !has_capability('mod/flashcards:editallquestions', $context)) {
        if (!$course->visible || !$cm->visible) {
            throw new require_login_exception("Course or course module not visible.");
        }
    }

    require_login($course, false, $cm);
    return [$context, $course, $cm];
}

/**
 * Get the next question for the given student and box
 *
 * @param int $flashcardsid
 * @param int $boxid
 * @return int
 */
function mod_flashcards_get_next_question($flashcardsid, $boxid) {
    global $DB, $USER;

    if ($boxid > 0) {
        try {
            mod_flashcards_check_student_rights($flashcardsid);
        } catch (require_login_exception $e) {
            return false;
        }

        $sql = "SELECT questionid
                    FROM (
                        SELECT fq.questionid,
                               ROW_NUMBER() OVER (ORDER BY fqr.lastanswered ASC, fq.questionid ASC) AS rn
                        FROM {flashcards_question} fq
                        JOIN {flashcards_q_stud_rel} fqr ON fq.id = fqr.fqid
                        WHERE fq.fcid = :fcid
                          AND fqr.studentid = :userid
                          AND fqr.currentbox = :box
                    ) subquery
                    WHERE rn = 1;
                    ";

        $questionid = $DB->get_field_sql($sql,
            ['userid' => $USER->id, 'box' => $boxid, 'fcid' => $flashcardsid]);

        return $questionid;
    } else {
        // Return first element of array and remove it from the session array.
        return array_shift($_SESSION[FLASHCARDS_LN . $flashcardsid]);
    }
}

/**
 * Checks for the question subcategory with the name 'von Studierenden erstellt' and adds it if not found
 * @param int $contextid
 * @param stdClass $flashcards
 * @param int $categoryid
 * @return int
 *
 */
function mod_flashcards_create_student_category_if_not_exists($contextid, $flashcards, $categoryid) {
    global $DB;

    $subcatid = $DB->get_field('question_categories', 'id',
        ['contextid' => $contextid, 'parent' => $categoryid, 'name' => get_string('createdbystudents', 'mod_flashcards')]);

    if (!$flashcards->studentsubcat && !$subcatid) {
        $cat = new stdClass();
        $cat->parent = $categoryid;
        $cat->contextid = $contextid;
        $cat->name = get_string('createdbystudents', 'mod_flashcards');
        $cat->info = get_string('createdbystudents', 'mod_flashcards');
        $cat->infoformat = 0;
        $cat->sortorder = 999;
        $cat->stamp = make_unique_id_code();
        $cat->idnumber = null;

        $subcatid = $DB->insert_record('question_categories', $cat);
    }

    return $subcatid;
}

/**
 * Get the questiontext for a preview (first 30 characters)
 * @param context $context
 * @param int $questionid
 * @param string $questiontext
 * @return string the first 30 chars of a question
 */
function mod_flashcards_get_preview_questiontext($context, $questionid, $questiontext) {
    $questiontext =
    file_rewrite_pluginfile_urls($questiontext, 'pluginfile.php', $context->id, 'question', 'questiontext',
        $questionid);
    $questiontext = format_text($questiontext, FORMAT_HTML);

    preg_match_all('/<img[^>]+>/i', $questiontext, $images);

    if (!empty($images)) {
        foreach ($images[0] as $image) {
            preg_match('/alt="(.*?)"/', $image, $imagealt);
            if (!empty($imagealt[1])) {
                $questiontext = str_replace($image, $imagealt[1], $questiontext);
            } else {
                $questiontext = str_replace($image, get_string('noimagetext', 'mod_flashcards'), $questiontext);
            }
        }
    }

    $questiontext = html_to_text($questiontext, 0);

    if (strlen($questiontext) > 30) {
        $questiontext = substr($questiontext, 0, 30) . '...';
    }
    return $questiontext;
}

/**
 * Deletes a student question, checks for rights before deleting
 * @param int $questionid the db-id of the question
 * @param stdClass $flashcards the flashcards-object
 * @param stdClass $context the context of the flashcards-module
 * @throws coding_exception if the question is null
 */
function mod_flashcards_delete_student_question($questionid, $flashcards, $context) {
    global $CFG, $DB;

    $question = question_bank::load_question($questionid);
    $sql = "SELECT q.createdby FROM {question} q JOIN {question_versions} v ON v.questionid = q.id
      WHERE v.questionbankentryid  = $question->questionbankentryid
        AND v.version = (SELECT MIN(v.version) FROM {question_versions} v WHERE v.questionbankentryid = $question->questionbankentryid)";
    $v1createdby = $DB->get_field_sql($sql);

    require_capability('mod/flashcards:deleteownquestion', $context);
    require_sesskey();
    if (!$questionid) {
        throw new coding_exception('deleting a question requires an id of the question to delete');
    }
    require_once($CFG->dirroot . '/lib/questionlib.php');
    if (!mod_flashcards_has_delete_rights($context, $flashcards, $questionid, $v1createdby)) {
        throw new \moodle_exception('deletion_not_allowed', 'flashcards');
        return;
    }
    $sql = "SELECT id FROM {flashcards_question}
      WHERE questionid  = $questionid
        AND fcid = $flashcards->id ";
    $fqsid = $DB->get_field_sql($sql);
    $DB->delete_records('question_references', ['component' => 'mod_flashcards', 'questionarea' => 'slot', 'itemid' => $fqsid]);

    if (questions_in_use([$questionid])) {
        $DB->set_field('question', 'hidden', 1, ['id' => $questionid]);
    } else {
        question_delete_question($questionid);
    }
    $DB->delete_records('flashcards_question', ['questionid' => $questionid, 'fcid' => $flashcards->id]);

    return;
}

/**
 * checks if the user has deletion rights for this question
 * @param stdClass $context context of the flashcards module
 * @param stdClass $flashcards flashcards object
 * @param stdClass $questionid id of the question
 * @param stdClass $v1createdby id of original creater of question
 * @return boolean true if allowed to delete, false if not
 */
function mod_flashcards_has_delete_rights($context, $flashcards, $questionid, $v1createdby = 0) {
    global $USER;
    $result = has_capability('mod/flashcards:deleteownquestion', $context);
    $question = question_bank::load_question_data($questionid);
    if ($v1createdby != $USER->id ||
        $question->category != $flashcards->studentsubcat ||
        $question->qtype != 'flashcard') {
        $result = false;
    }
        return $result;
}

/**
 * Get the username of a question creator
 * @param int $userid the userid of the author
 * @param int $courseid id of the course (needed if setting authordisplay set to "teacher/student")
 * @param int $authordisplay The type of how the author is displayed
 * @param bool $displayteachername to display the teachers name
 * @return string
 */
function mod_flashcards_get_author_display_name($userid, $courseid, $authordisplay = null, $displayteachername = false) {
    global $DB, $USER;
    if (!$authordisplay) {
        $authordisplay = get_config('flashcards', 'authordisplay');
    }

    $author = '';

    if ($authordisplay) {
        if ($authordisplay == FLASHCARDS_AUTHOR_GROUP) {
            $roleids = explode(',', get_config('flashcards', 'authordisplay_group_teacherroles'));
            if (count($roleids) > 0) {
                list($inrolesql, $roleparams) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'roleids');
                $params['courseid'] = $courseid;
                $params['userid'] = $userid;
                $sql = "SELECT userid
                          FROM {role_assignments} ra,
                               {context} c
                         WHERE c.contextlevel = 50
                           AND ra.contextid = c.id
                           AND c.instanceid = :courseid
                           AND ra.roleid $inrolesql
                           AND ra.userid = :userid";
                $isteacher = $DB->record_exists_sql($sql, $roleparams + $params);
            } else {
                $isteacher = false;
            }
            if ($userid == $USER->id) {
                $author = get_string('author_me', 'flashcards');
            } else if ($isteacher) {
                if ($displayteachername) {
                    $sql = "SELECT id,
                           firstname,
                           lastname,
                           firstnamephonetic,
                           lastnamephonetic,
                           middlename,
                           alternatename
                      FROM {user}
                     WHERE id = :id";
                    $user = $DB->get_record_sql($sql, ['id' => $userid]);
                    $author = fullname($user) . ' (' . $author = get_string('author_teacher', 'flashcards') . ')';
                } else {
                    $author = get_string('author_teacher', 'flashcards');
                }
            } else {
                $author = get_string('author_student', 'flashcards');
            }
        } else if ($authordisplay == FLASHCARDS_AUTHOR_NAME) {
            $sql = "SELECT id,
                           firstname,
                           lastname,
                           firstnamephonetic,
                           lastnamephonetic,
                           middlename,
                           alternatename
                      FROM {user}
                     WHERE id = :id";
            $user = $DB->get_record_sql($sql, ['id' => $userid]);
            $author = fullname($user);
        }

    }
    return $author;
}

/**
 * Returns an array with the pix_icon and color for the teacher check info column
 *
 * @param int $teachercheckresult
 * @return string[]|\pix_icon[]
 */
function mod_flashcard_get_teacher_check_info($teachercheckresult) {

    $checkinfo = [];
    if ($teachercheckresult == FLASHCARDS_CHECK_POS) {
        $checkicon = new \pix_icon('t/check', get_string('statusval1', 'flashcards'));
        $checkinfo['color'] = 'color-approved';
    } else if ($teachercheckresult == FLASHCARDS_CHECK_NEG) {
        $checkicon = new \pix_icon('e/cancel', get_string('statusval2', 'flashcards'));
        $checkinfo['color'] = 'color-declined';
    } else {
        $checkicon = new \pix_icon('e/question', get_string('statusval0', 'flashcards'));
        $checkinfo['color'] = 'color-pending';
    }
    $checkinfo['icon'] = $checkicon->export_for_pix();
    return $checkinfo;
}

/**
 * Returns a string for the class attribute of the respectiveup/downvote buttons.
 *
 * @param int $peerreviewvote
 * @param bool $isup
 * @return string
 */
function mod_flashcard_get_peer_review_info(int $peerreviewvote, bool $isup) {

    if ($peerreviewvote == FLASHCARDS_PEER_REVIEW_UP && $isup) {
        return 'btn-up';
    } else if ($peerreviewvote == FLASHCARDS_PEER_REVIEW_DOWN && !$isup) {
        return 'btn-down';
    } else {
        return '';
    }
}

/**
 * Returns the peer review vote of the current user for a certain question and flashcard.
 *
 * @param int $fqid
 * @return int
 */
function mod_flashcard_get_peer_review_vote_user(int $fqid) {
    global $DB, $USER;

    $sql = "SELECT peerreview
              FROM {flashcards_q_stud_rel} sd
             WHERE sd.fqid = :fqid
               AND sd.studentid = :studentid";
    $prvote = $DB->get_field('flashcards_q_stud_rel', 'peerreview', ['fqid' => $fqid, 'studentid' => $USER->id]);

    if (!$prvote) {
        return 0;
    }

    return $prvote;
}
/**
 * Returns string with the number of up/down votes for the a flashcard in a certain flashcards activity.
 *
 * @param int $fqid
 * @return int
 */
function mod_flashcard_get_peer_review_votes(int $fqid) {
    global $DB;

    $sql = "SELECT COUNT(id)
              FROM {flashcards_q_stud_rel} sd
             WHERE sd.fqid = :fqid
               AND sd.peerreview = :vote";

    $votes['upvotes'] = $DB->count_records_sql($sql, ['fqid' => $fqid, 'vote' => 1]);
    $votes['downvotes'] = $DB->count_records_sql($sql, ['fqid' => $fqid, 'vote' => 2]);

    return $votes;
}

/**
 * Creates a textual representation of a question for display.
 *
 * @param object $question A question object from the database questions table
 * @param bool $showicon If true, show the question's icon with the question. False by default.
 * @param bool $showquestiontext If true (default), show question text after question name.
 *       If false, show only question name.
 * @param bool $showidnumber If true, show the question's idnumber, if any. False by default.
 * @param core_tag_tag[]|bool $showtags if array passed, show those tags. Else, if true, get and show tags,
 *       else, don't show tags (which is the default).
 * @return string HTML fragment.
 */
function mod_flashcards_question_tostring($question, $showicon = false, $showquestiontext = true,
    $showidnumber = false, $showtags = false) {
    global $OUTPUT;
    $result = '';

    // Question name.
    $name = shorten_text(format_string($question->name), 200);
    if ($showicon) {
        $name .= print_question_icon($question) . ' ' . $name;
    }
    $result .= html_writer::span($name, 'questionname');

    // Question idnumber.
    if ($showidnumber && $question->idnumber !== null && $question->idnumber !== '') {
        $result .= ' ' . html_writer::span(
            html_writer::span(get_string('idnumber', 'question'), 'accesshide') .
            ' ' . s($question->idnumber), 'badge badge-primary');
    }

    // Question tags.
    if (is_array($showtags)) {
        $tags = $showtags;
    } else if ($showtags) {
        $tags = core_tag_tag::get_item_tags('core_question', 'question', $question->id);
    } else {
        $tags = [];
    }
    if ($tags) {
        $result .= $OUTPUT->tag_list($tags, null, 'd-inline', 0, null, true);
    }

    // Question text.
    if ($showquestiontext) {
        $questiontext = question_utils::to_plain_text($question->questiontext,
            $question->questiontextformat, ['noclean' => true, 'para' => false, 'filter' => false]);
        $questiontext = shorten_text($questiontext, 50);
        if ($questiontext) {
            $result .= ' ' . html_writer::span(s($questiontext), 'questiontext');
        }
    }

    return $result;
}

/**
 * Add a question to a flashcard collection
 *
 * @param int $questionid The id of the question to be added
 * @param int $flashcardsid The id of the flashcard collection
 * @return bool false if the question was already in the collection
 * @throws dml_exception
 * @throws dml_transaction_exception
 */
function mod_flashcards_add_question($questionid, $flashcardsid) {
    global $DB, $USER;

    list ($course, $cm) = get_course_and_cm_from_instance($flashcardsid, 'flashcards');
    $context = context_module::instance($cm->id);

    if ($DB->record_exists('flashcards_question', ['questionid' => $questionid, 'fcid' => $flashcardsid])) {
        return false;
    }
    $question = question_bank::load_question($questionid);

    switch ($question->get_type_name()) {
        case 'multichoice':
            $questionid = mod_flashcards_multichoice_to_flashcard($question, $flashcardsid);
            break;
        case 'multichoiceset':
            $questionid = mod_flashcards_multichoice_to_flashcard($question, $flashcardsid);
            break;
        case 'truefalse':
            $questionid = mod_flashcards_truefalse_to_flashcard($question, $flashcardsid);
            break;
        case 'shortanswer':
            $questionid = mod_flashcards_shortanswer_to_flashcard($question, $flashcardsid);
            break;
        case 'multianswer':
            $questionid = mod_flashcards_multianswer_to_flashcard($question, $flashcardsid);
            break;
    }
    $qbe = get_question_bank_entry($questionid);

    $trans = $DB->start_delegated_transaction();
    $fcqstatusid = $DB->insert_record('flashcards_question',
        ['questionid' => $questionid, 'qbankentryid' => $qbe->id, 'fcid' => $flashcardsid, 'teachercheck' => 0, 'addedby' => $USER->id], true);

    $questionreferences = new \StdClass();
    $questionreferences->usingcontextid = $context->id;
    $questionreferences->component = 'mod_flashcards';
    $questionreferences->questionarea = 'slot';
    $questionreferences->itemid = $fcqstatusid;
    $questionreferences->questionbankentryid = get_question_bank_entry($questionid)->id;
    $DB->insert_record('question_references', $questionreferences);

    $trans->allow_commit();
}

/**
 * get qids for selected flashcards
 *
 * @param int $flashcardsid
 * @param array $qids
 * @return array
 */
function mod_flashcards_get_selected_qids($flashcardsid, $qids) {
    global $DB;

    list($inids, $questionids) = $DB->get_in_or_equal($qids, SQL_PARAMS_NAMED);

    $sql = "SELECT fqs.id
              FROM {flashcards_question} fqs
              JOIN {question_versions} qv ON fqs.qbankentryid = qv.questionbankentryid
              JOIN {question} q ON qv.questionid = q.id
             WHERE fqs.fcid = :fcid
               AND q.id $inids";

    $questionids = $DB->get_fieldset_sql($sql, ['fcid' => $flashcardsid] + $questionids);

    return $questionids;
}

/**
 * Fires Level up events.
 *
 * @param int $flashcardsid
 * @param bool $isshuffle
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function mod_flashcards_load_xp_events($flashcardsid, $isshuffle = false) {
    global $DB, $USER;

    list ($course, $cm) = get_course_and_cm_from_instance($flashcardsid, 'flashcards');
    $context = context_module::instance($cm->id);

    $eventparams = [
        'context' => $context,
        'objectid' => $flashcardsid,
    ];

    $eventtriggered = false;

    $eventsrec = $DB->get_record('flashcards_stud_xp_events', ['fcid' => $flashcardsid, 'studentid' => $USER->id]);
    $countfirstcard = $DB->count_records_select('flashcards_q_stud_rel',
        'currentbox is not null AND studentid = :studid AND flashcardsid = :fcid',
        ['studid' => $USER->id, 'fcid' => $flashcardsid]);

    if (!$eventsrec) {
        $newentry = new stdClass();
        $newentry->fcid = $flashcardsid;
        $newentry->studentid = $USER->id;

        $entryid = $DB->insert_record('flashcards_stud_xp_events', $newentry);
        $eventsrec = $DB->get_record('flashcards_stud_xp_events', ['id' => $entryid]);
    }

    if ($countfirstcard) {
        if (!$eventsrec->firstquestion) {
            $event = \mod_flashcards\event\levelup_firstquestion::create($eventparams);
            $event->trigger();
            $eventsrec->firstquestion = 1;
            $eventtriggered = true;
        } else {
            $countmaxbox = $DB->count_records('flashcards_q_stud_rel',
                ['studentid' => $USER->id, 'flashcardsid' => $flashcardsid, 'currentbox' => 5]);

            if ($countmaxbox) {
                $questioncount = $DB->count_records('flashcards_question', ['fcid' => $flashcardsid]);

                if ((($countmaxbox / $questioncount) >= 0.25) && !$eventsrec->firstcheckpoint) {
                    $event = \mod_flashcards\event\levelup_firstcheckpoint::create($eventparams);
                    $event->trigger();
                    $eventsrec->firstcheckpoint = 1;
                    $eventtriggered = true;
                }
                if ((($countmaxbox / $questioncount) >= 0.5) && !$eventsrec->secondcheckpoint) {
                    $event = \mod_flashcards\event\levelup_secondcheckpoint::create($eventparams);
                    $event->trigger();
                    $eventsrec->secondcheckpoint = 1;
                    $eventtriggered = true;
                }
                if ((($countmaxbox / $questioncount) >= 0.9) && !$eventsrec->thirdcheckpoint) {
                    $event = \mod_flashcards\event\levelup_thirdcheckpoint::create($eventparams);
                    $event->trigger();
                    $eventsrec->thirdcheckpoint = 1;
                    $eventtriggered = true;
                }
            }
        }
    }

    if ($isshuffle && !$eventsrec->usedshuffle) {
        $event = \mod_flashcards\event\levelup_learnnow::create($eventparams);
        $event->trigger();
        $eventsrec->usedshuffle = 1;
        $eventtriggered = true;
    }

    if ($eventtriggered) {
        $DB->update_record('flashcards_stud_xp_events', $eventsrec);
    }
}

/**
 * copy multichoice to flashcard
 *
 * @param int $question
 * @param int $flashcardsid
 * @return int $flashcardsid
 */
function mod_flashcards_multichoice_to_flashcard($question, $flashcardsid) {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/lib/questionlib.php');
    list ($course, $cm) = get_course_and_cm_from_instance($flashcardsid, 'flashcards');
    $context = context_module::instance($cm->id);
    $flashcard = $DB->get_record('flashcards', ['id' => $flashcardsid]);

    list($answeroptions, $correctanswer) = mod_flashcards_mc_html_answer_list($question->answers, 'circle');
    $fcquestionext = $question->questiontext . $answeroptions;
    $fcanswerext = $question->questiontext . $correctanswer;

    $question2fc = mod_flashcards_create_flashcard($question, $flashcard, $fcquestionext, $fcanswerext);
    $answerid = array_key_first($question2fc->answers);

    mod_flashcards_save_image_files_for_flashcards($question, $question2fc->id, $answerid);

    // Set 2fc tag to mc question.
    mod_flashcards_add_2fc_tag($question->id, $context->id);

    return $question2fc->id;
}

/**
 * copy true/false to flashcard
 *
 * @param int $question
 * @param int $flashcardsid
 * @return int $flashcardsid
 */
function mod_flashcards_truefalse_to_flashcard($question, $flashcardsid) {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/lib/questionlib.php');
    list ($course, $cm) = get_course_and_cm_from_instance($flashcardsid, 'flashcards');
    $context = context_module::instance($cm->id);
    $flashcard = $DB->get_record('flashcards', ['id' => $flashcardsid]);

    $rightanswer = $question->rightanswer;
    $fcquestionext = '<p dir="ltr" style="text-align: left; font-weight: bold; font-size:110%;">'
                     . get_string('trueorfalse', 'mod_flashcards') . '<br></p>';
    $fcquestionext = $fcquestionext . $question->questiontext;
    if ($rightanswer) {
        $tfanswerid = $question->trueanswerid;
        $fcanswerext = '<p dir="ltr" style="font-weight: bold; font-size:130%;">'
                       . get_string('true', 'qtype_truefalse')
                       . '<br></p>';
    } else {
        $tfanswerid = $question->falseanswerid;
        $fcanswerext = '<p dir="ltr" style="font-weight: bold; font-size:130%;">'
                       . get_string('false', 'qtype_truefalse')
                       . '<br></p>';
    }

    $question2fc = mod_flashcards_create_flashcard($question, $flashcard, $fcquestionext, $fcanswerext);
    $answerid = array_key_first($question2fc->answers);

    mod_flashcards_save_image_files_for_flashcards($question, $question2fc->id, $answerid, $tfanswerid);

    // Set 2fc tag to mc question.
    mod_flashcards_add_2fc_tag($question->id, $context->id);

    return $question2fc->id;
}
/**
 * copy shortanswer to flashcard
 *
 * @param int $question
 * @param int $flashcardsid
 * @return int $flashcardsid
 */
function mod_flashcards_shortanswer_to_flashcard($question, $flashcardsid) {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/lib/questionlib.php');
    list ($course, $cm) = get_course_and_cm_from_instance($flashcardsid, 'flashcards');
    $context = context_module::instance($cm->id);
    $flashcard = $DB->get_record('flashcards', ['id' => $flashcardsid]);

    $fcquestionext = $question->questiontext;
    $fcanswerext = mod_flashcards_short_answer_html_list_sorted($question->answers);

    $question2fc = mod_flashcards_create_flashcard($question, $flashcard, $fcquestionext, $fcanswerext);
    $answerid = array_key_first($question2fc->answers);

    mod_flashcards_save_image_files_for_flashcards($question, $question2fc->id, $answerid);

    // Set 2fc tag to mc question.
    mod_flashcards_add_2fc_tag($question->id, $context->id);

    return $question2fc->id;
}
/**
 * copy multianswer to flashcard
 *
 * @param int $question
 * @param int $flashcardsid
 * @return int $flashcardsid
 */
function mod_flashcards_multianswer_to_flashcard($question, $flashcardsid) {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/lib/questionlib.php');
    list ($course, $cm) = get_course_and_cm_from_instance($flashcardsid, 'flashcards');
    $context = context_module::instance($cm->id);
    $flashcard = $DB->get_record('flashcards', ['id' => $flashcardsid]);

    $dropdown = ['MULTICHOICE', 'MC', 'MULTICHOICE_S', 'MCS'];
    $radiobutton = ['MULTICHOICE_H', 'MCH', 'MULTICHOICE_HS', 'MCHS', 'MULTICHOICE_V', 'MCV', 'MULTICHOICE_VS', 'MCVS'];
    $checkbox = ['MULTIRESPONSE_H', 'MRH', 'MULTIRESPONSE_HS', 'MRHS', 'MULTIRESPONSE', 'MR, MULTIRESPONSE_S', 'MRS'];

    $subquestions = $question->subquestions;
    $rawquestiontext = $question->questiontext;
    $rawanswertext = $question->questiontext;

    $matches = [];
    preg_match_all('/\{#[0-9]+\}/', $rawquestiontext, $matches, PREG_OFFSET_CAPTURE);
    $matches = $matches[0];
    $placeholders = [];
    foreach ($matches as $match) {
        array_push($placeholders, $match[0]);
    }
    $i = 0;
    foreach ($subquestions as $subquestion) {
        $answers = $subquestion->answers;
        $answeroptions = '';
        $correctanswer = '';
        $mcsubtext = [];
        if (get_class($subquestion->qtype) == 'qtype_multichoice') {
            preg_match('/M[A-Z]+_?[A-Z]?/', $subquestion->questiontext, $mcsubtext);
            if (in_array($mcsubtext[0], $dropdown)) {
                list($answeroptions, $correctanswer) = mod_flashcards_mc_html_answer_list($answers, 'circle');
                $answeroptions = ' _______ ' . $answeroptions;
                $correctanswer = ' _______ ' . $correctanswer;
            } else if (in_array($mcsubtext[0], $radiobutton)) {
                list($answeroptions, $correctanswer) = mod_flashcards_mc_html_answer_list($answers, 'circle');
            } else if (in_array($mcsubtext[0], $checkbox)) {
                list($answeroptions, $correctanswer) = mod_flashcards_mc_html_answer_list($answers, 'square');
            }
        } else if (get_class($subquestion->qtype) == 'qtype_shortanswer' || get_class($subquestion->qtype) == 'qtype_numerical') {
            $answeroptions = ' _______ ';
            $correctanswer = ' _______ ' . mod_flashcards_short_answer_html_list_sorted($answers);
        }

        $rawquestiontext = str_replace($placeholders[$i], $answeroptions, $rawquestiontext);
        $rawanswertext = str_replace($placeholders[$i], $correctanswer, $rawanswertext);
        $i++;
    }

    $fcquestionext = $rawquestiontext;
    $fcanswerext = $rawanswertext;

    $question2fc = mod_flashcards_create_flashcard($question, $flashcard, $fcquestionext, $fcanswerext);
    $answerid = array_key_first($question2fc->answers);

    mod_flashcards_save_image_files_for_flashcards($question, $question2fc->id, $answerid);
    // Set 2fc tag to mc question.
    mod_flashcards_add_2fc_tag($question->id, $context->id);

    return $question2fc->id;
}
/**
 * add 2fc tag to origin question
 *
 * @param array $options
 * @param string $type
 * @return array
 */
function mod_flashcards_mc_html_answer_list(array $options, string $type) {

    $checkboxopen = '';
    $checkboxclosed = '';
    $correct = "disc";
    if ($type == 'square') {
        $type = 'none';
        $correct = 'none';
        $checkboxopen = '&#9634; ';
        $checkboxclosed = '&#9635; ';
    }
    $answeroptions = '<ul style="margin-left: 20px;list-style-type:' . $type . ';">';
    $correctanswer = $answeroptions;
    foreach ($options as $option) {
        $answeroptions .= '<li>' . $checkboxopen . strip_tags($option->answer) . '</li>';
        if ( $option->fraction == 1) {
            $correctanswer .= '<li aria-label="' . get_string('answeriscorrect', 'flashcards') .
            '" style="list-style-type:' . $correct . '; font-weight: bold;">' . $checkboxclosed . strip_tags($option->answer) . ' ('
                . intval(preg_replace('/[^\d.]/', '', ($option->fraction * 100)))
            .'% ' . get_string('correct', 'flashcards') . ')</li>';
        } else if ($option->fraction > 0 && $option->fraction != 1) {
            $correctanswer .= '<li aria-label="' . get_string('answeriscorrect', 'flashcards') .
            '" style="list-style-type:' . $correct . '; font-weight: bold;">' . $checkboxclosed . strip_tags($option->answer) . ' ('
                . intval(preg_replace('/[^\d.]/', '', ($option->fraction * 100)))
                .'% ' . get_string('correct', 'flashcards') . ')</li>';
        } else {
            $correctanswer .= '<li aria-label="' . get_string('answeriswrong', 'flashcards') . '">' . $checkboxopen
                            . strip_tags($option->answer) . ' ('
                            . get_string('statusval2', 'flashcards') .')</li>';
        }
    }
    $answeroptions .= '</ul>';
    $correctanswer .= '</ul>';

    return [$answeroptions, $correctanswer];
}
/**
 * add 2fc tag to origin question
 *
 * @param array $answers
 * @return string
 */
function mod_flashcards_short_answer_html_list_sorted(array $answers) {

    $fractions = [];
    $numbercorrect = 0;
    foreach ($answers as $answer) {
        $fractions[$answer->id] = $answer->fraction;
        if ($answer->fraction > 0) {
            $numbercorrect++;
        }
    }
    arsort($fractions);
    $fcanswerext = '';
    if ($numbercorrect > 1) {
        $fcanswerext = '<p dir="ltr" style="text-align: left; font-weight: bold; font-size:110%;">'
            . get_string('multipepossibleanswers', 'mod_flashcards')
            . '<br></p>';
    }
    $fcanswerext .= '<p dir="ltr" style="text-align: left; font-weight: bold; font-size:110%;">'
        . get_string('correctanswers', 'mod_flashcards')
        . '<br></p> <ul style="margin-left: 20px;list-style-type:circle;">';
    $partialcorrect = true;
    foreach ($fractions as $key => $fraction) {
        if ($fraction == 1) {
            $fcanswerext = $fcanswerext . '<li style="list-style-type:disc">'
                . $answers[$key]->answer . '</li>';
        }
        if ($fraction < 1 && $fraction != 0) {
            if ($partialcorrect) {
                $fcanswerext = $fcanswerext . '</ul><p dir="ltr" style="text-align: left; font-weight: bold; font-size:110%;">'
                    . get_string('partialcorrectanswers', 'mod_flashcards')
                    . '<br></p> <ul style="margin-left: 20px;list-style-type:circle;">';
                    $partialcorrect = false;
            }
            $fcanswerext = $fcanswerext . '<li style="list-style-type:disc">'
                . $answers[$key]->answer . ' (' . intval(preg_replace('/[^\d.]/', '', ($fraction * 100)))
                .'% ' . get_string('correct', 'mod_flashcards') . ')</li>';
        }
    }

        $fcanswerext = $fcanswerext . '</ul>';
        return $fcanswerext;
}
/**
 * add 2fc tag to origin question
 *
 * @param int $questionid
 * @param int $contextid
 */
function mod_flashcards_add_2fc_tag(int $questionid, int $contextid) {
    global $DB, $USER;

    // Get tag id.
    $tag = $DB->get_record('tag', ['name' => '2fc']);

    if (!$tag) {
        $dataobject = new stdClass();
        $dataobject->userid = $USER->id;
        $dataobject->isstandard   = 0;
        $dataobject->timemodified = time();
        $dataobject->tagcollid    = 1;
        $dataobject = (object)(array)$dataobject;
        $dataobject->rawname = '2fc';
        $dataobject->name    = '2fc';
        $tagid = $DB->insert_record('tag', $dataobject);
    } else {
        $tagid = $tag->id;
    }

    $taginstance = new stdClass();
    $taginstance->tagid = $tagid;
    $taginstance->component = 'core_question';
    $taginstance->itemtype = 'question';
    $taginstance->itemid = $questionid;
    $taginstance->contextid = $contextid;
    $taginstance->ordering = 0;
    $taginstance->timecreated = time();
    $taginstance->timemodified = time();
    $DB->insert_record('tag_instance', $taginstance);
}

/**
 * copy multichoice to flashcard
 *
 * @param stdClass $question
 * @param int $question2fcid
 * @param int $answerid
 * @param int $tfanswerid
 */
function mod_flashcards_save_image_files_for_flashcards($question, $question2fcid, $answerid, $tfanswerid = 0) {
    global $DB;

    $fs = new \file_storage();
    list($inids, $questionids) = $DB->get_in_or_equal([$question->id, $tfanswerid], SQL_PARAMS_NAMED);
    $sql = "SELECT * FROM {files} WHERE itemid $inids AND component = 'question'";
    $files = $DB->get_records_sql($sql, $questionids);

    foreach ($files as $file) {
        unset($file->id);
        unset($file->pathnamehash);

        if ($question->get_type_name() == 'multichoice' || $question->get_type_name() == 'multichoiceset'
            || $question->get_type_name() == 'multianswer') {
            if ($file->filearea == 'questiontext' || $file->filearea == 'answer') {
                mod_flashcards_save_file($file, $question2fcid, 'questiontext');
                mod_flashcards_save_file($file, $answerid, 'answer');
            }
        }
        if ($question->get_type_name() == 'truefalse') {
            if ($file->filearea == 'answer') {
                mod_flashcards_save_file($file, $answerid, 'answer');
            } else if ($file->filearea == 'questiontext') {
                mod_flashcards_save_file($file, $question2fcid, 'questiontext');
            }
        }
        if ($question->get_type_name() == 'shortanswer') {
            mod_flashcards_save_file($file, $question2fcid, $file->filearea);
        }
    }

}
/**
 * copy multichoice to flashcard
 *
 * @param stdClass $file
 * @param int $itemid
 * @param string $filearea
 */
function mod_flashcards_save_file($file, $itemid, $filearea) {
    global $DB;

    $fs = new \file_storage();
    $file->itemid = $itemid;
    $file->filearea = $filearea;
    $file->timecreated = time();
    $file->pathnamehash = $fs->get_pathname_hash($file->contextid, 'question', $filearea, $itemid, '/', $file->filename);
    $DB->insert_record('files', $file);
}
/**
 * create flashcard
 *
 * @param stdClass $question
 * @param stdClass $flashcard
 * @param string $fcquestionext
 * @param string $fcanswerext
 * @return stdClass
 */
function mod_flashcards_create_flashcard($question, $flashcard, $fcquestionext, $fcanswerext) {
    global $USER;

    list ($course, $cm) = get_course_and_cm_from_instance($flashcard->id, 'flashcards');
    $context = context_module::instance($cm->id);

    $qtype = 'flashcard';
    $qtypeobj = question_bank::get_qtype($qtype);
    $question2fc = new stdClass();
    $question2fc->category = $flashcard->categoryid;
    $question2fc->qtype = $qtype;
    $question2fc->createdby = $USER->id;
    $question2fc->options = new stdClass();
    $question2fc->formoptions = new stdClass();
    $question2fc->contextid = $context->id;
    $question2fc->formoptions->canaddwithcat = question_has_capability_on($question, 'add');

    $question2fc->name = $question->name;
    $question2fc->questiontext = $fcquestionext;
    $question2fc->answer = $fcanswerext;
    $question2fc->generalfeedback['text'] = $question->generalfeedback;

    $questioncopy = fullclone($question2fc);
    $questioncopy->category = "{$flashcard->categoryid},{$context->id}";
    $questioncopy->cmid = $cm->id;
    $questioncopy->name = $question->name;
    $questioncopy->questiontext = [];
    $questioncopy->questiontext['text'] = $fcquestionext;
    $questioncopy->questiontext['format'] = FORMAT_HTML;
    $questioncopy->generalfeedback['text'] = $question->generalfeedback;
    $questioncopy->generalfeedbackformat = $question->generalfeedbackformat;

    $questioncopy->answer = [];
    $questioncopy->answer['text'] = $fcanswerext;
    $questioncopy->answer['format'] = FORMAT_HTML;
    $question2fc = $qtypeobj->save_question($question2fc, $questioncopy);
    $question2fc = question_bank::load_question($question2fc->id);

    return $question2fc;
}

/**
 * moves questions into and out of the flashcard collection
 *
 * @param int $flashcardsid
 * @param array $qids
 * @param int $currentbox
 * @return void
 * @throws coding_exception
 * @throws dml_exception
 */
function mod_flashcards_move_question($flashcardsid, $qids, $currentbox=null) {
    global $DB, $USER;

    $questionids = mod_flashcards_get_selected_qids($flashcardsid, $qids);
    $questionarray = [];

    foreach ($questionids as $question) {
        $recid = $DB->get_record('flashcards_q_stud_rel',
            ['fqid' => $question, 'studentid' => $USER->id]);
        if ($recid) {
            $DB->update_record('flashcards_q_stud_rel', ['id' => $recid->id, 'currentbox' => $currentbox]);
        } else {
            $questionentry = [
                'flashcardsid' => $flashcardsid,
                'fqid' => $question,
                'studentid' => $USER->id,
                'active' => 1,
                'currentbox' => $currentbox,
                'lastanswered' => 0,
                'tries' => 0,
                'wronganswercount' => 0,
            ];
            $questionarray[] = $questionentry;
        }
    }
    $DB->insert_records('flashcards_q_stud_rel', $questionarray);
}
