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

$(window).scroll(function () {
    if ($(window).scrollTop() > 300) {
        $('#backtotop').addClass('show');
    } else {
        $('#backtotop').removeClass('show');
    }
});

btn.on('click', function (e) {
    e.preventDefault();
    $('html, body').animate({scrollTop: 0}, '300');
});

function plugin_metademands_wizard_validateForm(metademandparams) {

    // This function deals with validation of the form fields
    var x, y = 0, w = 0, z = 0, i, valid = true, ko = 0, kop = 0;

    if (metademandparams.use_as_step == 1) {
        var x = document.getElementsByClassName('tab-step');
        // var x = {};
        //
        // for (var i = 0; i < tabs.length; i++) {
        //     x[i + 1] = tabs[i];
        // }
    } else {
        var x = document.getElementsByClassName('tab-nostep');
    }

    if (typeof x[metademandparams.currentTab] !== 'undefined') {
        y = x[metademandparams.currentTab].getElementsByTagName('input');
        z = x[metademandparams.currentTab].getElementsByTagName('select');
        w = x[metademandparams.currentTab].getElementsByTagName('textarea');
    }

    var mandatory = [];

    var mandatory_regex = [];

    //check mandatory signature
    let keys = Object.keys(sessionStorage);
    for (let key of keys) {
        if (key == 'mandatory_sign') {
            mandatory.push(sessionStorage.getItem(key));
            ko++;
        }
    }

    // A loop that checks every input field in the current tab:
    for (i = 0; i < y.length; i++) {

        // If a field is empty...
        fieldid = y[i].id;
        fieldname = y[i].name;
        fieldtype = y[i].type;

        if ((fieldtype == 'email'
                || fieldtype == 'tel'
                || fieldtype == 'url')
            && document.getElementById(fieldid) != null
            && !document.getElementById(fieldid).checkValidity()) {
            document.getElementById(fieldid).reportValidity();
            return false;
        }

        fieldmandatory = y[i].required;
        if (fieldname != '_uploader_filename[]'
            && fieldname != '_uploader_content[]'
            && fieldtype != 'file'
            && fieldtype != 'informations'
            //                                    && fieldtype != 'hidden'
            && fieldmandatory == true) {

            var res = $('[name=\"' + fieldname + '\"]').closest('[bloc-id]').css('display');

            if (res != 'none') {
                //ignore for hidden inputs below file inputs
                if (!y[i].parentElement.querySelector('input[type=\"file\"]')) {
                    if (y[i].value == '') {
                        $('[name=\"' + fieldname + '\"]').addClass('invalid');
                        $('[name=\"' + fieldname + '\"]').attr('required', 'required');
                        //                              $('[for=\"' + fieldname + '\"]').css('color', 'red');
                        //hack for date
                        $('[name=\"' + fieldname + '\"]').next('input').addClass('invalid');
                        $('[name=\"' + fieldname + '\"]').next('input').attr('required', 'required');
                        var newfieldname = fieldname.match(/\[(.*?)\]/);
                        if (newfieldname) {
                            mandatory.push(newfieldname[1]);
                        }
                        ko++;
                    } else {
                        $('[name=\"' + fieldname + '\"]').removeClass('invalid');
                        $('[name=\"' + fieldname + '\"]').removeAttr('required');
                        if (y[i].type === 'text') {
                            if (y[i].pattern) {
                                let regex = new RegExp(y[i].pattern);
                                if (!regex.test(y[i].value)) {
                                    $('[name=\"' + fieldname + '\"]').addClass('invalid');
                                    $('[name=\"' + fieldname + '\"]').attr('required', 'required');
                                    var newfieldname = fieldname.match(/\[(.*?)\]/);
                                    if (newfieldname) {
                                        mandatory_regex.push(newfieldname[1]);
                                    }
                                    kop++;
                                }
                            }
                        }
                        //hack for date
                        $('[name=\"' + fieldname + '\"]').next('input').removeClass('invalid');
                        $('[name=\"' + fieldname + '\"]').next('input').removeAttr('required');

                        //                              $('[for=\"' + fieldname + '\"]').css('color', 'unset');
                    }
                }
            } else {
                $('[name=\"' + fieldname + '\"]').removeClass('invalid');
                $('[name=\"' + fieldname + '\"]').removeAttr('required');
//                                hack for date
                $('[name=\"' + fieldname + '\"]').next('input').removeClass('invalid');
                $('[name=\"' + fieldname + '\"]').next('input').removeAttr('required');
            }
        }
        if (y[i].type == 'file'
            && fieldname == '_uploader_filename[]'
            && fieldname.indexOf('_uploader_field') == -1
            && y[i].required) {
            var inputPieceJointe = document.getElementById(fieldid);
            let fileIndicator = inputPieceJointe.parentElement.getElementsByClassName('fileupload_info')[0];

            if (fileIndicator.getElementsByTagName('p').length > 0) {
                $('#' + y[i].id).removeClass('invalid');
                $('#' + y[i].id).removeAttr('required');
//                         $('[for=\"' + fieldname + '\"]').css('color', 'unset');
            } else {
                $('#' + y[i].id).addClass('invalid');
                $('#' + y[i].id).attr('required', 'required');
//                         $('[for=\"' + fieldname + '\"]').css('color', 'red');
                var newfieldname = fileIndicator.id.match(/\d+$/);
                if (newfieldname) {
                    mandatory.push(newfieldname[0]);
                }
                ko++;
            }
        }


        if (y[i].type == 'radio'
            && fieldmandatory == true) {

            var boutonsRadio = document.querySelectorAll('input[name=\"' + fieldname + '\"]');
            var check = false;
            for (var b = 0; b < boutonsRadio.length; b++) {
                if (boutonsRadio[b].checked) {
                    check = true;
                    break;
                }
            }
            // Vérifier le résultat
            if (check) {
                $('[name=\"' + fieldname + '\"]').removeClass('invalid');
                $('[name=\"' + fieldname + '\"]').removeAttr('required');
//                              $('[for=\"' + fieldname + '\"]').css('color', 'unset');
            } else {
                $('[name=\"' + fieldname + '\"]').addClass('invalid');
                $('[name=\"' + fieldname + '\"]').attr('required', 'required');
//                              $('[for=\"' + fieldname + '\"]').css('color', 'red');
                var newfieldname = fieldid.match(/\[(.*?)\]/);
                if (newfieldname) {
                    mandatory.push(newfieldname[1]);
                }
                ko++;
            }

        }
        if (y[i].type == 'checkbox'
            && fieldmandatory == true) {
            isswitch = y[i].getAttribute('isswitch');
            if (isswitch == null) {
                var newfieldname = fieldname.match(/^(.*?)\[\w+\]/)[0];
                var casesACocher = document.querySelectorAll('input[name*=\"' + newfieldname + '\"]');

                // Parcourir les cases à cocher pour vérifier s'il y en a au moins une de cochée
                var check = false;
                for (var c = 0; c < casesACocher.length; c++) {
                    if (casesACocher[c].checked) {
                        check = true;
                        break;
                    }
                }
                if (check) {
                    $('[name*=\"' + newfieldname + '\"]').removeClass('invalid');
                    $('[name*=\"' + newfieldname + '\"]').removeAttr('required');
//                              $('[for*=\"' + newfieldname + '\"]').css('color', 'unset');
                } else {
                    $('[name*=\"' + newfieldname + '\"]').addClass('invalid');
                    $('[name*=\"' + newfieldname + '\"]').attr('required', 'required');
//                              $('[for*=\"' + newfieldname + '\"]').css('color', 'red');
                    var mandfieldname = fieldid.match(/\[(.*?)\]/);
                    if (mandfieldname) {
                        mandatory.push(mandfieldname[1]);
                    }
                    ko++;
                }
            }
        }

        if (y[i].type == 'range'
            && fieldmandatory == true) {
            minimal_mandatory = y[i].getAttribute('minimal_mandatory');
            var fieldname = y[i].name;
            var res = $('[name=\"' + fieldname + '\"]').closest('[bloc-id]').css('display');
            if (res != 'none' && parseInt(y[i].value) < parseInt(minimal_mandatory)) {
                $('[name=\"' + fieldname + '\"]').addClass('invalid');
                $('[name=\"' + fieldname + '\"]').attr('required', 'required');
                var newfieldname = fieldname.match(/\[(.*?)\]/);
                if (newfieldname) {
                    mandatory.push(newfieldname[1]);
                }
                ko++;
            } else {
                $('[name=\"' + fieldname + '\"]').removeClass('invalid');
                $('[name=\"' + fieldname + '\"]').removeAttr('required');
            }

        }
    }


    //for textarea
    if (w.length > 0) {
        for (var y = 0; y < w.length; y++) {
            fieldmandatory = w[y].required;
            var fieldname = w[y].name;
            var fieldid = w[y].id;

            var textarea = w[y];
            var res = $('[name=\"' + fieldname + '\"]').closest('[bloc-id]').css('display');
            //hack for tinymce

            if (document.querySelector('div[id-field=\"' + fieldid + '\"] > .tox-tinymce') !== null) {
                if (document.querySelector('div[id-field=\"' + fieldid + '\"] > .tox-tinymce').classList.contains('required')) {
                    fieldmandatory = true;
                }
            }
            const richtextarea = document.querySelector('textarea[id=\"' + fieldid + '\"]');
            const nextDiv = richtextarea.nextElementSibling;

            if (nextDiv && nextDiv.tagName.toLowerCase() === 'div') {
                if (nextDiv.classList.contains('required')) {
                    fieldmandatory = true;
                }
            }
            if (res != 'none'
                && fieldmandatory == true) {
                if (typeof tinymce !== 'undefined'
                    && tinymce.get(textarea.id)) {
                    var contenu = tinymce.get(textarea.id).getContent();
                    // Vérifier si le contenu est vide
                    if (contenu.trim().length) {
                        $('[name=\"' + fieldname + '\"]').removeClass('invalid');
                    } else {
                        $('[name=\"' + fieldname + '\"]').addClass('invalid');
                        $('[name=\"' + fieldname + '\"]').attr('required', 'required');
                        $('[name=\"' + fieldname + '\"]').next().css('border', 'solid 1px red');
                        var newfieldname = fieldname.match(/\[(.*?)\]/);

                        if (newfieldname) {
                            mandatory.push(newfieldname[1]);
                        }
                        ko++;
                    }
                } else {
                    var contenu = textarea.value.trim();
                    // Vérifier si le contenu est vide
                    if (contenu.length) {
                        $('[name=\"' + fieldname + '\"]').removeClass('invalid');
                    } else {
                        $('[name=\"' + fieldname + '\"]').addClass('invalid');
                        $('[name=\"' + fieldname + '\"]').attr('required', 'required');
                        var newfieldname = fieldid.match(/\[(.*?)\]/);
                        if (newfieldname) {
                            mandatory.push(newfieldname[1]);
                        }
                        ko++;
                    }
                }
            }
        }
    }
    //for select
    if (z.length > 0) {
        for (i = 0; i < z.length; i++) {
            fieldmandatory = z[i].required;
            // If a field is empty...
            isnumber = z[i].getAttribute('isnumber');
            ismultiplenumber = z[i].getAttribute('ismultiplenumber');
            minimal_mandatory = z[i].getAttribute('minimal_mandatory');
            var fieldname = z[i].name;
            var idfield = fieldname.replace(/[\[\]]/g, '')
            var visible = $('[id-field=\"' + idfield + '\"]').css('display');

            if (z[i].value == 0 && isnumber == null
                && ismultiplenumber == null
                && fieldmandatory == true
                && visible != 'none') {
                // add an 'invalid' class to the field:
                var fieldname = z[i].name;
                var res = $('[name=\"' + fieldname + '\"]').closest('[bloc-id]').css('display');
                if (res != 'none') {
                    $('[name=\"' + fieldname + '\"]').addClass('invalid');
                    $('[name=\"' + fieldname + '\"]').attr('required', 'required');
//                                 $('[for=\"' + fieldname + '\"]').css('color', 'red');

                    var newfieldname = fieldname.match(/\[(.*?)\]/);
                    if (newfieldname) {
                        mandatory.push(newfieldname[1]);
                    }
                    ko++;
                } else {
                    $('[name=\"' + fieldname + '\"]').removeClass('invalid');
                    $('[name=\"' + fieldname + '\"]').removeAttr('required');
//                                 $('[for=\"' + fieldname + '\"]').css('color', 'unset');
                }

            } else if (isnumber == 'isnumber'
                && ismultiplenumber == null
                && fieldmandatory == true) {
                // add an 'invalid' class to the field:
                var fieldname = z[i].name;
                var res = $('[name=\"' + fieldname + '\"]').closest('[bloc-id]').css('display');
                if (res != 'none' && parseInt(z[i].value) < parseInt(minimal_mandatory)) {
                    $('[name=\"' + fieldname + '\"]').addClass('invalid');
                    $('[name=\"' + fieldname + '\"]').attr('required', 'required');
                    var newfieldname = fieldname.match(/\[(.*?)\]/);
                    if (newfieldname) {
                        mandatory.push(newfieldname[1]);
                    }
                    ko++;
                } else {
                    $('[name=\"' + fieldname + '\"]').removeClass('invalid');
                    $('[name=\"' + fieldname + '\"]').removeAttr('required');
                }

            } else if (z[i].value == 0
                && ismultiplenumber == 'ismultiplenumber'
                && fieldmandatory == true) {
                // add an 'invalid' class to the field:
                var fieldname = z[i].name;
                var newfieldname = fieldname.match(/^(.*?)\[\w+\]/)[0];

                var numbers = document.querySelectorAll('[name*=\"' + newfieldname + '\"]');
                var check = false;
                for (var n = 0; n < numbers.length; n++) {
                    var myval = $('[name*=\"' + numbers[n].name + '\"]').children('option:selected').val();
                    if (myval > 0) {
                        check = true;
                        break;
                    }
                }

                if (check) {
                    $('[name*=\"' + newfieldname + '\"]').removeClass('invalid');
                    $('[name*=\"' + newfieldname + '\"]').removeAttr('required');
                    //                              $('[for*=\"' + fieldname + '\"]').css('color', 'unset');
                } else {
                    $('[name*=\"' + newfieldname + '\"]').addClass('invalid');
                    $('[name*=\"' + newfieldname + '\"]').attr('required', 'required');
                    //                              $('[for*=\"' + fieldname + '\"]').css('color', 'red');
                    var mandfieldname = fieldname.match(/\[(.*?)\]/);
                    if (mandfieldname) {
                        mandatory.push(mandfieldname[1]);
                    }
                    ko++;
                }

            } else {
                z[i].classList.remove('invalid');
            }
        }
    }

    if (ko > 0) {

        valid = false;

        const fields_mandatory = mandatory.filter(element => element !== '' && element !== null && element !== undefined);
        const fields_mandatory_unique = fields_mandatory.filter((element, index) => fields_mandatory.indexOf(element) === index);
        fields_mandatory_unique.sort((a, b) => a - b);
        //json_all_meta_fields
        var alert_mandatory_fields = [];
        $.each(fields_mandatory_unique, function (k, v) {
            $.each(metademandparams.json_all_meta_fields, function (key, value) {
                if (v == key) {
                    alert_mandatory_fields.push(value);
                }
            });
        });
        alert_mandatory_fields_list = alert_mandatory_fields.join('<br> ');
        alert_msg = metademandparams.msg + ' : <br><br>' + alert_mandatory_fields_list;
        alert(alert_msg);


    }
    return valid;
}

