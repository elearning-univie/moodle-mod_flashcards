define(['jquery', 'core/ajax', 'core/notification'], function ($, ajax, notification){
    return {
        init: function(questionid, fcid) {
            $("#teachercheck").change(function() {
                ajax.call([{
                    methodname: 'mod_flashcards_set_preview_status',
                    args: {flashcardsid: fcid, questionid: questionid, status: this.value},
                    done: function() {
                        window.console.log(this.value);
                    },
                    fail: notification.exception
                }]);
            });
        }
    };
});
