define(['jquery', 'core/ajax', 'core/notification'], function ($, ajax, notification){
    return {
        init: function() {
            $.mod_flashcards_call_update = function ($courseid, $questionid) {
                ajax.call([{
                    methodname: 'mod_flashcards_update_progress',
                    args: {courseid: $courseid, questionid: $questionid},
                    done: function(result) {
                        $("#mod-flashcards-question").html(result);
                    },
                    fail: notification.exception
                }]);
        };}
    };
});
