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
 * Calls the question engine to render a question
 *
 * @package   mod_flashcards
 * @copyright 2020 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once('locallib.php');

/**
 * Class renderer
 *
 * @copyright 2020 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_flashcards_renderer extends plugin_renderer_base {

    /**
     * Creates a flashcard object and calls the question renderer
     *
     * @param int $flashcardsid
     * @param int $userid
     * @param int $box
     * @param int $questionid
     * @return string|null
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function render_flashcard($flashcardsid, $userid, $box, $questionid) {
        return $this->render_question(new flashcard($flashcardsid, $userid, $box, $questionid));
    }

    /**
     * renders the question
     *
     * @param object $flashcard
     * @return string|null
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    protected function render_question(flashcard $flashcard) {
        $cm = get_coursemodule_from_instance("flashcards", $flashcard->id);
        $context = context_module::instance($cm->id);
        $this->page->set_context($context);

        $this->page->requires->js_call_amd('mod_flashcards/studentcontroller', 'init');
        $jsmodule = array(
                'name' => 'core_question_engine',
                'fullpath' => '/question/qengine.js'
        );
        $this->page->requires->js_init_call('M.core_question_engine.init_form',
                array('#mod-flashcards-responseform'), false, $jsmodule);

        $quba = question_engine::make_questions_usage_by_activity('mod_flashcards', $context);
        $quba->set_preferred_behaviour('immediatefeedback');

        if ($flashcard->questionid == null) {
            return null;
        }

        $question = question_bank::load_question($flashcard->questionid);
        $quba->add_question($question, 1);
        $quba->start_all_questions();
        question_engine::save_questions_usage_by_activity($quba);
        $qaid = $quba->get_question_attempt(1)->get_database_id();

        $result =
                '<form id="mod-flashcards-responseform" method="post"' .
                'action="javascript:;" onsubmit="$.mod_flashcards_call_update(' .
                $flashcard->id . ',' . $flashcard->questionid . ',' . $qaid . ',' . $cm->id .
                ')" enctype="multipart/form-data" accept-charset="utf-8">';
        $result .= "\n<div>\n";

        $options = new question_display_options();
        $options->marks = question_display_options::MAX_ONLY;
        $options->markdp = 2;
        $options->feedback = question_display_options::VISIBLE;
        $options->generalfeedback = question_display_options::HIDDEN;

        $result .= $quba->render_question(1, $options);

        return $result;
    }
}

/**
 * Renderable class used by the flashcards module.
 *
 * @package   mod_flashcards
 * @copyright 2020 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class flashcard implements renderable {
    /**
     * @var int
     */
    public $id;
    /**
     * @var int
     */
    public $userid;
    /**
     * @var int
     */
    public $box;
    /**
     * @var int
     */
    public $questionid;

    /**
     * renderer constructor.
     *
     * @param int $id
     * @param int $userid
     * @param int $box
     * @param int $questionid
     */
    public function __construct($id, $userid, $box, $questionid) {
        $this->id = $id;
        $this->userid = $userid;
        $this->box = $box;
        $this->questionid = $questionid;
    }
}