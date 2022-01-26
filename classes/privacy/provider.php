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
 * Privacy Subsystem implementation for mod_flashcards.
 *
 * @package    mod_flashcards
 * @copyright  2021 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_flashcards\privacy;
use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\userlist;
use \core_privacy\local\request\approved_userlist;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_flashcards module does not store any data.
 *
 * @copyright  2021 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
// This plugin has data.
\core_privacy\local\metadata\provider,

// This plugin currently implements the original plugin_provider interface.
\core_privacy\local\request\plugin\provider,

// This plugin saves user preferences
\core_privacy\local\request\user_preference_provider,

// This plugin is capable of determining which users have data within it.
\core_privacy\local\request\core_userlist_provider
{
    /**
     * get metadata
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection) : collection {

        $collection->add_database_table(
            'flashcards',
            [
                'course' => 'privacy:metadata:flashcards:course',
                'name' => 'privacy:metadata:flashcards:name',
                'categoryid' => 'privacy:metadata:flashcards:categoryid',
                'inclsubcats' => 'privacy:metadata:flashcards:inclsubcats',
                'intro' => 'privacy:metadata:flashcards:intro',
                'introformat' => 'privacy:metadata:flashcards:introformat',
                'addfcstudent' => 'privacy:metadata:flashcards:addfcstudent',
                'studentsubcat' => 'privacy:metadata:flashcards:studentsubcat',
            ],
            'privacy:metadata:flashcards'
            );

        $collection->add_database_table(
            'flashcards_q_status',
            [
                'questionid' => 'privacy:metadata:flashcards_q_status:questionid',
                'fcid' => 'privacy:metadata:flashcards_q_status:fcid',
                'teachercheck' => 'privacy:metadata:flashcards_q_status:teachercheck',
            ],
            'privacy:metadata:flashcards_q_status'
            );

        $collection->add_database_table(
            'flashcards_q_stud_rel',
            [
                'flashcardsid' => 'privacy:metadata:flashcards_q_stud_rel:flashcardsid',
                'questionid' => 'privacy:metadata:flashcards_q_stud_rel:questionid',
                'studentid' => 'privacy:metadata:flashcards_q_stud_rel:studentid',
                'active' => 'privacy:metadata:flashcards:active',
                'currentbox' => 'privacy:metadata:flashcards_q_stud_rel:currentbox',
                'lastanswered' => 'privacy:metadata:flashcards_q_stud_rel:lastanswered',
                'tries' => 'privacy:metadata:flashcards_q_stud_rel:tries',
                'wronganswercount' => 'privacy:metadata:flashcards_q_stud_rel:wronganswercount',
                'peerreview' => 'privacy:metadata:flashcards_q_stud_rel:peerreview',
            ],
            'privacy:metadata:flashcards_q_stud_rel'
            );

        $collection->add_database_table(
            'flashcards_stud_xp_events',
            [
                'fcid' => 'privacy:metadata:flashcards_stud_xp_events:fcid',
                'studentid' => 'privacy:metadata:flashcards_q_stud_rel:studentid',
                'firstquestion' => 'privacy:metadata:flashcards_q_stud_rel:firstquestion',
                'usedshuffle' => 'privacy:metadata:flashcards_q_stud_rel:usedshuffle',
                'firstcheckpoint' => 'privacy:metadata:flashcards_q_stud_rel:firstcheckpoint',
                'secondcheckpoint' => 'privacy:metadata:flashcards_q_stud_rel:secondcheckpoint',
                'thirdcheckpoint' => 'privacy:metadata:flashcards_q_stud_rel:thirdcheckpoint',
            ],
            'privacy:metadata:flashcards_q_stud_rel'
        );

        $collection->add_user_preference('flashcards_showapp', 'privacy:metadata:flashcards_showapp');

        // Mod flashcards links to the 'core_question' subsystem for all question functionality.
        $collection->add_subsystem_link('core_question', [], 'privacy:metadata:core_question');

        return $collection;
    }

    /**
     * Export all user preferences for the plugin.
     *
     * @param int $userid The userid of the user whose data is to be exported.
     */
    public static function export_user_preferences(int $userid) {
        $showapp = get_user_preferences('flashcards_showapp', null, $userid);
        if ($showapp !== null) {
            writer::export_user_preference('mod_flashcards',
                'flashcards_showapp',
                transform::yesno($showapp),
                get_string('privacy:metadata:flashcards_showapp', 'mod_flashcards')
            );
        }
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();

        $sql = "SELECT c.id
                 FROM {context} c
           INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
           INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
           INNER JOIN {flashcards} f ON f.id = cm.instance
            LEFT JOIN {flashcards_q_stud_rel} d ON d.flashcardsid = f.id
                WHERE (
                d.studentid = :studentid
                )
        ";

        $params = [
            'modname' => 'flashcards',
            'contextlevel' => CONTEXT_MODULE,
            'studentid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (!count($contextlist)) {
            return;
        }

        $user = $contextlist->get_user();
        $sql = "SELECT c.id contextid, cm.instance flashcards
                FROM {context} c
                JOIN {course_modules} cm ON cm.id = c.instanceid  AND contextlevel = 70
                JOIN {modules} m ON m.id = cm.module AND m.name = 'flashcards'
                AND EXISTS (
                        SELECT 1
                        FROM {flashcards_q_stud_rel} l
                        WHERE studentid = :userid AND flashcardsid = cm.instance)";
        $params = [ 'userid' => $user->id];

        $flashcards = $DB->get_records_sql($sql, $params);

        $exportobject = new \stdClass();

        foreach ($flashcards as $flashcardsinstance) {
            $context = \context::instance_by_id($flashcardsinstance->contextid);
            $sql = "SELECT sr.*
                      FROM {flashcards_q_stud_rel} sr
                     WHERE sr.flashcardsid =:fcid AND sr.studentid =:userid";
            $studrellist = $DB->get_records_sql($sql, ["userid" => $user->id, "fcid" => $flashcardsinstance->flashcards]);
            if ($studrellist) {
                $exportobject->studrellist = $studrellist;
            }
            $datafoldername = get_string('privacy:data_folder_name', 'mod_flashcards');

            writer::with_context($context)
            ->export_data([$datafoldername], $exportobject);
        }
    }

    /**
     * Get the list of users within a specific context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $params = [
            'cmid'    => $context->instanceid,
            'modname' => 'flashcards',
        ];

        // Users who using the flashcards activity.
        $sql = "SELECT qa.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {flashcards} q ON q.id = cm.instance
                  JOIN {flashcards_q_stud_rel} qa ON qa.flashcardsid = q.id
                 WHERE cm.id = :cmid AND qa.preview = 0";
        $userlist->add_from_sql('userid', $sql, $params);
    }
    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {

    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {

            if (!$context instanceof \context_module) {
                continue;
            }

            do_delete($context->instanceid, $userid);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $userids = $userlist->get_userids();

        foreach ($userids as $userid) {
            do_delete($context->instanceid, $userid);
        }
    }

    /**
     * Deletes the records from the db
     *
     * @param int $flashcardsid
     * @param int $userid
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private function do_delete($flashcardsid, $userid) {
        global $DB;
        $DB->delete_records('flashcards_q_stud_rel', ['flashcardsid' => $flashcardsid, 'studentid' => $userid]);
        $DB->delete_records('flashcards_stud_xp_events', ['flashcardsid' => $flashcardsid, 'studentid' => $userid]);

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $contextparams['createdby'] = $contextlist->get_user()->id;
        $DB->set_field_select('question', 'createdby', 0, "
                category IN (SELECT id FROM {question_categories} WHERE contextid {$contextsql})
            AND createdby = :createdby  AND qtype = 'flashcard'", $contextparams);

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $contextparams['modifiedby'] = $contextlist->get_user()->id;
        $DB->set_field_select('question', 'modifiedby', 0, "
                category IN (SELECT id FROM {question_categories} WHERE contextid {$contextsql})
            AND modifiedby = :modifiedby AND qtype = 'flashcard'", $contextparams);
    }
}

