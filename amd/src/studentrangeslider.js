define(function() {
    return {
        init: function() {
            var slider = document.getElementById("mod-flashcards-range-slider");
            var output = document.getElementById("mod-flashcards-range-slider-value");
            output.innerHTML = slider.value;
            slider.oninput = function() {
                output.innerHTML = this.value;
            };
        }
    };
});

