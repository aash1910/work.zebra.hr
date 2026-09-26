<?php

use BoldMinded\Bloqs\Controller\ModalController;
use ExpressionEngine\Library\CP\Table;

$table = ee('CP/Table', ['sortable' => false]);
$table->setNoResultsText('bloqs_definitions_no_results');

$tbl_cols = [
    'bloqs_blockdefinitions_name' => ['encode' => false],
    '' => ['encode' => false],
    'bloqs_blockdefinitions_shortname',
    'bloqs_blockdefinitions_usage' => ['encode' => false],
    'bloqs_blockdefinitions_manage' => ['type' => Table::COL_TOOLBAR],
];
$table->setColumns($tbl_cols);

$rows = [];

$modalController = new ModalController();

/** @var \BoldMinded\Bloqs\Entity\BlockDefinition $blockDefinition */
foreach($blockDefinitions as $blockDefinition) {
    if ($blockDefinition->isHidden()) {
        continue;
    }
    $blockDefinitionUsage = 0;
    $componentDefinitionUsage = 0;

    if (isset($blockDefinitionsUsage[$blockDefinition->getId()])) {
        $blockDefinitionUsage = count($blockDefinitionsUsage[$blockDefinition->getId()]);
    }

    if (isset($blockComponentsUsage[$blockDefinition->getId()])) {
        $componentDefinitionUsage = count($blockComponentsUsage[$blockDefinition->getId()]);
    }

    $componentIcon = $blockDefinition->isComponent() ? ' <span class="fas fa-layer-group" style="color: var(--ee-accent-dark);" title="Component"></span>' : '';
    $deprecatedNote = $blockDefinition->getDeprecatedNote() ? 'Deprecated: ' . $blockDefinition->getDeprecatedNote() : 'Deprecated';
    $deprecatedIcon = $blockDefinition->isDeprecated() ? '<span class="fas fa-fw fa-exclamation-circle" style="color: var(--ee-warning);" title="'. $deprecatedNote .'"></span>' : '';

    $toolbarItems = [
        'edit' => [
            'href' => ee('CP/URL')->make('addons/settings/bloqs/block-definition', ['definitionId' => $blockDefinition->getId()])->compile(),
            'title' => lang('edit'),
        ],
        'copy' => [
            'href'    => '',
            'title'   => lang('copy'),
            'class'   => 'm-link',
            'rel'     => 'modal-confirm-copy',
            'data-confirm' => 'Block Name: '.$blockDefinition->getName(),
            'data-definition-id' => $blockDefinition->getId(),
        ],
    ];

    $blockName = '<a href="'. ee('CP/URL')->make('addons/settings/bloqs/block-definition', ['definitionId' => $blockDefinition->getId()])->compile() .'">' . $blockDefinition->getName() . '</a>';
    $componentUsageMessage = $componentDefinitionUsage === 0 ? '' : sprintf(' and in %s components', $componentDefinitionUsage);
    $entryUsageMessage = sprintf('<a href="%s">Used in %s entries'. $componentUsageMessage .'</a>', ee('CP/URL')->make('addons/settings/bloqs/block-usage', ['definitionId' => $blockDefinition->getId()])->compile(), $blockDefinitionUsage);

    // Don't allow deleting of bloq definitions if they are used in any entry or component
    // Admin must first edit the entry or component and remove the bloq definition first.
    // Deleting a definition that is used in an entry or component will cause data loss.
    if ($blockDefinitionUsage === 0 && $componentDefinitionUsage === 0) {
        $toolbarItems['remove'] = [
            'href'    => '',
            'title'   => lang('delete'),
            'class'   => 'm-link',
            'rel'     => 'modal-confirm-remove',
            'data-confirm' => 'Block Name: '.$blockDefinition->getName(),
            'data-definition-id' => $blockDefinition->getId(),
        ];
    }

    $rows[] = [
        ['content' => $blockName],
        ['content' => $componentIcon . $deprecatedIcon],
        ['content' => $blockDefinition->getShortName()],
        ['content' => bool_config_item('bloqs_disable_usage_reports') ? '' : $entryUsageMessage],
        ['toolbar_items' => $toolbarItems],
    ];

    $modalController->create('modal-confirm-remove', 'ee:_shared/modal_confirm_remove', [
        'form_url' => $confirmDeleteBlockUrl,
        'hidden' => ['definitionId' => $blockDefinition->getId()],
        'checklist' => [['kind' => 'Block Name', 'desc' => $blockDefinition->getName()]]
    ]);

    $sections = [];

    $sections[0][] = [
        'title' => 'bloqs_blockdefinition_name',
        'desc' => lang('bloqs_blockdefinition_name_info'),
        'fields' => [
            'blockdefinition_name' => [
                'required' => 1,
                'type' => 'text',
                'value' => '',
            ]
        ]
    ];
    $sections[0][] = [
        'title' => 'bloqs_blockdefinition_shortname',
        'desc' => lang('bloqs_blockdefinition_shortname_info'),
        'fields' => [
            'blockdefinition_shortname' => [
                'required' => 1,
                'type' => 'text',
                'value' => '',
            ]
        ]
    ];

    $modalController->create('modal-confirm-copy', 'copy', [
        'form_url' => $copyBlockUrl,
        'definitionId' => $blockDefinition->getId(),
        'blockName' => $blockDefinition->getName(),
        'sections' => $sections
    ]);
}

$table->setData($rows);

?>

<div class="bloqs">
    <div class="tbl-ctrls">
        <fieldset class="tbl-search right">
            <a class="btn tn action" href="<?php echo $blockDefinitionUrl;?>"><?php echo lang('bloqs_blockdefinitions_add')?></a>
        </fieldset>
        <h1><?php echo  lang('bloqs_blockdefinitions_title') ?></h1>

        <?php echo ee('CP/Alert')->getAllInlines()?>
        <?php $this->embed('ee:_shared/table', $table->viewData()); ?>
    </div>
</div>

