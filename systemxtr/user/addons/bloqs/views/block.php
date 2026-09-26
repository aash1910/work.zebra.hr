<?php

use BoldMinded\Bloqs\Entity\Block;

/** @var Block $block */
$block = $blockData['block'];
$blockDefinition = $block->getDefinition();
$isComponent = $blockDefinition->isComponent();
$inComponent = $inComponent ?? false;
$isEditable = $blockDefinition->isEditable();
$isDeprecated = $blockDefinition->isDeprecated();
$isCloneable = isset($block) && $block->isCloneable();

$componentClass = $isComponent && !$isEditable ? ' js-nested-item__is-component js-nested-item__no-children' : '';
$listGroupClass = ($isComponent || $inComponent) && !$isEditable ? ' js-nested-item__no-children' : '';
$listItemClass = ($isComponent || $inComponent) && !$isEditable ? ' js-nested-item__no-children' : '';

if ($isCloneable) {
    $componentClass .= ' js-nested-item__cloneable';
}
?>

<li class="js-nested-item blocksft-block<?php echo $componentClass ?>
<?php if ($block->deleted == 'true'): ?> deleted<?php endif; ?>
<?php if ($block->draft === 1): ?> block-draft<?php endif; ?>
<?php if ($isDeprecated): ?> block-deprecated<?php endif; ?>"
    data-id="<?= $block->getId() ?>"
    data-definition-id="<?= $blockDefinition->getId() ?>"
    data-definition-settings="<?php echo htmlspecialchars(json_encode($blockDefinition->getSettings()), ENT_QUOTES, 'UTF-8') ?>"
    data-name="<?= $blockDefinition->getName() ?>"
    data-shortname="<?= $blockDefinition->getShortName() ?>"
    data-is-component="<?php echo $isComponent ? 'true' : 'false' ?>"
    data-is-component-editable="<?php echo $isEditable ? 'true' : 'false' ?>"
    <?php if ($isComponentBuilder || ($inComponent && !$isEditable)): ?>data-validate="false"<?php endif; ?>
