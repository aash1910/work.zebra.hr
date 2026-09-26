(function($) {

    // EE's native React fields need a little assistance...

    /**
     * @param {object} context
     */
    var onCloneColorPicker = function (context) {
        $('div[data-colorpicker-react]', context).each(function () {
            var props = JSON.parse(window.atob($(this).data('colorpickerReact')));
            var value = $(this).find('input').val();

            props.value = value;
            props.initialColor = value;
            props.name = $(this).data('inputValue');

            ReactDOM.render(React.createElement(ColorPicker, props, null), this);
        });
    };

    /**
     * @param {object} context
     */
    var onCloneRelationship = function (context) {
        $('div[data-relationship-react]', context).each(function () {
            var props = JSON.parse(window.atob($(this).data('relationshipReact')));
            var selected = [];
            var selectItems = [];

            // Find all the selected values from the cloned item, then add them
            // back to the props before the React element is rendered.
            $(this).find('[type="hidden"]').each(function() {
                var value = $(this).val();

                if (value) {
                    selected.push({value: Number(value)});
                }
            });

            selected.forEach(function (item, index) {
                selectItems.push(_.findWhere(props.items, item));
            });

            props.selected = selectItems;
            props.name = $(this).data('inputValue');
            ReactDOM.render(React.createElement(Relationship, props, null), this);
        });
    };

    /**
     * @param {object} context
     */
    var onCloneMultiSelect = function (context) {
        $('div[data-select-react]', context).each(function () {
            var props = JSON.parse(window.atob($(this).data('selectReact')));
            var selected = [];
            var selectItems = [];

            // Find all the selected values from the cloned item, then add them
            // back to the props before the React element is rendered.
            $(this).find('[type="hidden"]').each(function () {
                var value = $(this).val();

                if (value) {
                    selected.push({value: value});
                }
            });

            selected.forEach(function (item, index) {
                selectItems.push(_.findWhere(props.items, item));
            });

            props.selected = selectItems;
            props.name = $(this).data('inputValue');
            ReactDOM.render(React.createElement(SelectField, props, null), this);
        });
    };

    /**
     * @param {object} context
     */
    var onCloneWygwam = function (context) {
        // Remove the existing CKE field, the text area still exists
        // and contains the correct content, then re-create the CKE field.
        if (typeof window.Grid._eventHandlers.display !== 'undefined' &&
            typeof window.Grid._eventHandlers.display.wygwam === 'function'
        ) {
            $('.cke', context).remove();

            window.Grid._eventHandlers.display.wygwam(context);
        }
    };

    /**
     * @param {object} context
     */
    var onCloneRTE = function (context) {
        var $textarea = $('textarea', context);
        var configName = $textarea.data('config');
        var rteType = null;
        const {configs} = EE.Rte;

        if (configs && configs[configName]) {
            rteType = configs[configName]['type'] || null;
        }

        // Redactor type is not present in the _eventHandlers for some reason at this point.
        if (rteType === 'redactor') {
            var $redactor = $('.redactor-box', context);
            var $redactorField = $redactor.find('textarea');

            // Remove the existing Redactor field, then add the textarea which
            // contains the correct content back, then re-create the CKE field.
            $redactor.remove();
            context.append($redactorField);

            // For reasons unknown if we console log window.Grid._eventHandlers right now
            // it is an empty array, but calling display.rte seems to work.
            window.Grid._eventHandlers.display.rte(context);
        } else if (
            typeof window.Grid._eventHandlers.display !== 'undefined' &&
            typeof window.Grid._eventHandlers.display.rte === 'function'
        ) {
            var WysiHat = $('.WysiHat-container', context);

            // Jump through stupid hoops to deal with the crap RTE field in EE5
            // I really hope no one is using this editor anymore in EE 6+.
            // Redactor and CKEditor are native now, no reason to be using this.
            if (WysiHat.length) {
                var $field = $('.WysiHat-container textarea', context);
                var $toolbar = $('.WysiHat-container .toolbar', context);

                // Remove the old field
                $('.WysiHat-container', context).remove();

                // Create a map of the class names to their config value name.
                // Why these class names are different is beyond me. The whole RTE field is a piece of garbage.
                // It would have made sense to add the config as a data attribute to the field, but nope.
                var btnMap = {
                    'rte-bold': 'bold',
                    'rte-upload': 'image',
                    'rte-italic': 'italic',
                    'rte-link': 'link',
                    'rte-list': 'unordered_list',
                    'rte-order-list': 'ordered_list',
                    'rte-quote': 'blockquote',
                    'rte-underline': 'underline',
                    'rte-elements': ['headings'],
                    'rte-view': ['view_source'],
                };

                var buttons = [];

                Object.keys(btnMap).forEach(function (key, index) {
                    var button = $toolbar.find('li.' + key);
                    if (button.length) {
                        buttons.push(btnMap[key]);
                    }
                });

                // Finally reinitialize the whole field, b/c that's what we're forced to do.
                $field
                    .appendTo(context)
                    .addClass("WysiHat-field")
                    .wysihat({
                        buttons: buttons
                    });
            } else {
                // Remove the existing CKE or Redactor X field, the textarea still exists
                // and contains the correct content, then re-create the CKE or Redactor X field.
                $('.cke, .ck, .rx-container', context).remove();

                window.Grid._eventHandlers.display.rte(context);
            }
        }
    };

    Grid.bind('relationship', 'clone', onCloneRelationship);
    Grid.bind('checkboxes', 'clone', onCloneMultiSelect);
    Grid.bind('multi_select', 'clone', onCloneMultiSelect);
    Grid.bind('wygwam', 'clone', onCloneWygwam);
    Grid.bind('rte', 'clone', onCloneRTE);
    Grid.bind('colorpicker', 'clone', onCloneColorPicker);

})(jQuery);
