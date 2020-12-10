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
 * @copyright  2020 University of Vienna
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
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2019111800) {

        // Changing type of field inclsubcats on table flashcards to int.
        $table = new xmldb_table('flashcards');
        $field = new xmldb_field('inclsubcats', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'categoryid');

        // Launch change of type for field inclsubcats.
        $dbman->change_field_type($table, $field);

        // Flashcards savepoint reached.
        upgrade_mod_savepoint(true, 2019111800, 'flashcards');
    }
    if ($oldversion < 2019121201) {

        // Define table flashcards_q_stud_rel to be created.
        $table = new xmldb_table('flashcards_q_stud_rel');

        // Adding fields to table flashcards_q_stud_rel.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('flashcardsid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('studentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('currentbox', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('lastanswered', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('tries', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('wronganswercount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table flashcards_q_stud_rel.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table flashcards_q_stud_rel.
        $table->add_index('uniqueidindex', XMLDB_INDEX_UNIQUE, ['flashcardsid', 'questionid', 'studentid']);

        // Conditionally launch create table for flashcards_q_stud_rel.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Flashcards savepoint reached.
        upgrade_mod_savepoint(true, 2019121201, 'flashcards');
    }
    if ($oldversion < 2020113000) {

        // Define field addfcstudent to be added to flashcards.
        $table = new xmldb_table('flashcards');
        $field = new xmldb_field('addfcstudent', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'timemodified');

        // Conditionally launch add field addfcstudent.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Flashcards savepoint reached.
        upgrade_mod_savepoint(true, 2020113000, 'flashcards');
    }

    if ($oldversion < 2020120900) {

        // Define field studentsubcat to be added to flashcards.
        $table = new xmldb_table('flashcards');
        $field = new xmldb_field('studentsubcat', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'addfcstudent');

        // Conditionally launch add field studentsubcat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Flashcards savepoint reached.
        upgrade_mod_savepoint(true, 2020120900, 'flashcards');
    }
    if ($oldversion < 2020120900.01) {
        
        // Changing the default of field addfcstudent on table flashcards to 0.
        $table = new xmldb_table('flashcards');
        $field = new xmldb_field('addfcstudent', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'timemodified');
        
        // Launch change of default for field addfcstudent.
        $dbman->change_field_default($table, $field);
        
        // Flashcards savepoint reached.
        upgrade_mod_savepoint(true, 2020120900.01, 'flashcards');
    }
    
    return true;
}
