define(['jquery'], function() {
    return {
        init: function(fcstring) {
            document.getElementById('id_name').addEventListener('change', function(){
               var activityname = document.getElementById('id_name').value;
               document.getElementById('id_newcategoryname').value = fcstring + ' ' + activityname;
    });
        }
    };
});