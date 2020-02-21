define(['jquery', 'core/ajax', 'core/notification', 'core/url'], function ($, ajax, notification, url){
    return {
        init: function() {
            var slider = document.getElementById("mod-flashcards-range-slider");
            var output = document.getElementById("mod-flashcards-range-slider-value");
            output.innerHTML = slider.value;
            slider.oninput = function() {
                output.innerHTML = this.value;
            };
            $.mod_flashcards_start_learn_now = function(flashcardsid) {
                ajax.call([{
                    methodname: 'mod_flashcards_start_learn_now',
                    args: {flashcardsid: flashcardsid, qcount: output.innerHTML},
                    done: function(result) {
                        window.console.log(result);
                        //window.location = url.relativeUrl('/mod/flashcards/studentview.php?id=' + $cmid);
                    },
                    fail: notification.exception
                }]);
            };
        }
    };
});

