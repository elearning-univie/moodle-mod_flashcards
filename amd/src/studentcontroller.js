define(['jquery', 'core/ajax', 'core/notification', 'core/url'], function ($, ajax, notification/*, templates*/, url){
    return {
        init: function() {
            $.mod_flashcards_call_update = function ($courseid, $questionid, $qaid) {
                var $qanswervalue = document.getElementById('qflashcard-question-answer-'.concat($qaid)).value;

                ajax.call([{
                    methodname: 'mod_flashcards_update_progress',
                    args: {courseid: $courseid, questionid: $questionid, qanswervalue: $qanswervalue},
                    done: function(result) {
                        if (result !== null) {
                            $("#mod-flashcards-question").html(result);
                        } else {
                            window.location = url.relativeUrl('/mod/flashcards/studentview.php?id=' + $courseid);
                        }
                    },
                    fail: notification.exception
                }]);
            };

            $(".testbtnbtw").click(function() {
                ajax.call([{
                    methodname: 'mod_flashcards_load_questions',
                    args: {courseid: 2},
                    done: function() {
                        window.location.reload();
                    },
                    fail: notification.exception
                }]);
            });

        }
    };
});