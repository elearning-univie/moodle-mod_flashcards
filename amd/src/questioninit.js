define(['jquery', 'core/ajax', 'core/notification', 'core/url'], function ($, ajax, notification) {
    return {
        init: function () {
            $.mod_flashcards_load_questions = function (aid) {
                let data = document.querySelectorAll(".mod-flashcards-checkbox");
                var qids = {};
                for (let i = 0; i < data.length; i++) {
                    if (data[i].checked == true) {
                        qids[i] = data[i].dataset.value;
                    }
                }
                ajax.call([{
                    methodname: 'mod_flashcards_load_questions',
                    args: {flashcardsid: aid, qids: qids},
                    done: function () {
                        location.reload();
                    },
                    fail: notification.exception
                }]);
            };
            $.mod_flashcards_select_all = function (selected) {
                $('input:checkbox').not(selected).prop('checked', selected.checked);
            };
        }
    };
});