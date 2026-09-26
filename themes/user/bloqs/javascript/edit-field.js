jQuery(function($) {

    var $blockDefinitionsField = $('[data-input-value="blockDefinitions"]');
    var $componentDefinitionsField = $('[data-input-value="componentDefinitions"]');
    var $form = $blockDefinitionsField.closest('form');

    /**
     * @param {jQuery} $fieldObj
     * @param {string} fieldName
     */
    var updateDefinitionOrder = function ($fieldObj, fieldName) {
        var $hiddenFields = $fieldObj.find('input[name="'+ fieldName +'[]"]');
        var $hiddenFieldsDisassociate = $fieldObj.find('input[name="'+ fieldName +'_disassociate[]"]');
        var selections = $hiddenFields.map(function () {
            return parseInt($(this).val());
        }).get();

        var $labels = $fieldObj.find('label');
        var order = [];

        $labels.each(function () {
            if (parseInt($(this).data('id'))) {
                order.push($(this).data('id'));
            }
        });

        // Remove old fields before inserting new ones, in the order we want to save them.
        $hiddenFields.remove();
        $hiddenFieldsDisassociate.remove();

        order.forEach(function (id) {
            if (selections.includes(id)) {
                $fieldObj.after('<input type="hidden" name="'+ fieldName +'[]" value="'+ id +'" />');
            } else {
                $fieldObj.after('<input type="hidden" name="'+ fieldName +'_disassociate[]" value="'+ id +'" />');
            }
        });
    };

    /**
     * @param {jQuery} $fieldObj
     * @returns {[]}
     */
    var getFieldNames = function ($fieldObj) {
        var fieldNames = [];
        var $labels = $fieldObj.find('label.checkbox-label');

        $labels.each(function () {
            var $label = $(this);

            if ($label.find('input[type="checkbox"]').is(':checked')) {
                fieldNames.push($label.find('[data-field-name]').data('fieldName'));
            }
        });

        return fieldNames;
    };

    $form.on('submit', function () {
        if ($blockDefinitionsField.length) {
            updateDefinitionOrder($blockDefinitionsField, 'blockDefinitions');
        }
        if ($componentDefinitionsField.length) {
            updateDefinitionOrder($componentDefinitionsField, 'componentDefinitions');
        }
    });

    var $fieldId = $('[name="field_id"]');
    var $fieldName = $('[name="field_name"]');
    var $nestable = $('[name="nestable"]');

    var fetchTemplateCode = function() {
        if (!$fieldId.val()) {
            return;
        }
        $.ajax({
            type: "GET",
            url: EE.bloqs.ajax_fetch_template_code,
            data: {
                field_name: $fieldName.val(),
                include_blocks: getFieldNames($blockDefinitionsField),
                nestable: $nestable.val() === 'y'
            },
            success: function (data) {
                if (!data) {
                    return;
                }

                $('textarea[name="template"]').val(data);
            }
        });
    };

    fetchTemplateCode();
    $fieldName.on('change', fetchTemplateCode);
    $nestable.on('change', fetchTemplateCode); // @todo this isn't working?
    $blockDefinitionsField.on('change', fetchTemplateCode);
});
