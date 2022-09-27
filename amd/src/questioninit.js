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
                            let params = new URLSearchParams(location.search);
                            params.set('page', '0');
                            location.search = params.toString();
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
            $.mod_flashcards_selected = function () {
                var checkboxes = document.getElementsByName('selectbox');
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
            $.mod_flashcards_select_all = function (selected) {
                $('input:checkbox').not(selected).prop('checked', selected.checked);
                this.mod_flashcards_selected();
            };
        }
    };
});

