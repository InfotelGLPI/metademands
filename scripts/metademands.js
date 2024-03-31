
/**
 * metademandWizard
 *
 * @param  options
 */

(function ($) {
   $.fn.metademandWizard = function (options) {

      var object = this;
      init();

      /**
       * Start the plugin
       */
      function init() {
         object.params = [];
         object.params.lang = '';
         object.params.root_doc = '';

         if (options != undefined) {
            $.each(options, function (index, val) {
               if (val != undefined && val != null) {
                  object.params[index] = val;
               }
            });
         }
      }

      /**
       * metademands_show_field_onchange : show or hide fields
       *
       * @param params $params - id : id of object to observe
       *                       - value : value to compare
       *                       - valueDisplay : value field to dislay
       *                       - titleDisplay : title field to dislay
       */
      this.metademands_show_field_onchange = function (params) {
         var item = document.getElementById(params.id);
         item.onchange = function () {
            object.metademands_show_field(params);

            // If datetime interval, show label2
            if (item.value == 'datetime_interval' || item.value == 'date_interval') {
               document.getElementById('show_label2').style.display = "inline";
            } else {
               document.getElementById('show_label2').style.display = "none";
            }
         };
      };

      /**
       * metademands_show_field : show or hide fields
       *
       * @param params $params - id : id of object to observe
       *                       - value : value to compare
       *                       - valueDisplay : value field to dislay
       *                       - titleDisplay : title field to dislay
       */
      this.metademands_show_field = function (params) {
         var item = document.getElementById(params.id);

         if (params.values.includes(item.value)|| params.values_plugin.includes(item.value)) {
            document.getElementById(params.valueDisplay).style.display = "inline";
            if (params.titleDisplay != undefined) {
               document.getElementById(params.titleDisplay).style.display = "inline";
            }

            document.getElementById(params.valueDisplay_title).style.display = "none";
            if (params.titleDisplay != undefined) {
               document.getElementById(params.titleDisplay_title).style.display = "none";
            }
         } else if (item.value == params.value_title || item.value == params.value_informations || item.value == params.value_title_block) {
            document.getElementById(params.valueDisplay).style.display = "none";
            if (params.titleDisplay != undefined) {
               document.getElementById(params.titleDisplay).style.display = "none";
            }

            document.getElementById(params.valueDisplay_title).style.display = "inline";
            if (params.titleDisplay != undefined) {
               document.getElementById(params.titleDisplay_title).style.display = "inline";
            }
         } else {
            document.getElementById(params.valueDisplay).style.display = "none";
            if (params.titleDisplay != undefined) {
               document.getElementById(params.titleDisplay).style.display = "none";
            }

            document.getElementById(params.valueDisplay_title).style.display = "none";
            if (params.titleDisplay != undefined) {
               document.getElementById(params.titleDisplay_title).style.display = "none";
            }
         }

         // If datetime interval, show label2
         if (item.value == 'datetime_interval' || item.value == 'date_interval') {
            document.getElementById('show_label2').style.display = "inline";
         } else {
            document.getElementById('show_label2').style.display = "none";
         }

      };



      /**
       * metademands_add_custom_values : add text input
       */
      this.metademands_add_custom_values = function (field_id) {
         var count = $('#count_custom_values').val();
         $('#count_custom_values').val(parseInt(count) + 1);

         var display_comment = $('#display_comment').val();
         var display_default = $('#display_default').val();
         $.ajax({
            url: object.params.root_doc + '/ajax/addnewvalue.php',
            type: "POST",
            dataType: "html",
            data: {
               'action': 'add',
               'display_comment': display_comment,
               'display_default': display_default,
               'count': $('#count_custom_values').val()
            },
            success: function (response) {
               var item_bloc = $('#' + field_id);
               item_bloc.append(response);
               $('#add_custom_values').show();
               var scripts, scriptsFinder = /<script[^>]*>([\s\S]+?)<\/script>/gi;
               while (scripts == scriptsFinder.exec(response)) {
                  eval(scripts[1]);
               }
            }
         });
      };

      /**
       * setMandatoryField : change mandatory mark
       *
       * @param  toupdate  : element id to update
       * @param  toobserve : element id to observe
       * @param  check_value : value to check
       */
      this.metademand_setMandatoryField = function (toupdate, toobserve, check_value, type) {

         object.metademand_checkEmptyField(toupdate, toobserve, check_value, type);

         if (type == 'checkbox') {
            $("input[name='" + toobserve + "']").change(function () {
               object.metademand_checkEmptyField(toupdate, toobserve, check_value, type);
            });
         } else if (type == 'radio') {
            $("[id='" + toobserve + "']").change(function () {
               object.metademand_checkEmptyField(toupdate, toobserve, check_value, type);
            });
         } else {
            $("[name='" + toobserve + "']").change(function () {
               object.metademand_checkEmptyField(toupdate, toobserve, check_value, type);
            });
         }
      };

      /**
       * metademand_checkEmptyField : check if field must be mandatory
       *
       * @param  toupdate    : element id to update
       * @param  toobserve   : element id to observe
       * @param  check_value : value to check
       */
      this.metademand_checkEmptyField = function (toupdate, toobserve, check_value, type) {

         var obs;
         var id_field;
         if (type == 'checkbox') {
            obs = $("input[name='" + toobserve + "']:checked");
         } else if (type == 'radio') {
            obs = $("[id='" + toobserve + "']:checked");
         } else {
            obs = $("[name='" + toobserve + "']");
         }

         // console.log(obs.val());
         // console.log(check_value);
         //check_value is not an array
         
         var op1 = (!Array.isArray(check_value) && (check_value != 0 && obs.val() == check_value || check_value == -1 && obs.val() != 0));
         //check_value is an array
         var op2 = (Array.isArray(check_value) && obs.val() != 0 && type != 'radio' && type != 'checkbox'  && (check_value.includes(parseInt(obs.val(), 10)) || check_value.includes(-1)));

         var op3 = (Array.isArray(check_value)  && (type == 'radio' || type == 'checkbox') && check_value.includes(parseInt(obs.val(), 10)));

      
         if (op1 || op2 || op3) {

            $('#' + toupdate).html('*');
            id_field = toupdate.substring(22);

            //Must use good type no observe field type but id_field type
            // if ($("[name='field[" + id_field + "]']").length > 0) {
            //    if (type == 'checkbox') {
            //       $("[check^='field[" + id_field + "]']").attr('required', 'required');
            //       $("[name^='quantity[" + id_field + "]']").attr('required', 'required');
            //       $("[name^='quantity[" + id_field + "]']").attr('ismultiplenumber', 'ismultiplenumber');
            //    } else if (type == 'radio') {
            //       $("[name^='field[" + id_field + "]']").attr('required', 'required');
            //       $("[name^='quantity[" + id_field + "]']").attr('required', 'required');
            //       $("[name^='quantity[" + id_field + "]']").attr('ismultiplenumber', 'ismultiplenumber');
            //    } else {
                  $("[check^='field[" + id_field + "]']").attr('required', 'required');
                  $("[name='field[" + id_field + "]']").attr('required', 'required');
                  $("[name^='quantity[" + id_field + "]']").attr('required', 'required');
                  $("[name^='quantity[" + id_field + "]']").attr('ismultiplenumber', 'ismultiplenumber');
               // }
               //hack for date field..
               $("[name='field[" + id_field + "]']").next('input').attr('required', 'required');
            // }
         } else {
            // if (type != 'checkbox') {
               $('#' + toupdate).html('');
               id_field = toupdate.substring(22);
               $("[name^='field[" + id_field + "]']").removeAttr('required');
               $("[name^='quantity[" + id_field + "]']").removeAttr('required');
               $("[name^='quantity[" + id_field + "]']").removeAttr('ismultiplenumber');
               // $("[for^='field[" + id_field + "]']").css('color', 'unset');
               //hack for date field..
               $("[name='field[" + id_field + "]']").next('input').removeAttr('required');
            // }
         }
      };

      // this.metademand_displayField = function (toupdate, toobserve, check_value) {
      //     $('#' + toupdate).hide();
      //
      //     this.metademand_checkField(toupdate, toobserve, check_value);
      //     $("[name='" + toobserve + "']").change(function () {
      //         this.metademand_checkField(toupdate, toobserve, check_value);
      //     });
      // };
      //
      // this.metademand_checkField = function (toupdate, toobserve, check_value) {
      //     if (check_value != 0 && ($("[name='" + toobserve + "']").val() == check_value
      //         || (check_value == 'NOT_NULL' && $("[name='" + toobserve + "']").val() != 0))) {
      //         $('#' + toupdate).show();
      //     } else {
      //         $('#' + toupdate).hide();
      //     }
      // };

      return this;
   };
}(jQuery));


