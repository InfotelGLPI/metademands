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

function plugin_metademands_wizard_validateForm(metademands) {

    // This function deals with validation of the form fields
    var x, y = 0, w = 0, z = 0, i, valid = true, ko = 0, kop = 0, radioexists = 0, lengthr = 0;

    if (metademands.use_as_step == 1) {
        var x = document.getElementsByClassName('tab-step');
    } else {
        var x = document.getElementsByClassName('tab-nostep');
    }

    if (typeof x[metademands.currentTab] !== 'undefined') {
        y = x[metademands.currentTab].getElementsByTagName('input');
        z = x[metademands.currentTab].getElementsByTagName('select');
        w = x[metademands.currentTab].getElementsByTagName('textarea');
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
//                                console.log(numbers[n].name);
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
        //all_meta_fields
        var alert_mandatory_fields = [];
        $.each(fields_mandatory_unique, function (k, v) {
            $.each(metademands.all_meta_fields, function (key, value) {
                if (v == key) {
                    alert_mandatory_fields.push(value);
                }
            });
        });
        alert_mandatory_fields_list = alert_mandatory_fields.join('<br> ');
        alert_msg = metademands.msg + ' : <br><br>' + alert_mandatory_fields_list;
        alert(alert_msg);


    }
    return valid;
}

function plugin_metademands_wizard_showTab(metademands, create) {
    // This function will display the specified tab of the form...
    //document.getElementById('nextMsg').style.display = 'none';
    if (metademands.use_as_step == 1) {
        var x = document.getElementsByClassName('tab-step');
    } else {
        var x = document.getElementsByClassName('tab-nostep');
    }

    x[metademands.currentTab].style.display = 'block';
    //... and fix the Previous/Next buttons:
    if (metademands.currentTab == metademands.firstnumTab) {
        document.getElementById('prevBtn').style.display = 'none';
    } else {
        document.getElementById('prevBtn').style.display = 'inline';
    }

    document.getElementById('nextBtn').innerHTML = metademands.nexttitle;
    if (metademands.currentTab == (x.length - 1) || create == true) {
        document.getElementById('nextBtn').innerHTML = metademands.submittitle;
    }

    //... and run a function that will display the correct step indicator:
    if (metademands.use_as_step == 1) {
        plugin_metademands_wizard_fixStepIndicator(metademands);
        fixButtonIndicator(metademands);
    }
}

/**
 *  set the content of the nextBtn element
 */
function fixButtonIndicator(metademands) {

    if (typeof metademands !== 'undefined') {
        use_as_step = 1;
        if (metademands.use_as_step) {
            x = document.getElementsByClassName('tab-step');
        } else {
            x = document.getElementsByClassName('tab-nostep');
        }

        let create = false;
        if (metademands.use_as_step == 1) {

            const asArray = Array.from(x);
            const displayed = asArray.find(e => e.style.display == 'block');

            let nextTab = asArray.indexOf(displayed) + 1;

            while (nextTab < x.length && x[nextTab].firstChild.style.display == 'none') {
                nextTab = nextTab + 1;
            }

            if (x[nextTab] != undefined) {
                let bloc = x[nextTab].firstChild.getAttribute('bloc-id');

                let id_bloc = parseInt(bloc.replace('bloc', ''));

                if (typeof metademands !== 'undefined') {
                    if (!metademands.listStepBlock.includes(id_bloc)) {
                        create = true;
                    }
                }
            }

            if (nextTab >= x.length) {
                document.getElementById('nextBtn').innerHTML = metademands.submittitle;
            } else {
                if (create) {
                    document.getElementById('nextBtn').innerHTML = metademands.submitsteptitle;
                } else {
                    document.getElementById('nextBtn').innerHTML = metademands.nextsteptitle;
                }
            }
        }
    }
}

function plugin_metademands_wizard_fixStepIndicator(metademands) {
    // This function removes the 'active' class of all steps...
    var i, x = document.getElementsByClassName('step_wizard');
    for (i = 0; i < x.length; i++) {
        x[i].className = x[i].className.replace(' active', '');
    }
    //... and adds the 'active' class on the current step:

    if (x[metademands.currentTab] != undefined && x[metademands.currentTab].className) {
        x[metademands.currentTab].className += ' active';
    }

    if (metademands.use_as_step == 1) {
        var tabx = document.getElementsByClassName('tab-step');
    } else {
        var tabx = document.getElementsByClassName('tab-nostep');
    }

    bloc = tabx[metademands.currentTab].firstChild.getAttribute('bloc-id');
    id_bloc = parseInt(bloc.replace('bloc', ''));
//                     console.log(id_bloc);
    $(document).ready(function () {
        $.ajax({
            url: metademands.root_doc + '/ajax/getNextMessage.php',
            type: 'POST',
            data:
                {
                    '_glpi_csrf_token': metademands.token,
                    plugin_metademands_metademands_id: metademands.id,
                    block_id: id_bloc
                },
            success: function (response) {
                if (response.length == 0) {
                    document.getElementById('nextMsg').style.display = 'none';
                } else {

                    document.getElementById('nextMsg').style.display = 'block';
                    document.getElementById('nextMsg').innerHTML = response;
                    sessionStorage.setItem('currentStep', id_bloc);
                }
            },
            error: function (xhr, status, error) {
                console.log(xhr);
                console.log(status);
                console.log(error);
            }
        });
    });
}