function plugin_metademands_wizard_showTab(metademandparams, metademandconditionsparams) {
    // This function will display the specified tab of the form...

    if (metademandconditionsparams.use_condition == true) {
        if (metademandconditionsparams.show_rule == 2) {
            if (document.getElementById('nextBtn').innerHTML == metademandconditionsparams.submittitle) {
                document.getElementById('nextBtn').style.display = 'none';
            }
        }

        $('#prevBtn').on('click', function () {
            if (document.getElementById('nextBtn').innerHTML == metademandconditionsparams.nexttitle) {
                document.getElementById('nextBtn').style.display = 'inline';
            }
        });
        plugin_metademands_wizard_checkConditions(metademandconditionsparams);

    }

    if (metademandparams.use_as_step == 1) {
        var x = document.getElementsByClassName('tab-step');
        // var x = {};
        //
        // for (var i = 0; i < tabs.length; i++) {
        //     x[i + 1] = tabs[i];
        // }
    } else {
        var x = document.getElementsByClassName('tab-nostep');
    }

    if (metademandparams.block_id > 0) {
        bloc = x[metademandparams.currentTab].firstChild.getAttribute('bloc-id');
        id_bloc = parseInt(bloc.replace('bloc', ''));
        while (metademandparams.block_id != id_bloc) {
            metademandparams.currentTab = metademandparams.currentTab + 1;
            bloc = x[metademandparams.currentTab].firstChild.getAttribute('bloc-id');
            id_bloc = parseInt(bloc.replace('bloc', ''));
        }
        firstnumTab = metademandparams.currentTab;
    }

    //First loading of first tab
    if (metademandparams.currentTab == 0) {
        document.getElementById('prevBtn').style.display = 'none';
    } else {
        document.getElementById('prevBtn').style.display = 'inline';

    }

    x[metademandparams.currentTab].style.display = 'block';
    //... and fix the Previous/Next buttons:

    if (typeof firstnumTab !== 'undefined') {
        if (metademandparams.currentTab == firstnumTab) {
            document.getElementById('prevBtn').style.display = 'none';
        } else {
            document.getElementById('prevBtn').style.display = 'inline';
        }
    }
    if (metademandparams.currentTab == 0) {
        document.getElementById('prevBtn').style.display = 'none';
    }

    document.getElementById('nextBtn').innerHTML = metademandparams.nexttitle;
    if (metademandparams.currentTab == (x.length - 1)) {
        document.getElementById('nextBtn').innerHTML = metademandparams.submittitle;
    }

    //... and run a function that will display the correct step indicator:
    if (metademandparams.use_as_step == 1) {
        plugin_metademands_wizard_displayStepButton(metademandparams);
        plugin_metademands_wizard_displayStepMsg(metademandparams);
    }
}

