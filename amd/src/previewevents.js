define(['jquery', 'core/ajax', 'core/notification'], function ($, ajax, notification){
    return {
        init: function(questionid, fcid) {
            var oldval = $("#teachercheck").val();
            $("#teachercheck").change(function() {
                var valueselected = this.value;
                ajax.call([{
                    methodname: 'mod_flashcards_set_preview_status',
                    args: {flashcardsid: fcid, questionid: questionid, status: valueselected},
                    done: function() {
                        $("#tcicon" + oldval).hide();
                        $("#tcicon" + valueselected).show();
                        oldval = valueselected;
                    },
                    fail: notification.exception
                }]);
            });
        }
    };
});
