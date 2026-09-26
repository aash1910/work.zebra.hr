/* global _ */

/**
 * MenuValidation is proactive. It is executed when the user clicks one of the add bloq menu items. It's validation
 * logic is similar, but not exactly like TreeValidation since we're modifying the menu based on the current state
 * of the tree before something is added, so we need to validate as if a new node was present, but actually is not.
 * It also does not need to handle displaying messages to the user, or validate "no children", or "min/max" children.
 * "no children" is handled by the php view file... the add as child menu option isn't even rendered to the page.
 *
 * @param {jQuery} $blocksField
 * @param {object} blockDefinitions
 * @param {object} nestedSet
 * @returns {{validate: validate}}
 * @constructor
 */
function MenuValidation($blocksField, blockDefinitions, nestedSet)
{
    var settings;
    var block;
    var blockDefinition;
    var blockDefinitionId;
    var blockId;
    var menuContext;
    var insertLocation;

    var validate = function (blockToValidate, context, location) {
        block = blockToValidate;
        menuContext = context;
        insertLocation = location;
        blockDefinitionId = block.data('definition-id');
        blockId = menuContext.data('id');

        // Should we explicitly ignore validation on this block?
        if (block.data('validate') === false) {
            return;
        }

        loadSettings();

        return !(
            !validateRootOnly() ||
            !validateChildOf() ||
            !validateMaxChildren()
        );
    };

    /**
     * @returns {boolean}
     */
    var isRootOnly = function () {
        return !settings || (settings && settings.root === 'root_only');
    };

    /**
     * @returns {boolean}
     */
    var isRootOrChildOf = function () {
        return !settings || (settings && settings.root === 'root_or_child_of');
    }

    /**
     * @param {string} location
     * @returns {boolean}
     */
    var isInsertLocation = function (location) {
        return insertLocation === location;
    };

    /**
     * @param {object} blockNode
     * @returns {number|null}
     */
    var getParentDefinitionId = function (blockNode) {
        return isInsertLocation('child') ? blockNode.definition_id : blockNode.parent_definition_id;
    };

    /**
     * @returns {boolean}
     */
    var validateRootOnly = function () {
        if (!isRootOnly()) {
            return true;
        }

        var blockNode = getBlockNode();

        if (typeof blockNode === 'undefined') {
            return true;
        }

        if (isInsertLocation('child') && isRootOnly()) {
            return false;
        }

        return (blockNode.parent_id === null && isRootOnly()) || isRootOrChildOf();
    };

    /**
     * @returns {boolean}
     */
    var validateChildOf = function () {
        if (!settings || !settings.child_of || settings.child_of.length === 0 || settings.child_of[0] === '') {
            return true;
        }

        var blockNode = getBlockNode();

        if (typeof blockNode === 'undefined') {
            return true;
        }

        var parentDefinitionId = getParentDefinitionId(blockNode);
        var childOf = _.map(settings.child_of, function(value) {
            return parseInt(value);
        });

        // If inserting from root context and is allowed to be root or child, approve without checking the parent.
        if (!isInsertLocation('child') && isRootOrChildOf()) {
            return true;
        }

        return !(!parentDefinitionId || _.indexOf(childOf, parentDefinitionId) === -1);
    };

    /**
     * @returns {boolean}
     */
    var validateMaxChildren = function () {
        blockDefinitionId = menuContext.data('definitionId');

        if (blockId && !isInsertLocation('child') && !isRootOnly()) {
            var parentNode = getParentNode(blockId);

            if (parentNode) {
                blockId = parentNode.id;
                blockDefinitionId = parentNode.definition_id;
            }
        }

        loadSettings();

        var maxChildren = parseInt(settings.max_children || 0);

        if (maxChildren === 0) {
            return true;
        }

        var children = getChildBlocks(blockId);

        return children.length < maxChildren;
    };

    /**
     * @param {int} parentBlockId
     * @returns {array}
     */
    var getChildBlocks = function (parentBlockId) {
        var children = _.where(nestedSet, {'parent_id': parentBlockId});

        return _.filter(children, function (childBlock) {
            return !getBlock(childBlock.id).hasClass('deleted');
        });
    };

    /**
     * @param {int} blockIdToGet
     * @returns {jQuery}
     */
    var getBlock = function (blockIdToGet) {
        return $blocksField.find('.blocksft-block[data-id="' + blockIdToGet + '"]');
    };

    /**
     * @param {int} id
     * @returns {object}
     */
    var getBlockNode = function (id) {
        if (id) {
            return _.findWhere(nestedSet, {'id': id});
        }

        // If we don't have a blockId, then assume we're trying to insert at the root from one of the utility controls
        if (!blockId) {
            return {
                'id': 0,
                'definition_id': 0,
                'parent_id': null,
                'parent_definition_id': null
            };
        }

        return _.findWhere(nestedSet, {'id': blockId});
    };

    /**
     * @param {int} id
     * @returns {object}
     */
    var getParentNode = function (id) {
        var parentId = getBlockNode(id).parent_id;

        if (parentId) {
            return getBlockNode(parentId);
        }

        return null;
    };

    /**
     * @returns {object}
     */
    var loadSettings = function () {
        blockDefinition = _.findWhere(blockDefinitions, {
            'id': blockDefinitionId
        });

        if (blockDefinition &&
            blockDefinition.hasOwnProperty('settings') &&
            blockDefinition.settings !== null &&
            blockDefinition.settings.hasOwnProperty('nesting') &&
            blockDefinition.settings.nesting !== null
        ) {
            settings = blockDefinition.settings.nesting;
        }
    };

    return {
        'validate': validate
    };
}
