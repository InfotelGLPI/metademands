
// metademandfreelinesparams.existLine = '$existLine';
// metademandfreelinesparams.rand = '$rand';
// metademandfreelinesparams.root = '$root';
// metademandfreelinesparams.encoded_fields = $encoded_fields;
// metademandfreelinesparams.mandatory_encoded_fields = $mandatory_encoded_fields;
// metademandfreelinesparams.types_encoded_fields = $types_encoded_fields;
// metademandfreelinesparams.dropdown_values_encoded_fields = $dropdown_values_encoded_fields;
// metademandfreelinesparams.orderfollowup_is_active = $orderfollowup_is_active;
// metademandfreelinesparams.size = $size;
// metademandfreelinesparams.empty_value = '$empty_value';
// metademandfreelinesparams.plugin_metademands_metademands_id = $plugin_metademands_metademands_id;
// metademandfreelinesparams.style = '$style';
// metademandfreelinesparams.lastid = $lastid;
// metademandfreelinesparams.text = $texttype;
// metademandfreelinesparams.select = $selecttype;
// metademandfreelinesparams.number = $numbertype;
// metademandfreelinesparams.readonly = $readonlytype;
// metademandfreelinesparams.date = $datetype;
// metademandfreelinesparams.time = $timetype;


function addLine(metademandfreelinesparams)
{

    window.metademandfreelinesparams = metademandfreelinesparams;

    var fields = metademandfreelinesparams.encoded_fields;
    var type_fields = metademandfreelinesparams.types_encoded_fields;
    var dropdown_values_fields = metademandfreelinesparams.dropdown_values_encoded_fields;
    var lastid = metademandfreelinesparams.lastid;
    const tabfields = [];

    if (!document.querySelector('#freetable_table' + metademandfreelinesparams.rand + '.add_item')) {

        if ($('#freetable_table'+ metademandfreelinesparams.rand + ' tr[id^=line_' + metademandfreelinesparams.rand + '_]:first').length > 0) {
            // console.log(lastid);
            if (lastid > 0 && lastid > i) {
                i = lastid;
            }
            i++;
            // console.log("existe deja");
            // console.log(i);

            tabtr = '<tr class=\"tab_bg_1\" id=\"line_' + metademandfreelinesparams.rand + '_' + i + '\">';

            $.each(fields, function (index, valuej) {

                if (type_fields[index] == metademandfreelinesparams.text) {
                    tabfields.push('<td><input id = \"' + index + '\" type=\"text\" name=\"' + index + '\" size=\"' + metademandfreelinesparams.size + '\" ></td>');
                } else if (type_fields[index] == metademandfreelinesparams.select) {
                    var select_open = '<td><select id = \"' + index + '\" name=\"' + index + '\">';
                    var select_options = '';
                    $.each(dropdown_values_fields, function (indexv, values) {
                        $.each(values, function (indexd, valued) {
                            if (index == indexv) {
                                select_options += '<option value=\"' + valued + '\">' + valued + '</option>';
                            }
                        });
                    });
                    var select_close = '</select></td>';
                    var select = [select_open, select_options, select_close].join(' ');
                    tabfields.push(select);
                } else if (type_fields[index] == metademandfreelinesparams.number) {
                    tabfields.push('<td><input add=0 id = \"' + index + '\" type=\"number\" min=\"0\" name=\"' + index + '\" style=\"width: 7em;\" ></td>');
                } else if (type_fields[index] == metademandfreelinesparams.readonly && metademandfreelinesparams.orderfollowupisactive) {
                    tabfields.push('<td></td>');
                } else if (type_fields[index] == metademandfreelinesparams.date) {
                    tabfields.push('<td><input add=0 id = \"' + index + '\" type=\"date\" name=\"' + index + '\" ></td>');
                } else if (type_fields[index] == metademandfreelinesparams.time) {
                    tabfields.push('<td><input add=0 id = \"' + index + '\" type=\"time\" name=\"' + index + '\" ></td>');
                }

            });

            var str = '<button class =\"btn btn-success add_item\" type = \"button\" name =\"add_item\" onclick=\"confirmUpdateLine(this, '+ i +', 1)\">';
            tabbutton = '<td style=\"text-align: right;\" colspan=\"2\">'
                + str
                + '<i class =\"ti ti-check\"></i></button></td>'
                + '<td style=\"text-align: center;\"><button onclick =\"removeLine(' + i + ')\" class =\"btn btn-danger\" type = \"button\" name =\"delete_item\">'
                + '<i class =\"ti ti-trash\"></i></button></td>'
                + '</tr>';

            var joined = [tabtr, tabfields, tabbutton].join(' ');

            $('#freetable_table'+ metademandfreelinesparams.rand + ' tr[id^=line_' + metademandfreelinesparams.rand + '_]:last').after(joined);
        } else {
            // console.log("n existe pas");
            // console.log(i);
            tabtr = '<tr class=\"tab_bg_1\" id=\"line_' + metademandfreelinesparams.rand + '_' + i + '\">';
            $.each(fields, function (index, valuej) {
                if (type_fields[index] == metademandfreelinesparams.text) {
                    tabfields.push('<td><input id = \"' + index + '\" type=\"text\" name=\"' + index + '\" size=\"' + metademandfreelinesparams.size + '\" ></td>');
                } else if (type_fields[index] == metademandfreelinesparams.select) {
                    var select_open = '<td><select id = \"' + index + '\" name=\"' + index + '\">';
                    var select_options = '';
                    $.each(dropdown_values_fields, function (indexv, values) {
                        $.each(values, function (indexd, valued) {
                            if (index == indexv) {
                                select_options += '<option value=\"' + valued + '\">' + valued + '</option>';
                            }
                        });
                    });
                    var select_close = '</select></td>';
                    var select = [select_open, select_options, select_close].join(' ');
                    tabfields.push(select);
                } else if (type_fields[index] == metademandfreelinesparams.number) {
                    tabfields.push('<td><input add=1 id=\"' + index + '\" value=\"0\" type=\"number\" min=\"0\" name=\"' + index + '\" style=\"width: 7em;\" ></td>');
                } else if (type_fields[index] == metademandfreelinesparams.readonly && metademandfreelinesparams.orderfollowupisactive) {
                    tabfields.push('<td></td>');
                } else if (type_fields[index] == metademandfreelinesparams.date) {
                    tabfields.push('<td><input add=1 id=\"' + index + '\" value=\"0\" type=\"date\" name=\"' + index + '\" ></td>');
                } else if (type_fields[index] == metademandfreelinesparams.time) {
                    tabfields.push('<td><input add=1 id=\"' + index + '\" value=\"0\" type=\"time\" name=\"' + index + '\" ></td>');
                }
            });
            var str = '<button class =\"btn btn-success add_item\" type = \"button\" name =\"add_item\" onclick=\"confirmUpdateLine(this, ' + i +',  1)\">';
            tabbutton = '<td style=\"text-align: center;\" colspan=\"2\">'
                + str
                + '<i class =\"ti ti-check\"></i></button></td>'
                + '<td style=\"text-align: center;\"><button onclick =\"removeLine( ' + i + ')\" class =\"btn btn-danger\" type = \"button\" name =\"delete_item\">'
                + '<i class =\"ti ti-trash\"></i></button></td>'
                + '</tr>';
            var joined = [tabtr, tabfields, tabbutton].join(' ');
            $('#freetable_table'+ metademandfreelinesparams.rand + ' tr[class^=tab_bg_1]:last').after(joined);
        }
    } else {
        alert(metademandfreelinesparams.existLine);
    }


}


