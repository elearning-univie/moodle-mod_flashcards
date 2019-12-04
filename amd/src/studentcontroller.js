define(['jquery', 'core/ajax', 'core/notification'], function ($, ajax, notification){
    return {
        init: function() {
            $.mod_flashcards_call_update = function ($courseid, $questionid) {
                window.console.log($courseid);
                window.console.log($questionid);
                ajax.call([{
                    methodname: 'mod_flashcards_update_progress',
                    args: {courseid: $courseid, questionid: $questionid},
                    done: window.console.log("ajax done"),
                    fail: notification.exception
                }]);
        };}
    };
});
