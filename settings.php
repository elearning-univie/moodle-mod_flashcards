<?php
// This file is part of mod_offlinequiz for Moodle - http://moodle.org/
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
 * Administration settings definitions for the offlinequiz module.
 *
 * @package       mod
 * @subpackage    flashcards
 * @author        Thomas Wedekind <Thomas.Wedekind@univie.ac.at
 * @copyright     2020 University of Vienna
 * @since         Moodle 3.10
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    global $DB;


    // Introductory explanation that all the settings are defaults for the add offlinequiz form.
    $settings->add(new admin_setting_heading('flashcards', '', get_string('configintro', 'flashcards')));

    // Authordisplay.
    $options = array();
    $options[0] = get_string("authordisplay_disabled", "flashcards");
    $options[1] = get_string("authordisplay_group", "flashcards");
    $options[2] = get_string("authordisplay_name", "flashcards");

    $settings->add(new admin_setting_configselect('flashcards/authordisplay', get_string('authordisplay', 'flashcards'),
            get_string('authordisplay', 'flashcards'), 1, $options));
    $allroles = role_fix_names(get_all_roles(), null, ROLENAME_ORIGINALANDSHORT, true);
    $defaultteacherroles = $DB->get_fieldset_select('role', 'id',
        "archetype = 'editingteacher' OR archetype = 'manager' OR archetype = 'coursecreator'");
    $settings->add(new admin_setting_configmultiselect('flashcards/authordisplay_group_teacherroles',
        get_string('authordisplay_teacherroles', 'flashcards'), get_string('authordisplay_teacherroles_desc',
        'flashcards'), $defaultteacherroles, $allroles));
}
