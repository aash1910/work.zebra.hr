<?php use BoldMinded\Bloqs\Entity\BlockDefinition; ?>

<div class="blocksft grid-input-form <?php echo $isModernEE ? 'isModernEE' : ''; ?>"
     data-field-id="<?php echo intval($fieldId) ?>"
     data-setting-nestable="<?php echo $fieldSettingNestable ?>"
>
    <?php if ($isComponentBuilder): ?>
        <div class="blocksft-navigation clear-float">
            <div class="blocksft-navigation__message"></div>
        </div>
    <?php else: ?>
        <div class="blocksft-navigation clear-float">
            <div class="blocksft-navigation__action blocksft-expand-collapse">
                <a href="#" class="expand-all button button--default button--xsmall" js-expandall>Expand All</a>
                <a href="#" class="collapse-all hidden button button--default button--xsmall" js-collapseall>Collapse All</a>
            </div>
            <div class="blocksft-navigation__message"></div>
        </div>
    <?php endif; ?>

    <div id="field_id_<?php echo intval($fieldId) ?>"
         class="blocksft-wrapper grid_field_container <?php echo $fieldSettingNestable === 'y' ? 'nestable' : 'sortable'; ?>--blocksft"><!-- start: blocksft-wrapper -->

        <div class="blocksft-contains-deprecated hidden">
            <?php echo ee('CP/Alert')
                ->makeInline()
                ->cannotClose()
                ->asWarning()
                ->addToBody(lang('bloqs_field_has_deprecated_message'))
                ->render();
            ?>
        </div>

        <!-- Existing Bloq Data -->
        <ul class="list-group <?php if ($fieldSettingNestable === 'y'): ?>list-group-nested<?php endif; ?> blocksft-blocks<?php if ($showEmpty): ?> blocksft-no-results<?php endif; ?>">
            <li class="blocksft-block blocksft-block--utility js-nested-item__disabled blocksft-block--add-root <?php if ($showEmpty): ?> hidden<?php endif; ?>">
                <div class="blocksft-insert blocksft-insert--below js-dropdown-toggle">
                    <div class="blocksft-insert--control blocksft-insert--control__below">
                        <a href="#" js-insert-block data-location="below" title="Add another bloq"><i class="fas fa-fw fa-plus"></i></a>
                    </div>
                </div>
            </li>
            <?php
            foreach ($bloqs as $blockData) {
                /** @var \BoldMinded\Bloqs\Entity\Block $block */
                $block = $blockData['block'];
                $this->embed('block', [
                    'blockData' => $blockData,
                ]);
            }
            ?>
            <li class="blocksft-block blocksft-block--utility blocksft-block--no-results<?php if (!$showEmpty): ?> hidden<?php endif; ?>">
                <div class="list-item no-results">
                    <div class="list-item__content">
                        <div class="blocksft-header">
                            <p>No <b>bloqs</b> found. Add your first!</p>
                        </div>
                    </div>
                </div>
                <div class="blocksft-insert blocksft-insert--below js-dropdown-toggle">
                    <div class="blocksft-insert--control blocksft-insert--control__below">
                        <a href="#" js-insert-block data-location="below" title="Add first bloq"><i class="fas fa-fw fa-plus"></i></a>
                    </div>
                </div>
            </li>
            <li class="blocksft-block blocksft-block--utility js-nested-item__disabled blocksft-block--add-root <?php if ($showEmpty): ?> hidden<?php endif; ?>">
                <div class="blocksft-insert blocksft-insert--above js-dropdown-toggle">
                    <div class="blocksft-insert--control blocksft-insert--control__above">
                        <a href="#" js-insert-block data-location="above" title="Add another bloq"><i class="fas fa-fw fa-plus"></i></a>
                    </div>
                </div>
            </li>
        </ul>

        <!-- Add new Bloq Data -->
        <div class="dropdown dropdown--closed blocksft-filters-menu">
            <div class="sub-menu <?php echo ($menuGridDisplay ? 'grid' : '') ?>">
                <div class="dropdown__search">
                    <div class="filter-search search-input">
                        <input value="" placeholder="filter bloqs" type="text" class="search-input__input blocksft-search-input">
                    </div>
                </div>
                <div class="dropdown__scroll">
                    <?php
                    $prevGroupId = null;

                    foreach ($blockDefinitions as $groupId => $groupedDefinitions) {
                        foreach ($groupedDefinitions as $blockDefinitionVars) {
                            /** @var \BoldMinded\Bloqs\Entity\BlockDefinition $blockDefinition */
                            $blockDefinition = $blockDefinitionVars['definition'];

                            if ($blockDefinition->isDeprecated()
                                || $blockDefinition->isHidden()
                                || $blockDefinition->isHiddenInMenu()
                            ) {
                                continue;
                            }

                            // Only use groups if there are more than 1. E.g. no sense in showing "Ungrouped" if it is the only group.
                            if (count($blockGroups) > 1 && $groupId !== $prevGroupId) {
                                // Close any open divs
                                if ($prevGroupId !== null) {
                                    echo '</div>';
                                }

                                echo '<div class="blocksft-filters-menu__group-label">' . $blockGroups[$blockDefinition->getGroupId()] . '</div>';
                                echo '<div class="blocksft-filters-menu__group">';
                            }

                            $extraClass = $blockDefinition->isComponent() ? 'dropdown__link--component' : '';
                            $instructions = $blockDefinition->getInstructions() ? '<div class="dropdown__link--instructions">' . strip_tags($blockDefinition->getInstructions()) . '</div>' : '';
                            $prevGroupId = $blockDefinition->getGroupId();
                            $previewImage = '';

                            if ($blockDefinition->getPreviewImage()) {
                                $previewImage = sprintf('<img src="%s" class="blocksft-block-preview-thumb" />', $blockDefinitionVars['previewImageParsed']);
                            }
                            if ($blockDefinition->getPreviewIcon()) {
                                $previewImage = sprintf('<i class="fa-fw fa-%s"></i>', $blockDefinitionVars['previewIconParsed']);
                            }

                            echo '<a class="dropdown__link '. $extraClass .'" href="#" js-newblock data-template="' . $blockDefinitionVars['componentId'] . '" data-validate="'. ($isComponentBuilder ? 'false' : 'true') .'" data-location="bottom" data-definition-id="'. $blockDefinition->getId() .'">' . $previewImage . '<div class="dropdown__link--title"><span class="dropdown__link--bloq-name">' . $blockDefinition->getName() . '</span>' . $instructions . '</div></a>';
                        }
                    }

                    // Close any open divs
                    if (count($blockGroups) > 1 && $prevGroupId !== null) {
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <input type="hidden" name="field_id_<?php echo intval($fieldId) ?>[tree_order]" class="tree" value="" />
        <input type="hidden" name="version_number" value="<?php echo ee()->input->get('version') ?>" />
        <input type="hidden" name="<?php echo $formSecretFieldName ?>" value="<?php echo $formSecret ?>" />
    </div>

    <!-- HTML Templates for new bloqs -->
    <div class="blockDefinitions" data-definitions="<?php echo $jsonDefinitions ?>" style="display: none;">
        <?php
        foreach($blockDefinitions as $groupId => $groupedDefinitions):
            foreach ($groupedDefinitions as $blockDefinitionVars):
                /** @var BlockDefinition $definition */
                $blockDefinition = $blockDefinitionVars['definition'];

                if ($blockDefinition->isDeprecated()) {
                    continue;
                }
                ?>
                <div id="<?php echo $blockDefinitionVars['componentId'] ?>">
                    <?php
                    $vars = [
                        'blockDefinitionVars' => $blockDefinitionVars,
                        'blockDefinition' => $blockDefinition,
                        'fieldSettingNestable' => $fieldSettingNestable,
                        'isComponentBuilder' => $isComponentBuilder,
                        'collapsed' => false,
                    ];

                    // Do we have a component with child blocks? If so then update the vars so
                    // editor-block-definition can recurse the children.
                    if ($blockDefinition->isComponent()) {
                        foreach ($blockDefinition->getBlocks() as $componentBlock) {
                            $vars['block'] = $componentBlock;
                            $vars['collapsed'] = true;
                            $vars['blockDefinition'] = $componentBlock->getDefinition();
                            $vars['blockDefinitionVars'] = $blockDefinitionsVarsCollection[$componentBlock->getDefinition()->getId()];
                            $vars['blockDefinitionsVarsCollection'] = $blockDefinitionsVarsCollection;
                            $this->embed('editor-block-definition', $vars);
                        }
                    } else {
                        $this->embed('editor-block-definition', $vars);
                    }
                    ?>
                </div><!-- end: blocksft-block -->
            <?php
            endforeach;
        endforeach;
        ?>
    </div>

</div><!-- end: blocksft grid-publish -->
