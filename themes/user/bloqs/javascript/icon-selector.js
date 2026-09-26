jQuery(function($) {
    var $iconSelector = $('.blocksft-icon-selector');
    var $options = $iconSelector.find('.blocksft-icon-selector__icon');
    var $selected = $iconSelector.find('.blocksft-icon-selector__selected');
    var $input = $iconSelector.find('input[type="hidden"]');
    var $removeSelection = $iconSelector.find('.blocksft-icon-selector__remove');
    var $searchInput = $iconSelector.find('.blocksft-icon-selector__search');
    var value = $input.val();

    var setSelected = function ($option) {
        var iconValue = $option.data('iconValue');
        var iconName = $option.data('iconName');

        $option.addClass('selected');

        $selected.find('.blocksft-icon-selector__selected-icon').html('<i class="fa-fw fa-' + iconValue + '"></i>');
        $selected.find('.blocksft-icon-selector__selected-name').text(iconName);
        $selected.addClass('has-selection');
        $removeSelection.removeClass('hidden');

        $input.val(iconValue);
    };

    var removeSelected = function () {
        $options.removeClass('selected');

        $selected.find('.blocksft-icon-selector__selected-icon').html('');
        $selected.find('.blocksft-icon-selector__selected-name').text('');
        $selected.removeClass('has-selection');
        $removeSelection.addClass('hidden');

        $input.val('');
    };

    var hideAllOptions = function () {
        $options.hide();
    };

    var showAllOptions = function () {
        $options.show();
    };

    if (value) {
        setSelected($iconSelector.find('[data-icon-value="' + value + '"]'));
    }

    $options.on('click', function () {
        removeSelected();
        setSelected($(this));
    });

    $removeSelection.on('click', function (event) {
        event.preventDefault();
        removeSelected();
    });

    $searchInput.on('input', function () {
        var $input = $(this);
        var value = $input.val();

        if (value.length < 2) {
            showAllOptions();
            return;
        }

        hideAllOptions();

        $iconSelector.find('[data-icon-name*="' + value + '"]').show();
    });
});
