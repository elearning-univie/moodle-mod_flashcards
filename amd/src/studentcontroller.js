define(['jquery', 'core/ajax', 'core/notification', 'core/url'], function ($, ajax, notification, url){
    return {
        init: function() {
            $.mod_flashcards_call_update = function ($questionid, $qaid, $cmid) {
                var $qanswervalue = document.getElementById('qflashcard-question-answer-'.concat($qaid)).value;

                ajax.call([{
                    methodname: 'mod_flashcards_update_progress',
                    args: {questionid: $questionid, cmid: $cmid, qanswervalue: $qanswervalue},
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
        }
    };
});