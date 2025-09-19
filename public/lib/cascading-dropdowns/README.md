# Chained Selects
jQuery plugin for populating chained selects using hierarchical javascript (JSON) data

## Sample usage

[Demo can be seen here](https://smarek.github.io/jquery-chained-selects/demo.html)

```html
<form>
  <select id="sample-select"></select>
</form>

<script type="text/javascript">
var chainedData = {
    "A": {
        1: "AA",
        2: "AB"
    },
    "B": {
        "BB": {
            3: "BBB"
        }
    }
};

$(document).ready(function () {
    $('#sample-select').chainedSelects({
        data: chainedData,
        loggingEnabled: true
    });
});
</script>
```

## Full options
```javascript
$("#select-id").chainedSelects({
    placeholder: "", // placeholder text, can be left empty, default value is "", if the placeholder is empty, no empty option will be created
    selectCssClass: "my-select-extra-css-class", // extra css class to add on used/generated html select elements, defaults to `false`
    data: dataVariable, // data, can be function which returns data structure, or plain variable, defaults to `{}`
    maxLevels: 10, // to avoid browser hangs, by default is limited to 10 levels of hierarchy, you can raise this if you need to
    loggingEnabled: false, // enables internal logging, might be useful for debugging, defaults to `false`
    selectedKey: 3, // will pre-select options by option value, accepts numeric or string (string for selecting either category, number for the final option), default to `false`
    // IMPORTANT: selectedKey option will override defaultPath option
    defaultPath: ["B", "BB"], // will pre-select options by path, defaults to `false`
    sortByValue: false, // sort options by text value, defaults to `false`
    // IMPORTANT: if provided callback function fails, it will not report caught error if the `loggingEnabled` is not `true`
    onSelectedCallback: function(id){}, // will call user defined function with id of currently selected, or empty string if non-final option was chosen, defaults to `false`
    autoSelectSingleOptions: true, // will automatically select single options at any level (recursively), forcing user to make a choice only when there is choice to make, defaults to `false`
});
```

## API Methods
```javascript
// Set logging enabled (true or false, in case of invalid argument, defaults to true)
$("#select-id").data("chainedSelects").setLoggingEnabled(boolean);
// Change current selected key (integer or string, for either specific choice or category)
$("#select-id").data("chainedSelects").changeSelectedKey(newSelectedKey);
```

## Notes about usage

- Will not allow you to select parent, only values where key is numeric (in sample selectable only 1(AA), 2(AB) and 3(BBB)
- Will bind to form surrounding the target select, and before form submit, place dummy option with selected value to the target select
