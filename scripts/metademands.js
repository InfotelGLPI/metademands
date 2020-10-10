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
         object.params = new Array();
         object.params['lang'] = '';
         object.params['root_doc'] = '';

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
            if (item.value == 'datetime_interval') {
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
         if (item.value == params.value) {
            document.getElementById(params.valueDisplay).style.display = "inline";
            if (params.titleDisplay != undefined) {
               document.getElementById(params.titleDisplay).style.display = "inline";
            }

            document.getElementById(params.valueDisplay_title).style.display = "none";
            if (params.titleDisplay != undefined) {
               document.getElementById(params.titleDisplay_title).style.display = "none";
            }
         } else if (item.value == params.value_title) {
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
         if (item.value == 'datetime_interval') {
            document.getElementById('show_label2').style.display = "inline";
         } else {
            document.getElementById('show_label2').style.display = "none";
         }

      };

      /**
       * changeNbValue : display text input
       *
       * @param newValue
       */
      this.changeNbValue = function (newValue) {
         document.getElementById('nbValue').value = newValue;
         return true;
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
            url: object.params['root_doc'] + '/plugins/metademands/ajax/addnewvalue.php',
            type: "POST",
            dataType: "html",
            data: {
               'action': 'add',
               'display_comment': display_comment,
               'display_default': display_default,
               'count': $('#count_custom_values').val()
            },
            success: function (response, opts) {
               var item_bloc = $('#' + field_id);
               item_bloc.append(response);

               var scripts, scriptsFinder = /<script[^>]*>([\s\S]+?)<\/script>/gi;
               while (scripts = scriptsFinder.exec(response)) {
                  eval(scripts[1]);
               }
            }
         });
      };

      /**
       * metademands_delete_custom_values : delete text input
       *
       * @param field_id
       */
      this.metademands_delete_custom_values = function (field_id) {
         var count = $('#count_custom_values').val();
         $('#custom_values' + count).remove();
         $('#comment_values' + count).remove();
         $('#default_values' + count).remove();
         $('#' + field_id + count).remove();
         $('#count_custom_values').val(parseInt(count) - 1);


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
            $("input[check='" + toobserve + "']").change(function () {
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

         if (type == 'checkbox') {
            obs = $("input[check='" + toobserve + "']:checked");
         } else if (type == 'radio') {
            obs = $("[name='" + toobserve + "']:checked");
         } else {
            obs = $("[name='" + toobserve + "']");
         }
         if (
            check_value != 0 &&
            obs.val() == check_value
            //  ||
            // check_value == 'NOT_NULL' &&
            // $("[name='" + toobserve + "']").val() != 0
         ) {
            $('#' + toupdate).html('*');
         } else {
            $('#' + toupdate).html('');
         }
      };

      // this.metademand_displayField = function (toupdate, toobserve, check_value) {
      //     console.log('toto');
      //     $('#' + toupdate).hide();
      //
      //     this.metademand_checkField(toupdate, toobserve, check_value);
      //     $("[name='" + toobserve + "']").change(function () {
      //         this.metademand_checkField(toupdate, toobserve, check_value);
      //     });
      // };
      //
      // this.metademand_checkField = function (toupdate, toobserve, check_value) {
      //     console.log(check_value);
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

/**
 * metademandAdditionalFields
 *
 * @param  options
 */
(function ($) {
   $.fn.metademandAdditionalFields = function (options) {

      var object = this;
      init();

      /**
       * Start the plugin
       */
      function init() {
         object.params = new Array();
         object.params['lang'] = '';
         object.params['root_doc'] = '';

         object.countSubmit = 0;

         if (options != undefined) {
            $.each(options, function (index, val) {
               if (val != undefined && val != null) {
                  object.params[index] = val;
               }
            });
         }
      }

      /**
       * metademand_getAdditionalFields : prepare additional fields injection
       */
      this.metademand_getAdditionalFields = function () {
         // On UPDATE/ADD side
         $(document).ready(function () {
            var tickets_id = object.urlParam(window.location.href, 'id');
            // only in ticket form
            if (location.pathname.indexOf('front/ticket.form.php') > 0) {
               object.injectFields(tickets_id, 'central');

               // Self-service specific case
            } else if (window.location.href.indexOf('helpdesk.public.php?create_ticket=1') > 0 || location.pathname.indexOf('tracking.injector.php') > 0) {
               object.injectFields(tickets_id, 'helpdesk');

            }
         });
      };

      /**
       * injectFields : Inject additional fields
       */
      this.injectFields = function (tickets_id, type) {

         // Inject fields
         if ($("select[name='itilcategories_id']").length != 0) {
            var formName = 'form_ticket';
            if (type == 'helpdesk') {
               formName = 'helpdeskform';
            }

            var familyLoaded = false;
            if (object.params['config'].enable_families) {
               if (type == 'central') {
                  object.metademands_loadFamilies(tickets_id, 0, formName);
               } else {
                  object.metademands_loadHelpdeskFamilies(tickets_id, formName);
               }
               familyLoaded = true;
            }

            // Normal call
            if (object.params['config'].enable_application_environment) {
               if (!familyLoaded) {
                  if (type == 'central') {
                     object.metademands_loadApplication(tickets_id, formName);
                  } else {
                     object.metademands_loadHelpdeskApplication(tickets_id, formName);
                  }

                  // Wait for family injection
               } else {
                  var loaded = 0;
                  $(document).ajaxComplete(function (event, xhr, option) {
                     if ((object.urlParam(option.data, 'action') == 'getHelpdeskFamily' || object.urlParam(option.data, 'action') == 'getFamily')
                        && option.url.indexOf("metademands/ajax/ticket.php") != -1 && loaded === 0) {

                        loaded++;
                        if (type == 'central') {
                           object.metademands_loadApplication(tickets_id, formName);
                        } else {
                           object.metademands_loadHelpdeskApplication(tickets_id, formName);
                        }
                     }
                  }, this);
               }
            }
         }
      };

      /**
       * metademands_loadApplication : Inject application / environement fields
       */
      this.metademands_loadApplication = function (tickets_id, formName) {
         var table = "mainformtable2";
         if (tickets_id == 0) {
            table = "mainformtable";
         }
         var tab_cadre_fixe = $("table[id='" + table + "'] th:contains('" + object.params['lang'].category + "')").closest('tr');

         if (tab_cadre_fixe != undefined) {
            $.ajax({
               url: object.params['root_doc'] + '/plugins/metademands/ajax/ticket.php',
               type: "POST",
               dataType: "html",
               data: 'tickets_id=' + tickets_id + '&' + object.getFormData(formName) + '&action=getApplicationEnvironment',
               success: function (response, opts) {
                  if ($('#plugin_metademands_ticket').length > 0) {

                     $('#plugin_metademands_ticket').remove();
                  }
                  if (response != '') {
                     $(response).insertAfter(tab_cadre_fixe);
                  }

                  var scripts, scriptsFinder = /<script[^>]*>([\s\S]+?)<\/script>/gi;
                  while (scripts = scriptsFinder.exec(response)) {
                     eval(scripts[1]);
                  }
               }
            });
         }
      };

      /**
       * metademands_loadFamilies : Inject type / family / categroy fields
       */
      this.metademands_loadFamilies = function (tickets_id, loadCounter, formName) {
         $.ajax({
            url: object.params['root_doc'] + '/plugins/metademands/ajax/ticket.php',
            type: "POST",
            dataType: "html",
            data: 'tickets_id=' + tickets_id + '&' + object.getFormData(formName) + '&action=getFamily',
            success: function (response, opts) {
               if (response != '') {
                  var tabId = 'mainformtable';
                  if (tickets_id > 0) {
                     tabId = 'mainformtable2';
                  }
                  // Add td for good display
                  if (loadCounter === 0) {
                     if (tickets_id == 0) {
                        $("table[id='" + tabId + "'] tbody tr:nth-child(2)").append('<td colspan="2"></td>');
                        $("tr[class='headerRow responsive_hidden'] th").attr('colspan', 6);

                     } else {
                        $("table[id='" + tabId + "'] th:contains('" + object.params['lang'].source + "')").closest('tr').append('<td colspan="2"></td>');
                        $("table[id='" + tabId + "'] th:contains('" + object.params['lang'].approval + "')").closest('tr').append('<td colspan="2"></td>');
                        $("table[id='" + tabId + "'] th:contains('" + object.params['lang'].location + "')").closest('tr').append('<td colspan="2"></td>');
                        $("table[id='" + tabId + "'] th:contains('" + object.params['lang'].element + "')").closest('tr').append('<td rowspan="2" colspan="2"></td>');
                     }
                  }

                  // Append response
                  $("table[id='" + tabId + "'] th:contains('" + object.params['lang'].category + "')").closest('tr').html(response);

                  // Type change : reload family and category
                  $("select[name='type']").on('change', function () {
                     $("input[name='families_id']").val(0);
                     $("input[name='itilcategories_id']").val(0);
                     object.metademands_loadFamilies(tickets_id, loadCounter, formName);
                  });

                  // Type change : reload family and category
                  $("select[name='families_id']").on('change', function () {
                     $("input[name='itilcategories_id']").val(0);
                     object.metademands_loadFamilies(tickets_id, loadCounter, formName);
                  });

                  // Remove td width
                  if (loadCounter === 0) {
                     $("table[id='" + tabId + "'] tbody tr td").removeAttr('width');
                  }

                  loadCounter++;
               }

               var scripts, scriptsFinder = /<script[^>]*>([\s\S]+?)<\/script>/gi;
               while (scripts = scriptsFinder.exec(response)) {
                  eval(scripts[1]);
               }
            }
         });
      };

      /**
       * metademands_loadHelpdeskApplication : Inject application / environement fields
       */
      this.metademands_loadHelpdeskApplication = function (tickets_id, formName) {
         var tab_cadre_fixe = $("select[name='itilcategories_id'], input[name='itilcategories_id']").closest('tr');
         if (tab_cadre_fixe != undefined) {
            $.ajax({
               url: object.params['root_doc'] + '/plugins/metademands/ajax/ticket.php',
               type: "POST",
               dataType: "html",
               data: 'tickets_id=' + tickets_id + '&' + object.getFormData(formName) + '&action=getHelpdeskApplicationEnvironment',
               success: function (response, opts) {
                  if (response != '') {
                     $(response).insertAfter(tab_cadre_fixe);
                  }

                  var scripts, scriptsFinder = /<script[^>]*>([\s\S]+?)<\/script>/gi;
                  while (scripts = scriptsFinder.exec(response)) {
                     eval(scripts[1]);
                  }
               }
            });
         }
      };

      /**
       * metademands_loadHelpdeskFamilies : Inject type / family / categroy fields
       */
      this.metademands_loadHelpdeskFamilies = function (tickets_id, formName) {
         $.ajax({
            url: object.params['root_doc'] + '/plugins/metademands/ajax/ticket.php',
            type: "POST",
            dataType: "html",
            data: 'tickets_id=' + tickets_id + '&' + object.getFormData(formName) + '&action=getHelpdeskFamily',
            success: function (response, opts) {
               if (response != '') {
                  $("select[name='type'], input[name='type']").closest('tr').remove();
                  $("select[name='itilcategories_id']").closest('tr').remove();
                  $("select[name='families_id']").closest('tr').remove();

                  if ($("select[name='plugin_metademands_itilapplications_id']").length > 0) {
                     $(response).insertBefore($("select[name='plugin_metademands_itilapplications_id']").closest('tr'));
                  } else {
                     $(response).insertBefore($("select[name='urgency']").closest('tr'));
                  }

                  // Type change : reload family and category
                  $("select[name='type']").on('change', function () {
                     $("input[name='families_id']").val(0);
                     $("input[name='itilcategories_id']").val(0);
                     object.metademands_loadHelpdeskFamilies(tickets_id, formName);
                  });

                  // Type change : reload family and category
                  $("select[name='families_id']").on('change', function () {
                     $("input[name='itilcategories_id']").val(0);
                     object.metademands_loadHelpdeskFamilies(tickets_id, formName);
                  });
               }

               var scripts, scriptsFinder = /<script[^>]*>([\s\S]+?)<\/script>/gi;
               while (scripts = scriptsFinder.exec(response)) {
                  eval(scripts[1]);
               }
            }
         });
      };

      /**
       *  Get the form values and construct data url
       *
       * @param object form
       */
      this.getFormData = function (form) {
         if (typeof (form) !== 'object') {
            var form = $("form[name='" + form + "']");
         }

         return object.encodeParameters(form[0]);
      };

      /**
       * Encode form parameters for URL
       *
       * @param array elements
       */
      this.encodeParameters = function (elements) {
         var kvpairs = [];

         $.each(elements, function (index, e) {
            if (e.name != '') {
               switch (e.type) {
                  case 'radio':
                  case 'checkbox':
                     if (e.checked) {
                        kvpairs.push(encodeURIComponent(e.name) + "=" + encodeURIComponent(e.value));
                     }
                     break;
                  case 'select-multiple':
                     var name = e.name.replace("[", "").replace("]", "");
                     $.each(e.selectedOptions, function (index, option) {
                        kvpairs.push(encodeURIComponent(name + '[' + option.index + ']') + '=' + encodeURIComponent(option.value));
                     });
                     break;
                  default:
                     kvpairs.push(encodeURIComponent(e.name) + "=" + encodeURIComponent(e.value));
                     break;
               }
            }
         });

         return kvpairs.join("&");
      };

      /**
       * Get url parameter
       *
       * @param string url
       * @param string name
       */
      this.urlParam = function (url, name) {
         var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(url);
         if (results == null || results == undefined) {
            return 0;
         }

         return results[1];
      };

      /**
       * Is IE navigator
       */
      this.isIE = function () {
         var ua = window.navigator.userAgent;
         var msie = ua.indexOf("MSIE ");

         if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./)) {      // If Internet Explorer, return version number
            return true;
         }

         return false;
      };

      return this;
   };
}(jQuery));