var table = document.getElementById('tablesearch');

if (table !== null) {
console.log(tablesearch);
   // Get input elements and table
   var filterRefInput = document.getElementById('searchref');
   var filterNameInput = document.getElementById('searchname');
   var filterDescriptionInput = document.getElementById('searchdescription');
   var rows = table.getElementsByTagName('tr');

// Add event listeners to the input elements
   filterRefInput.addEventListener('input', filterTable);
   filterNameInput.addEventListener('input', filterTable);
   filterDescriptionInput.addEventListener('input', filterTable);

   function filterTable() {
      var filterRef = filterRefInput.value.toUpperCase();
      var filterName = filterNameInput.value.toUpperCase();
      var filterDesc = filterDescriptionInput.value.toUpperCase();

      // Loop through all table rows, hide those that don't match the filter criteria
      for (var i = 0; i < rows.length; i++) {
         var ref = rows[i].getElementsByTagName('td')[0].textContent.toUpperCase();
         var name = rows[i].getElementsByTagName('td')[1].textContent.toUpperCase();
         var desc = rows[i].getElementsByTagName('td')[2].textContent.toUpperCase();

         if (ref.includes(filterRef) && name.includes(filterName) && desc.includes(filterDesc)) {
            rows[i].style.display = '';
         } else {
            rows[i].style.display = 'none';
         }
      }
   }
}

var btn = $('#backtotop');

$(window).scroll(function() {
   if ($(window).scrollTop() > 300) {
      btn.addClass('show');
   } else {
      btn.removeClass('show');
   }
});

btn.on('click', function(e) {
   e.preventDefault();
   $('html, body').animate({scrollTop:0}, '300');
});

