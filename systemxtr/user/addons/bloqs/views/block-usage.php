<?php

use BoldMinded\Bloqs\Controller\ModalController;
use ExpressionEngine\Library\CP\Table;

$table = ee('CP/Table', ['sortable' => false]);
$table->setNoResultsText('bloqs_usage_no_results');

$table->setColumns([
    'title',
]);

$rows = [];

/** @var \ExpressionEngine\Model\Channel\ChannelEntry $entry */
foreach($entries as $entry) {
    $rows[] = [
        [
            'href' => ee('CP/URL')->make(sprintf('cp/publish/edit/entry/%d', $entry->getId()))->compile(),
            'content' => $entry->title,
        ]
    ];
}

$table->setData($rows);
?>

<div class="box bloqs panel">
    <div class="tbl-ctrls">
        <div class="panel-heading">
            <div class="title-bar title-bar--large">
                <h3 class="title-bar__title"><?php echo lang('entries') ?></h3>
                <div class="filter-bar filter-bar--collapsible">
                    <p class="filter-bar__item"><?php echo sprintf(lang('bloqs_blockdefinitions_usage_description'), $blockDefinition->getName()); ?></p>
                </div>
            </div>
        </div>
        <?php $this->embed('ee:_shared/table', $table->viewData()); ?>
    </div>
</div>


<?php if (!empty($components)): ?>
<?php

$table = ee('CP/Table', ['sortable' => false]);
$table->setNoResultsText('bloqs_usage_no_results');

$table->setColumns([
    'title',
]);

$rows = [];

/** @var \BoldMinded\Bloqs\Entity\BlockDefinition $component */
foreach($components as $component) {
    $rows[] = [
        [
            'href' => ee('CP/URL')->make('addons/settings/bloqs/block-definition', ['definitionId' => $component->getId()])->compile(),
            'content' => $component->getName(),
        ]
    ];
}

$table->setData($rows);
?>

<div class="box bloqs panel">
    <div class="tbl-ctrls">
        <div class="panel-heading">
            <div class="title-bar title-bar--large">
                <h3 class="title-bar__title"><?php echo lang('bloqs_block_components_title') ?></h3>
                <div class="filter-bar filter-bar--collapsible">
                    <p class="filter-bar__item"><?php echo sprintf(lang('bloqs_blockdefinitions_component_usage_description'), $blockDefinition->getName()); ?></p>
                </div>
            </div>
        </div>
        <?php $this->embed('ee:_shared/table', $table->viewData()); ?>
    </div>
</div>

<?php endif; ?>
