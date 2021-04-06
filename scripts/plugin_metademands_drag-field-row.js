/* enable strict mode */
"use strict";

var plugin_metademands_redipsInit;   // function sets dropMode parameter

// redips initialization
plugin_metademands_redipsInit = function () {
    // reference to the REDIPS.drag lib
    var rd = REDIPS.drag;
    // initialization
    rd.init();

    rd.event.rowDroppedBefore = function (sourceTable, sourceRowIndex) {
        var pos = rd.getPosition();
        var old_index = sourceRowIndex;
        var new_index = pos[1];
        var field = document.getElementById('fields_id').value;

        jQuery.ajax({
            type: "POST",
            url: "../ajax/reorder.php",
            data: {
               old_order: old_index,
               new_order: new_index,
               field_id: field
            }
         })
            .fail(function () {
                return false;
            });
    }
};