function plugin_metademands_wizard_displayStepButton(metademandparams) {

    //for next user button change
    if (typeof metademandparams !== 'undefined') {

        var x = document.getElementsByClassName('tab-step');
        // var x = {};
        //
        // for (var i = 0; i < tabs.length; i++) {
        //     x[i + 1] = tabs[i];
        // }

        let create = false;
        const asArray = Array.from(x);
        const displayed = asArray.find(e => e.style.display == 'block');

        let nextTab = asArray.indexOf(displayed) + 1;

        while (nextTab < x.length && x[nextTab].firstChild.style.display == 'none') {
            nextTab = nextTab + 1;
        }

        if (x[nextTab] != undefined) {
            let bloc = x[nextTab].firstChild.getAttribute('bloc-id');

            let id_bloc = parseInt(bloc.replace('bloc', ''));

            if (typeof metademandparams !== 'undefined') {
                if (!metademandparams.listStepBlock.includes(id_bloc)) {
                    create = true;
                }
            }
        }

        if (nextTab >= x.length) {
            document.getElementById('nextBtn').innerHTML = metademandparams.submittitle;
        } else {
            if (create) {
                document.getElementById('nextBtn').innerHTML = metademandparams.submitsteptitle;
            } else {
                document.getElementById('nextBtn').innerHTML = metademandparams.nextsteptitle;
            }
        }
    }
}

