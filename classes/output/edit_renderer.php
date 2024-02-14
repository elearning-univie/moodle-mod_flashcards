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
 * Renderer outputting the flashcards action menu.
 *
 * @package mod_flashcards
 * @copyright 2021 University of Vienna
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_flashcards\output;

use mod_flashcards\structure;
use html_writer;

/**
 * Renderer outputting the quiz editing UI.
 *
 * @copyright 2021 University of Vienna
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_renderer extends \plugin_renderer_base {

    /** @var string The toggle group name of the checkboxes for the toggle-all functionality. */
    protected $togglegroup = 'quiz-questions';

    /**
     * edit_flashcards
     * @param \moodle_url $pageurl
     * @param \core_question\local\bank\question_edit_contexts $contexts
     * @param array $pagevars
     * @return string
     */
    public function edit_flashcards($pageurl, $contexts, $pagevars) {

        // Include the contents of any other popups required.
        $thiscontext = $contexts->lowest();
        $this->page->requires->js_call_amd('mod_flashcards/modal_flashcards_question_bank', 'init', [
            $thiscontext->id,
        ]);
        $addmenu = html_writer::tag('span', $this->add_menu_actions($pageurl, $contexts, $pagevars),
            ['class' => 'add-menu-outer mr-5']);

        $output = html_writer::div($addmenu, 'add-menu-space');

        return $output;
    }

    /**
     * Returns the add menu that is output once per page.
     * @param \moodle_url $pageurl the canonical URL of this page.
     * @param array $contexts the relevant question bank contexts.
     * @param array $pagevars the variables from {@see \question_edit_setup()}.
     * @return string HTML to output.
     */
    public function add_menu_actions(\moodle_url $pageurl,
         $contexts, array $pagevars) {

        $actions = $this->edit_menu_actions($pageurl, $pagevars);
        if (empty($actions)) {
            return '';
        }
        $menu = new \action_menu();
        $menu->set_constraint('.mod-flashcards-edit-content');
        $trigger = html_writer::tag('span', get_string('add', 'quiz'), array('class' => 'add-menu'));
        $menu->set_menu_trigger($trigger);
        // The menu appears within an absolutely positioned element causing width problems.
        // Make sure no-wrap is set so that we don't get a squashed menu.
        $menu->set_nowrap_on_items(true);

        foreach ($actions as $action) {
            if ($action instanceof \action_menu_link) {
                $action->add_class('add-menu');
            }
            $menu->add($action);
        }
        $menu->attributes['class'] .= ' page-add-actions commands';

        // Prioritise the menu ahead of all other actions.
        $menu->prioritise = true;

        return $this->render($menu);
    }

    /**
     * Returns the list of actions to go in the add menu.
     * @param \moodle_url $pageurl the canonical URL of this page.
     * @param array $pagevars the variables from {@see \question_edit_setup()}.
     * @return array the actions.
     */
    public function edit_menu_actions(\moodle_url $pageurl, array $pagevars) {
        static $str;
        if (!isset($str)) {
            $str = get_strings(array('addasection', 'addaquestion', 'addarandomquestion',
                'addarandomselectedquestion', 'questionbank'), 'quiz');
        }

        // Get section, page, slotnumber and maxmark.
        $actions = array();

        // Call question bank.
        $icon = new \pix_icon('t/add', $str->questionbank, 'moodle', array('class' => 'iconsmall', 'title' => ''));
        $title = get_string('addquestionfrombankatend', 'quiz');
        $attributes = array('class' => 'cm-edit-action questionbank',
            'data-header' => $title, 'data-action' => 'questionbank', 'onClick' => '');
        $actions['questionbank'] = new \action_menu_link_secondary($pageurl, $icon, $str->questionbank, $attributes);

        $title = get_string('createflashcardbutton', 'mod_flashcards');
        $attributes = array('class' => 'cm-edit-action questionbank',
            'data-header' => $title, 'onClick' => '');
        $link = new \moodle_url('/mod/flashcards/simplequestion.php', $pagevars['createlinkparams']);
        $actions['creatquestion'] = new \action_menu_link_secondary($link, $icon, $title, $attributes);

        return $actions;
    }

    /**
     * Return the contents of the question bank, to be displayed in the question-bank pop-up.
     *
     * @param \mod_quiz\question\bank\custom_view $questionbank the question bank view object.
     * @param array $pagevars the variables from {@see \question_edit_setup()}.
     * @return string HTML to output / send back in response to an AJAX request.
     */
    public function question_bank_contents(\mod_flashcards\question\bank\custom_view $questionbank, array $pagevars) {

        $qbank = $questionbank->render('editq', $pagevars['qpage']/*, $pagevars['qperpage'],
            $pagevars['cat'], $pagevars['recurse'], $pagevars['showhidden'], $pagevars['qbshowtext'],
            $pagevars['qtagids']*/);
        return html_writer::div(html_writer::div($qbank, 'bd'), 'questionbankformforpopup');
    }
}
