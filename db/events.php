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
 * Mod flashcards events.
 *
 * @package    mod_flashcards
 * @copyright  2021 University of vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname'   => '\mod_flashcards\event\simplequestion_updated',
        'callback'    => '\mod_flashcards\event\simplequestionform_observer::simplequestion_updated',
    ),
    array(
        'eventname'   => '\mod_flashcards\event\simplequestion_created',
        'callback'    => '\mod_flashcards\event\simplequestionform_observer::simplequestion_created',
        'internal'    => true,
    ),
    array(
        'eventname'   => '\mod_flashcards\event\levelup_firstquestion',
        'callback'    => '\mod_flashcards\event\levelup_firstquestion::get_name',
        'internal'    => false,
    ),
    array(
        'eventname'   => '\mod_flashcards\event\levelup_learnnow',
        'callback'    => '\mod_flashcards\event\levelup_learnnow::get_name',
        'internal'    => false,
    ),
    array(
        'eventname'   => '\mod_flashcards\event\levelup_firstcheckpoint',
        'callback'    => '\mod_flashcards\event\levelup_firstcheckpoint::get_name',
        'internal'    => false,
    ),
    array(
        'eventname'   => '\mod_flashcards\event\levelup_secondcheckpoint',
        'callback'    => '\mod_flashcards\event\levelup_secondcheckpoint::get_name',
        'internal'    => false,
    ),
    array(
        'eventname'   => '\mod_flashcards\event\levelup_thirdcheckpoint',
        'callback'    => '\mod_flashcards\event\levelup_thirdcheckpoint::get_name',
        'internal'    => false,
    ),
);