function plugin_metademands_wizard_displayStepMsg(metademandparams) {
    // This function removes the 'active' class of all steps...
    var i, x = document.getElementsByClassName('step_wizard');
    for (i = 0; i < x.length; i++) {
        x[i].className = x[i].className.replace(' active', '');
    }
    //... and adds the 'active' class on the current step:

    if (x[metademandparams.currentTab] != undefined && x[metademandparams.currentTab].className) {
        x[metademandparams.currentTab].className += ' active';
    }

    var x = document.getElementsByClassName('tab-step');
    // var x = {};
    //
    // for (var i = 0; i < tabs.length; i++) {
    //     x[i + 1] = tabs[i];
    // }

    bloc = x[metademandparams.currentTab].firstChild.getAttribute('bloc-id');
    id_bloc = parseInt(bloc.replace('bloc', ''));

    $(document).ready(function () {
        $.ajax({
            url: metademandparams.root_doc + '/ajax/getNextMessage.php',
            type: 'POST',
            data:
                {
                    '_glpi_csrf_token': metademandparams.token,
                    plugin_metademands_metademands_id: metademandparams.id,
                    block_id: id_bloc
                },
            success: function (response) {
                if (response.length == 0) {
                    document.getElementById('nextMsg').style.display = 'none';
                } else {

                    document.getElementById('nextMsg').style.display = 'block';
                    document.getElementById('nextMsg').innerHTML = response;
                }
            },
            error: function (xhr, status, error) {
                // console.log(xhr);
                // console.log(status);
                // console.log(error);
            }
        });
    });
}

