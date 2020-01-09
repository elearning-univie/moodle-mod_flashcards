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
 * External service definition
 *
 * @package    mod_flashcards
 * @copyright  2020 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$services = array(
        'flashcardsservice' => array(
                'functions' => array ('mod_flashcards_update_progress', 'mod_flashcards_load_questions'),
                'requiredcapability' => 'mod/flashcards:studentview',
                'restrictedusers' =>0,
                'enabled'=>1,
        )
);

$functions = array(
        'mod_flashcards_update_progress' => array(
                'classname'   => 'mod_flashcards_external',
                'methodname'  => 'update_progress',
                'classpath'   => 'mod/flashcards/externallib.php',
                'description' => 'Update question progress of a student',
                'type'        => 'write',
                'ajax' => true,
                'loginrequired' => true
        ),
        'mod_flashcards_load_questions' => array(
                'classname'   => 'mod_flashcards_external',
                'methodname'  => 'load_questions',
                'classpath'   => 'mod/flashcards/externallib.php',
                'description' => 'Load questions for a student',
                'type'        => 'write',
                'ajax' => true,
                'loginrequired' => true
        )
);
