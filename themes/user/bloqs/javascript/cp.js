;jQuery(function ($) {

    var summarizers = {
        'text': function ($atomContainer) {
            return $atomContainer.find('input').val();
        },
        'date': function ($atomContainer) {
            return $atomContainer.find('input').val();
        },
        'email_address': function ($atomContainer) {
            return $atomContainer.find('input').val();
        },
        'url': function ($atomContainer) {
            return $atomContainer.find('input').val();
        },
        'wygwam': function ($atomContainer) {
            return $atomContainer.find('textarea').first().val();
        },
        'file': function ($atomContainer) {
            // Account for EE4 and EE3 File class names
            return $atomContainer.find('.fields-upload-chosen-file img, .file-chosen img').attr('alt');
        },
        'relationship': function ($atomContainer) {
            var relateFields = $atomContainer.find('[data-relationship-react]');

            // If multi...
            if (relateFields.length) {
                var text = [];

                relateFields.each(function () {
                    var selectedRelationships = $(this).find('.list-item__title');

                    if (selectedRelationships.length) {
                        selectedRelationships.each(function () {
                            text.push($(this).text());
                        });
                    }
                });

                if (text.length === 0) {
                    return '';
                }

                return text.join(', ');
            }
        },
        'textarea': function ($atomContainer) {
            return $atomContainer.find('textarea').val();
        },
        'rte': function ($atomContainer) {
            return $atomContainer.find('textarea').val();
        }
    };

    var dragClass = 'list-group--dragging';
    var handleClass = 'blocksft-list-item__handle'; // be explicit as to not interfere with child draggable lists
    var itemClass = 'js-nested-item';
    var listClass = 'list-group';
    var placeClass = 'drag-placeholder__nestable';

    var nestableFieldOptions = {
        rootClass: 'nestable--blocksft',
        listNodeName: 'ul',
        listNodeClassName: '.list-group-nested',
        listClass: listClass,
        itemNodeName: 'li',
        itemClassName: '.blocksft-block',
        itemClass: itemClass,
        dragClass: dragClass,
        handleClass: handleClass,
        placeClass: placeClass,
        expandBtnHTML: '',
        collapseBtnHTML: '',
        maxDepth: 10,
        noChildrenClass: itemClass + '__no-children',
    };

    $(window).resize(function () {
        $('.blocksft-insert--control').trigger('blocks.resize');
    });

    $(document).keyup(function (e) {
        if (e.key === "Escape") {
            hideAllFilterMenus();
        }
    });

    var $bloqsContext = null;

    var showFilterMenu = function ($control, $filterMenu, location) {
        hideAllFilterMenus();
        // Since we're using the same menu and moving it around the page, update the current context.
        $bloqsContext = $control.closest('.blocksft-block');

        if ($bloqsContext.length === 0) {
            $bloqsContext = $control.closest('.blocksft-block--no-results');
        }

        $control
            .closest('.blocksft-insert--control')
            .addClass('visible')
            .on('blocks.resize', function () {
                $filterMenu.offset(getPosition($control, $filterMenu.find('.sub-menu')));
            })
        ;

        $filterMenu
            .find('[data-location]')
            .attr('data-location', location)
        ;

        $filterMenu
            .removeClass('dropdown--closed hidden')
            .addClass('dropdown--open visible')
            .offset(getPosition($control, $filterMenu.find('.sub-menu')))
        ;

        if ($bloqsContext.closest('.blocksft').data('settingNestable') !== 'y') {
            $filterMenu.find('.dropdown__link').removeClass('disabled');
            return;
        }

        var $field = $bloqsContext.closest('.blocksft');
        var blockDefinitions = $field.find('.blockDefinitions').data('definitions');
        var nestedSet = asNestedSet($field);

        $filterMenu.find('.dropdown__link').each(function () {
            var menuItem = $(this);
            var menuValidation = new MenuValidation($field, blockDefinitions, nestedSet);
            var result = menuValidation.validate(menuItem, $bloqsContext, location);

            // Reset everything. The status will changed based on the context
            menuItem.removeClass('disabled');

            if (result === false) {
                menuItem.addClass('disabled');
            }
        });
    };

    /**
     * @param originElement Element to get origin
     * @param targetElement Element we're setting position of in relation to the origin
     * @returns {{top: *, left: number}}
     */
    var getPosition = function (originElement, targetElement) {
        var width = targetElement.width();
        var originPos = originElement.offset();
        var leftOffset = 14; // half the width of the + control button
        var topOffset = 36;

        return {
            left: (originPos.left - (width / 2)) + leftOffset,
            top: originPos.top + topOffset
        };
    };

    var filterMenuHandler = function(event) {
        // if the target is a descendent of container do nothing
        if ($(event.target).is('.blocksft-insert--control, .blocksft-insert--control *, .blocksft-filters-menu *')) return;

        hideAllFilterMenus();
    };

    /**
     * Use hidden/visible and closed/open to support EE5 and EE6
     */
    var hideAllFilterMenus = function () {
        var $filterMenu = $('.blocksft-filters-menu');

        $filterMenu
            .addClass('dropdown--closed hidden')
            .removeClass('dropdown--open visible')
            .find('.sub-menu .search-input__input, .sub-menu .filter-search input')
            .val('')
        ;

        // Reset the visibility of all options and group labels
        $filterMenu
            .find('li, .dropdown__link, .blocksft-filters-menu__group-label')
            .show();

        $('.blocksft-insert--control')
            .removeClass('visible')
        ;
    };

    $(document).on("click", filterMenuHandler);

    if ('publisher' in EE) {
        $('.form-standard > form:first-child')
            .find('.button--primary[name="submit"]')
            .bind('click', function (e) {
                $(this).css('pointer-events', 'none');
            });
    }

    $('.blocksft').each(function () {
        var $blocksFieldType = $(this);
        var blocksFieldId = $blocksFieldType.data('field-id');
        var $blocks = $blocksFieldType.find('.blocksft-blocks');
        var $filterMenu = $blocksFieldType.find('.blocksft-filters-menu');
        var $blockCollection = $blocks.find('.blocksft-block:visible').not('.blocksft-block--utility');

        determineUtilityControlsInitialState($blocksFieldType);

        // Set the iterator for new blocks added to the page
        $blocksFieldType.data('newBlockCount', $blockCollection.length + 1);

        $blocks.on('click', '[js-insert-block]', function (e) {
            e.preventDefault();
            var $block = $(this);
            showFilterMenu($block, $filterMenu, $block.data('location'));
        });

        $blocksFieldType.on('click', '[js-newblock]', function (e) {
            e.preventDefault();
            var newButton = $(this);
            var templateId = newButton.attr('data-template');
            var template = $blocksFieldType.find('.blockDefinitions #' + templateId + ' > .blocksft-block');
            var location = newButton.attr('data-location');

            createBlock($blocksFieldType, $blocks, location, $bloqsContext, blocksFieldId, template);
        });

        $blocksFieldType.on('click', '[js-clone]', function (e) {
            e.preventDefault();
            var cloneButton = $(this);
            var location = cloneButton.attr('data-location');
            var originalBlock = cloneButton.closest('.blocksft-block');
            $bloqsContext = originalBlock;

            createBlock($blocksFieldType, $blocks, location, $bloqsContext, blocksFieldId, originalBlock, true);
        });

        fireEvent("display", $blocks.find('[data-fieldtype]'));

        $blocksFieldType.closest('fieldset').find('.field-instruct').addClass('field-instruct--blocksft');

        // Callback for ajax form validation
        if (blocksFieldId !== '') {
            EE.cp && void 0 !== EE.cp.formValidation.bindCallbackForField && EE.cp.formValidation.bindCallbackForField('field_id_' + blocksFieldId, _bloqsValidationCallback);
        }

        // Honestly I think the checkbox validation is broken.
        // https://github.com/ExpressionEngine/ExpressionEngine/issues/1224
        $.each($blockCollection, function () {
            // @todo tring to fix https://boldminded.com/support/ticket/3141
            // var $atoms = $blocksFieldType.find('.blocksft-atom');
            // if ($atoms.length) {
            //     $atoms.each(function () {
            //         var $atom = $(this);
            //         var name = $atom.find('input:eq(0)[name^=field_id_]').attr('name');
            //         EE.cp.formValidation.bindInputs($atom);
            //     });
            // }

            if ($blocksFieldType.find('.blocksft-atom[data-fieldtype="checkboxes"]').length) {
                EE.cp && void 0 !== EE.cp.formValidation.bindCallbackForField && EE.cp.formValidation.bindCallbackForField('field_id_' + blocksFieldId + '[]', _bloqsCheckboxValidationCallback);
            }
        });

        // This is a more logical fix, but doesn't appear to work (see above comment)
        // $blocks.find('.blocksft-atom[data-fieldtype="checkboxes"]').each(function() {
        //     var $checkboxField = $(this);
        //     // Find the hidden field inside of a React component
        //     var $hiddenField = $checkboxField.find('input[type=hidden]:eq(0)[name^=field_id_]');
        //     var hiddenFieldName = $hiddenField.attr('name');
        //     EE.cp && void 0 !== EE.cp.formValidation.bindCallbackForField && EE.cp.formValidation.bindCallbackForField(hiddenFieldName, _bloqsCheckboxValidationCallback);
        // });

        validateDeprecatedOnLoad($blocksFieldType);

        var isNestable = $blocksFieldType.data('setting-nestable');
        var $blocksContainer;

        if (isNestable === 'y') {
            $blocksContainer = $blocksFieldType.find('.nestable--blocksft');

            bindNestedSortable($blocksContainer);

            // Set the tree data on first load based on the current nesting.
            // Calling this now wasn't necessary until Live Preview came around.
            updateTreeField($blocksContainer);

            // Update the tree data again on form submit to ensure the latest tree data,
            // if modified since loading, is submitted.
            $blocksFieldType.closest('form').on('submit', function () {
                updateTreeField($blocksContainer, true);
            });

            // Same for when clicking the Live Preview.
            $blocksFieldType.closest('form').find('button[rel="live-preview"]').on('click', function () {
                updateTreeField($blocksContainer, true);
            });
        } else {
            bindSortable($blocksFieldType.find('.sortable--blocksft'));
        }

        $blocks.on('click', '[js-remove]', function (e) {
            e.preventDefault();

            var button = $(this);
            var block = button.closest('.blocksft-block');
            var shortName = block.data('shortname');
            var siblings = block
                .siblings('.js-nested-item__cloneable[data-shortname="' + shortName + '"]')
                .not('.deleted')
            ;

            var $confirmModal = $('.' + button.attr('rel'));

            if (block.hasClass('js-nested-item__cloneable') && siblings.length === 0) {
                $('.dialog__body', $confirmModal)
                    .html(EE.bloqs.confirm_removal_cloneable_desc);

                $confirmModal.trigger("modal:open");

                $('button', $confirmModal)
                    .attr('data-submit-text', EE.bloqs.btn_close)
                    .attr('value', EE.bloqs.btn_close)
                    .text(EE.bloqs.btn_close)
                    .unbind('click')
                    .on('click', function (e) {
                        e.preventDefault();
                        $confirmModal.trigger("modal:close");
                    });

                return;
            }

            if (EE.bloqs.confirmBloqRemoval === true) {
                $('.dialog__body', $confirmModal)
                    .html(EE.bloqs.confirm_removal_desc + '<ul class="checklist"><li>' + button.data('confirm') + '</li></ul>');

                $('button', $confirmModal)
                    .attr('data-submit-text', EE.bloqs.btn_confirm_and_remove)
                    .attr('value', EE.bloqs.btn_confirm_and_remove)
                    .text(EE.bloqs.btn_confirm_and_remove)
                    .unbind('click')
                    .on('click', function (e) {
                        e.preventDefault();
                        $confirmModal.trigger("modal:close");
                        removeBlock(block);
                    });

                return;
            }

            removeBlock(block);
        });

        /**
         * @param {jQuery} block
         */
        var removeBlock = function (block) {
            var deletedInput = block.find('[data-deleted-field]');

            clearErrorsOnBlock(block);

            if (deletedInput.length) {
                deletedInput.val('true');
                block.addClass('deleted');
                block.find('[data-order-field]').remove();
            } else {
                block.remove();
            }

            hideUtilityControls($blocks);
            reorderFields($blocksFieldType);
            updateTreeField($blocksFieldType);
            // validateDeprecatedOnRemove($blocksFieldType);

            $(document).trigger('removeBloq.bloqs', [$blocks, block]);
        };

        $blocks.on('click', '[js-toggle-expand]', function (e) {
            e.preventDefault();
            var $button = $(this);
            var $block = $button.closest('.blocksft-block');
            var visibility = $block.find('> .list-item').attr('data-blockvisibility');

            if (visibility === 'expanded') {
                collapseBlock($block);
            } else {
                expandBlock($block);
            }
        });

        $blocks.on('mousedown', '.blocksft-header', function (e) {
            // Don't prevent default on the drag handle.
            if ($(e.target).is('.blocksft-block-handle')) {
                return;
            }

            // Prevent default so we don't highlight a bunch of stuff when double-clicking.
            e.preventDefault();
        });

        $blocks.on('dblclick', '.blocksft-header', function (e) {
            var $block = $(this).closest('.blocksft-block');
            var visibility = $block.find('> .list-item').attr('data-blockvisibility');

            if (visibility === 'expanded') {
                collapseBlock($block);
            } else {
                expandBlock($block);
            }
        });

        $blocksFieldType.on('click', '[js-expandall]', function (e) {
            e.preventDefault();
            $blocks.find('.blocksft-block').each(function () {
                expandBlock($(this));
            });
            $(this).addClass('hidden');
            $(this).next('.collapse-all').removeClass('hidden');
        });

        $blocksFieldType.on('click', '[js-collapseall]', function (e) {
            e.preventDefault();
            $blocks.find('.blocksft-block').each(function () {
                collapseBlock($(this));
            });
            $(this).addClass('hidden');
            $(this).prev('.expand-all').removeClass('hidden');
        });

        $blocks.on('click', '[js-toggle-status]', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var $block = $(this).closest('.blocksft-block');
            var isOn = $btn.hasClass('on');

            // Now add children
            var $children = $block.find('.blocksft-block');
            var $childToggleBtns = $children.find('[js-toggle-status]');
            $block = $block.add($children);

            if (isOn) {
                setBlockDraft($block);

                if (!$childToggleBtns.hasClass('off')) {
                    $childToggleBtns.removeClass('on');
                    $childToggleBtns.addClass('off');
                }
            } else {
                setBlockLive($block);

                if (!$childToggleBtns.hasClass('on')) {
                    $childToggleBtns.removeClass('off');
                    $childToggleBtns.addClass('on');
                }
            }
        });

        $blocks.on('click', '[js-toggle-cloneable]', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var $block = $(this).closest('.blocksft-list-item');
            var isOn = $btn.hasClass('on');

            if (isOn) {
                setBlockNonCloneable($block);
            } else {
                setBlockCloneable($block);
            }
        });

        $blocks.find('.blocksft-block').each(function () {
            var block = $(this);
            summarizeBlock(block);

            // Special exception for the new React based drag and drop File field in EE 5.1
            block.find('div[data-file-field-react-bloqs]').each(function () {
                var $field = $(this);

                $field
                    .attr('data-file-field-react', $field.attr('data-file-field-react-bloqs'))
                    .removeAttr('data-file-field-react-bloqs');

                // Call native EE function to re-instantiate a new instance of the React field.
                setupFileField($field.closest('.blocksft-atomcontainer'), false);
            });
        });

    }); // $('.blocksft').each

    /**
     * @param {jQuery} $fieldContainer
     */
    function bindNestedSortable($fieldContainer)
    {
        var $blocks = $fieldContainer.find('.blocksft-blocks');

        $blocks.nestedSortable({
            handle: '.blocksft-list-item__handle',
            items: '.js-nested-item',
            disabledClass: 'js-nested-item__disabled',
            disableNestingClass: 'js-nested-item__no-children',
            toleranceElement: '> div',
            listType: 'ul',
            listClass: 'list-group',

            placeholder: {
                element: function (currentItem) {
                    var height = currentItem[0].offsetHeight;
                    var margin = currentItem.css('margin');
                    return $('<li><div class="drag-placeholder"><div class="none" style="height: ' + height + 'px; margin: ' + margin + '"></div></div></li>')[0];
                },
                update: function (container, p) {
                    return;
                }
            },
            start: function (event, ui) {
                var $block = $(ui.item);

                if (EE.bloqs.collapseOnDrag === true) {
                    // Dragging and sorting expanded blocks is awkward
                    $blocks.each(function () {
                        collapseBlock($(this));
                    });
                }

                $block.find('[data-fieldtype]').each(function () {
                    fireEvent('beforeSort', $(this));
                });
            },
            beforeStop: function (event, ui) {
                // Pass the turn...
            },
            update: function (event, ui) {
                // If a text field inside of a block is updated this on change event will trigger,
                // but we only want to take action if a block position moved, so make sure the changed
                // element is a block, not a child field.
                var $block = $(ui.item);

                if ($block) {
                    $block.find('[data-fieldtype]').each(function () {
                        fireEvent('afterSort', $(this));
                    });
                    updateTreeField($fieldContainer);
                }
            },
            isAllowed: function (placeholder, parentItem, currentItem) {
                // If it's not a cloneable block, then dragging anywhere is allowed.
                if (!currentItem.hasClass('js-nested-item__cloneable')) {
                    return true;
                }

                var oldParent = currentItem.parent().parent();

                // If the parent has changed, then prevent the drop action.
                return oldParent.is(parentItem);
            }
        });
    }

    /**
     * @param {jQuery} $fieldContainer
     */
    function bindSortable($fieldContainer)
    {
        var $blocks = $fieldContainer.find('.blocksft-blocks');

        $fieldContainer.sortable({
            axis: 'y',  // Only allow vertical dragging
            handle: '.blocksft-list-item__handle', // Set drag handle
            items: '.js-nested-item', // Only allow these to be sortable
            sort: EE.sortable_sort_helper,
            forcePlaceholderSize: true,
            placeholder: {
                element: function (currentItem) {
                    var height = currentItem[0].offsetHeight;
                    var margin = currentItem.css('margin');
                    return $('<li><div class="drag-placeholder"><div class="none" style="height: ' + height + 'px; margin: ' + margin + '"></div></div></li>')[0];
                },
                update: function (container, p) {
                    return;
                }
            },
            start: function (event, ui) {
                var block = $(ui.item);

                if (EE.bloqs.collapseOnDrag === true) {
                    // Dragging and sorting expanded blocks is awkward
                    $blocks.find('.blocksft-block').each(function () {
                        collapseBlock($(this));
                    });
                }

                block.find('[data-fieldtype]').each(function () {
                    fireEvent('beforeSort', $(this));
                });
            },
            stop: function (event, ui) {
                var block = $(ui.item);
                block.removeAttr('style'); // sorting adds inline styles, which mess up the <li> positioning the 2nd time a block has been sorted.
                block.find('[data-fieldtype]').each(function () {
                    fireEvent('afterSort', $(this));
                });
            },
            update: function (event, ui) {
                reorderFields($fieldContainer);
                // Non-nestable fields that contain bloq components might actually contain nested data in the component
                updateTreeField($fieldContainer);
            }
        });
    }

    /**
     * @param {jQuery} $blocksField
     */
    function showUtilityControls($blocksField)
    {
        $blocksField.find('.blocksft-block--no-results').addClass('hidden');
        $blocksField.find('.blocksft-block--add-root').removeClass('hidden');
    }

    /**
     * @param {jQuery} $blocksField
     */
    function hideUtilityControls($blocksField)
    {
        if ($blocksField.find('.blocksft-block:visible').not('.blocksft-block--utility').length === 0) {
            $blocksField.find('.blocksft-block--no-results').removeClass('hidden');
            $blocksField.find('.blocksft-block--add-root').addClass('hidden');
        }
    }

    /**
     * Depending on the blocks on load, show/hide the utility controls
     * @param {jQuery} $blocksField
     */
    function determineUtilityControlsInitialState($blocksField)
    {
        if ($blocksField.find('.blocksft-block:visible').not('.blocksft-block--utility').length > 0) {
            showUtilityControls($blocksField);
        } else {
            hideUtilityControls($blocksField);
        }
    }

    /**
     * @param {jQuery} $element
     * @param {boolean} isFormSubmit
     */
    function updateTreeField($element, isFormSubmit) {
        var $treeField = $element.find('.tree');

        $(document).trigger('entry:preview');

        if (!$treeField.length) {
            return;
        }

        var nestedSet = asNestedSet($element);
        var $field = $element.closest('.blocksft');

        $treeField.val(JSON.stringify(nestedSet));

        // If we're submitting the form then it's too late to present validation errors
        if (!isFormSubmit) {
            var blockDefinitions = $field.find('.blockDefinitions').data('definitions');
            var treeValidation = new TreeValidation($field, blockDefinitions, nestedSet);
            treeValidation.clearErrorMessages();

            $element.find('.blocksft-block').each(function () {
                var $block = $(this);
                treeValidation.validate($block);
            });
        }
    }

    /**
     * Lifted from https://github.com/RamonSmit/Nestable2 and slightly modified
     *
     * @param list
     * @returns {array}
     */
    function asNestedSet(list) {
        var o = nestableFieldOptions, depth = -1, ret = [], lft = 1;
        // Be careful here, we don't want to select the <li> elements in the error alert div
        var items = list.find(o.listNodeClassName).first().children(o.itemClassName).not('.blocksft-block--utility');

        items.each(function () {
            lft = traverse(this, depth + 1, lft);
        });

        ret = ret.sort(function(a,b){ return (a.lft - b.lft); });
        return ret;

        function traverse(item, depth, lft) {
            var rgt = lft + 1,
                id,
                parentId,
                definitionId,
                parentDefinitionId,
                name,
                displayName
            ;

            if ($(item).children(o.listNodeName).children(o.itemClassName).length > 0 ) {
                depth++;
                $(item).children(o.listNodeName).children(o.itemClassName).each(function () {
                    rgt = traverse($(this), depth, rgt);
                });
                depth--;
            }

            id = $(item).attr('data-id');
            if (!isNaN(id)) {
                id = parseInt(id);
            }

            parentId = $(item).parent(o.listNodeName).parent(o.itemClassName).attr('data-id') || null;
            if (parentId !== null && !isNaN(parentId)) {
                parentId = parseInt(parentId);
            }

            definitionId = parseInt($(item).attr('data-definition-id'));
            parentDefinitionId = $(item).parent(o.listNodeName).parent(o.itemClassName).attr('data-definition-id') || null;

            if (parentDefinitionId) {
                parentDefinitionId = parseInt(parentDefinitionId);
            }

            name = $(item).attr('data-name');

            if (id) {
                ret.push({
                    "id": id,
                    "name": name,
                    "definition_id": definitionId,
                    "parent_id": parentId,
                    "parent_definition_id": parentDefinitionId,
                    "depth": depth,
                    "lft": lft,
                    "rgt": rgt
                });
            }

            lft = rgt + 1;
            return lft;
        }
    }

    /**
     * @param {jQuery} blocksFieldType
     * @param {jQuery} blocks
     * @param {string} location
     * @param {jQuery} context
     * @param {int} blocksFieldId
     * @param {jQuery} originalBlock
     * @param {bool} isCloneAction
     */
    function createBlock(blocksFieldType, blocks, location, context, blocksFieldId, originalBlock, isCloneAction) {
        var blockClone = originalBlock.clone();
        var newBlockCount = blocksFieldType.data('newBlockCount');
        var children = blockClone.find('.blocksft-list-item');
        // Remove the ID field, otherwise cloning of existing blocks will not work.
        blockClone.find('[js-block-id-field], [js-deleted-field]').remove();

        // Buckle up, potentially a lot of iterations happening...
        $(children).each(function () {
            var block = $(this);
            var baseName = block.attr('data-base-name');
            var newBlockId = 'blocks_new_block_' + newBlockCount;

            // Be sure to update the parent li with the data-id, otherwise asNestedSet will fail miserably.
            block.closest('li.blocksft-block').attr('data-id', newBlockId);
            block.find(':input').removeAttr("disabled");

            if (baseName && baseName.match(/blocks_block_id_\d+/g)) {
                block.attr('data-base-name', baseName.replace(/blocks_block_id_\d+/g, newBlockId));
            }
            if (baseName && baseName.match(/blocks_new_block_\d+/g)) {
                block.attr('data-base-name', baseName.replace(/blocks_new_block_\d+/g, newBlockId));
            }

            // Find all child elements, could be inputs, or other divs with data attributes referenced in JS etc
            // Filter out the dropdown fields, which in some cases may contain hundreds or thousands of entries
            // in a Relationship field, but we only care about the hidden input that the dropdown updates.
            // In a test of 6000 entries in a Relationship field, this is the difference of it filtering ~50
            // HTML elements vs ~20,000. Yikes. https://boldminded.com/support/ticket/3021
            block.find('*').not('.dropdown *').each(function () {
                var element = $(this);
                var name = element.attr('name');

                if (name && name.match(/blocks_block_id_\d+/g)) {
                    element.attr('name', name.replace(/blocks_block_id_\d+/g, newBlockId));
                }
                if (name && name.match(/blocks_new_block_\d+/g)) {
                    element.attr('name', name.replace(/blocks_new_block_\d+/g, newBlockId));
                }

                // Account for React fields
                var inputValue = element.attr('data-input-value');

                if (inputValue && inputValue.match(/blocks_block_id_\d+/g)) {
                    element.attr('data-input-value', inputValue.replace(/blocks_block_id_\d+/g, newBlockId));
                }
                if (inputValue && inputValue.match(/blocks_new_block_\d+/g)) {
                    element.attr('data-input-value', inputValue.replace(/blocks_new_block_\d+/g, newBlockId));
                }
            });

            newBlockCount++;
        });

        // If we're adding or cloning a component, the highlighter class needs to be on a different div
        if (blockClone.hasClass('js-nested-item__is-component')) {
            blockClone.addClass('highlighter');

            // and lets automatically expand the component block so it reveals it's fields and child blocks,
            // but only if we're adding a new one. If cloning we want it to appear in the same state.
            if (!isCloneAction) {
                expandBlock(blockClone);
            }
        }

        blockClone
            .find(':input').removeAttr("disabled")
            .end()
            .find('.list-item')
            .addClass('highlighter')
        ;

        blocksFieldType.data('newBlockCount', newBlockCount);

        showUtilityControls(blocksFieldType);
        prepareReactFields(blockClone);
        removeHighlighter(blockClone);
        _bindEventsToNewBlock(blocksFieldType, blocks, location, context, blocksFieldId, blockClone, isCloneAction);
    }

    /**
     * @todo I'm not 100% sure this code is relevant anymore? 4/12/22
     * @param {jQuery} blockClone
     */
    function prepareReactFields(blockClone) {
        blockClone.find('input, div[input-data-value]').each(function () {
            var elements = [].filter.call(this.attributes, function(at) {
                return /^data-/.test(at.name) && /-react$/.test(at.name);
            });

            if (elements.length) {
                $(this)
                    .clone()
                    .attr('disabled', 'disabled')
                    .addClass('react-component-clone')
                    .insertBefore($(this));
            }
        });
    }

    function _bindEventsToNewBlock(blocksFieldType, blocks, location, context, blocksFieldId, blockClone, isCloneAction) {
        hideAllFilterMenus();

        if (!location) {
            location = 'below';
        }

        switch (location) {
            case 'above':
                context.before(blockClone);
                break;
            case 'below':
                context.after(blockClone);
                break;
            case 'child':
                var childListGroup = context.find('> .list-item + .list-group');

                // If the current block has a nested child list group, add it to the end as a new child.
                // If not, then create a new nested child list group and add it.
                if (childListGroup.length) {
                    childListGroup.prepend(blockClone);
                } else {
                    var $listGroup = $('<ul class="list-group"></ul>');
                    $listGroup.append(blockClone);
                    context.append($listGroup);
                }

                break;
        }

        if (isCloneAction) {
            fireEvent('clone', blockClone.find('[data-fieldtype]'));
        } else {
            fireEvent('display', blockClone.find('[data-fieldtype]'));
        }

        // Special exception for the new React based drag and drop File field in EE 5.1
        blockClone.find('div[data-file-field-react-bloqs]').each(function () {
            var $field = $(this);

            $field
                .attr('data-file-field-react', $field.attr('data-file-field-react-bloqs'))
                .removeAttr('data-file-field-react-bloqs');

            // Call native EE function to re-instantiate a new instance of the React field.
            setupFileField($field.closest('.blocksft-atomcontainer'), true);
        });

        var textArea = blockClone.find('.grid-textarea');
        if (typeof textArea !== undefined && textArea !== '' && $.isFunction($.fn.FilePicker)) {
            $('.textarea-field-filepicker, li.html-upload', textArea).FilePicker({
                callback: filePickerCallback
            });
        }

        EE.cp && void 0 !== EE.cp.formValidation && EE.cp.formValidation.bindInputs(blockClone);
        EE.cp && void 0 !== EE.cp.formValidation.bindCallbackForField && EE.cp.formValidation.bindCallbackForField('field_id_' + blocksFieldId, _bloqsValidationCallback);

        reorderFields(blocksFieldType);
        updateTreeField(blocksFieldType);
    }

    function removeHighlighter(block) {
        // Remove the highlighter class, otherwise it will flash again when moved/re-ordered
        setTimeout(function () {
            block.find('.list-item').removeClass('highlighter');
        }, 3000);
    }

    /**
     * @param {jQuery} $container
     * @param {boolean} resetOnSetup
     */
    function setupFileField($container, resetOnSetup) {
        if (resetOnSetup) {
            resetFileFigure($container.find('.fields-upload-chosen'));
        }

        $('.file-field-filepicker', $container).FilePicker({
            callback: EE.FileField.pickerCallback
        });

        $('li.remove a').click(function (e) {
            resetFileFigure($(this).closest('.fields-upload-chosen'));
            e.preventDefault();
        });

        // Drag and drop component
        FileField.renderFields($container);
    }

    /**
     * @param {jQuery} $figureContainer
     */
    function resetFileFigure($figureContainer) {
        $figureContainer.addClass('hidden');
        $figureContainer.siblings('em').remove(); // Hide the "missing file" error
        $figureContainer.siblings('input[type="hidden"]').val('').trigger('change');
        $figureContainer.siblings('.fields-upload-btn').removeClass('hidden');
    }

    /**
     * Set the order value for all of the fields.
     */
    function reorderFields(blocksFieldType) {
        var order = 1;
        blocksFieldType.find('[data-order-field]').each(function () {
            $(this).val(order);
            order++;
        });
    }

    /**
     * @param {jQuery} $block
     */
    function collapseBlock($block) {
        var $listItem = getBlockListItem($block);

        $listItem
            .attr('data-blockvisibility', 'collapsed');

        $listItem
            .find('[js-toggle-expand] > i')
            .removeClass('fa-caret-square-up fa-caret-square-up')
            .addClass('fa-caret-square-down');

        summarizeBlock($block);
    }

    /**
     * @param {jQuery} $block
     */
    function expandBlock($block) {
        var $listItem = getBlockListItem($block);

        $listItem
            .attr('data-blockvisibility', 'expanded');

        $listItem
            .find('[js-toggle-expand] > i')
            .removeClass('fa-caret-square-up fa-caret-square-down')
            .addClass('fa-caret-square-up');
    }

    /**
     * @param {jQuery} $block
     */
    function setBlockDraft($block) {
        $block.find('> .list-item .block-draft').val('1');
        $block.addClass('block-draft');

        if (EE.bloqs.eeVersion <= 5) {
            $block.find('> .list-item .blocksft-contextmenu .toggle-btn').toggleClass('on off');
        }
    }

    /**
     * @param {jQuery} $block
     */
    function setBlockLive($block) {
        $block.find('> .list-item .block-draft').val('0');
        $block.removeClass('block-draft');

        if (EE.bloqs.eeVersion <= 5) {
            $block.find('> .list-item .blocksft-contextmenu .toggle-btn').toggleClass('off on');
        }
    }

    /**
     * @param {jQuery} $block
     */
    function setBlockCloneable($block) {
        $block.find('.block-cloneable').val('1');
    }

    /**
     * @param {jQuery} $block
     */
    function setBlockNonCloneable($block) {
        $block.find('.block-cloneable').val('0');
    }

    function stripHtml(string) {
        if (!string) {
            return '';
        }

        return string.replace(/<\/?[^>]+(>|$)/g, '');
    }

    /**
     * @param {jQuery} $block
     */
    function summarizeBlock($block) {
        var isComponent = $block.hasClass('js-nested-item__is-component');
        var $listItem = getBlockListItem($block);
        var summary = '';

        if (isComponent) {
            summary = createSummary(summarizeListItem($block), 60);
        } else {
            summary = createSummary(summarizeListItem($listItem), 30);
        }

        if (summary) {
            $listItem.find('[js-summary]').text(summary);
        }
    }

    /**
     * @param {jQuery} $listItem
     * @return {array}
     */
    function summarizeListItem($listItem) {
        var summaryCollection = [];

        $listItem.find('[data-fieldtype]').each(function () {
            var atom = $(this);
            var fieldtype = atom.attr('data-fieldtype');

            if (summarizers[fieldtype]) {
                var text = stripHtml(summarizers[fieldtype](atom.find('.blocksft-atomcontainer')));

                if (text && !/^\s*$/.test(text)) {
                    summaryCollection.push(text);
                }
            }
        });

        return summaryCollection;
    }

    /**
     * @param {array} collection
     * @param {int} length
     * @returns {string}
     */
    function createSummary(collection, length) {
        var summary = collection.join(' \u2013 ');

        if (length && summary.length > length) {
            summary = summary.substring(0, length) + '...';
        }

        if (summary !== '') {
            return ' — ' + summary;
        }

        return '';
    }

    /**
     * @param {jQuery} $block
     */
    function getBlockListItem($block) {
        return $block.find('> .list-item');
    }

    function fireEvent(eventName, fields) {
        fields.each(function () {
            if (eventName === 'clone') {
                var fieldType = $(this).data('fieldtype');
                // Only if an event listener has subscribed do we trigger the clone event.
                if (typeof window.Grid._eventHandlers.clone !== 'undefined' &&
                    typeof window.Grid._eventHandlers.clone[fieldType] === 'function'
                ) {
                    window.Grid.Settings.prototype._fireEvent(eventName, $(this));
                } else {
                    window.Grid.Settings.prototype._fireEvent('display', $(this));
                }
            } else {
                window.Grid.Settings.prototype._fireEvent(eventName, $(this));
            }
        });
    }

    // On occassion, Blocks will load before another field type within a
    // block, and so Grid.bind will not have been called yet. So, we need to
    // intercept those and initialize them as well. I'm not convinced this is
    // the best way to do this, so it may need to be refined in the future.
    var g = Grid;
    var b = g.bind;
    g.bind = function (fieldType, eventName, callback) {
        b.apply(g, [fieldType, eventName, callback]);
        if (eventName === "display") {
            fireEvent("display", $('.blocksft .blocksft-blocks [data-fieldtype="' + fieldType + '"]'));
        }
    };

    $('a.m-link').click(function (e) {
        var modalIs = $('.' + $(this).attr('rel'));
        $('.checklist', modalIs)
            .html('') // Reset it
            .append('<li>' + $(this).data('confirm') + '</li>');
        $('input[name="blockdefinition"]', modalIs).val($(this).data('blockdefinition'));

        e.preventDefault();
    });

    var filePickerCallback = function (i, e) {
        var t = e.input_value;

        // Output as image tag if image
        if (
            // May be a markItUp button
            0 == t.size() && (t = e.source.parents(".markItUpContainer").find("textarea.markItUpEditor")),
                // Close the modal
                e.modal.find(".m-close").click(),
                // Assign the value {filedir_#}filename.ext
                file_string = "{filedir_" + i.upload_location_id + "}" + i.file_name, i.isImage) {
            var a = '<img src="' + file_string + '"';
            a += ' alt=""', i.file_hw_original && (dimensions = i.file_hw_original.split(" "), a = a + ' height="' + dimensions[0] + '" width="' + dimensions[1] + '"'), a += ">", t.insertAtCursor(a)
        } else
            // Output link if non-image
            t.insertAtCursor('<a href="' + file_string + '">' + i.file_name + "</a>")
    };

    /**
     * @param {jQuery} blocksFieldType
     */
    function validateDeprecatedOnLoad(blocksFieldType) {
        var $deprecatedBlocks = blocksFieldType.find('.block-deprecated');

        if ($deprecatedBlocks.length > 0) {
            blocksFieldType.find('.blocksft-contains-deprecated').removeClass('hidden');
            return;

            // Maybe in the future if we want to impose stricter behavior with deprecated blocks
            blocksFieldType.closest('fieldset').addClass('fieldset-invalid');
            $deprecatedBlocks.find('> .list-item').addClass('list-item--error');
            _disablePublishFormButtons(blocksFieldType.closest('form'));
        }
    }

    /**
     * Unused - Exploring disabling saving if there is a deprecated bloq
     * @param {jQuery} blocksFieldType
     */
    function validateDeprecatedOnRemove(blocksFieldType) {
        var $deprecatedBlocks = blocksFieldType.find('.block-deprecated');
        var $form = blocksFieldType.closest('form');
        var fieldSet = blocksFieldType.closest('fieldset');

        // Only clear errors if there are no other atom/field errors in the block
        if ($deprecatedBlocks.length === 0 && $form.find('.blocksft-atom.invalid').length === 0) {
            blocksFieldType.closest('fieldset').removeClass('fieldset-invalid');
            fieldSet.removeClass('fieldset-invalid');
            block.removeClass('list-item--error');

            _enablePublishFormButtons($form);
        }
    }

    function _bloqsValidationCallback(success, message, input) {
        var form = input.parents('form');
        var block = input.closest('.tbl-list-item, .list-item'); // EE4/5, EE6
        var blocksErrorElement = 'em.blocks-ee-form-error-message';
        var blocksAtomContainer = input.closest('.blocksft-atomcontainer');
        var blocksAtom = blocksAtomContainer.parent('.blocksft-atom');
        var fieldSet = blocksAtom.closest('fieldset');

        // @todo if there are errors in multiple blocks this does not work.
        // The field is marked as valid, the form buttons are re-enabled prematurely

        // The ajax-validate toggleErrorFields function is a bit ambitious when it comes to removing field errors,
        // especially with the way Bloqs is built. To keep it from adding/removing errors unnecessarily, we change the
        // class name on the error message html element. We'll take care of that ourselves.
        message = $(message).removeClass('ee-form-error-message').addClass('blocks-ee-form-error-message no');
        fieldSet.addClass('fieldset-invalid');

        if (success === false) {
            // Remove old error messages, otherwise it will just keep appending the same one
            if (blocksAtomContainer.has(blocksErrorElement).length) {
                blocksAtomContainer.find(blocksErrorElement).remove();
            }

            block.addClass('list-item--error');
            blocksAtom.addClass('invalid');
            blocksAtomContainer.append(message);
        }
        else {
            blocksAtom.removeClass('invalid');
            blocksAtomContainer.find(blocksErrorElement).remove();
        }

        if (block.find('.blocksft-atom.invalid').length) {
            _disablePublishFormButtons(form);
        } else {
            fieldSet.removeClass('fieldset-invalid');
            block.removeClass('list-item--error');

            _enablePublishFormButtons(form);
        }
    }

    function clearErrorsOnBlock(block) {
        var blocksAtomContainer = block.find('.blocksft-atoms');
        if (EE.cp && EE.cp.formValidation !== false) {
            EE.cp.formValidation.markFieldValid(blocksAtomContainer.find("input, select, textarea"));
        }
    }

    function _bloqsCheckboxValidationCallback(success, message, input) {
        var form = input.parents('form');
        var block = input.closest('.list-item');
        var blocksErrorElement = 'em.blocks-ee-form-error-message';
        var blocksAtom = input.closest('.blocksft-atom');
        var blocksAtomContainer = input.closest('.blocksft-atomcontainer');
        var fieldSet = blocksAtom.closest('fieldset');
        var checkboxes = blocksAtom.find('input[type="checkbox"]');
        var hasSelectedValue = false;

        if (!checkboxes.length || blocksAtom.hasClass('required') !== true) {
            return;
        }

        $.each(checkboxes, function () {
            if (this.checked) {
                hasSelectedValue = true;
            }
        });

        if (hasSelectedValue) {
            block.removeClass('list-item--error');
            blocksAtom.removeClass('invalid');
            blocksAtom.find(blocksErrorElement).remove();
            if (fieldSet.find('.invalid').length > 0 && !fieldSet.hasClass('invalid')) {
                fieldSet.addClass('invalid');
            }
            else {
                fieldSet.removeClass('invalid');
            }
        }
        else {
            fieldSet.addClass('invalid');
            if (!blocksAtom.hasClass('invalid')) {
                block.addClass('list-item--error');
                blocksAtom.addClass('invalid');
            }
            if (typeof message != 'undefined') {
                message = $(message).removeClass('ee-form-error-message').addClass('blocks-ee-form-error-message no');
                blocksAtomContainer.append(message);
            }
            _disablePublishFormButtons(form);
        }
    }

    function _disablePublishFormButtons(form) {
        var buttons = form
            .find('.form-btns .btn, .form-btns .button')
            .not('.saving-options') // EE6 button with additional dropdown options
        ;

        buttons.each(function () {
            var button = $(this);

            // Disable submit buttons
            button
                .addClass('disable')
                .attr('disabled', 'disabled')
                .prop('disabled', true)
            ;

            var submitText = decodeURIComponent(EE.lang.btn_fix_errors);

            if (button.is('input')) {
                button.attr('value', submitText);
            } else if (button.is('button')) {
                button.html(submitText);
            }
        });
    }

    function _enablePublishFormButtons(form) {
        var buttons = form
            .find('.form-btns .btn, .form-btns .button')
            .not('.saving-options') // EE6 button with additional dropdown options
        ;

        buttons.each(function () {
            var button = $(this);

            // Enable submit buttons
            button
                .removeClass('disable')
                .attr('disabled', '')
                .prop('disabled', false)
            ;

            var submitText = decodeURIComponent(button.data('submit-text'));

            if (button.is('input')) {
                button.attr('value', submitText);
            } else if (button.is('button')) {
                button.html(submitText);
            }
        });
    }

});
