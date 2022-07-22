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
use mod_flashcards;


/**
 * Subclass to customise the view of the question bank for the quiz editing screen.
 *
 * @package   mod_flashcards
 * @copyright 2021 University of Vienna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_view extends \core_question\bank\view {
    /** @var \stdClass the quiz settings. */
    protected $flashcards = false;
    /** @var array the flashcards questionlist. */
    protected $questionlist;
    /** @var int The maximum displayed length of the category info. */
    const MAX_TEXT_LENGTH = 200;

    /**
     * Constructor
     * @param \question_edit_contexts $contexts
     * @param \moodle_url $pageurl
     * @param \stdClass $course course settings
     * @param \stdClass $cm activity settings.
     * @param \stdClass $flashcards flashcards settings.
     */
    public function __construct($contexts, $pageurl, $course, $cm, $flashcards) {
        global $DB;
        $this->contexts = $contexts;
        $this->baseurl = $pageurl;
        $this->course = $course;
        $this->cm = $cm;
        $this->flashcards = $flashcards;
        $this->questionlist = $DB->get_fieldset_select('flashcards_q_status', 'questionid', 'fcid = ' . $this->flashcards->id);

        // Create the url of the new question page to forward to.
        $returnurl = $pageurl->out_as_local_url(false);
        $this->editquestionurl = new \moodle_url('/question/question.php',
            array('returnurl' => $returnurl));
        if ($cm !== null) {
            $this->editquestionurl->param('cmid', $cm->id);
        } else {
            $this->editquestionurl->param('courseid', $this->course->id);
        }

        $this->lastchangedid = optional_param('lastchanged', 0, PARAM_INT);

        $this->init_columns($this->wanted_columns(), $this->heading_column());
        $this->init_sort();
        $this->init_search_conditions();

    }

    /**
     * wanted_columns
     * @return \question_bank_column_base[]
     * @throws coding_exception
     */
    protected function wanted_columns() {
        global $CFG;

        if (empty($CFG->quizquestionbankcolumns)) {
            $quizquestionbankcolumns = array(
                    'add_action_column',
                    'checkbox_column',
                    'question_type_column',
                    'question_name_text_column',
                    'preview_action_column',
            );
        } else {
            $quizquestionbankcolumns = explode(',', $CFG->quizquestionbankcolumns);
        }

        foreach ($quizquestionbankcolumns as $fullname) {
            if (!class_exists($fullname)) {
                if (class_exists('mod_flashcards\\question\\bank\\' . $fullname)) {
                    $fullname = 'mod_flashcards\\question\\bank\\' . $fullname;
                } else if (class_exists('core_question\\bank\\' . $fullname)) {
                    $fullname = 'core_question\\bank\\' . $fullname;
                } else if (class_exists('question_bank_' . $fullname)) {
                    debugging('Legacy question bank column class question_bank_' .
                            $fullname . ' should be renamed to mod_quiz\\question\\bank\\' .
                            $fullname, DEBUG_DEVELOPER);
                    $fullname = 'question_bank_' . $fullname;
                } else {
                    throw new coding_exception('Invalid quiz question bank column', $fullname);
                }
            }
            $this->requiredcolumns[$fullname] = new $fullname($this);
        }
        return $this->requiredcolumns;
    }

    /**
     * Specify the column heading
     *
     * @return string Column name for the heading
     */
    protected function heading_column() {
        return 'mod_flashcards\\question\\bank\\question_name_text_column';
    }

    /**
     * default_sort
     * @return int[]
     */
    protected function default_sort() {
        return array(
                'core_question\\bank\\question_type_column' => 1,
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
        return new \moodle_url('/mod/flashcards/teacherview.php', $params);
    }

    /**
     * Renders the html question bank (same as display, but returns the result).
     *
     * Note that you can only output this rendered result once per page, as
     * it contains IDs which must be unique.
     *
     * @param string $tabname question bank edit tab name, for permission checking.
     * @param int $page the page number to show.
     * @param int $perpage the number of questions per page to show.
     * @param string $cat 'categoryid,contextid'.
     * @param int $recurse     Whether to include subcategories.
     * @param bool $showhidden  whether deleted questions should be displayed.
     * @param bool $showquestiontext whether the text of each question should be shown in the list. Deprecated.
     * @param array $tagids current list of selected tags.
     * @return false|string HTML code for the form
     */
    public function render($tabname, $page, $perpage, $cat, $recurse, $showhidden,
            $showquestiontext, $tagids = []) {
        ob_start();
        $this->display($tabname, $page, $perpage, $cat, $recurse, $showhidden, $showquestiontext, $tagids);
        $out = ob_get_contents();
        ob_end_clean();
        return $out;
    }

    /**
     * Prints the table of questions in a category with interactions
     *
     * @param array      $contexts    Not used!
     * @param \moodle_url $pageurl     The URL to reload this page.
     * @param string     $categoryandcontext 'categoryID,contextID'.
     * @param \stdClass  $cm          Not used!
     * @param int        $recurse     Whether to include subcategories.
     * @param int        $page        The number of the page to be displayed
     * @param int        $perpage     Number of questions to show per page
     * @param bool       $showhidden  Not used! This is now controlled in a different way.
     * @param bool       $showquestiontext Not used! This is now controlled in a different way.
     * @param array      $addcontexts contexts where the user is allowed to add new questions.
     */
    protected function display_question_list($contexts, $pageurl, $categoryandcontext,
            $cm = null, $recurse=1, $page=0, $perpage=100, $showhidden=false,
            $showquestiontext = false, $addcontexts = array()) {
        global $OUTPUT;

        // This function can be moderately slow with large question counts and may time out.
        // We probably do not want to raise it to unlimited, so randomly picking 5 minutes.
        // Note: We do not call this in the loop because quiz ob_ captures this function (see raise() PHP doc).
        \core_php_time_limit::raise(300);

        $category = $this->get_current_category($categoryandcontext);

        list($categoryid, $contextid) = explode(',', $categoryandcontext);
        $catcontext = \context::instance_by_id($contextid);

        $canadd = has_capability('moodle/question:add', $catcontext);

        $this->create_new_question_form($category, $canadd);

        $this->build_query();
        $totalnumber = $this->get_question_count();
        if ($totalnumber == 0) {
            return;
        }
        $questionsrs = $this->load_page_questions($page, $perpage);
        $questions = [];
        foreach ($questionsrs as $question) {
            $questions[$question->id] = $question;
        }
        $questionsrs->close();
        foreach ($this->requiredcolumns as $name => $column) {
            $column->load_additional_data($questions);
        }

        echo '<div class="categorypagingbarcontainer">';

        $pageingurl = new \moodle_url('teacherview.php', $pageurl->params());
        $pagingbar = new \paging_bar($totalnumber, $page, $perpage, $pageingurl);
        $pagingbar->pagevar = 'qpage';
        echo $OUTPUT->render($pagingbar);
        echo '</div>';

        echo '<form method="post" action="teacherview.php">';
        echo '<fieldset class="invisiblefieldset" style="display: block;">';
        echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';

        echo \html_writer::input_hidden_params($this->baseurl);

        echo '<div class="categoryquestionscontainer" id="questionscontainer">';
        $this->start_table();
        $rowcount = 0;
        foreach ($questions as $question) {
            $this->print_table_row($question, $rowcount);
            $rowcount += 1;
        }
        $this->end_table();
        echo "</div>\n";

        echo '<div class="categorypagingbarcontainer pagingbottom">';
        echo $OUTPUT->render($pagingbar);
        if ($totalnumber > DEFAULT_QUESTIONS_PER_PAGE) {
            if ($perpage == DEFAULT_QUESTIONS_PER_PAGE) {
                $url = new \moodle_url('edit.php', array_merge($pageurl->params(),
                        array('qpage' => 0, 'qperpage' => MAXIMUM_QUESTIONS_PER_PAGE)));
                if ($totalnumber > MAXIMUM_QUESTIONS_PER_PAGE) {
                    $showall = '<a href="'.$url.'">'.get_string('showperpage', 'moodle', MAXIMUM_QUESTIONS_PER_PAGE).'</a>';
                } else {
                    $showall = '<a href="'.$url.'">'.get_string('showall', 'moodle', $totalnumber).'</a>';
                }
            } else {
                $url = new \moodle_url('edit.php', array_merge($pageurl->params(),
                        array('qperpage' => DEFAULT_QUESTIONS_PER_PAGE)));
                $showall = '<a href="'.$url.'">'.get_string('showperpage', 'moodle', DEFAULT_QUESTIONS_PER_PAGE).'</a>';
            }
            echo "<div class='paging'>{$showall}</div>";
        }
        echo '</div>';

        $this->display_bottom_controls($totalnumber, $recurse, $category, $catcontext, $addcontexts);

        echo '</fieldset>';
        echo "</form>\n";
    }

    /**
     * Display the controls at the bottom of the list of questions.
     * @param int       $totalnumber Total number of questions that might be shown (if it was not for paging).
     * @param bool      $recurse     Whether to include subcategories.
     * @param \stdClass $category    The question_category row from the database.
     * @param \context  $catcontext  The context of the category being displayed.
     * @param array     $addcontexts contexts where the user is allowed to add new questions.
     */
    protected function display_bottom_controls($totalnumber, $recurse, $category, \context $catcontext, array $addcontexts) {
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
     * @param string $categoryandcontext 'categoryID,contextID'.
     * @deprecated since Moodle 2.6 MDL-40313.
     * @see \core_question\bank\search\category_condition
     * @todo MDL-41978 This will be deleted in Moodle 2.8
     */
    protected function print_choose_category_message($categoryandcontext) {
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
            $showtextoption = false) {
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
    protected function create_new_question_form($category, $canadd) {
        // Don't display this.
    }

    /**
     * Override the base implementation in \core_question\bank\view
     * because we don't want to print the headers in the fragment
     * for the modal.
     */
    protected function display_question_bank_header() {
    }

    /**
     * Override the base implementation in \core_question\bank\view
     * because we don't want it to read from the $_POST global variables
     * for the sort parameters since they are not present in a fragment.
     *
     * Unfortunately the best we can do is to look at the URL for
     * those parameters (only marginally better really).
     */
    protected function init_sort_from_params() {
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
    protected function build_query() {
        global $DB;

        // Get the required tables and fields.
        $joins = array();
        $fields = array('q.hidden', 'q.category');
        foreach ($this->requiredcolumns as $column) {
            $extrajoins = $column->get_extra_joins();
            foreach ($extrajoins as $prefix => $join) {
                if (isset($joins[$prefix]) && $joins[$prefix] != $join) {
                    throw new \coding_exception('Join ' . $join . ' conflicts with previous join ' . $joins[$prefix]);
                }
                $joins[$prefix] = $join;
            }
            $fields = array_merge($fields, $column->get_required_fields());
        }

        $fields = array_unique($fields);

        // Build the order by clause.
        $sorts = array();
        foreach ($this->sort as $sort => $order) {
            list($colname, $subsort) = $this->parse_subsort($sort);
            $sorts[] = $this->requiredcolumns[$colname]->sort_expression($order < 0, $subsort);
        }

        // Build the where clause.
        $tests = array('q.parent = 0');
        $this->sqlparams = array();

        foreach ($this->searchconditions as $searchcondition) {
            if ($searchcondition->where()) {
                $tests[] = '((' . $searchcondition->where() .'))';
            }
            if ($searchcondition->params()) {
                $this->sqlparams = array_merge($this->sqlparams, $searchcondition->params());
            }
        }

        // Build the SQL.
        $sql = ' FROM {question} q ' . implode(' ', $joins);
        $sql .= ' WHERE ' . implode(' AND ', $tests);
        $sql .= '   AND q.qtype IN (\'flashcard\', \'multichoice\', \'truefalse\') ';
        $sql .= "   AND q.id NOT IN (SELECT qu.id FROM {question} qu
                                      JOIN {tag_instance} ti ON ti.itemid = qu.id
                                      JOIN {tag} t ON t.id = ti.tagid
                                     WHERE t.name = '2fc') ";
        $this->countsql = 'SELECT count(1)' . $sql;
        $this->loadsql = 'SELECT ' . implode(', ', $fields) . $sql . ' ORDER BY ' . implode(', ', $sorts);
    }

    /**
     * flashcards_contains
     * @param int $questionid
     * @return bool
     */
    public function flashcards_contains($questionid) {
        if (in_array($questionid, $this->questionlist)) {
            return true;
        }
        return false;
    }
}
