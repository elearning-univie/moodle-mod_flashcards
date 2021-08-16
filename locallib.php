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
 * @copyright  2019 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
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
    return array($context, $course, $cm);
}

/**
 * Get the next question for the given student and box
 *
 * @param int $fid
 * @param int $boxid
 * @return int
 */
function mod_flashcards_get_next_question($fid, $boxid) {
    global $DB, $USER;

    if ($boxid > 0) {
        try {
            mod_flashcards_check_student_rights($fid);
        } catch (require_login_exception $e) {
            return false;
        }

        $sql = "SELECT min(questionid) AS questionid
                 FROM {flashcards_q_stud_rel} q
                 JOIN {question} qq ON qq.id = q.questionid
                WHERE q.studentid = :userid
                  AND q.currentbox = :box
                  AND q.flashcardsid = :flashcardsid
                  AND q.lastanswered =
                      (SELECT min(lastanswered)
                        FROM {flashcards_q_stud_rel} subq
                       WHERE subq.studentid = q.studentid
                         AND subq.currentbox = q.currentbox
                         AND subq.active = q.active
                         AND subq.flashcardsid = q.flashcardsid)";

        $questionid = $DB->get_field_sql($sql,
            ['userid' => $USER->id, 'box' => $boxid, 'flashcardsid' => $fid]);

        return $questionid;
    } else {
        // Return first element of array and remove it from the session array.
        return array_shift($_SESSION[FLASHCARDS_LN . $fid]);
    }
}

/**
 * Deletes records from flashcards_q_stud_rel when the question got deleted
 * @throws dml_exception
 */
function mod_flashcards_check_for_orphan_or_hidden_questions() {
    global $USER, $DB;

    $sql = "questionid NOT IN (SELECT id FROM {question} WHERE hidden = 0) AND studentid = :userid";

    $DB->delete_records_select('flashcards_q_stud_rel', $sql, array('userid' => $USER->id));
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
        ['contextid' => $contextid, 'parent' => $categoryid, 'name' => 'von Studierenden erstellt']);

    if (!$flashcards->studentsubcat && !$subcatid) {
        $cat = new stdClass();
        $cat->parent = $categoryid;
        $cat->contextid = $contextid;
        $cat->name = 'von Studierenden erstellt';
        $cat->info = 'von Studierenden erstellt';
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
    require_capability('mod/flashcards:deleteownquestion', $context);
    require_sesskey();
    if (!$questionid) {
        throw new coding_exception('deleting a question requires an id of the question to delete');
    }
    require_once($CFG->dirroot . '/lib/questionlib.php');
    if (!mod_flashcards_has_delete_rights($context, $flashcards, $questionid)) {
        print_error('deletion_not_allowed', 'flashcards');
        return;
    }
    if (questions_in_use(array($questionid))) {
        $DB->set_field('question', 'hidden', 1, array('id' => $questionid));
    } else {
        question_delete_question($questionid);
    }
    return;
}

/**
 * checks if the user has deletion rights for this question
 * @param stdClass $context context of the flashcards module
 * @param stdClass $flashcards flashcards object
 * @param stdClass $questionid id of the question
 * @return boolean true if allowed to delete, false if not
 */
function mod_flashcards_has_delete_rights($context, $flashcards, $questionid) {
    global $USER;
    $result = has_capability('mod/flashcards:deleteownquestion', $context);
    $question = question_bank::load_question_data($questionid);
    if ($question->createdby != $USER->id ||
        $question->category != $flashcards->studentsubcat ||
        $question->qtype != 'flashcard') {
        $result = false;
    }
    return $result;
}

/**
 * /**
 * Get the username of a question creator
 * @param int $userid the userid of the author
 * @param int $courseid id of the course (needed if setting authordisplay set to "teacher/student")
 * @param int $authordisplay The type of how the author is displayed
 * @return string
 */
function mod_flashcards_get_author_display_name($userid, $courseid, $authordisplay = null) {
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
                $author = get_string('author_teacher', 'flashcards');
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

    $checkinfo = array();
    if ($teachercheckresult == FLASHCARDS_CHECK_POS) {
        $checkicon = new \pix_icon('t/check', get_string('yes'));
        $checkinfo['color'] = 'color-approved';
    } else if ($teachercheckresult == FLASHCARDS_CHECK_NEG) {
        $checkicon = new \pix_icon('e/cancel', get_string('no'));
        $checkinfo['color'] = 'color-declined';
    } else {
        $checkicon = new \pix_icon('e/question', get_string('question'));
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
 * @param int $questionid
 * @param int $fcid
 * @return int
 */
function mod_flashcard_get_peer_review_vote(int $questionid, int $fcid) {
    global $DB, $USER;

    $sql = "SELECT peerreview
              FROM {flashcards_q_stud_rel} sd
             WHERE sd.questionid = :questionid
               AND sd.flashcardsid = :flashcardsid
               AND sd.studentid = :studentid";
    $prvote = $DB->get_field('flashcards_q_stud_rel', 'peerreview', ['questionid' => $questionid, 'flashcardsid' => $fcid, 'studentid' => $USER->id]);

    if (!$prvote) {
        return 0;
    }

    return $prvote;
}
/**
 * Returns string with the number of up/down votes for the a flashcard in a certain flashcards activity.
 *
 * @param int $questionid
 * @param int $fcid
 * @param bool $upvotes
 * @return int
 */
function mod_flashcard_get_peer_review_votes(int $questionid, int $fcid, bool $upvotes) {
    global $DB;

    $params['questionid'] = $questionid;
    $params['fcid'] = $fcid;
    $params['vote'] = 2;
    if ($upvotes) {
        $params['vote'] = 1;
    }

    $sql = "SELECT COUNT(id)
              FROM {flashcards_q_stud_rel} sd
             WHERE sd.questionid = :questionid
               AND sd.flashcardsid = :fcid
               AND sd.peerreview = :vote";

    $votes = $DB->count_records_sql($sql, $params);

    return $votes;
}

/**
 * Returns the peer review information displayed int the student/teacher overview list.
 *
 * @param int $questionid
 * @param int $fcid
 * @return string
 */
function mod_flashcard_peer_review_info_overview(int $questionid, int $fcid) {

    $noup = mod_flashcard_get_peer_review_votes($questionid, $fcid, true);
    $nodown = mod_flashcard_get_peer_review_votes($questionid, $fcid, false);

    return $noup.'/'.$nodown;
}
