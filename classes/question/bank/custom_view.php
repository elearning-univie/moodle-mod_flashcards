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
 * Defines the custom question bank view used on the Edit quiz page.
 *
 * @package   mod_flashcards
 * @copyright 2021 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_flashcards\question\bank;

use coding_exception;
use core\output\datafilter;
use core_question\local\bank\column_base;
use core_question\local\bank\condition;
use core_question\local\bank\question_version_status;
use mod_flashcards;
use mod_flashcards\question\bank\filter\custom_category_condition;
use question_bank;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/flashcards/locallib.php');
/**
 * Subclass to customise the view of the question bank for the quiz editing screen.
 *
 * @package   mod_flashcards
 * @copyright 2021 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_view extends \core_question\local\bank\view {
    /** @var int number of questions per page to show in the add from question bank modal. */
    const DEFAULT_PAGE_SIZE = 20;
    /** @var \stdClass the quiz settings. */
    protected $flashcards = false;
    /** @var array the flashcards questionlist. */
    protected $questionlist;
    /** @var int The maximum displayed length of the category info. */
    const MAX_TEXT_LENGTH = 200;
    /**
     * @var string $component the component the api is used from.
     */
    public $component = 'mod_flashcards';
    
    /**
     * Constructor
     * @param \question_edit_contexts $contexts
     * @param \moodle_url $pageurl
     * @param \stdClass $course course settings
     * @param \stdClass $cm activity settings.
     * @param array $params
     * @param array $extraparams
     * @param \stdClass $flashcards flashcards settings.
     */
    public function __construct($contexts, $pageurl, $course, $cm, $params, $extraparams, $flashcards = null) {
        // Default filter condition.
        if (!isset($params['filter'])) {
            $params['filter']  = [];
            [$categoryid, $contextid] = custom_category_condition::validate_category_param($params['cat']);
            if (!is_null($categoryid)) {
                $category = custom_category_condition::get_category_record($categoryid, $contextid);
                $params['filter']['category'] = [
                    'jointype' => custom_category_condition::JOINTYPE_DEFAULT,
                    'values' => [$category->id],
                    'filteroptions' => ['includesubcategories' => false],
                ];
            }
        }
        $this->init_columns($this->wanted_columns(), $this->heading_column());
        parent::__construct($contexts, $pageurl, $course, $cm, $params, $extraparams);
        [$this->flashcards, ] = get_module_from_cmid($cm->id);
        /*if (is_null($flashcards)) {
            parent::__construct($contexts, $pageurl, $course, $cm, $params, $extraparams);
            [$this->flashcards, ] = get_module_from_cmid($cm->id);
        } else {
            parent::__construct($contexts, $pageurl, $course, $cm);
            $this->flashcards = $flashcards;
        }*/
    }
    /**
     *
     * {@inheritDoc}
     * @see \core_question\local\bank\view::get_question_bank_plugins()
     */
    protected function get_question_bank_plugins(): array {
        $questionbankclasscolumns = [];
        $customviewcolumns = [
            'mod_flashcards\question\bank\add_action_column' . column_base::ID_SEPARATOR  . 'add_action_column',
            'core_question\local\bank\checkbox_column' . column_base::ID_SEPARATOR . 'checkbox_column',
            'qbank_viewquestiontype\question_type_column' . column_base::ID_SEPARATOR . 'question_type_column',
            'mod_flashcards\question\bank\question_name_text_column' . column_base::ID_SEPARATOR . 'question_name_text_column',
            'mod_flashcards\question\bank\preview_action_column'  . column_base::ID_SEPARATOR  . 'preview_action_column',
        ];

        foreach ($customviewcolumns as $columnid) {
            [$columnclass, $columnname] = explode(column_base::ID_SEPARATOR, $columnid, 2);
            if (class_exists($columnclass)) {
                $questionbankclasscolumns[$columnid] = $columnclass::from_column_name($this, $columnname);
            }
        }

        return $questionbankclasscolumns;
    }

    /**
     * Specify the column heading
     *
     * @return string Column name for the heading
     */
    protected function heading_column(): string {
        return 'mod_flashcards\\question\\bank\\question_name_text_column';
    }

    /**
     * default_sort
     * @return int[]
     */
    protected function default_sort(): array {
        return array(
                'qbank_viewquestiontype\\question_type_column' => 1,
                'mod_flashcards\\question\\bank\\question_name_text_column' => 1,
        );
    }

    /**
     * preview_question_url
     * @param \stdClass $question
     * @return \moodle_url
     */
    public function preview_question_url($question) {
        return quiz_question_preview_url($this->quiz, $question);
    }

    /**
     * add_to_flashcards_url
     * @param int $questionid
     * @return \moodle_url
     * @throws \moodle_exception
     * @throws coding_exception
     */
    public function add_to_flashcards_url($questionid) {
        global $CFG;
        $params = $this->baseurl->params();
        $params['addquestion'] = $questionid;
        $params['sesskey'] = sesskey();
        $params['addsingle'] = true;
        $params['cmid'] = $this->cm->id;
        return new \moodle_url('/mod/flashcards/teacherview.php', $params);
    }
    /**
     * render
     *
     * @param array $pagevars
     * @param string $tabname
     * @return string
     */
    public function render($pagevars, $tabname): string {
        ob_start();
        $this->display();
        $out = ob_get_contents();
        ob_end_clean();
        return $out;
    }

    /**
     * Display the controls at the bottom of the list of questions.
     * @param \context  $catcontext  The context of the category being displayed.
     */
    protected function display_bottom_controls(\context $catcontext): void {
        $cmoptions = new \stdClass();
        $cmoptions->hasattempts = !empty($this->quizhasattempts);

        $canuseall = has_capability('moodle/question:useall', $catcontext);

        echo '<div class="modulespecificbuttonscontainer">';
        if ($canuseall) {

            // Add selected questions to the quiz.
            $params = array(
                    'type' => 'submit',
                    'name' => 'add',
                    'class' => 'btn btn-primary',
                    'value' => get_string('addselectedquestionstoquiz', 'flashcards'),
                    'data-action' => 'toggle',
                    'data-togglegroup' => 'qbank',
                    'data-toggle' => 'action',
                    'disabled' => true,
            );
            echo \html_writer::empty_tag('input', $params);
        }
        echo "</div>\n";
    }

    /**
     * Prints a form to choose categories.
     * @deprecated since Moodle 2.6 MDL-40313.
     * @see \core_question\bank\search\category_condition
     * @todo MDL-41978 This will be deleted in Moodle 2.8
     */
    protected function print_choose_category_message(): void {
        global $OUTPUT;
        debugging('print_choose_category_message() is deprecated, ' .
                'please use \core_question\bank\search\category_condition instead.', DEBUG_DEVELOPER);
        echo $OUTPUT->box_start('generalbox questionbank');
        $this->display_category_form($this->contexts->having_one_edit_tab_cap('edit'),
                $this->baseurl, $categoryandcontext);
        echo "<p style=\"text-align:center;\"><b>";
        print_string('selectcategoryabove', 'question');
        echo "</b></p>";
        echo $OUTPUT->box_end();
    }

    /**
     * display_options_form
     * @param bool $showquestiontext
     * @param string $scriptpath
     * @param false $showtextoption
     */
    protected function display_options_form($showquestiontext, $scriptpath = '/mod/flashcards/teacherview.php',
        $showtextoption = false): void {
        // Overridden just to change the default values of the arguments.
        parent::display_options_form($showquestiontext, $scriptpath, $showtextoption);
    }

    /**
     * print_category_info
     * @param \stdClass $category
     * @throws coding_exception
     */
    protected function print_category_info($category) {
        $formatoptions = new \stdClass();
        $formatoptions->noclean = true;
        $strcategory = get_string('category', 'quiz');
        echo '<div class="categoryinfo"><div class="categorynamefieldcontainer">' .
                $strcategory;
        echo ': <span class="categorynamefield">';
        echo shorten_text(strip_tags(format_string($category->name)), 60);
        echo '</span></div><div class="categoryinfofieldcontainer">' .
                '<span class="categoryinfofield">';
        echo shorten_text(strip_tags(format_text($category->info, $category->infoformat,
                $formatoptions, $this->course->id)), 200);
        echo '</span></div></div>';
    }

    /**
     * display_options
     * @param bool $recurse
     * @param bool $showhidden
     * @param bool $showquestiontext
     * @throws coding_exception
     */
    protected function display_options($recurse, $showhidden, $showquestiontext) {
        debugging('display_options() is deprecated, see display_options_form() instead.', DEBUG_DEVELOPER);
        echo '<form method="get" action="teacherview.php" id="displayoptions">';
        echo "<fieldset class='invisiblefieldset'>";
        echo \html_writer::input_hidden_params($this->baseurl,
                array('recurse', 'showhidden', 'qbshowtext'));
        $this->display_category_form_checkbox('recurse', $recurse,
                get_string('includesubcategories', 'question'));
        $this->display_category_form_checkbox('showhidden', $showhidden,
                get_string('showhidden', 'question'));
        echo '<noscript><div class="centerpara"><input type="submit" value="' .
                get_string('go') . '" />';
        echo '</div></noscript></fieldset></form>';
    }

    /**
     * create_new_question_form
     * @param \stdClass $category
     * @param bool $canadd
     */
    protected function create_new_question_form($category, $canadd): void {
        // Don't display this.
    }

    /**
     * Override the base implementation in \core_question\bank\view
     * because we don't want to print the headers in the fragment
     * for the modal.
     */
    protected function display_question_bank_header(): void {
    }

    /**
     * Override the base implementation in \core_question\bank\view
     * because we don't want it to read from the $_POST global variables
     * for the sort parameters since they are not present in a fragment.
     *
     * Unfortunately the best we can do is to look at the URL for
     * those parameters (only marginally better really).
     */
    protected function init_sort_from_params(): void {
        $this->sort = [];
        for ($i = 1; $i <= self::MAX_SORTS; $i++) {
            if (!$sort = $this->baseurl->param('qbs' . $i)) {
                break;
            }
            // Work out the appropriate order.
            $order = 1;
            if ($sort[0] == '-') {
                $order = -1;
                $sort = substr($sort, 1);
                if (!$sort) {
                    break;
                }
            }
            // Deal with subsorts.
            list($colname, $subsort) = $this->parse_subsort($sort);
            $this->requiredcolumns[$colname] = $this->get_column_type($colname);
            $this->sort[$sort] = $order;
        }
    }

    /**
     * Create the SQL query to retrieve the indicated questions, based on
     * \core_question\bank\search\condition filters.
     */
    protected function build_query(): void {

        // Get the required tables and fields.
        [$fields, $joins] = $this->get_component_requirements(array_merge($this->requiredcolumns, $this->questionactions));

        // Build the order by clause.
        $sorts = [];
        foreach ($this->sort as $sortname => $sortorder) {
            [$colname, $subsort] = $this->parse_subsort($sortname);
            $sorts[] = $this->requiredcolumns[$colname]->sort_expression($sortorder == SORT_DESC, $subsort);
        }

        // Build the where clause.
        $latestversion = 'qv.version = (SELECT MAX(v.version)
                                          FROM {question_versions} v
                                          JOIN {question_bank_entries} be
                                            ON be.id = v.questionbankentryid
                                         WHERE be.id = qbe.id)';
        $onlyready = '((' . "qv.status = '" . question_version_status::QUESTION_STATUS_READY . "'" .'))';
        $this->sqlparams = [];
        $conditions = [];
        foreach ($this->searchconditions as $searchcondition) {
            if ($searchcondition->where()) {
                $conditions[] = '((' . $searchcondition->where() .'))';
            }
            if ($searchcondition->params()) {
                $this->sqlparams = array_merge($this->sqlparams, $searchcondition->params());
            }
        }
        $majorconditions = ['q.parent = 0', $latestversion, $onlyready];
        // Get higher level filter condition.
        $jointype = isset($this->pagevars['jointype']) ? (int)$this->pagevars['jointype'] : condition::JOINTYPE_DEFAULT;
        $nonecondition = ($jointype === datafilter::JOINTYPE_NONE) ? ' NOT ' : '';
        $separator = ($jointype === datafilter::JOINTYPE_ALL) ? ' AND ' : ' OR ';
        // Build the SQL.
        $sql = ' FROM {question} q ' . implode(' ', $joins);
        $sql .= ' WHERE ' . implode(' AND ', $majorconditions);

        $qtypes = '\'flashcard\', \'multichoice\', \'truefalse\', \'shortanswer\', \'multianswer\'';
        if (question_bank::qtype_exists('multichoiceset')) {
            $qtypes = $qtypes . ', \'multichoiceset\'';
        }

        $sql .= '   AND q.qtype IN (' . $qtypes . ')';
        $sql .= "   AND q.id NOT IN (SELECT qu.id FROM {question} qu
                                      JOIN {tag_instance} ti ON ti.itemid = qu.id
                                      JOIN {tag} t ON t.id = ti.tagid
                                     WHERE t.name = '2fc') ";

        if (!empty($conditions)) {
            $sql .= ' AND ' . $nonecondition . ' ( ';
            $sql .= implode($separator, $conditions);
            $sql .= ' ) ';
        }
        $this->countsql = 'SELECT count(1)' . $sql;
        $this->loadsql = 'SELECT ' . implode(', ', $fields) . $sql . ' ORDER BY ' . implode(', ', $sorts);
    }

    /**
     * flashcards_contains
     * @param int $questionid
     * @return bool
     */
    public function flashcards_contains($questionid) {
        /*if (in_array($questionid, $this->questionlist)) {
            return true;
        }*/
        return false;
    }
    /**
     *
     * {@inheritDoc}
     * @see \core_question\local\bank\view::add_standard_search_conditions()
     */
    public function add_standard_search_conditions(): void {
        foreach ($this->plugins as $componentname => $plugin) {
            if (\core\plugininfo\qbank::is_plugin_enabled($componentname)) {
                $pluginentrypointobject = new $plugin();
                if ($componentname === 'qbank_managecategories') {
                    $pluginentrypointobject = new flashcards_managecategories_feature();
                }
                if ($componentname === 'qbank_viewquestiontext' || $componentname === 'qbank_deletequestion') {
                    continue;
                }
                $pluginobjects = $pluginentrypointobject->get_question_filters($this);
                foreach ($pluginobjects as $pluginobject) {
                    $this->add_searchcondition($pluginobject, $pluginobject->get_condition_key());
                }
            }
        }
    }

}
