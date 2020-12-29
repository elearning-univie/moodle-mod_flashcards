define(['jquery', 'core/ajax', 'core/notification', 'core/url'], function ($, ajax, notification){
    return {
        init: function() {
            var slider = document.getElementById("mod-flashcards-range-slider");
            var output = document.getElementById("mod-flashcards-range-slider-value");
            if(slider) {
                var slidermin = Number(slider.getAttribute("min"));
                var slidermax = Number(slider.getAttribute("max"));
                output.value = slider.value;
                slider.oninput = function() {
                    output.value = this.value;
                };
                output.oninput = function() {
                    var intval = Math.floor(this.value);
                    if (intval == this.value && $.isNumeric(this.value)) {
                        if (this.value >= slidermin && this.value <= slidermax) {
                            slider.value = intval;
                            return;
                        } else if (this.value < slidermin) {
                            slider.value = slidermin;
                        } else {
                            slider.value = slidermax;
                        }
                    } else {
                        slider.value = slidermin;
                    }
                    output.value = slider.value;
                };
                $.mod_flashcards_start_learn_now = function(flashcardsid) {
                    ajax.call([{
                        methodname: 'mod_flashcards_start_learn_now',
                        args: {flashcardsid: flashcardsid, qcount: slider.value},
                        done: function(result) {
                            window.location = result;
                        },
                        fail: notification.exception
                    }]);
                };
            }
        }
    };
});