/**
 * metademandTicketLink
 *
 * @param  options
 */
(function ($) {
   $.fn.metademandTicketLink = function (options) {

      var object = this;
      init();

      /**
       * Start the plugin
       */
      function init() {
         object.params = new Array();
         object.params['lang'] = '';
         object.params['root_doc'] = '';

         if (options != undefined) {
            $.each(options, function (index, val) {
               if (val != undefined && val != null) {
                  object.params[index] = val;
               }
            });
         }
      }

      /**
       * metademand_ticketlink : Inject ticket link button on ticket form
       */
      this.metademand_ticketlink = function () {
         // On UPDATE/ADD side
         $(document).ready(function () {
            // only in ticket form
            if (location.pathname.indexOf('front/ticket.form.php') > 0) {
               var tickets_id = object.urlParam(window.location.href, 'id');

               // Inject Ticket link
               var ticketlink_bloc = $("table[id='mainformtable4'] tr:nth-child(3) th:first-child");

               if (ticketlink_bloc.length != 0) {

                  var response = "<a id=\"metademand_add_ticketlink\" href=\"javascript:void(0)\" " +
                     "title=\"" + object.params['lang']['create_link'] + "\" " +
                     "name=\"metademand_add_ticketlink\">" + object.params['lang']['create_link'] + "</a>\n\
                                                    <script type='text/javascript'>\n\
                                                       document.getElementById('metademand_add_ticketlink').onclick=function(){\n\
                                                           object.metademand_createLinkTicket('" + tickets_id + "');\n\
                                                       }\n\
                                                    </script>";

                  $(ticketlink_bloc).append(response);


                  var scripts, scriptsFinder = /<script[^>]*>([\s\S]+?)<\/script>/gi;
                  while (scripts = scriptsFinder.exec(response)) {
                     eval(scripts[1]);
                  }
               }

            }
         });
      };

      /**
       * metademand_createLinkTicket : redirect to ticket add form with predefined values
       *
       * @param  tickets_id
       */
      this.metademand_createLinkTicket = function (tickets_id) {
         $.ajax({
            url: object.params['root_doc'] + '/plugins/metademands/ajax/ticket.php',
            type: "POST",
            dataType: "json",
            data: {
               'tickets_id': tickets_id,
               'action': 'setTicketLinkFields'
            },
            success: function (response, opts) {
               window.location.href = object.params['root_doc'] + "/front/ticket.form.php";
            }
         });
      };

      /**
       * Get url parameter
       *
       * @param string url
       * @param string name
       */
      this.urlParam = function (url, name) {
         var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(url);
         if (results == null || results == undefined) {
            return 0;
         }

         return results[1];
      };

      return this;
   };
}(jQuery));