function plugin_metademands_wizard_checkConditions(metademandconditionsparams) {

    var formDatas;
    formDatas = $('#wizard_form').serializeArray();

    if (typeof tinyMCE !== 'undefined' && metademandconditionsparams.use_richtext) {
        for (let i = 0; i < metademandconditionsparams.richtext_ids.length; i++) {
            let field = 'field' + metademandconditionsparams.richtext_ids[i];

            if (typeof tinyMCE.get(field) !== 'undefined') {
                let content = tinyMCE.get(field).getContent();
                let name = 'field[' + metademandconditionsparams.richtext_ids[i] + ']';
                formDatas.push({
                    name: name,
                    value: content
                });
            }
        }
    }

    $.ajax({
        url: metademandconditionsparams.root_doc + '/ajax/condition.php',
        type: 'POST',
        datatype: 'JSON',
        data: formDatas,
        success: function (response) {
            if (response) {
                eval('valid_condition=' + response);
                if (valid_condition) {
                    if (metademandconditionsparams.show_button == 1) {
                        if (document.getElementById('nextBtn').innerHTML == metademandconditionsparams.submittitle) {
                            document.getElementById('nextBtn').style.display = 'none';
                        }
                        if (document.getElementById('nextBtn').innerHTML == metademandconditionsparams.nextsteptitle) {
                            document.getElementById('nextBtn').style.display = 'none';
                        }
                    } else {
                        if (document.getElementById('nextBtn').innerHTML == metademandconditionsparams.submittitle) {
                            document.getElementById('nextBtn').style.display = 'inline';
                        }
                        if (document.getElementById('nextBtn').innerHTML == metademandconditionsparams.nextsteptitle) {
                            document.getElementById('nextBtn').style.display = 'inline';
                        }
                    }
                } else {
                    if (metademandconditionsparams.show_button == 1) {
                        if (document.getElementById('nextBtn').innerHTML == metademandconditionsparams.submittitle) {
                            document.getElementById('nextBtn').style.display = 'inline';
                        }
                        if (document.getElementById('nextBtn').innerHTML == metademandconditionsparams.nextsteptitle) {
                            document.getElementById('nextBtn').style.display = 'inline';
                        }
                    } else {
                        if (document.getElementById('nextBtn').innerHTML == metademandconditionsparams.submittitle) {
                            document.getElementById('nextBtn').style.display = 'none';
                        }
                        if (document.getElementById('nextBtn').innerHTML == metademandconditionsparams.nextsteptitle) {
                            document.getElementById('nextBtn').style.display = 'none';
                        }
                    }
                }
            }
        },
        error: function (xhr, status, error) {
            console.log(xhr);
            console.log(status);
            console.log(error);
        }
    });
}


