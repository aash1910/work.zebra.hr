jQuery(function($) {
    var $nestingLevelField = $('input[name="blockdefinition_nesting[root]"]');
    var $parentsField = $('.blocksft-setting-child-of-options');
    var $canHaveChildrenField = $('.blocksft-setting-can-have-children .toggle-btn');
    var $haveExactChildrenField = $('.blocksft-setting-children-options__exact input');
    var $isComponentField = $('.blocksft-setting-is-component .toggle-btn');
    var $isDeprecatedField = $('.blocksft-setting-is-deprecated .toggle-btn');
    var $minMaxChildrenFields = $('.blocksft-setting-children-options__min, .blocksft-setting-children-options__max');

    // Stupid methods because there are no events to subscribe to on toggle fields.
    var enableToggleField = function ($fieldSelector) {
        $fieldSelector
            .removeClass('off')
            .addClass('on')
            .attr('alt', 'on')
            .attr('data-state', 'on')
            .attr('aria-checked', 'true')
        ;

        $fieldSelector
            .find('input[type="hidden"]')
            .val($fieldSelector.hasClass('yes_no') ? 'y' : 1)
        ;
    };

    var disableToggleField = function ($fieldSelector) {
        $fieldSelector
            .removeClass('on')
            .addClass('off')
            .attr('alt', 'off')
            .attr('data-state', 'off')
            .attr('aria-checked', 'false')
        ;

        $fieldSelector
            .find('input[type="hidden"]')
            .val($fieldSelector.hasClass('yes_no') ? 'n' : 0)
        ;
    };

    // Check for the opposite status because again, no event to subscribe to, so our click events below are actually
    // happening _after_ the state of the toggle is changed... so everything is backwards. yay.
    var toggleCanHaveChildrenField = function (canHaveChildren) {
        var $childOptionsField = $('.blocksft-setting-children-options');

        if (!canHaveChildren) {
            $childOptionsField.removeClass('blocksft-setting-no-children');
            $haveExactChildrenField.trigger('change');
            disableToggleField($isComponentField);
        } else {
            $childOptionsField.addClass('blocksft-setting-no-children');
        }
    };

    var toggleIsComponentField = function (isComponent) {
        var $isEditableField = $('#fieldset-blockdefinition_is_editable');
        var $componentBuilder = $('.block-container.component-builder');

        if (!isComponent) {
            $isEditableField.removeClass('hidden');
            $componentBuilder.removeClass('hidden');
            disableToggleField($canHaveChildrenField);
        } else {
            $isEditableField.addClass('hidden');
            $componentBuilder.addClass('hidden');
        }
    };

    $nestingLevelField.on('click', function () {
        var level = $(this).val();

        $parentsField.removeClass('hidden');

        if (level === 'root_only') {
            $parentsField.addClass('hidden');
        }
    });

    $canHaveChildrenField.on('click', function () {
        var canHaveChildren = $(this).find('input').val() === 'y';
        toggleCanHaveChildrenField(canHaveChildren);
    });

    $isComponentField.on('click', function () {
        var isComponent = $(this).find('input').val() === 'y';
        toggleIsComponentField(isComponent);
    });

    $haveExactChildrenField.on('change', function () {
        var exactChildren = $(this).val();

        if (exactChildren > 0) {
            $minMaxChildrenFields.addClass('blocksft-setting-no-children');
        } else {
            $minMaxChildrenFields.removeClass('blocksft-setting-no-children');
        }
    });

    $isDeprecatedField.on('click', function () {
        var isDeprecated = $(this).find('input').val() === 'y';
        var $deprecatedNoteField = $('.blocksft-setting-deprecated-note');

        if (!isDeprecated) {
            $deprecatedNoteField.removeClass('hidden');
        } else {
            $deprecatedNoteField.find('textarea').val('');
            $deprecatedNoteField.addClass('hidden');
        }
    });


    // Bind Color Picker events :(
    // I think this can go away after the EE bug is fixed. https://github.com/ExpressionEngine/ExpressionEngine/issues/714
    $('input[name^="grid[cols]"]').on('change', function () {
        if ($(this).val() !== 'colorpicker') {
            return;
        }

        // (╯°□°）╯︵ ┻━┻
        setTimeout(function () {
            $('.fields-keyvalue', '[data-fieldtype="colorpicker"]').miniGrid({
                grid_min_rows: 0,
                grid_max_rows: ''
            });

            ColorPicker.renderFields();
        }, 100);
    });
});
