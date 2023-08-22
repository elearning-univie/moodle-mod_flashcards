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
 * Initialise the an add question modal on the quiz page.
 *
 * @module    mod_flashcards/addquestionmodallauncher
 * @copyright 2021 University of Vienna
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import $ from 'jquery';
import Notification from 'core/notification';
import ModalFactory from 'core/modal_factory';

const AddQuestionModalLauncher = {
    /**
     * Create a modal using the modal factory and add listeners to launch the
     * modal when clicked.
     *
     * @param  {string} modalType Which modal to create
     * @param  {string} selector The selectors for the elements that trigger the modal
     * @param  {int} contextId The current context id
     * @param  {function} preShowCallback A callback to execute before the modal is shown
     * @return {promise} Resolved with the modal
     */
    init(modalType, selector, contextId, preShowCallback) {
        const body = $('body');
        return ModalFactory.create(
            {
                type: modalType,
                large: true,
                preShowCallback(triggerElement, modal) {
                    triggerElement = $(triggerElement);
                    modal.setContextId(contextId);
                    modal.setAddOnPageId(triggerElement.attr('data-addonpage'));
                    modal.setTitle(triggerElement.attr('data-header'));

                    if (preShowCallback) {
                        preShowCallback(triggerElement, modal);
                    }
                }
            },
            [body, selector]
        ).fail(Notification.exception);
    }
};

export default AddQuestionModalLauncher;
