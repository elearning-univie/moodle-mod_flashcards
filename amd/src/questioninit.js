define(['jquery', 'core/ajax', 'core/notification', 'core/url'], function ($, ajax, notification, url) {
    return {
        init: function () {
            $.mod_flashcards_load_questions = function (aid, cmid) {
                let data = document.querySelectorAll(".mod-flashcards-checkbox");
                var qids = [];
                for (let i = 0; i < data.length; i++) {
                    if (data[i].checked == true) {
                        qids[i] = data[i].dataset.value;
                    }
                }
                if (qids && qids.length) {
                    ajax.call([{
                        methodname: 'mod_flashcards_load_questions',
                        args: {flashcardsid: aid, qids: qids},
                        done: function () {
                            window.location = url.relativeUrl('/mod/flashcards/studentview.php?id=' + cmid);
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