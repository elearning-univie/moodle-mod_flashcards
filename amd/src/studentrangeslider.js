import $ from "jquery";
import ajax from "core/ajax";
import notification from "core/notification";

export const init = () => {
    var slider = document.getElementById("mod-flashcards-range-slider");
    var output = document.getElementById("mod-flashcards-range-slider-value");
    var progressbar = $("#mod-flashcards-range-progressbar");
    /**
     * update slider.
     */
    function updatebar() {
        let barwidth = ((output.value - 1) * 100) / (slidermax - 1);
        progressbar.css('width', barwidth + '%');
    }
    if(slider) {
        var slidermin = Number(slider.getAttribute("min"));
        var slidermax = Number(slider.getAttribute("max"));
        output.value = slider.value;
        updatebar();
        slider.oninput = function() {
            output.value = this.value;
            updatebar();
        };
        output.onchange = updatebar;
        output.onblur = function() {
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
            updatebar();
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
};
