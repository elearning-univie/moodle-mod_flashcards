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
 * @copyright  2019 University of Vienna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class teacherviewtable extends table_sql {

    /** @var int course module id */
    private $cmid;

    /** @var int course id */
    private $courseid;

    /** @var int flashcard id */
    private $fcid;

    /** @var string text for the edit icon */
    private $editicontext;

    /** @var string text for the delete icon */
    private $deleteicontext;

    /** @var string text for the preview icon */
    private $previewicontext;

    /** @var string jump back url if a question is getting deleted */
    private $returnurl;

    /**
     * Constructor
     * @param int $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     */
    function __construct($uniqueid, $cmid, $courseid, $fcid) {
        parent::__construct($uniqueid);
        $this->cmid = $cmid;
        $this->courseid = $courseid;
        $this->fcid = $fcid;
        $this->returnurl = '/mod/flashcards/teacherview.php?id=' . $cmid;

        $this->editicontext = get_string('edit', 'moodle');
        $this->deleteicontext = get_string('delete', 'moodle');
        $this->previewicontext = get_string('preview', 'moodle');

        // Define the list of columns to show.
        $columns = array('name', 'createdby', 'teachercheck', 'peerreview', 'edit', 'preview', 'delete');
        $this->define_columns($columns);
        $this->column_class('teachercheck', 'flashcards_teacherview_ec');
        $this->column_class('peerreview', 'flashcards_teacherview_ec');
        $this->column_class('edit', 'flashcards_teacherview_ec');
        $this->column_class('preview', 'flashcards_teacherview_ec');
        $this->column_class('delete', 'flashcards_teacherview_ec');

        // Define the titles of columns to show in header.
        $headers = array(
                get_string('question', 'mod_flashcards'),
                get_string('author', 'mod_flashcards'),
                get_string('teachercheck', 'mod_flashcards'),
                get_string('peerreview', 'mod_flashcards'),
                null,
                null,
                null);
        $this->define_headers($headers);

        $this->collapsible(false);
        $this->sortable(true);
        $this->pageable(true);
        $this->is_downloadable(false);
    }

    /**
     * Prepares column teachercheck for display
     *
     * @param object $values
     * @return string
     */
    function col_teachercheck($values) {
        global $OUTPUT;

        $teachercheckresult = mod_flashcard_get_teacher_check_result($values->id, $this->fcid, $this->courseid);
        $checkinfo = mod_flashcard_get_teacher_check_info($teachercheckresult);

        return html_writer::div($OUTPUT->pix_icon($checkinfo['icon']['key'], $checkinfo['icon']['title']), $checkinfo['color']);
    }

    /**
     * Prepares column peerreview for display
     *
     * @param object $values
     * @return string
     */
    function col_peerreview($values) {
        $htmlcode = '-/-';

        return $htmlcode;
    }

    /**
     * Prepares column edit for display
     *
     * @param object $values
     * @return string
     */
    function col_edit($values) {
        global $OUTPUT;

        $eurl = new moodle_url('/question/question.php',
                array('returnurl' => $this->returnurl, 'courseid' => $this->courseid, 'id' => $values->id ));

        return html_writer::link($eurl, $OUTPUT->pix_icon('i/settings', $this->editicontext));
    }

    /**
     * Prepares column preview for display
     *
     * @param object $values
     * @return string
     */
    function col_preview($values) {
        global $OUTPUT;

        $qurl = new moodle_url('/mod/flashcards/flashcardpreview.php', array('id' => $values->id, 'cmid' => $this->cmid, 'fcid' => $this->fcid));

        return html_writer::link($qurl, $OUTPUT->pix_icon('i/preview', $this->previewicontext));
    }

    /**
     * Prepares column delete for display
     *
     * @param object $values
     * @return string
     */
    function col_delete($values) {
        global $OUTPUT;

        $durl = new moodle_url('/mod/flashcards/teacherview.php',
                array('id' => $this->cmid, 'deleteselected' => $values->id, 'sesskey' => sesskey()));

        return html_writer::link($durl, $OUTPUT->pix_icon('t/delete', $this->deleteicontext));
    }
}