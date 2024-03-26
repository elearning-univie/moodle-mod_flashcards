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
 * Table class for displaying the flashcard list of an activity for a teacher.
 *
 * @package    mod_flashcards
 * @copyright  2021 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_flashcards\output;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');
require_once('locallib.php');

use moodle_url;
use table_sql;
use html_writer;

/**
 * Table class for displaying the flashcard list of an activity for a teacher.
 *
 * @copyright  2021 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class teacherviewtable extends table_sql {

    /** @var int course module id */
    private $cmid;

    /** @var int course id */
    private $courseid;

    /** @var string text for the edit icon */
    private $editicontext;

    /** @var string text for the delete icon */
    private $deleteicontext;

    /** @var string text for the preview icon */
    private $previewicontext;

    /** @var string jump back url if a question is getting edited */
    private $editreturnurl;

    /** @var array array to save previously looked up authors */
    private $authors;

    /** @var object course module context */
    private $context;

    /**
     * teacherviewtable constructor.
     *
     * @param int $uniqueid
     * @param int $cmid
     * @param int $course
     * @param string $callbackurl
     * @throws \coding_exception
     */
    public function __construct($uniqueid, $cmid, $course, $callbackurl) {
        parent::__construct($uniqueid);
        $this->cmid = $cmid;
        $this->courseid = $course;
        $this->editreturnurl = $callbackurl;
        $this->authors = array();
        $this->context = \context_module::instance($cmid);

        $this->editicontext = get_string('edit', 'moodle');
        $this->deleteicontext = get_string('removeflashcard', 'mod_flashcards');
        $this->previewicontext = get_string('fcview', 'mod_flashcards');

        $thumbsup = '<i class="icon fa fa-thumbs-up fa-fw " title="Yes" aria-label="Yes"></i>';
        $thumbsdown = '<i class="icon fa fa-thumbs-down fa-fw " title="No" aria-label="No"></i>';

        // Define the list of columns to show. TODO: createdby zu addedby umschreiben
        $columns = ['name', 'teachercheck', 'upvotes', 'sep', 'downvotes', 'v1createdby', 'modifiedby', 'addedby',
            'timemodified', 'version', 'preview', 'edit', 'remove'];
        $this->define_columns($columns);
        $this->column_class('teachercheck', 'flashcards_studentview_tc');
        $this->column_class('upvotes', 'flashcards_up');
        $this->column_class('sep', 'flashcards_sep');
        $this->column_class('downvotes', 'flashcards_down');
        $this->column_class('v1createdby', 'flashcards_studentview_tc');
        $this->column_class('modifiedby', 'flashcards_studentview_tc');
        $this->column_class('createdby', 'flashcards_studentview_tc');
        $this->column_class('timemodified', 'flashcards_studentview_tc');
        $this->column_class('version', 'flashcards_studentview_dr');
        $this->column_class('edit', 'flashcards_teacherview_ec');
        $this->column_class('preview', 'flashcards_teacherview_ec');
        $this->column_class('remove', 'flashcards_teacherview_dr');

        // Define the titles of columns to show in header.
        $headers = array(
            get_string('question', 'mod_flashcards'),
            get_string('teachercheck', 'mod_flashcards'),
            get_string('peerreviewtableheaderup', 'mod_flashcards', ['thumbsup' => $thumbsup]),
            "/",
            get_string('peerreviewtableheaderdown', 'mod_flashcards', ['thumbsdown' => $thumbsdown]),
            get_string('v1author', 'mod_flashcards'),
            get_string('modifiedby', 'mod_flashcards'),
            get_string('addedby', 'mod_flashcards'),
            get_string('timemodified', 'mod_flashcards'),
            get_string('version', 'mod_flashcards'),
            get_string('fcview', 'mod_flashcards'),
            get_string('edit'),
            get_string('removeflashcard', 'mod_flashcards'));
        $this->define_headers($headers);

        // Define help for columns teachercheck and peer review.
        $helpforheaders = array(
            null,
            new \help_icon('teachercheck', 'mod_flashcards'),
            null,
            null,
            new \help_icon('peerreview', 'mod_flashcards'),
            null,
            null,
            null,
            null,
            null,
            new \help_icon('removeflashcardinfo', 'mod_flashcards'));
        $this->define_help_for_headers($helpforheaders);

        $this->collapsible(false);
        $this->sortable(true, 'timemodified', SORT_DESC);
        $this->pageable(true);
        $this->is_downloadable(false);

        $this->no_sorting('peerreview');
        $this->no_sorting('edit');
        $this->no_sorting('sep');
        $this->no_sorting('version');
        $this->no_sorting('preview');
        $this->no_sorting('remove');
    }

    /**
     * Prepares column name for display
     *
     * @param object $values
     * @return string
     */
    public function col_name($values) {
        return html_writer::div($values->name, null,
            ['title' => mod_flashcards_get_preview_questiontext($this->context, $values->id, $values->questiontext),
                'class' => 'qtitle_tooltip']);
    }

    /**
     * Prepares column v1createdby for display
     *
     * @param object $values
     * @return string
     */
    public function col_v1createdby($values) {
        if (!key_exists($values->v1createdby, $this->authors)) {
            $author = mod_flashcards_get_author_display_name($values->v1createdby, $this->courseid, FLASHCARDS_AUTHOR_NAME);
            $this->authors[$values->v1createdby] = $author;
        } else {
            $author = $this->authors[$values->v1createdby];
        }

        return $author;
    }

    /**
     * Prepares column addedby for display
     *
     * @param object $values
     * @return string
     */
    public function col_addedby($values) {

        if (is_null($values->addedby)) {
            return "-";
        }
        if (!key_exists($values->addedby, $this->authors)) {
            $author = mod_flashcards_get_author_display_name($values->addedby, $this->courseid, FLASHCARDS_AUTHOR_NAME);
            $this->authors[$values->addedby] = $author;
        } else {
            $author = $this->authors[$values->addedby];
        }

        return $author;
    }

    /**
     * Prepares column modifiedby for display
     *
     * @param object $values
     * @return string
     */
    public function col_modifiedby($values) {
        if (!key_exists($values->modifiedby, $this->authors)) {
            $author = mod_flashcards_get_author_display_name($values->modifiedby, $this->courseid, FLASHCARDS_AUTHOR_NAME);
            $this->authors[$values->modifiedby] = $author;
        } else {
            $author = $this->authors[$values->modifiedby];
        }

        return $author;
    }

    /**
     * Prepares column teachercheck for display
     *
     * @param object $values
     * @return string
     */
    public function col_teachercheck($values) {
        global $OUTPUT;

        $checkinfo = mod_flashcard_get_teacher_check_info($values->teachercheck);
        $qurl = new moodle_url('/mod/flashcards/flashcardpreview.php',
            ['id' => $values->id, 'cmid' => $this->cmid, 'flashcardsid' => $values->flashcardsid]);
        return html_writer::link($qurl, html_writer::div($OUTPUT->pix_icon($checkinfo['icon']['key'],
            $checkinfo['icon']['title']), $checkinfo['color']),
            ['class' => 'mod_flashcards_questionpreviewlink', 'target' => 'questionpreview']);
    }

        /**
         * Prepares column sep for display
         *
         * @param object $values
         * @return string
         */
    public function col_sep($values) {
        return "/";
    }

    /**
     * Prepares column timemodified for display
     *
     * @param object $values
     * @return string
     */
    public function col_timemodified($values) {
        return userdate($values->timemodified, get_string('strftimedatetimeshort', 'langconfig'));
    }

    /**
     * Prepares column edit for display
     *
     * @param object $values
     * @return string
     */
    public function col_edit($values) {
        global $OUTPUT;

        $eurl = new moodle_url('/mod/flashcards/simplequestion.php',
            ['action' => 'edit', 'id' => $values->id, 'cmid' => $this->cmid,
                'fcid' => $values->fqid, 'origin' => $this->editreturnurl]);

        return html_writer::link($eurl, $OUTPUT->pix_icon('i/settings', $this->editicontext));
    }

    /**
     * Prepares column preview for display
     *
     * @param object $values
     * @return string
     */
    public function col_preview($values) {
        global $OUTPUT;

        $qurl = new moodle_url('/mod/flashcards/flashcardpreview.php',
            ['id' => $values->id, 'cmid' => $this->cmid, 'flashcardsid' => $values->flashcardsid]);

        return html_writer::link($qurl, $OUTPUT->pix_icon('viewfc', $this->previewicontext, 'mod_flashcards'),
                ['class' => 'mod_flashcards_questionpreviewlink', 'target' => 'questionpreview']);
    }

    /**
     * Prepares column delete for display
     *
     * @param object $values
     * @return string
     */
    public function col_remove($values) {
        global $OUTPUT;

        $durl = new moodle_url('/mod/flashcards/teacherview.php',
            ['cmid' => $this->cmid, 'deleteselected' => $values->id, 'sesskey' => sesskey(),
                'delete' => true, 'fcid' => $values->fqid]);

        return html_writer::link($durl, $OUTPUT->pix_icon('t/delete', $this->deleteicontext));
    }
}
