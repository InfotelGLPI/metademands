$(function () {
    var
        $table = $('#tree-table'),
        rows = $table.find('tr');

    rows.each(function (index, row) {
        var
            $row = $(row),
            level = $row.data('level'),
            id = $row.data('id'),
            $columnName = $row.find('td[data-column="name"]'),
            children = $table.find('tr[data-parent="' + id + '"]');

        if (children.length) {
            var expander = $columnName.prepend('' +
                '<span class="treegrid-expander ti ti-chevron-down"></span>' +
                '');

            children.show();

            expander.on('click', function (e) {
                var $target = $(e.target);

                if ($target.hasClass('ti ti-chevron-right')) {
                    $target
                        .removeClass('ti ti-chevron-right')
                        .addClass('ti ti-chevron-down');

                    children.show();
                } else if ($target.hasClass('ti ti-chevron-down')) {
                    $target
                        .removeClass('ti ti-chevron-down')
                        .addClass('ti ti-chevron-right');

                    reverseHide($table, $row);
                }
            });
        }

        $columnName.prepend('' +
            '<span class="treegrid-indent" style="width:' + 15 * level + 'px"></span>' +
            '');
    });

    // Reverse hide all elements
    reverseHide = function (table, element) {
        var
            $element = $(element),
            id = $element.data('id'),
            children = table.find('tr[data-parent="' + id + '"]');

        if (children.length) {
            children.each(function (i, e) {
                reverseHide(table, e);
            });

            $element
                .find('.ti ti-chevron-down')
                .removeClass('ti ti-chevron-down')
                .addClass('ti ti-chevron-right');

            children.hide();
        }
    };
});
