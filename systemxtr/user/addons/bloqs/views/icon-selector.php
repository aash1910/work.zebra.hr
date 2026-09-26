<div class="blocksft-icon-selector">
    <input type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>" />
    <div class="blocksft-icon-selector__selected">
        <div class="blocksft-icon-selector__selected-icon"></div>
        <div class="blocksft-icon-selector__selected-name"></div>
        <a class="button button--secondary button--small action blocksft-icon-selector__remove hidden">
            <i class="fas fa-w fa-trash-alt"></i>
        </a>
    </div>
    <div class="dropdown__search">
        <div class="filter-search search-input">
            <input type="text" class="search-input__input blocksft-icon-selector__search" placeholder="search icons" />
        </div>
    </div>
    <div class="dropdown__scroll blocksft-icon-selector__icons">
        <?php foreach ($icons as $iconValue => $iconName): ?>
            <div class="blocksft-icon-selector__icon" data-icon-value="<?php echo $iconValue; ?>" data-icon-name="<?php echo $iconName; ?>">
                <i class="fa-fw fa-<?php echo $iconValue; ?>" title="<?php echo $iconName; ?>"></i>
            </div>
        <?php endforeach; ?>
    </div>
</div>
