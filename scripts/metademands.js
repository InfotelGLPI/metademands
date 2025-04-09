
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
       * metademands_add_custom_values : add text input
       */
      this.metademands_add_custom_values = function (field_name, field_id) {
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
               'field_id': field_id,
               'count': $('#count_custom_values').val()
            },
            success: function (response) {
               var item_bloc = $('#' + field_name);
               item_bloc.append(response);
               $('#add_custom_values').show();
               var scripts, scriptsFinder = /<script[^>]*>([\s\S]+?)<\/script>/gi;
               while (scripts == scriptsFinder.exec(response)) {
                  eval(scripts[1]);
               }
            }
         });
      };

      return this;
   };
}(jQuery));


var table = document.getElementById('tablesearch');

if (table !== null) {

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
      $('#backtotop').addClass('show');
   } else {
      $('#backtotop').removeClass('show');
   }
});

btn.on('click', function(e) {
   e.preventDefault();
   $('html, body').animate({scrollTop:0}, '300');
});