function confirmUpdateLine(node, nb, typepost, newparams)
{

    if (newparams) {
        params = newparams;
    } else {
        window.metademandfreelinesparams = metademandfreelinesparams;
        params = window.metademandfreelinesparams;
    }

    var fields = params.encoded_fields;
    var type_fields = params.types_encoded_fields;
    var dropdown_values_fields = params.dropdown_values_encoded_fields;
    var mandatory_fields = params.mandatory_encoded_fields;
    var empty_value = params.empty_value;
    var elem_parent = $(node).parent().parent();
    var tabfields = [];

    l = {
        'id': nb,
    };
    $.each(fields, function (index, valuej) {
        if (type_fields[index] == params.text) {
            l[index] = elem_parent.find('input[name=' + index + ']').val();
        } else if (type_fields[index] == params.select) {
            l[index] = elem_parent.find('select[name=' + index + ']').val();
        } else if (type_fields[index] == params.number) {
            l[index] = elem_parent.find('input[name=' + index + ']').val();
        } else if (type_fields[index] == params.date) {
            l[index] = elem_parent.find('input[name=' + index + ']').val();
        } else if (type_fields[index] == params.time) {
            l[index] = elem_parent.find('input[name=' + index + ']').val();
        }
        l['type'] = type_fields[index];
    });
    var unit_price = elem_parent.find('input[name=unit_price]').val();
    var quantity = elem_parent.find('input[name=quantity]').val();
    //orderfollowup
    if (params.orderfollowupisactive) {
        var total = quantity * unit_price;
        l[4] = total;
    }

    if (typepost == 1) {
        var line = {
            'add': l,
            'metademands_id': params.plugin_metademands_metademands_id,
            'fields_id': params.rand
        };
    } else {
        var line = {
            'update': l,
            'metademands_id': params.plugin_metademands_metademands_id,
            'fields_id': params.rand
        };
    }

    // data['lines' + params.rand] = {ind: l};
    // data['metademands_id'] = params.plugin_metademands_metademands_id;
    // data['fields_id'] = params.rand;
//                    } else {
//                       $.each(l, function (key, datas) {
//                        $.each(datas, function (key_data, data_lines) {
//                                if(key_data == 'id'){
//                                    if(data_lines == 'ind'){
//                                       data['lines$rand'][key] = l;
//                                    }
//                                }
//                            });
//                        });
//                    }
// console.log(data);
    $.ajax({
        url: params.root + '/ajax/freetable_item.php',
        type: 'POST',
        data: {datas: line}
    });
    ko = 0;

    $.each(fields, function (index, valuej) {

        if (mandatory_fields.includes(index)) {
            if (type_fields[index] == params.text) {
                if (elem_parent.find('input[name=' + index + ']').val() === '') {
                    elem_parent.find('input[name=' + index + ']').css('border-color', 'red');
                    ko = 1;
                } else {
                    elem_parent.find('input[name=' + index + ']').css('border-color', '');
                }
            } else if (type_fields[index] == params.select) {
                var select = document.getElementById(index);
                if (select.selectedIndex != undefined) {
                    var text = select.options[select.selectedIndex].text;
                    if (text == empty_value) {
                        select.style.borderColor = 'red';
                        ko = 1;
                    } else {
                        select.style.borderColor = '';
                    }
                }
            } else if (type_fields[index] == params.number) {
                if (elem_parent.find('input[name=' + index + ']').val() == 0) {
                    elem_parent.find('input[name=' + index + ']').css('border-color', 'red');
                    ko = 1;
                } else {
                    elem_parent.find('input[name=' + index + ']').css('border-color', '');
                }
            } else if (type_fields[index] == params.date) {
                if (elem_parent.find('input[name=' + index + ']').val() == 0) {
                    elem_parent.find('input[name=' + index + ']').css('border-color', 'red');
                    ko = 1;
                } else {
                    elem_parent.find('input[name=' + index + ']').css('border-color', '');
                }
            } else if (type_fields[index] == params.time) {
                if (elem_parent.find('input[name=' + index + ']').val() == 0) {
                    elem_parent.find('input[name=' + index + ']').css('border-color', 'red');
                    ko = 1;
                } else {
                    elem_parent.find('input[name=' + index + ']').css('border-color', '');
                }
            }
        }

    });
    //orderfollowup
    if (params.orderfollowupisactive) {
        total = Math.round((total + Number.EPSILON) * 100) / 100;
    }
    if (ko == 0) {
        if ($('[id^=line_' + params.rand + '_]').length == 0) {
            tabtr = '<tr name=\"data\" $style id=\"line_' + params.rand + '_' + i + '\" disabled>';

            $.each(fields, function (index, valuej) {

                if (type_fields[index] == params.text) {
                    tabfields.push('<td $style><input id = \"' + index + '_' + i + '\" type=\"text\" name=\"' + index + '\" size=\"' + params.size + '\" disabled ></td>');
                } else if (type_fields[index] == params.select) {
                    var select_open = '<td $style><select id = \"' + index + '_' + i + '\" name=\"' + index + '\">';
                    var select_options = '';
                    $.each(dropdown_values_fields, function (indexv, values) {
                        $.each(values, function (indexd, valued) {
                            if (index == indexv) {
                                select_options += '<option value=\"' + valued + '\">' + valued + '</option>';
                            }
                        });
                    });
                    var select_close = '</select></td>';
                    var select = [select_open, select_options, select_close].join(' ');
                    tabfields.push(select);
                } else if (type_fields[index] == params.number) {
                    tabfields.push('<td $style><input add=2 id= \"' + index + '_' + i + '\" type=\"number\" min=\"0\" name=\"' + index + '\" style=\"width: 7em;\" disabled ></td>');
                } else if (type_fields[index] == params.readonly && params.orderfollowupisactive) {
                    //orderfollowup
                    tabfields.push('<td $style id=\"linetotal\">' + total.toFixed(2) + ' €</td>');
                } else if (type_fields[index] == params.date) {
                    tabfields.push('<td $style><input add=2 id= \"' + index + '_' + i + '\" type=\"date\" name=\"' + index + '\" disabled ></td>');
                } else if (type_fields[index] == params.time) {
                    tabfields.push('<td $style><input add=2 id= \"' + index + '_' + i + '\" type=\"time\" name=\"' + index + '\" disabled ></td>');
                }

            });
            tabbutton = '<td></td><td style=\"text-align: center;\"><button onclick =\"editLine(' + i +')\" class =\"btn btn-info\" type = \"button\" name =\"edit_item\">'
                + '<i class =\"ti ti-pencil\"></i></button></td>'
                + '<td style=\"text-align: center;\"><button onclick =\"removeLine( ' + i + ')\" class =\"btn btn-danger\" type = \"button\" name =\"delete_item\">'
                + '<i class =\"ti ti-trash\"></i></button></td></tr>'

            var joined = [tabtr, tabfields, tabbutton].join(' ');

            $('#freetable_table' + params.rand + ' tr:last').before(joined);
            $('#name_' + i).val(name);

            elem_parent.find('input[name=name' + params.rand + ']').val('');
            $.each(fields, function (index, valuej) {
                if (type_fields[index] == params.text) {
                    $('#' + index + '_' + i).val(elem_parent.find('input[name=' + index + ']').val());
                    elem_parent.find('input[name=' + index + ']').val('');
                } else if (type_fields[index] == params.select) {
                    $('#' + index + '_' + i).val(elem_parent.find('select[name=' + index + ']').val());
                    elem_parent.find('select[name=' + index + ']').val('');
                } else if (type_fields[index] == params.number) {
                    $('#' + index + '_' + i).val(elem_parent.find('input[name=' + index + ']').val());
                    elem_parent.find('input[name=' + index + ']').val(0);
                } else if (type_fields[index] == params.date) {
                    $('#' + index + '_' + i).val(elem_parent.find('input[name=' + index + ']').val());
                    elem_parent.find('input[name=' + index + ']').val(0);
                } else if (type_fields[index] == params.time) {
                    $('#' + index + '_' + i).val(elem_parent.find('input[name=' + index + ']').val());
                    elem_parent.find('input[name=' + index + ']').val(0);
                }
            });
        } else if (typepost == 'add') {
            tabtr = '<tr name=\"data\" $style id=\"line_' + params.rand + '_' + i + '\" disabled>';

            $.each(fields, function (index, valuej) {

                if (type_fields[index] == params.text) {
                    tabfields.push('<td $style><input id = \"' + index + '_' + i + '\" type=\"text\" name=\"' + index + '\" size=\"' + params.size + '\" disabled ></td>');
                } else if (type_fields[index] == params.select) {
                    var select_open = '<td $style><select id = \"' + index + '_' + i + '\" name=\"' + index + '\">';
                    var select_options = '';
                    $.each(dropdown_values_fields, function (indexv, values) {
                        $.each(values, function (indexd, valued) {
                            if (index == indexv) {
                                select_options += '<option value=\"' + valued + '\">' + valued + '</option>';
                            }
                        });
                    });
                    var select_close = '</select></td>';
                    var select = [select_open, select_options, select_close].join(' ');
                    tabfields.push(select);
                } else if (type_fields[index] == params.number) {
                    tabfields.push('<td $style><input add=3 id=\"' + index + '_' + i + '\" type=\"number\" min=\"0\" name=\"' + index + '\" style=\"width: 7em;\" disabled ></td>');
                } else if (type_fields[index] == params.readonly && params.orderfollowupisactive) {
                    //orderfollowup
                    tabfields.push('<td $style id=\"linetotal\">' + total.toFixed(2) + ' €</td>');
                } else if (type_fields[index] == params.date) {
                    tabfields.push('<td $style><input add=3 id=\"' + index + '_' + i + '\" type=\"date\" name=\"' + index + '\" disabled ></td>');
                } else if (type_fields[index] == params.time) {
                    tabfields.push('<td $style><input add=3 id=\"' + index + '_' + i + '\" type=\"time\" name=\"' + index + '\" disabled ></td>');
                }

            });
            tabbutton = '<td></td><td style=\"text-align: center;\"><button onclick=\"editLine(' + i +')\" class =\"btn btn-info\" type = \"button\" name =\"edit_item\">'
                + '<i class =\"ti ti-pencil\"></i></button></td>'
                + '<td style=\"text-align: center;\"><button onclick =\"removeLine( ' + i + ')\" class =\"btn btn-danger\" type = \"button\" name =\"delete_item\">'
                + '<i class =\"ti ti-trash\"></i></button></td></tr>'

            var joined = [tabtr, tabfields, tabbutton].join(' ');

            $('#freetable_table' + params.rand + ' tr[id^=line_' + params.rand + '_]:last').after(joined);
            $('#name_' + i).val(name);
            elem_parent.find('input[name=name' + params.rand + ']').val('');

            $.each(fields, function (index, valuej) {
                if (type_fields[index] == params.text) {
                    $('#' + index + '_' + i).val(elem_parent.find('input[name=' + index + ']').val());
                    elem_parent.find('input[name=' + index + ']').val('');
                } else if (type_fields[index] == params.select) {
                    $('#' + index + '_' + i).val(elem_parent.find('select[name=' + index + ']').val());
                    elem_parent.find('select[name=' + index + ']').val('');
                } else if (type_fields[index] == params.number) {
                    $('#' + index + '_' + i).val(elem_parent.find('input[name=' + index + ']').val());
                    elem_parent.find('input[name=' + index + ']').val(0);
                } else if (type_fields[index] == params.date) {
                    $('#' + index + '_' + i).val(elem_parent.find('input[name=' + index + ']').val());
                    elem_parent.find('input[name=' + index + ']').val(0);
                } else if (type_fields[index] == params.time) {
                    $('#' + index + '_' + i).val(elem_parent.find('input[name=' + index + ']').val());
                    elem_parent.find('input[name=' + index + ']').val(0);
                }
            });
        } else {
            tabtr = '<tr name=\"data\" $style id=\"line_' + params.rand + '_' + i + '\">';

            $.each(fields, function (index, valuej) {

                if (type_fields[index] == params.text) {
                    tabfields.push('<td $style><input id = \"' + index + '_' + i + '\" type=\"text\" name=\"' + index + '\" size=\"' + params.size + '\" disabled ></td>');
                } else if (type_fields[index] == params.select) {
                    var select_open = '<td $style><select id = \"' + index + '_' + i + '\" name=\"' + index + '\">';
                    var select_options = '';
                    $.each(dropdown_values_fields, function (indexv, values) {
                        $.each(values, function (indexd, valued) {
                            if (index == indexv) {
                                select_options += '<option value=\"' + valued + '\">' + valued + '</option>';
                            }
                        });
                    });
                    var select_close = '</select></td>';
                    var select = [select_open, select_options, select_close].join(' ');
                    tabfields.push(select);
                } else if (type_fields[index] == params.number) {
                    tabfields.push('<td $style><input add=4 id=\"' + index + '_' + i + '\" type=\"number\" min=\"0\" name=\"' + index + '\" style=\"width: 7em;\" disabled ></td>');
                } else if (type_fields[index] == params.readonly && params.orderfollowupisactive) {
                    //orderfollowup
                    tabfields.push('<td $style id=\"linetotal\">' + total.toFixed(2) + ' €</td>');
                } else if (type_fields[index] == params.date) {
                    tabfields.push('<td $style><input add=4 id=\"' + index + '_' + i + '\" type=\"date\" name=\"' + index + '\"  disabled ></td>');
                } else if (type_fields[index] == params.time) {
                    tabfields.push('<td $style><input add=4 id=\"' + index + '_' + i + '\" type=\"time\" name=\"' + index + '\"  disabled ></td>');
                }
            });
            tabbutton = '<td></td><td style=\"text-align: center;\"><button onclick=\"editLine(' + i +')\" class =\"btn btn-info\" type = \"button\" name =\"edit_item\">'
                + '<i class =\"ti ti-pencil\"></i></button></td>'
                + '<td style=\"text-align: center;\"><button onclick=\"removeLine( ' + i + ')\" class =\"btn btn-danger\" type = \"button\" name =\"delete_item\">'
                + '<i class =\"ti ti-trash\"></i></button></td></tr>'

            var joined = [tabtr, tabfields, tabbutton].join(' ');

            $('#freetable_table' + params.rand + ' tr[id^=line_' + params.rand + '_]:last').after(joined);

            $('#freetable_table' + params.rand + ' tr[id^=line_' + params.rand + '_]:last #name_' + i).val(name);
            $.each(fields, function (index, valuej) {
                if (type_fields[index] == params.text) {
                    $('#freetable_table' + params.rand + ' tr[id^=line_' + params.rand + '_]:last #' + index + '_' + i).val(elem_parent.find('input[name=' + index + ']').val());
                } else if (type_fields[index] == params.select) {
                    $('#freetable_table' + params.rand + ' tr[id^=line_' + params.rand + '_]:last #' + index + '_' + i).val(elem_parent.find('select[name=' + index + ']').val());
                } else if (type_fields[index] == params.number) {
                    $('#freetable_table' + params.rand + ' tr[id^=line_' + params.rand + '_]:last #' + index + '_' + i).val(elem_parent.find('input[name=' + index + ']').val());
                } else if (type_fields[index] == params.date) {
                    $('#freetable_table' + params.rand + ' tr[id^=line_' + params.rand + '_]:last #' + index + '_' + i).val(elem_parent.find('input[name=' + index + ']').val());
                } else if (type_fields[index] == params.time) {
                    $('#freetable_table' + params.rand + ' tr[id^=line_' + params.rand + '_]:last #' + index + '_' + i).val(elem_parent.find('input[name=' + index + ']').val());
                }
            });
        }
        node.parentNode.parentNode.remove();
        if (typepost == 'add') {
            nb++;
            i++;
        }
        //orderfollowup
        if (params.orderfollowupisactive) {
            showConfirmButton();
        }
    }
}

