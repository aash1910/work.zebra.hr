<?php

use BoldMinded\Bloqs\Entity\Block;

$isComponent = $blockDefinition->isComponent();
if ($isComponent) {
    $componentId = $blockDefinition->getId();
} else {
    $componentId = $componentId ?? false;
}
$inComponent = $inComponent ?? false;
$isEditable = $isEditable ?? $blockDefinition->isEditable();
$isDeprecated = $blockDefinition->isDeprecated();
$isCloneable = isset($block) && $block->isCloneable();

$componentClass = $isComponent && !$isEditable ? ' js-nested-item__is-component js-nested-item__no-children' : '';
$listGroupClass = ($isComponent || $inComponent) && !$isEditable ? ' js-nested-item__no-children' : '';
$listItemClass = ($isComponent || $inComponent) && !$isEditable ? ' js-nested-item__no-children' : '';

if ($isCloneable) {
    $componentClass .= ' js-nested-item__cloneable';
}
?>

<li class="js-nested-item blocksft-block<?php echo $componentClass ?>"
    data-definition-id="<?php echo $blockDefinition->getId() ?>"
    data-definition-settings="<?php echo htmlspecialchars(json_encode($blockDefinition->getSettings()), ENT_QUOTES, 'UTF-8') ?>"
    data-name="<?php echo $blockDefinition->getName() ?>"
    data-shortname="<?php echo $blockDefinition->getShortName() ?>"
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

    <div class="list-item list-item--action blocksft-list-item<?php echo $listItemClass ?>" data-blockvisibility="<?php if ($collapsed) { echo 'collapsed'; } else { echo 'expanded'; } ?>" data-base-name="<?php echo htmlspecialchars($blockDefinitionVars['fieldNames']->baseName) ?>">
        <div class="list-item__handle blocksft-list-item__handle"><i class="fas fa-bars"></i></div>

        <div class="list-item__content">
            <nav class="blocksft-contextmenu">
                <?php if ($isComponentBuilder): ?>
                    <span class="blocksft-cloneable">
                        Cloneable?
                        <button title="Cloneable?" js-toggle-cloneable type="button" class="toggle-btn yes_no off" data-state="off" role="switch">
                            <span class="slider"></span>
                        </button>
                    </span>
                <?php endif; ?>
                <?php if (!$isComponentBuilder): ?>
                    <a class="blocksft-expand-collapse" href="#" title="Expand/Collapse" js-toggle-expand>
                        <i class="fas fa-fw fa-caret-square-<?php if ($collapsed): ?>down<?php else: ?>up<?php endif; ?>"></i>
                    </a>
                    <span class="blocksft-status">
                        <button title="Draft/Live" js-toggle-status type="button" class="toggle-btn yes_no on" data-state="on" role="switch">
                            <span class="slider"></span>
                        </button>
                    </span>
                    <a class="blocksft-clone" href="#" title="Clone" js-clone><i class="fas fa-fw fa-copy"></i></a>
                <?php endif; ?>
                <a class="blocksft-remove<?php if ($confirmBloqRemoval): ?> m-link<?php endif ;?>" data-confirm="<?php echo $blockDefinition->getName() ?>" rel="modal-confirm-remove-bloq" href="#" title="Remove" js-remove><i class="fas fa-fw fa-trash-alt"></i></a>
            </nav>
            <div class="blocksft-header">
                <div class="blocksft-title">
                    <span class="list-item__title">
                        <span class="list-item__title--important"><span class="fas fa-fw fa-exclamation-circle"></span></span>
                        <?php echo $blockDefinition->getName() ?>
                        <span class="list-item__title--draft">(Draft)</span>
                        <?php $this->embed('hint', ['name' => $blockDefinition->getShortName()]) ?>
                    </span>
                    <span class="list-item__summary summary" js-summary></span>
                </div>
            </div>

            <?php if (!$isComponentBuilder): ?>
                <div class="secondary blocksft-content"><!-- start: blocksft-content -->
                    <?php if (!is_null($blockDefinition->getInstructions()) && $blockDefinition->getInstructions() != ''): ?>
                        <div class="field-instruct">
                            <em><?= $blockDefinition->getInstructions() ?></em>
                        </div>
                    <?php endif; ?>

                    <div class="blocksft-atoms"><!-- start: blocksft-atoms -->
                        <?php foreach ($blockDefinitionVars['controls'] as $control): ?>
                            <?php
                            if ($control['atom']->shortname === '__hidden') {
                                continue;
                            }

                            $atomType = $control['atom']->type;

                            if ($atomType === 'file') {
                                // Add an additional class that the React FileField code is expecting to find,
                                // otherwise events do not get bound to the correct DOM elements.
                                $atomType = 'file grid-file-upload';
                            }

                            $blocksft_atom_class = 'blocksft-atom';
                            $blocksft_atom_class .= ' grid-'.$atomType;
                            $blocksft_atom_class .= get_bool_from_string($control['atom']->settings['col_required'] ?? 'no') ? ' required' : '';
                            ?>
                            <div class="<?php echo$blocksft_atom_class?>"
                                 data-fieldtype="<?php echo $control['atom']->type ?>"
                                 data-column-id="<?php echo $control['atom']->id ?>"
                                 data-atom-name="<?php echo $control['atom']->shortname ?>"
                            >
                                <label class="blocksft-atom-name">
                                    <?php echo $control['atom']->name ?>
                                    <?php $this->embed('hint', ['name' => $control['atom']->shortname]) ?>
                                </label>

                                <?php if (!is_null($control['atom']->instructions) && $control['atom']->instructions != ''): ?>
                                    <label class="blocksft-atom-instructions"><?php echo $control['atom']->instructions ?></label>
                                <?php endif; ?>

                                <div class="blocksft-atomcontainer">
                                    <?php
                                    // Special exception for the new React based drag and drop File field in EE 5.1
                                    if (strpos($control['html'], 'data-file-field-react') !== false) {
                                        echo str_replace('data-file-field-react', 'data-file-field-react-bloqs', $control['html']);
                                    } else {
                                        echo $control['html'];
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div><!-- end: blocksft-atoms -->
                </div><!-- end: blocksft-content -->
            <?php endif; ?>

            <input type="hidden" name="<?php echo $blockDefinitionVars['fieldNames']->blockDefinitionId ?>" value="<?php echo $blockDefinition->getId() ?>" />
            <input type="hidden" name="<?php echo $blockDefinitionVars['fieldNames']->order ?>" value="0" data-order-field />
            <input type="hidden" name="<?php echo $blockDefinitionVars['fieldNames']->draft ?>" value="0" class="block-draft" />
            <input type="hidden" name="<?php echo $blockDefinitionVars['fieldNames']->cloneable ?>" value="<?php echo intval($isCloneable) ?>" class="block-cloneable" />

            <?php if ($componentId): ?>
                <input type="hidden" name="<?php echo $blockDefinitionVars['fieldNames']->componentDefinitionId ?>" value="<?php echo $componentId ?>" />
            <?php endif; ?>

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

        </div><!-- list-item__content -->
    </div><!-- end: list-item -->

    <?php
    /**
     * Do we need to render child blocks for a component?
     * @var Block $block
     */
    if (isset($block) && $block->hasChildren()): ?>
        <ul class="list-group<?php echo $listGroupClass ?>">
            <?php
            /** @var Block $child */
            foreach ($block->getChildren() as $child) {
                $this->embed('editor-block-definition', [
                    'block' => $child,
                    'blockDefinition' => $child->getDefinition(),
                    'blockDefinitionVars' => $blockDefinitionsVarsCollection[$child->getDefinition()->getId()],
                    'blockDefinitionsVarsCollection' => $blockDefinitionsVarsCollection,
                    'componentId' => $componentId,
                    'fieldSettingNestable' => $fieldSettingNestable,
                    'isComponentBuilder' => $isComponentBuilder,
                    // If current block is a component notify it's children
                    'inComponent' => $isComponent || $inComponent,
                    'isEditable' => $isEditable,
                ]);
            }
            ?>
        </ul>
    <?php endif; ?>
</li>
