define(['jquery', 'core/ajax', 'core/notification', 'core/url'], function ($, ajax, notification) {
    return {
        init: function () {
            $.mod_flashcards_init_questions = function (aid) {
                var data = document.querySelectorAll(".mod-flashcards-checkbox");
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
                            location.reload();
                        },
                        fail: notification.exception
                    }]);
                }
            };
            $.mod_flashcards_remove_questions = function (aid) {
                var data = document.querySelectorAll(".mod-flashcards-checkbox");
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
            $.mod_flashcards_select_all = function (selected) {
                $('input:checkbox').not(selected).prop('checked', selected.checked);
            };
        }
    };
});