function plugin_metademands_wizard_nextBtn(n, metademandparams, metademandconditionsparams) {

    var firstnumTab = 0;
    // This function will figure out which tab to display
    if (metademandparams.use_as_step == 1) {
        var x = document.getElementsByClassName('tab-step');
        // var x = {};
        //
        // for (var i = 0; i < tabs.length; i++) {
        //     x[i + 1] = tabs[i];
        // }
    } else {
        var x = document.getElementsByClassName('tab-nostep');
    }
    if (metademandparams.block_id > 0) {
        bloc = x[metademandparams.currentTab].firstChild.getAttribute('bloc-id');
        id_bloc = parseInt(bloc.replace('bloc', ''));
        while (metademandparams.block_id != id_bloc) {
            metademandparams.currentTab = metademandparams.currentTab + 1;
            // if (typeof x[metademandparams.currentTab] !== 'undefined') {
            bloc = x[metademandparams.currentTab].firstChild.getAttribute('bloc-id');
            id_bloc = parseInt(bloc.replace('bloc', ''));
            // }

        }
        firstnumTab = metademandparams.currentTab;
    }
    // Exit the function if any field in the current tab is invalid:
    if (n == 1 && !plugin_metademands_wizard_validateForm(metademandparams)) return false;

    // Increase or decrease the current tab by 1:
    nextTab = metademandparams.currentTab + n;
    // Hide the current tab:
    if (x[metademandparams.currentTab] !== undefined) {
        x[metademandparams.currentTab].style.display = 'none';
    }

    // Increase or decrease the current tab by 1:

    metademandparams.currentTab = metademandparams.currentTab + n;

    create = false;
    createNow = false;

    if (metademandparams.use_as_step == 1) {

        var finded = false;

        while (finded == false) {

            if (true) {

                if (x[metademandparams.currentTab] == undefined || x[metademandparams.currentTab].firstChild == undefined) {
                    createNow = true;
                    finded = true;
                } else {
                    if (x[metademandparams.currentTab].firstChild.style.display != 'none') {
                        finded = true;
                        nextTab = metademandparams.currentTab + n;
                        while (nextTab >= firstnumTab && nextTab < x.length && x[nextTab].firstChild.style.display == 'none') {
                            nextTab = nextTab + n;
                        }
                        if (nextTab >= x.length) {
                            create = true;
                        }
                    } else {
                        metademandparams.currentTab = metademandparams.currentTab + n;
                    }
                }
            } else {
                finded = true;
            }
        }
    }

    // if you have reached the end of the form...
    if (metademandparams.currentTab >= x.length || createNow) {

        document.getElementById('nextBtn').style.display = 'none';
        // ... the form gets submitted:
        if (typeof tinyMCE !== 'undefined') {
            tinyMCE.triggerSave();
        }
        jQuery('.resume_builder_input').trigger('change');
        $('select[id$=\"_to\"] option').each(function () {
            $(this).prop('selected', true);
        });
        $('#ajax_loader').show();
        arrayDatas = $('#wizard_form').serializeArray();
        arrayDatas.push({name: 'save_form', value: true});
        arrayDatas.push({name: 'step', value: 2});
        arrayDatas.push({name: 'form_name', value: metademandparams.nameform});

        if (metademandparams.seesummary == 1) {
            $.ajax({
                url: metademandparams.root_doc + '/ajax/createmetademands.php?metademands_id=' + metademandparams.id + '&step=2' + metademandparams.paramUrl,
                type: 'POST',
                datatype: 'html',
                data: $('#wizard_form').serializeArray(),
                success: function (response) {
                    $('#ajax_loader').hide();
                    $('.md-wizard').append(response);
                },
                error: function (xhr, status, error) {
                    // console.log(xhr);
                    // console.log(status);
                    // console.log(error);
                }
            });
        } else {
            $.ajax({
                url: metademandparams.root_doc + '/ajax/addform.php',
                type: 'POST',
                datatype: 'html',
                data: arrayDatas,
                success: function (response) {
                    if (response != 1) {
                        $.ajax({
                            url: metademandparams.root_doc + '/ajax/createmetademands.php?' + metademandparams.paramUrl,
                            type: 'POST',
                            data: arrayDatas,
                            success: function (response) {
                                if (response != 1) {
                                    window.location.href = metademandparams.root_doc + '/front/wizard.form.php?metademands_id=' + metademandparams.id + '&step=create_metademands';
                                } else {
                                    location.reload();
                                }
                            },
                            error: function (xhr, status, error) {
                                // console.log(xhr);
                                // console.log(status);
                                // console.log(error);
                            }
                        });
                    } else {
                        location.reload();
                    }
                },
                error: function (xhr, status, error) {
                    // console.log(xhr);
                    // console.log(status);
                    // console.log(error);
                }
            });
        }

        return false;
    }

    if (metademandparams.use_as_step == 1
        && typeof metademandparams !== 'undefined') {

        if (x[metademandparams.currentTab] !== undefined) {
            bloc = x[metademandparams.currentTab].firstChild.getAttribute('bloc-id');
            id_bloc = parseInt(bloc.replace('bloc', ''));

            document.querySelectorAll('a[id^=\"ablock\"]').forEach(a => a.classList.remove('active'));
            document.getElementById('ablock' + id_bloc)?.classList.add('active');
            if (document.querySelector('.scrollable-tabs')) {
                document.querySelector('.scrollable-tabs').scrollBy({left: 150, behavior: 'smooth'});
            }

            if (!metademandparams.listStepBlock.includes(id_bloc)) {
                if (typeof tinyMCE !== 'undefined') {
                    tinyMCE.triggerSave();
                }
                jQuery('.resume_builder_input').trigger('change');
                $('select[id$=\"_to\"] option').each(function () {
                    $(this).prop('selected', true);
                });
                arrayDatas = $('#wizard_form').serializeArray();
                arrayDatas.push({name: 'block_id', value: id_bloc});
                arrayDatas.push({name: 'action', value: 'nextUser'});
                arrayDatas.push({name: 'form_name', value: metademandparams.nameform});
                arrayDatas.push({name: 'update_stepform', value: metademandparams.updatestepform});
                if (metademandparams.havenextuser == true) {
                    plugin_metademands_wizard_showStep(metademandparams.root_doc, arrayDatas);
                } else {
                    plugin_metademands_wizard_nextUser(metademandparams.root_doc, arrayDatas);
                }

            } else {
                plugin_metademands_wizard_showTab(metademandparams, metademandconditionsparams);
            }
        } else {
            location.href = metademandparams.root_doc + '/front/wizard.form.php';
        }
    }
}

