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

    if ($oldversion < 2023042400) {

        // Define field fcstatusid to be added to flashcards_q_stud_rel.
        $table = new xmldb_table('flashcards_q_stud_rel');
        $field = new xmldb_field('fqid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'id');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            $records = $DB->get_records('flashcards_q_stud_rel');
            foreach ($records as $record) {
                $fqsrec = $DB->get_record('flashcards_q_status',
                    ['questionid' => $record->questionid, 'fcid' => $record->flashcardsid]);
                $record->fqid = $fqsrec->id;
                $DB->update_record('flashcards_q_stud_rel', $record);
            }

            $key = new xmldb_key('flashcards_q_stud_rel', XMLDB_KEY_UNIQUE, ['flashcardsid', 'questionid', 'studentid']);
            $dbman->drop_key($table, $key);

            $key = new xmldb_key('flashcards_q_stud_rel', XMLDB_KEY_UNIQUE, ['flashcardsid', 'fqid', 'studentid']);
            $dbman->add_key($table, $key);
        }

        $field = new xmldb_field('questionid');

        // Conditionally launch drop field studentid.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $table = new xmldb_table('flashcards_q_status');
        $field = new xmldb_field('qbankentryid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'id');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $records = $DB->get_records('flashcards_q_status');
        foreach ($records as $record) {
            $qvrec = $DB->get_record('question_versions', ['questionid' => $record->questionid]);
            $record->qbankentryid = $qvrec ? $qvrec->questionbankentryid : 0;
            $DB->update_record('flashcards_q_status', $record);
        }

        $field = new xmldb_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'id');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Flashcards savepoint reached.
        upgrade_mod_savepoint(true, 2023042400, 'flashcards');
    }

    if ($oldversion < 2023042403) {

        // Define field addedby to be added to flashcards_q_status.
        $table = new xmldb_table('flashcards_q_status');
        $field = new xmldb_field('addedby', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timemodified');

        // Conditionally launch add field addedby.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Flashcards savepoint reached.
        upgrade_mod_savepoint(true, 2023042403, 'flashcards');
    }

    if ($oldversion < 2023042404) {

        $sql = "SELECT fqs.id itemid, c.id usingcontextid, 'mod_flashcards' component, 'slot' questionarea,  qv.questionbankentryid questionbankentryid, qv.version \"version\"
              FROM {flashcards_q_status} fqs
              JOIN {modules} m ON m.name ='flashcards'
              JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = fqs.fcid
              JOIN {context} c ON c.instanceid = cm.id AND c.contextlevel = '70'
              JOIN {question_versions} qv ON qv.questionid = fqs.questionid
              WHERE NOT EXISTS (
                   SELECT 1
                     FROM {question_references} mqr
                    WHERE component = 'mod_flashcards'
                      AND questionarea = 'slot'
                      AND itemid = fqs.id
                      )";
        $sql2 = "INSERT INTO {question_references} (itemid, usingcontextid, component, questionarea, questionbankentryid, version) ($sql LIMIT 10000)";
        $thiscount = $DB->count_records('question_references');
        $lastcount = -1;
        try {
            while ($thiscount > $lastcount) {
                $DB->execute($sql2);
                $lastcount = $thiscount;
                $thiscount = $DB->count_records('question_references');
            }
        } catch (Exception $e) {
            // Database doesn't support this type of insert, we have to get them out of the databse and insert them manually.
            while ($records = $DB->get_records_sql($sql, [], 0, 10000)) {
                $DB->insert_records('question_references', $records);
            }
        }

        // Flashcards savepoint reached.
        upgrade_mod_savepoint(true, 2023042404, 'flashcards');
    }

    if ($oldversion < 2024010100.02) {

        $table = new xmldb_table('flashcards_q_status');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'flashcards_question');
        }

        // Flashcards savepoint reached.
        upgrade_mod_savepoint(true, 2024010100.02, 'flashcards');
    }

    return true;
}
