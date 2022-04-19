define(['jquery', 'core/ajax', 'core/notification', 'core/url', 'core/event'], function ($, ajax, notification, url, event){
    return {
        init: function() {
            var mobilebox = document.getElementById('mod-flashcards-mobile-app-info');
            var showbtn = document.getElementById('mod-flashcards-show-app');
            var collectionbox = document.getElementById('mod-flashcards-collection-box');
            $.mod_flashcards_call_update = function ($fid, $questionid, $qaid, $cmid) {
                var $qanswervalue = document.getElementById('qflashcard-question-answer-'.concat($qaid)).value;
                var urlParams = new URLSearchParams(window.location.search);
                var boxid = urlParams.get('box');
                ajax.call([{
                    methodname: 'mod_flashcards_update_progress',
                    args: {fid: $fid, boxid: boxid, questionid: $questionid, qanswervalue: $qanswervalue},
                    done: function () {
                        ajax.call([{
                            methodname: 'mod_flashcards_load_next_question',
                            args: {fid: $fid, boxid: boxid},
                            done: function(result) {
                                if (result !== null) {
                                    $("#mod-flashcards-question").html(result);
                                    event.notifyFilterContentUpdated($("#mod-flashcards-question"));
                                    ajax.call([{
                                        methodname: 'mod_flashcards_load_learn_progress',
                                        args: {fid: $fid, boxid: boxid},
                                        done: function(result) {
                                            if (result !== null) {
                                                $("#mod-flashcards-learning-progress").html(result);
                                            }
                                        },
                                        fail: notification.exception
                                    }]);
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
            $.mod_flashcards_minimize_app = function () {
                mobilebox.style.display = "none";
                showbtn.style.display = "inline";

                collectionbox.classList.remove('col-sm-8');
                collectionbox.classList.add('col-sm-12');

                ajax.call([{
                    methodname: 'mod_flashcards_set_showappinfo',
                    args: {prefval: false},
                    fail: notification.exception
                }]);
            };
            $.mod_flashcards_show_app = function () {
                mobilebox.style.display = "block";
                showbtn.style.display = "none";

                collectionbox.classList.remove('col-sm-12');
                collectionbox.classList.add('col-sm-8');

                ajax.call([{
                    methodname: 'mod_flashcards_set_showappinfo',
                    args: {prefval: true},
                    fail: notification.exception
                }]);
            };
        }
    };
});
