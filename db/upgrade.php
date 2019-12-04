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
//
// This file is part of BasicLTI4Moodle
//
// BasicLTI4Moodle is an IMS BasicLTI (Basic Learning Tools for Interoperability)
// consumer for Moodle 1.9 and Moodle 2.0. BasicLTI is a IMS Standard that allows web
// based learning tools to be easily integrated in LMS as native ones. The IMS BasicLTI
// specification is part of the IMS standard Common Cartridge 1.1 Sakai and other main LMS
// are already supporting or going to support BasicLTI. This project Implements the consumer
// for Moodle. Moodle is a Free Open source Learning Management System by Martin Dougiamas.
// BasicLTI4Moodle is a project iniciated and leaded by Ludo(Marc Alier) and Jordi Piguillem
// at the GESSI research group at UPC.
// SimpleLTI consumer for Moodle is an implementation of the early specification of LTI
// by Charles Severance (Dr Chuck) htp://dr-chuck.com , developed by Jordi Piguillem in a
// Google Summer of Code 2008 project co-mentored by Charles Severance and Marc Alier.
//
// BasicLTI4Moodle is copyright 2009 by Marc Alier Forment, Jordi Piguillem and Nikolas Galanis
// of the Universitat Politecnica de Catalunya http://www.upc.edu
// Contact info: Marc Alier Forment granludo @ gmail.com or marc.alier @ upc.edu.

/**
 * This file keeps track of upgrades to the streamlti module
 *
 * @package mod_flashcards
 * @copyright  2009 Marc Alier, Jordi Piguillem, Nikolas Galanis
 *  marc.alier@upc.edu
 * @copyright  2009 Universitat Politecnica de Catalunya http://www.upc.edu
 * @author     Marc Alier
 * @author     Jordi Piguillem
 * @author     Nikolas Galanis
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
    global $CFG, $DB, $OUTPUT;

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
    
        if ($oldversion < 2019112500) {

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
        upgrade_mod_savepoint(true, 2019112500, 'flashcards');
    }

    
    return true;
}
