define(['jquery', 'core/ajax', 'core/notification', 'core/url'], function ($, ajax, notification, url){
    return {
        init: function() {
            $.mod_flashcards_call_update = function ($fid, $questionid, $qaid, $cmid) {
                var $qanswervalue = document.getElementById('qflashcard-question-answer-'.concat($qaid)).value;
                var urlParams = new URLSearchParams(window.location.search);
                var boxid = urlParams.get('box');
                ajax.call([{
                    methodname: 'mod_flashcards_update_progress',
                    args: {fid: $fid, questionid: $questionid, qanswervalue: $qanswervalue},
                    done: function () {
                        ajax.call([{
                            methodname: 'mod_flashcards_load_next_question',
                            args: {fid: $fid, boxid: boxid},
                            done: function(result) {
                                if (result !== null) {
                                    $("#mod-flashcards-question").html(result);
                                } else {
                                    window.location = url.relativeUrl('/mod/flashcards/studentview.php?id=' + $cmid);
                                }
                            },
                            fail: notification.exception
                        }]);
                    },
                    fail: notification.exception
                }]);
            };
        }
    };
});