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
 * Class for the structure used to restore one flashcards activity.
 *
 * @package   mod_flashcards
 * @copyright 2021 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Structure step to restore one flashcards activity
 *
 * @package   mod_flashcards
 * @category  backup
 * @copyright 2020 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_flashcards_activity_structure_step extends restore_questions_activity_structure_step {

    /**
     * Defines structure of path elements to be processed during the restore
     *
     * @return array of {@see restore_path_element}
     */
    protected function define_structure() {

        $paths = array();
        $paths[] = new restore_path_element('flashcards', '/activity/flashcards');
        $paths[] = new restore_path_element('flashcards_q_status', '/activity/flashcards/flashcards_q_status');
        $quizquestioninstance = new restore_path_element('flashcards_question_instance',
            '/activity/flashcards/question_instances/question_instance');
        $paths[] = $quizquestioninstance;
            $this->add_question_references($quizquestioninstance, $paths);
            $this->add_question_set_references($quizquestioninstance, $paths);

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the given restore path element data
     *
     * @param array $data parsed element data
     */
    protected function process_flashcards($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        if (empty($data->timemodified)) {
            $data->timemodified = time();
        }

        if ($mappedcat = $this->get_mappingid('question_category', $data->categoryid)) {
            $data->categoryid = $mappedcat;
        }

        if (!empty($data->studentsubcat) && ($mappedstudcat = $this->get_mappingid('question_category', $data->studentsubcat))) {
            $data->studentsubcat = $mappedstudcat;
        }

        $newitemid = $DB->insert_record('flashcards', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process_flashcards_q_status
     *
     * @param array $data parsed element data
     */
    protected function process_flashcards_q_status($data) {
        global $DB;

        $data = (object)$data;
        $data->fcid = $this->get_new_parentid('flashcards');
        $questionmappingid = $this->get_mappingid('question', $data->questionid);
        $data->questionid = $questionmappingid ? $questionmappingid : $data->questionid;
        if ($entry = $DB->get_field('question_versions', 'questionbankentryid', array('questionid' => $data->questionid))) {
            $data->qbankentryid = $entry;
        }
        $data->timemodified = time();

        $fqsid = $DB->insert_record('flashcards_q_status', $data);

        $data = (object) $data;
        $data->usingcontextid = $this->task->get_contextid();
        $data->itemid = $fqsid;
        $data->component = 'mod_flashcards';
        $data->questionarea = 'slot';
        // Fill in the selected version form question_version.
        if ($entry = $DB->get_field('question_versions', 'questionbankentryid', array('questionid' => $data->questionid))) {
            $data->questionbankentryid = $entry;
        }
        $DB->insert_record('question_references', $data);
    }

    /**
     * Process quiz slots.
     *
     * @param stdClass|array $data
     */
    protected function process_flashcards_question_instance($data) {
        global $CFG, $DB;
        
        $data = (object)$data;
        
        print_object($data);

    }

    /**
     * Post-execution actions
     */
    protected function after_execute() {
        $this->add_related_files('mod_flashcards', 'intro', null);
    }
    protected function inform_new_usage_id($newusageid)
    {}

}