function plugin_metademands_wizard_prevBtn(n, metademandparams, metademandconditionsparams) {

    var firstnumTab = 0;
    // This function will figure out which tab to display
    if (metademandparams.use_as_step == 1) {
        var x = document.getElementsByClassName('tab-step');
        // var x = {};
        //
        // for (var i = 0; i < tabs.length; i++) {
        //     x[i + 1] = tabs[i];
        // }
    } else {
        var x = document.getElementsByClassName('tab-nostep');
    }
    if (metademandparams.block_id > 0) {
        bloc = x[metademandparams.currentTab].firstChild.getAttribute('bloc-id');
        id_bloc = parseInt(bloc.replace('bloc', ''));
        while (metademandparams.block_id != id_bloc) {
            metademandparams.currentTab = metademandparams.currentTab + 1;
            // if (typeof x[metademandparams.currentTab] !== 'undefined') {
            bloc = x[metademandparams.currentTab].firstChild.getAttribute('bloc-id');
            id_bloc = parseInt(bloc.replace('bloc', ''));
            // }

        }
        firstnumTab = metademandparams.currentTab;
    }

    // Increase or decrease the current tab by 1:
    nextTab = metademandparams.currentTab + n;
    // Hide the current tab:
    if (x[metademandparams.currentTab] !== undefined) {
        x[metademandparams.currentTab].style.display = 'none';
    }

    // Increase or decrease the current tab by 1:
    if (metademandparams.currentTab >= 1) {
        metademandparams.currentTab = metademandparams.currentTab + n;
    }

    if (metademandparams.use_as_step == 1) {

        if (metademandparams.currentTab >= 1) {
            var finded = false;
            while (finded == false) {

                if (true) {

                    if (x[metademandparams.currentTab] == undefined || x[metademandparams.currentTab].firstChild == undefined) {
                        finded = true;
                    } else {
                        if (x[metademandparams.currentTab].firstChild.style.display != 'none') {
                            finded = true;
                            nextTab = metademandparams.currentTab + n;
                            while (nextTab >= firstnumTab && nextTab < x.length && x[nextTab].firstChild.style.display == 'none') {
                                nextTab = nextTab + n;
                            }
                        } else {
                            metademandparams.currentTab = metademandparams.currentTab + n;
                        }
                    }
                } else {
                    finded = true;
                }
            }
        }


        if (x[metademandparams.currentTab] !== undefined) {
            bloc = x[metademandparams.currentTab].firstChild.getAttribute('bloc-id');
            id_bloc = parseInt(bloc.replace('bloc', ''));

            document.querySelectorAll('a[id^=\"ablock\"]').forEach(a => a.classList.remove('active'));
            document.getElementById('ablock' + id_bloc)?.classList.add('active');
            if (document.querySelector('.scrollable-tabs')) {
                document.querySelector('.scrollable-tabs').scrollBy({left: -150, behavior: 'smooth'});
            }

            plugin_metademands_wizard_showTab(metademandparams, metademandconditionsparams);
        } else {
            location.href = metademandparams.root_doc + '/front/wizard.form.php';
        }
    }
}

