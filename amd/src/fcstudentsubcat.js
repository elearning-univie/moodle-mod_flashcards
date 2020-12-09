define(function() {
    return {
        init: function() {
            document.getElementById('id_addfcstudent').addEventListener('change', function(){
               var selectvalue = document.getElementById('id_addfcstudent').value;
               var inclsubcats = document.getElementById("id_inclsubcats");
               if (selectvalue == 1) {
                 inclsubcats.checked = true;
                 inclsubcats.disabled = true;
                 document.getElementById("id_studentsubcatname").style.visibility = "visible";
               } else {
                 inclsubcats.checked = false;
                 inclsubcats.disabled = false;
                 document.getElementById("id_studentsubcatname").style.visibility = "hidden";
               }
    });
        }
    };
});