function plugin_metademands_wizard_checkConditions(metademands, show_button) {
    var formDatas;
    formDatas = $('#wizard_form').serializeArray();
    if (typeof tinymce !== 'undefined' && metademands.use_richtext) {
        for (let i = 0; i < metademands.richtext_ids.length; i++) {
            let field = 'field' + metademands.richtext_ids[i];
            let content = tinyMCE.get(field).getContent();
            let name = 'field[' + metademands.richtext_ids[i] + ']';
            formDatas.push({
                name: name,
                value: content
            });
        }
    }
    $.ajax({
        url: metademands.root_doc + '/ajax/condition.php',
        type: 'POST',
        datatype: 'JSON',
        data: formDatas,
        success: function (response) {

            eval('valid_condition=' + response);
            if (valid_condition) {
                if (show_button == 1) {
                    if (document.getElementById('nextBtn').innerHTML == metademands.submittitle) {
                        document.getElementById('nextBtn').style.display = 'none';
                    }
                    if (document.getElementById('nextBtn').innerHTML == metademands.nextsteptitle) {
                        document.getElementById('nextBtn').style.display = 'none';
                    }
                } else {
                    if (document.getElementById('nextBtn').innerHTML == metademands.submittitle) {
                        document.getElementById('nextBtn').style.display = 'inline';
                    }
                    if (document.getElementById('nextBtn').innerHTML == metademands.nextsteptitle) {
                        document.getElementById('nextBtn').style.display = 'inline';
                    }
                }
            } else {
                if (show_button == 1) {
                    if (document.getElementById('nextBtn').innerHTML == metademands.submittitle) {
                        document.getElementById('nextBtn').style.display = 'inline';
                    }
                    if (document.getElementById('nextBtn').innerHTML == metademands.nextsteptitle) {
                        document.getElementById('nextBtn').style.display = 'inline';
                    }
                } else {
                    if (document.getElementById('nextBtn').innerHTML == metademands.submittitle) {
                        document.getElementById('nextBtn').style.display = 'none';
                    }
                    if (document.getElementById('nextBtn').innerHTML == metademands.nextsteptitle) {
                        document.getElementById('nextBtn').style.display = 'none';
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

function plugin_metademands_wizard_findFirstTab(metademands) {
    if (metademands.use_as_step == 1) {
        var x = document.getElementsByClassName('tab-step');
    } else {
        var x = document.getElementsByClassName('tab-nostep');
    }

    if (metademands.block_id > 0) {
        bloc = x[currentTab].firstChild.getAttribute('bloc-id');
        id_bloc = parseInt(bloc.replace('bloc', ''));
        while (metademands.block_id != id_bloc) {
            currentTab = currentTab + 1;
            bloc = x[currentTab].firstChild.getAttribute('bloc-id');
            id_bloc = parseInt(bloc.replace('bloc', ''));
        }
        firstnumTab = currentTab;
    }
}

function plugin_metademands_wizard_nextPrev(n, metademands) {

    // This function will figure out which tab to display
    if (metademands.use_as_step == 1) {
        var x = document.getElementsByClassName('tab-step');
    } else {
        var x = document.getElementsByClassName('tab-nostep');
    }
    // Exit the function if any field in the current tab is invalid:
    if (n == 1 && !plugin_metademands_wizard_validateForm(metademands)) return false;

    // Increase or decrease the current tab by 1:
    nextTab = metademands.currentTab + n;
    // Hide the current tab:
    if (x[metademands.currentTab] !== undefined) {
        x[metademands.currentTab].style.display = 'none';
    }

    // Increase or decrease the current tab by 1:
    metademands.currentTab = metademands.currentTab + n;

    create = false;
    createNow = false;

    if (metademands.use_as_step == 1) {

        var finded = false;

        while (finded == false) {

            if (true) {
                if (x[metademands.currentTab] == undefined || x[metademands.currentTab].firstChild == undefined) {
                    createNow = true;
                    finded = true;
                } else {
                    if (x[metademands.currentTab].firstChild.style.display != 'none') {
                        finded = true;
                        nextTab = metademands.currentTab + n;
                        while (nextTab >= metademands.firstnumTab && nextTab < x.length && x[nextTab].firstChild.style.display == 'none') {
                            nextTab = nextTab + n;
                        }
                        if (nextTab >= x.length) {
                            create = true;
                        }
                    } else {
                        metademands.currentTab = metademands.currentTab + n;
                    }
                }
            } else {
                finded = true;
            }
        }
    }

    // if you have reached the end of the form...
    if (metademands.currentTab >= x.length || createNow) {

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
        arrayDatas.push({name: 'form_name', value: '$name'});

        if (metademands.seesummary == 1) {
            $.ajax({
                url: metademands.root_doc + '/ajax/createmetademands.php?metademands_id=' + metademands.id + '&step=2' + metademands.paramUrl,
                type: 'POST',
                datatype: 'html',
                data: $('#wizard_form').serializeArray(),
                success: function (response) {
                    $('#ajax_loader').hide();
                    $('.md-wizard').append(response);
                },
                error: function (xhr, status, error) {
                    console.log(xhr);
                    console.log(status);
                    console.log(error);
                }
            });
        } else {
            $.ajax({
                url: metademands.root_doc + '/ajax/addform.php',
                type: 'POST',
                datatype: 'html',
                data: arrayDatas,
                success: function (response) {
                    if (response != 1) {
                        $.ajax({
                            url: metademands.root_doc + '/ajax/createmetademands.php?' + metademands.paramUrl,
                            type: 'POST',
                            data: arrayDatas,
                            success: function (response) {
                                if (response != 1) {
                                    window.location.href = metademands.root_doc + '/front/wizard.form.php?metademands_id=' + metademands.id + '&step=create_metademands';
                                } else {
                                    location.reload();
                                }
                            },
                            error: function (xhr, status, error) {
                                console.log(xhr);
                                console.log(status);
                                console.log(error);
                            }
                        });
                    } else {
                        location.reload();
                    }
                },
                error: function (xhr, status, error) {
                    console.log(xhr);
                    console.log(status);
                    console.log(error);
                }
            });
        }

        return false;
    }
    if (typeof metademands !== 'undefined') {

        if (x[metademands.currentTab] !== undefined) {
            bloc = x[metademands.currentTab].firstChild.getAttribute('bloc-id');
            id_bloc = parseInt(bloc.replace('bloc', ''));

            document.querySelectorAll('a[id^=\"ablock\"]').forEach(a => a.classList.remove('active'));
            document.getElementById('ablock' + id_bloc)?.classList.add('active');
            if (metademands.currentTab == -1) {
                if (document.querySelector('.scrollable-tabs')) {
                    document.querySelector('.scrollable-tabs').scrollBy({left: -150, behavior: 'smooth'});
                }
            } else {
                if (document.querySelector('.scrollable-tabs')) {
                    document.querySelector('.scrollable-tabs').scrollBy({left: 150, behavior: 'smooth'});
                }
            }

            if (!metademands.listStepBlock.includes(id_bloc)) {
                if (typeof tinyMCE !== 'undefined') {
                    tinyMCE.triggerSave();
                }
                var updatestepform = '$updateStepform';
                jQuery('.resume_builder_input').trigger('change');
                $('select[id$=\"_to\"] option').each(function () {
                    $(this).prop('selected', true);
                });
                arrayDatas = $('#wizard_form').serializeArray();
                arrayDatas.push({name: 'block_id', value: id_bloc});
                arrayDatas.push({name: 'action', value: 'nextUser'});
                arrayDatas.push({name: 'form_name', value: '$name'});
                arrayDatas.push({name: 'update_stepform', value: updatestepform});
                if (metademands.modal == true) {
                    plugin_metademands_wizard_showStep(metademands.root_doc, arrayDatas);
                } else {
                    plugin_metademands_wizard_nextUser(metademands.root_doc, arrayDatas);
                }

            } else {
                plugin_metademands_wizard_showTab(metademands, false);
            }
        } else {
            location.href = metademands.root_doc + '/front/wizard.form.php';
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
                console.log(xhr);
                console.log(status);
                console.log(error);
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
//                                console.log(response);
//                                $('#modalgroupspan').html(response.html);
                $('#modalgroupspan').html(response);
                $.globalEval(response.js);
                $('#modalgroup').modal('show');
                document.dispatchEvent(event);
            },
            error: function (xhr, status, error) {
                console.log(xhr);
                console.log(status);
                console.log(error);
            }

        });

}
