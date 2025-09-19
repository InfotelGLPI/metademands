/* enable strict mode */
"use strict";

var plugin_metademands_redipsInit;   // function sets dropMode parameter
var plugin_metademands_freetableredipsInit;   // function sets dropMode parameter
var plugin_metademands_orderredipsInit;   // function sets dropMode parameter

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
        var type = document.getElementById('type').value;
        jQuery.ajax({
            type: "POST",
            url: "../ajax/reorder.php",
            data: {
               old_order: old_index,
               new_order: new_index,
               field_id: field,
                type: type
            }
         })
            .fail(function () {
                return false;
            });
    }
};

// redips initialization
plugin_metademands_freetableredipsInit = function () {
    // reference to the REDIPS.drag lib
    var rd = REDIPS.drag;
    // initialization
    rd.init();

    rd.event.rowDroppedBefore = function (sourceTable, sourceRowIndex) {
        var pos = rd.getPosition();
        var old_index = sourceRowIndex;
        var new_index = pos[1];
        var field = document.getElementById('fields_id').value;
        var type = document.getElementById('type_object').value;
        jQuery.ajax({
            type: "POST",
            url: "../ajax/reorder.php",
            data: {
                old_order: old_index,
                new_order: new_index,
                field_id: field,
                type: type
            }
        })
            .fail(function () {
                return false;
            });
    }
};


plugin_metademands_orderredipsInit = function (rand, plugin_metademands_metademands_id) {
    // reference to the REDIPS.drag lib
    // console.log(rand);
    var rd = REDIPS.drag;
    // initialization
    rd.init('drag' + rand);

    rd.event.rowDroppedBefore = function (sourceTable, sourceRowIndex) {
        var pos = rd.getPosition();
        var old_index = sourceRowIndex;
        var new_index = pos[1];
        var hash = window.location.hash;

        var rank = hash.substring(6);
        var fieldid = sessionStorage.getItem("loadedblock");
        if (!hash && fieldid) {
            rank = fieldid.substring(5);
        }
        jQuery.ajax({
            type: "POST",
            url: "../ajax/reorderfields.php",
            data: {
                old_order: old_index,
                new_order: new_index,
                plugin_metademands_metademands_id: plugin_metademands_metademands_id,
                rank: rank
            }
        })
            .fail(function () {
                return false;
            });
    }
};



