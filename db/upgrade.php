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
 * Flashcards lib
 *
 * @package    mod_flashcards
 * @copyright  2021 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * xmldb_streamlti_upgrade is the function that upgrades
 * the streamlti module database when is needed
 *
 * This function is automaticly called when version number in
 * version.php changes.
 *
 * @param int $oldversion New old version number.
 *
 * @return boolean
 */
function xmldb_flashcards_upgrade($oldversion) {
    global $DB, $CFG;

    $dbman = $DB->get_manager();

    if ($oldversion < 2021061603) {

        // Define table flashcards_q_status to be created.
        $table = new xmldb_table('flashcards_q_status');

        // Adding fields to table flashcards_q_status.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('fcid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('teachercheck', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table flashcards_q_status.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fc_q_status_unique', XMLDB_KEY_UNIQUE, ['questionid', 'fcid']);

        // Conditionally launch create table for flashcards_q_status.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $flashcards = $DB->get_records('flashcards');

        foreach ($flashcards as $flashcard) {
            if ($flashcard->inclsubcats) {
                require_once($CFG->dirroot . '/lib/questionlib.php');
                $qcategories = question_categorylist($flashcard->categoryid);
            } else {
                $qcategories = $flashcard->categoryid;
            }

            list($sqlwhere, $qcategories) = $DB->get_in_or_equal($qcategories);
            $sqlwhere = "category $sqlwhere";
            $sql = "SELECT id, createdby
                      FROM {question}
                     WHERE $sqlwhere
                       AND qtype = 'flashcard'";

            $questions = $DB->get_records_sql($sql, $qcategories);
            $cmid = $DB->get_field('course_modules', 'id', ['instance' => $flashcard->id, 'course' => $flashcard->course]);
            $context = context_module::instance($cmid);

            foreach ($questions as $q) {
                $sql = "SELECT id
                          FROM {flashcards_q_status}
                         WHERE questionid = :questionid
                           AND fcid = :fcid";

                if (!$DB->record_exists_sql($sql, ['questionid' => $q->id, 'fcid' => $flashcard->id])) {
                    $teachercheck = has_capability('mod/flashcards:editallquestions', $context, $q->createdby) ? 1 : 0;
                    $DB->insert_record('flashcards_q_status', ['questionid' => $q->id, 'fcid' => $flashcard->id, 'teachercheck' => $teachercheck]);
                }
            }
        }

        // Flashcards savepoint reached.
        upgrade_mod_savepoint(true, 2021061603, 'flashcards');
    }

    if ($oldversion < 2021072600) {

        // Define field peerreview to be added to flashcards_q_stud_rel.
        $table = new xmldb_table('flashcards_q_stud_rel');
        $field = new xmldb_field('peerreview', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'wronganswercount');

        // Conditionally launch add field peerreview.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Flashcards savepoint reached.
        upgrade_mod_savepoint(true, 2021072600, 'flashcards');
    }

    if ($oldversion < 2022011000) {

        // Define table flashcards_q_status to be created.
        $table = new xmldb_table('flashcards_stud_xp_events');

        // Adding fields to table flashcards_q_status.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('fcid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('studentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('firstquestion', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usedshuffle', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('firstcheckpoint', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('secondcheckpoint', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('thirdcheckpoint', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table flashcards_q_status.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fc_stud_xp_unique', XMLDB_KEY_UNIQUE, ['fcid', 'studentid']);

        // Conditionally launch create table for flashcards_q_status.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Flashcards savepoint reached.
        upgrade_mod_savepoint(true, 2022011000, 'flashcards');
    }

    return true;
}