//orderfollowup
function showConfirmButton()
{
    var tabdatas = $('[id^=line_]');
    $('#nextBtn').hide();

    if (tabdatas.length == 0) {
        $('#add_freeinputs').css('display', 'none');
        $('#div_save_draft').css('display', 'none');
        if ($('#button_save_mydraft')) {
            $('#button_save_mydraft').css('display', 'none');
        }
    } else {
        $('#add_freeinputs').css('display', 'inline-block');
        $('#div_save_draft').css('display', 'inline-block');
        if ($('#button_save_mydraft')) {
            $('#button_save_mydraft').css('display', 'inline-block');
        }
    }
}

function removeLine(l, newparams)
{
    if (newparams) {
        params = newparams;
    } else {
        window.metademandfreelinesparams = metademandfreelinesparams;
        params = window.metademandfreelinesparams;
    }

    $('#line_' + params.rand + '_' + l).remove();
    var line = {
        'remove': l,
        'metademands_id': params.plugin_metademands_metademands_id,
        'fields_id': params.rand
    };

    $.ajax({
        url: params.root + '/ajax/freetable_item.php',
        type: 'POST',
        data: {type: 'remove', datas: line}
    });
    var tabdatas = $('[id^=line_' + params.rand + '_]');
    if (tabdatas.length == 0) {
        $('#add_freetables' + params.rand).css('display', 'none');
        $('#div_save_draft').css('display', 'none');
        if ($('#button_save_mydraft')) {
            $('#button_save_mydraft').css('display', 'none');
        }
    }
    if (document.querySelector('tr[id=\"tr_valid' + params.rand + '\"]')) {
        document.querySelector('tr[id=\"tr_valid' + params.rand + '\"]').remove();
    }
}

