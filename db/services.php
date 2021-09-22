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

defined('MOODLE_INTERNAL') || die();

/**
 * short name for the service.
 */
const MOD_FLASHCARDS_SERVICE_SHORTNAME = 'flashcards';

$services = array(
        'flashcardsservice' => array(
                'functions' => array('mod_flashcards_update_progress',
                    'mod_flashcards_load_next_question',
                    'mod_flashcards_load_learn_progress',
                    'mod_flashcards_init_questions',
                    'mod_flashcards_remove_questions',
                    'mod_flashcards_start_learn_now',
                    'mod_flashcards_set_preview_status',
                    'mod_flashcards_set_peer_review_vote'),
                'shortname' => MOD_FLASHCARDS_SERVICE_SHORTNAME,
                'requiredcapability' => 'mod/flashcards:webservice',
                'restrictedusers' => 0,
                'enabled' => 1,
        )
);

$functions = array(
        'mod_flashcards_update_progress' => array(
            'classname' => 'mod_flashcards_external',
            'methodname' => 'update_progress',
            'classpath' => 'mod/flashcards/externallib.php',
            'description' => 'Update question progress of a student',
            'type' => 'write',
            'ajax' => true,
            'loginrequired' => true
        ),
        'mod_flashcards_load_learn_progress' => array(
            'classname' => 'mod_flashcards_external',
            'methodname' => 'load_learn_progress',
            'classpath' => 'mod/flashcards/externallib.php',
            'description' => 'Loads a html progress bar for learn now',
            'type' => 'write',
            'ajax' => true,
            'loginrequired' => true
        ),
        'mod_flashcards_load_next_question' => array(
            'classname' => 'mod_flashcards_external',
            'methodname' => 'load_next_question',
            'classpath' => 'mod/flashcards/externallib.php',
            'description' => 'Update question progress of a student',
            'type' => 'write',
            'ajax' => true,
            'loginrequired' => true
        ),
        'mod_flashcards_init_questions' => array(
            'classname' => 'mod_flashcards_external',
            'methodname' => 'init_questions',
            'classpath' => 'mod/flashcards/externallib.php',
            'description' => 'Load questions for a student',
            'type' => 'write',
            'ajax' => true,
            'loginrequired' => true
        ),
        'mod_flashcards_remove_questions' => array(
            'classname' => 'mod_flashcards_external',
            'methodname' => 'remove_questions',
            'classpath' => 'mod/flashcards/externallib.php',
            'description' => 'Remove questions for a student from box 1',
            'type' => 'write',
            'ajax' => true,
            'loginrequired' => true
        ),
        'mod_flashcards_start_learn_now' => array(
            'classname' => 'mod_flashcards_external',
            'methodname' => 'start_learn_now',
            'classpath' => 'mod/flashcards/externallib.php',
            'description' => 'Initializes the learn now session',
            'type' => 'write',
            'ajax' => true,
            'loginrequired' => true
        ),
        'mod_flashcards_set_preview_status' => array(
            'classname' => 'mod_flashcards_external',
            'methodname' => 'set_preview_status',
            'classpath' => 'mod/flashcards/externallib.php',
            'description' => 'Sets the status of a flashcard',
            'type' => 'write',
            'ajax' => true,
            'loginrequired' => true
        ),
        'mod_flashcards_set_peer_review_vote' => array(
        'classname' => 'mod_flashcards_external',
        'methodname' => 'set_peer_review_vote',
        'classpath' => 'mod/flashcards/externallib.php',
        'description' => 'Sets the users peer review vote of a flashcard',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true
    )
);
