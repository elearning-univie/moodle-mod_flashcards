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
            $.mod_flashcards_peer_review = function (questionid, fcid, vote) {
                var downvotebtncl = document.getElementById('downvotebtn').classList;
                var upvotebtncl = document.getElementById('upvotebtn').classList;
                var upvoteval = parseInt(document.getElementById('upvotescount').innerHTML);
                var downvoteval = parseInt(document.getElementById('downvotescount').innerHTML);
                ajax.call([{
                    methodname: 'mod_flashcards_set_peer_review_vote',
                    args: {flashcardsid: fcid, questionid: questionid, vote: vote},
                    done: function() {
                        if (upvotebtncl.contains('btn-up')) {
                            upvotebtncl.remove('btn-up');
                            upvoteval -= 1;
                        } else if (downvotebtncl.contains('btn-down')) {
                            downvotebtncl.remove('btn-down');
                            downvoteval -= 1;
                        }

                        if (vote == 1) {
                            upvotebtncl.add('btn-up');
                            upvoteval += 1;
                        } else {
                            downvotebtncl.add('btn-down');
                            downvoteval += 1;
                        }
                        document.getElementById('upvotescount').innerHTML = upvoteval;
                        document.getElementById('downvotescount').innerHTML = downvoteval;
                     },
                     fail: notification.exception
                }]);
            };
            $.mod_flashcards_hide_show_answer = function (qaid, htxt, stxt) {
                var x = document.getElementById('mod-flashcards-showhidecontainer-answer-'.concat(qaid));
                var btn = document.getElementById('mod-flashcards-showhidebutton-'.concat(qaid));
                if (x.style.display === 'none') {
                  btn.textContent = htxt;
                  x.style.display = 'block';
                } else {
                    btn.textContent = stxt;
                  x.style.display = 'none';
                }
            };
        }
    };
});
