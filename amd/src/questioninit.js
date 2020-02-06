define(['jquery', 'core/ajax', 'core/notification', 'core/url'], function ($, ajax, notification/*, templates*/, url){
    return {
        init: function() {
            $.mod_flashcards_load_questions = function ($courseid, $questionid, $qaid, $cmid) {
                var $qanswervalue = document.getElementById('qflashcard-question-answer-'.concat($qaid)).value;

                ajax.call([{
                    methodname: 'mod_flashcards_update_progress',
                    args: {courseid: $courseid, questionid: $questionid, qanswervalue: $qanswervalue},
                    done: function(result) {
                        if (result !== null) {
                            $("#mod-flashcards-question").html(result);
                        } else {
                            window.location = url.relativeUrl('/mod/flashcards/studentview.php?id=' + $cmid);
                        }
                    },
                    fail: notification.exception
                }]);
            };
            $.mod_flashcards_select_all = function ($selected) {
                $('input:checkbox').not($selected).prop('checked', $selected.checked);
            };
        }
    };
});