>
    <?php if ($isComponent && !$isEditable): ?>
        <div class="blocksft-insert blocksft-insert--below js-dropdown-toggle">
            <div class="blocksft-insert--control blocksft-insert--control__below">
                <a href="#" js-insert-block data-location="below" title="Add bloq after"><i class="fas fa-fw fa-plus"></i></a>
            </div>
        </div>
    <?php endif; ?>

    <div class="list-item list-item--action blocksft-list-item<?php echo $listItemClass ?>" data-blockvisibility="<?= $blockData['visibility'] ?>" data-base-name="<?= htmlspecialchars($blockData['fieldNames']->baseName) ?>">

        <input type="hidden" name="<?= $blockData['fieldNames']->draft ?>" value="<?= $block->draft ?>" class="block-draft" />
        <input type="hidden" name="<?= $blockData['fieldNames']->id ?>" value="<?= $block->id ?>" js-block-id-field />
        <input type="hidden" name="<?= $blockData['fieldNames']->definitionId ?>" value="<?= $blockDefinition->getId() ?>" />
        <input type="hidden" name="<?= $blockData['fieldNames']->componentDefinitionId ?>" value="<?php echo $block->getComponentDefinitionId() ?>" />
        <input type="hidden" name="<?= $blockData['fieldNames']->cloneable ?>" value="<?php echo intval($isCloneable) ?>" class="block-cloneable" />
        <input type="hidden" data-order-field name="<?= $blockData['fieldNames']->order ?>" value="<?= $block->order ?>" />

        <?php if (isset($blockData['fieldNames']->deleted)): ?>
            <input type="hidden" data-deleted-field name="<?= $blockData['fieldNames']->deleted ?>" value="<?= $block->deleted ?>" js-deleted-field />
        <?php endif; ?>

        <div class="list-item__handle blocksft-list-item__handle"><i class="fas fa-bars"></i></div>

        <div class="list-item__content">
            <nav class="blocksft-contextmenu">
                <?php if ($isComponentBuilder): ?>
                    <span class="blocksft-cloneable">
                        Cloneable?
                        <?php $toggleCloneable = $block->cloneable === 1 ? 'on' : 'off'; ?>
                        <button title="Cloneable?" js-toggle-cloneable type="button" class="toggle-btn yes_no <?php echo $toggleCloneable ?>" data-state="off" role="switch">
                            <span class="slider"></span>
                        </button>
                    </span>
                <?php endif; ?>
                <?php if (!$isComponentBuilder): ?>
                    <a class="blocksft-expand-collapse" href="#" title="Expand/Collapse" js-toggle-expand>
                        <i class="fas fa-fw fa-caret-square-<?php if ($blockData['visibility'] === 'expanded'): ?>up<?php else: ?>down<?php endif; ?>"></i>
                    </a>
                    <?php $toggleStatus = $block->draft === 1 ? 'off' : 'on'; ?>
                    <span class="blocksft-status">
                        <button title="Draft/Live" js-toggle-status type="button" class="toggle-btn yes_no <?php echo $toggleStatus ?>" data-state="<?php echo $toggleStatus ?>" role="switch">
                            <span class="slider"></span>
                        </button>
                    </span>
                    <?php if (!$isDeprecated): ?>
                        <a class="blocksft-clone" href="#" title="Clone" js-clone><i class="fas fa-fw fa-copy"></i></a>
                    <?php endif; ?>
                <?php endif; ?>
                <a class="blocksft-remove<?php if ($confirmBloqRemoval): ?> m-link<?php endif ;?>" data-confirm="<?php echo $block->getDefinition()->getName() ?>" rel="modal-confirm-remove-bloq" href="#" title="Remove" js-remove><i class="fas fa-fw fa-trash-alt"></i></a>
            </nav>
            <div class="blocksft-header">
                <div class="blocksft-title">
                    <span class="list-item__title">
                        <span class="list-item__title--important"><span class="fas fa-fw fa-exclamation-circle"></span></span>
                        <?php echo $block->getDefinition()->getName() ?>
                        <span class="list-item__title--draft">(Draft)</span>
                        <?php $this->embed('hint', ['name' => $block->getDefinition()->getShortName()]) ?>
                    </span>
                    <span class="list-item__summary summary" js-summary></span>
                </div>
            </div>

            <?php if (!$isComponentBuilder): ?>

                <div class="secondary blocksft-content"><!-- start: blocksft-content -->
                    <?php if ($isDeprecated): ?>
                    <div class="blocksft-deprecated-note">
                        <?php
                        echo ee('CP/Alert')
                            ->makeInline()
                            ->cannotClose()
                            ->asWarning()
                            ->addToBody($blockDefinition->getDeprecatedNote() ?: lang('bloqs_blockdefinition_deprecated_message'))
                            ->render();
                        ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($blockDefinition->getInstructions()): ?>
                        <div class="field-instruct">
                            <em><?= $blockDefinition->getInstructions() ?></em>
                        </div>
                    <?php endif; ?>

                    <div class="blocksft-atoms"><!-- start: blocksft-atoms -->
                        <?php foreach ($blockData['controls'] as $control): ?>
                            <?php
                            if ($control['atom']->definition->shortname === '__hidden') {
                                continue;
                            }

                            $atomType = $control['atom']->definition->type;

                            if ($atomType === 'file') {
                                // Add an additional class that the React FileField code is expecting to find,
                                // otherwise events do not get bound to the correct DOM elements.
                                $atomType = 'file grid-file-upload';
                            }

                            $blocksft_atom_class = 'blocksft-atom';
                            $blocksft_atom_class .= ' grid-'.$atomType;
                            $blocksft_atom_class .= (isset($control['atom']->error)) ?  ' invalid' : '';
                            $blocksft_atom_class .= get_bool_from_string($control['atom']->definition->settings['col_required'] ?? 'n') ? ' required' : '';
                            ?>

                            <div class="<?=$blocksft_atom_class?>"
                                 data-row-id="<?= $block->id  ?>"
                                 data-fieldtype="<?= $control['atom']->definition->type ?>"
                                 data-column-id="<?= $control['atom']->definition->id  ?>"
                                 data-atom-name="<?= $control['atom']->definition->shortname ?>"
                            >
                                <label class="blocksft-atom-name">
                                    <?= $control['atom']->definition->name ?>
                                    <?php $this->embed('hint', ['name' => $control['atom']->definition->shortname]) ?>
                                </label>

                                <?php if (!is_null($control['atom']->definition->instructions) && $control['atom']->definition->instructions != ''): ?>
                                    <label class="blocksft-atom-instructions"><?= $control['atom']->definition->instructions ?></label>
                                <?php endif; ?>

                                <div class="blocksft-atomcontainer grid-<?= $control['atom']->definition->type ?>">
                                    <?php
                                    // Special exception for the new React based drag and drop File field in EE 5.1
                                    if (strpos($control['html'], 'data-file-field-react') !== false) {
                                        echo str_replace('data-file-field-react', 'data-file-field-react-bloqs', $control['html']);
                                    } else {
                                        echo $control['html'];
                                    }
                                    ?>
                                    <div style="clear:both"></div>
                                    <?php if (isset($control['atom']->error)): ?>
                                        <em class="blocks-ee-form-error-message"><?= $control['atom']->error ?></em>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div><!-- end: blocksft-atoms -->
                </div><!-- end: blocksft-content -->

            <?php endif; ?>

        </div><!-- end: txt -->

        <div class="blocksft-insert blocksft-insert--below js-dropdown-toggle">
            <?php if ($fieldSettingNestable === 'y' && $blockDefinition->getNestingRule('no_children') !== 'n'): ?>
                <div class="blocksft-insert--control blocksft-insert--control__below blocksft-insert--control__multi">
                    <a href="#" js-insert-block data-location="below" title="Add bloq after"><i class="fas fa-fw fa-plus"></i></a>
                    <a href="#" js-insert-block data-location="child" title="Add bloq as child"><i class="fas fa-fw fa-level-up-alt"></i></a>
                </div>
            <?php else: ?>
                <div class="blocksft-insert--control blocksft-insert--control__below">
                    <a href="#" js-insert-block data-location="below" title="Add bloq after"><i class="fas fa-fw fa-plus"></i></a>
                </div>
            <?php endif; ?>
        </div>

    </div><!-- end: list-item -->

    <ul class="list-group<?php echo $listGroupClass ?>">
    <?php
    /** @var Block $block */
    if ($block->hasChildren()) {
        foreach ($block->getChildren() as $child) {
            $this->embed('block', [
                'blockData' => $child,
                // If current block is a component notify it's children
                'inComponent' => $isComponent,
                'isEditable' => $isEditable,
            ]);
        }
    }
    ?>
    </ul>

</li>