function editLine(l, newparams)
{

    if (newparams) {
         params = newparams;
    } else {
        window.metademandfreelinesparams = metademandfreelinesparams;
         params = window.metademandfreelinesparams;
    }

    let line = document.querySelector('#line_' + params.rand + '_' + l);

    let inputs = line.querySelectorAll('input');
    let selects = line.querySelectorAll('select');
    let areas = line.querySelectorAll('textarea');

    for (var i = 0; i < inputs.length; i++) {
        inputs[i].disabled = false;
    }

    for (var i = 0; i < selects.length; i++) {
        inputs[i].disabled = false;
    }

    for (var i = 0; i < areas.length; i++) {
        inputs[i].disabled = false;
    }

    line.querySelector('button[name=\"delete_item\"]').parentNode.remove();
    line.querySelector('button[name=\"edit_item\"]').parentNode.remove();

    let td = document.createElement('td');
    td.setAttribute('class', 'tbl-center');
    let button = document.createElement('button');
    button.className = 'btn btn-success';
    button.type = 'button';

    let ico = document.createElement('i');
    ico.className = 'ti ti-check';
    button.appendChild(ico);
    button.dataset.id = l;
    button.addEventListener('click', function () {
        confirmUpdateLine(this, l, 2, newparams);
    });
    td.appendChild(button);
    line.appendChild(td);

    let td1 = document.createElement('td');
    td1.setAttribute('class', 'tbl-center');
    let button1 = document.createElement('button');
    button1.className = 'btn btn-danger';
    button1.type = 'button';

    let ico1 = document.createElement('i');
    ico1.className = 'ti ti-trash';
    button1.appendChild(ico1);
    button1.dataset.id = l;
    button1.addEventListener('click', function () {
        removeLine(this, this.dataset.id, newparams);
    });
    td1.appendChild(button1);
    line.appendChild(td1);
}