function plugin_metademands_wizard_nextUser(root_doc, arrayDatas) {
    $.ajax(
        {
            type: 'POST',
            url: root_doc + '/ajax/nextUser.php',
            data: arrayDatas,
            dataType: 'JSON',
            success: function (ret) {

                if (ret == 0) {
                    location.href = root_doc + '/front/wizard.form.php';
                } else {
                    window.location.reload();
                }
            },
            error: function (xhr, status, error) {
                // console.log(xhr);
                // console.log(status);
                // console.log(error);
            }
        }
    );
}

function plugin_metademands_wizard_showStep(root_doc, arrayDatas) {
    $.ajax(
        {
            type: 'POST',
            url: root_doc + '/ajax/showStep.php',
            data: arrayDatas,
            dataType: 'JSON',
            success: function (response) {
                try {

                    // For modern browsers except IE:
                    var event = new Event('show.bs.modal');

                } catch (err) {

                    // If IE 11 (or 10 or 9...?) do it this way:

                    // Create the event.
                    var event = document.createEvent('Event');

                }
                $('#modalgroupspan').html(response);
                $.globalEval(response.js);
                $('#modalgroup').modal('show');
                document.dispatchEvent(event);
            },
            error: function (xhr, status, error) {
                // console.log(xhr);
                // console.log(status);
                // console.log(error);
            }

        });

}

function updateActiveTab(rank) {
    document.querySelectorAll('a[id^=\"ablock\"]').forEach(a => a.classList.remove('active'));
    document.querySelectorAll('div[id^=\"block\"]').forEach(div => div.classList.remove('active'));

    document.getElementById('ablock' + rank)?.classList.add('active');
    $('div[id^=\"block\"]').hide();
    $('#block' + rank).show();

    document.getElementById('ablock' + rank)?.scrollIntoView({behavior: 'smooth', inline: 'center', block: 'nearest'});
}
