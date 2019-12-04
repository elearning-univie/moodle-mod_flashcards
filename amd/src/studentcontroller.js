define(['jquery', 'core/ajax', 'core/notification'], function ($, ajax, notification){
    return {
        init: function() {
            $.mod_flashcards_call_update = function () {
                ajax.call([{
                    methodname: 'mod_flashcards_update_progress',
                    args: {courseid: 1},
                    done: window.console.log("ajax done"),
                    fail: notification.exception
                }]);
        };}
    };
});
