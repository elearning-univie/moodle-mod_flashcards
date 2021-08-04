define(['jquery', 'core/ajax', 'core/notification'], function ($, ajax, notification){
    return {
        init: function() {
            $.mod_flashcards_teacher_check = function (questionid, fcid, oldval, valueselected) {
                    ajax.call([{
                        methodname: 'mod_flashcards_set_preview_status',
                        args: {flashcardsid: fcid, questionid: questionid, status: valueselected},
                        done: function() {
                            for (let i = 0; i < 3; i++) {
                                $("#tcicon" + i).hide();
                              }
                            $("#tcicon" + valueselected).show();
                         },
                         fail: notification.exception
                    }]);
            };
            $.mod_flashcards_peer_review_up = function (questionid, fcid, userid) {
                var up = 1;
                ajax.call([{
                    methodname: 'mod_flashcards_set_peer_review_vote',
                    args: {flashcardsid: fcid, questionid: questionid, userid: userid, vote: up},
                    done: function() {
                        document.location.reload();
                     },
                     fail: notification.exception
                }]);
            };
            $.mod_flashcards_peer_review_down = function (questionid, fcid, userid) {
                var up = 2;
                ajax.call([{
                    methodname: 'mod_flashcards_set_peer_review_vote',
                    args: {flashcardsid: fcid, questionid: questionid, userid: userid, vote: up},
                    done: function() {
                        document.location.reload();
                     },
                     fail: notification.exception
                }]);
            };
        }
    };
});
