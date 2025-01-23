import $ from "jquery";
import ajax from "core/ajax";
import notification from "core/notification";

export const init = () => {
    const modFlashcardsInitQuestions = function (aid) {
        const data = document.querySelectorAll(".mod-flashcards-checkbox");
        var qids = [];
        for (var i = 0; i < data.length; i++) {
            if (data[i].checked == true) {
                qids[i] = data[i].dataset.value;
            }
        }
        if (qids && qids.length) {
            ajax.call([{
                methodname: 'mod_flashcards_init_questions',
                args: {flashcardsid: aid, qids: qids},
                done: function () {
                    let params = new URLSearchParams(location.search);
                    params.set('page', '0');
                    location.search = params.toString();
                },
                fail: notification.exception
            }]);
        }
    };
    window.modFlashcardsInitQuestions = modFlashcardsInitQuestions;
    const modFlashcardsRemoveQuestions = function (aid) {
        const data = document.querySelectorAll(".mod-flashcards-checkbox");
        var qids = [];
        for (var i = 0; i < data.length; i++) {
            if (data[i].checked == true) {
                qids[i] = data[i].dataset.value;
            }
        }
        if (qids && qids.length) {
            ajax.call([{
                methodname: 'mod_flashcards_remove_questions',
                args: {flashcardsid: aid, qids: qids},
                done: function () {
                    location.reload();
                },
                fail: notification.exception
            }]);
        }
    };
    window.modFlashcardsRemoveQuestions = modFlashcardsRemoveQuestions;
    const modFlashcardsSelected = () => {
        const checkboxes = document.getElementsByName('selectbox');
        var checkboxesChecked = [];
        for (var i=0; i<checkboxes.length; i++) {
            if (checkboxes[i].checked) {
                checkboxesChecked.push(checkboxes[i]);
            }
        }
        if(checkboxesChecked.length > 0){
            document.getElementById("maintanancebtn").disabled = false;
        } else{
            document.getElementById("maintanancebtn").disabled = true;
        }
    };

    // Expose the function for external use (if necessary)
    window.modFlashcardsSelected = modFlashcardsSelected;

    const modFlashcardsSelectAll = function (selected) {
        $('input:checkbox').not(selected).prop('checked', selected.checked);
        window.modFlashcardsSelected();
    };
    window.modFlashcardsSelectAll = modFlashcardsSelectAll;
};
