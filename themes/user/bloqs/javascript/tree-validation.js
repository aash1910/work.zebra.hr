/* global _ */

/**
 * TreeValidation is reactive. It is executed after a node is inserted or moved (after nestedSet is updated).
 * Because of this the validation logic is slightly different than MenuValidation, which is proactive.
 *
 * @param {jQuery} $blocksField
 * @param {object} blockDefinitions
 * @param {object} nestedSet
 * @returns {{validate: validate}}
 * @constructor
 */
function TreeValidation($blocksField, blockDefinitions, nestedSet)
{
    var settings;
    var block;
    var blockDefinition;
    var blockDefinitionId;
    var blockId;
    var $blocks;
    var errorMessages = {};
    var errorsCleared = true;
    var isEditable = false;
    var isComponent = false;
    var strictTreeStructure = EE.bloqs.strictTreeStructure || false;
    var noticeClass = strictTreeStructure ? 'error' : 'important';
    var errorClassName = 'list-item--' + noticeClass;

    var validate = function (blockToValidate) {
        block = blockToValidate;
        blockDefinitionId = block.data('definition-id');
        blockId = block.data('id');
        isEditable = block.data('is-editable');
        isComponent = block.data('is-component');

        // Should we explicitly ignore validation on this field or block?
        if ($blocksField.data('settingNestable') !== 'y' || block.data('validate') === false) {
            return;
        }

        $blocks = $blocksField.find('.blocksft-block');
        errorMessages = $blocksField.data('errorMessages') || {};

        loadSettings();

        if (!blockId
            || !validateRootOnly()
            || !validateChildOf()
            || !validateNoChildren()
            || !validateExactChildren()
            || !validateMinChildren()
            || !validateMaxChildren()
        ) {
            showErrorMessages();
            return false;
        }
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

        if (blockNode.parent_id !== null && !isRootOrChildOf()) {
            addErrorMessage(block, '<b>' + blockNode.name + '</b> must be a root bloq');
            return false;
        }

        clearErrorMessagesForBlock(blockNode.id);

        return true;
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

        var childOf = settings.child_of.map(Number);
        var parentDefinitionId =  blockNode.parent_definition_id;
        var parentDefinitions = getBlockDefinitions(childOf);

        if (isRootOrChildOf() && !parentDefinitionId) {
            return true;
        }

        if (!parentDefinitionId || _.indexOf(childOf, parentDefinitionId) === -1) {
            if (!parentDefinitionId && !isRootOrChildOf()) {
                addErrorMessage(block, '<b>' + blockNode.name + '</b> can not be a root bloq');
            } else {
                var parentDefinitionNames = _.pluck(parentDefinitions, 'name');
                if (isRootOrChildOf()) {
                    parentDefinitionNames.push('or be a root bloq');
                }
                addErrorMessage(block, '<b>' + blockNode.name + '</b> must be a child of <b>' + parentDefinitionNames.join(', ') + '</b>');
            }

            return false;
        }

        clearErrorMessagesForBlock(blockNode.id);

        return true;
    };

    /**
     * @returns {boolean}
     */
    var validateNoChildren = function () {
        if (!settings) {
            return true;
        }

        var canHaveChildren = settings.no_children || null;
        var children = getChildBlocks(blockId);

        if (shouldNotValidateChildren()) {
            return true;
        }

        // Check for blockDefinition too. If a block was removed from a field, but an entry still contains said block,
        // then it'll try to validate it. getAllowedBlockDefinitionsForField() will only return what is assigned to the field.
        // so it won't appear in the jsonDefinitions list that we're using to validate with.
        if (canHaveChildren === 'n' && children.length > 0 && blockDefinition) {
            // set the parent as the current block so the error styles are applied to the relevant block.
            block = $blocksField.find('[data-id="'+ blockId +'"]');
            addErrorMessage(block, '<b>' + blockDefinition.name + '</b> can\'t have child bloqs');

            return false;
        }

        clearErrorMessagesForBlock(blockId);

        return true;
    };

    /**
     * @returns {boolean}
     */
    var validateExactChildren = function () {
        var exactChildren = parseInt(settings.exact_children || 0);

        if (exactChildren === 0 || shouldNotValidateChildren()) {
            return true;
        }

        var children = getChildBlocks(blockId);

        if (children.length !== exactChildren && blockDefinition) {
            // set the parent as the current block so the error styles are applied to the relevant block.
            block = $blocksField.find('[data-id="'+ blockId +'"]');
            addErrorMessage(block, '<b>' + blockDefinition.name + '</b> must have exactly ' + exactChildren + ' child bloqs.');

            return false;
        }

        clearErrorMessagesForBlock(blockId);

        return true;
    };

    /**
     * @returns {boolean}
     */
    var validateMinChildren = function () {
        var minChildren = parseInt(settings.min_children || 0);

        if (minChildren === 0 || shouldNotValidateChildren()) {
            return true;
        }

        var children = getChildBlocks(blockId);

        if (children.length < minChildren && blockDefinition) {
            // set the parent as the current block so the error styles are applied to the relevant block.
            block = $blocksField.find('[data-id="'+ blockId +'"]');
            addErrorMessage(block, '<b>' + blockDefinition.name + '</b> must have at least ' + minChildren + ' child bloqs.');

            return false;
        }

        clearErrorMessagesForBlock(blockId);

        return true;
    };

    /**
     * @returns {boolean}
     */
    var validateMaxChildren = function () {
        var maxChildren = parseInt(settings.max_children || 0);

        if (maxChildren === 0 || shouldNotValidateChildren()) {
            return true;
        }

        var children = getChildBlocks(blockId);

        if (children.length > maxChildren && blockDefinition) {
            // set the parent as the current block so the error styles are applied to the relevant block.
            block = $blocksField.find('[data-id="'+ blockId +'"]');
            addErrorMessage(block, '<b>' + blockDefinition.name + '</b> must have no more than ' + maxChildren + ' child bloqs.');

            return false;
        }

        clearErrorMessagesForBlock(blockId);

        return true;
    };

    /**
     * @returns {boolean}
     */
    var shouldNotValidateChildren = function ()
    {
        // Non-editable components should not validate it's children.
        // Follow whatever the developer defined as the component.
        return (isComponent && !isEditable);
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
     * @returns {jQuery}
     */
    var getBlocks = function () {
        return $blocksField.find('.blocksft-block');
    };

    /**
     * @returns {jQuery}
     */
    var getMessageContainer = function () {
        return $blocksField.find('.blocksft-navigation__message');
    };

    /**
     * @param {int} id
     * @returns {object}
     */
    var getBlockNode = function (id) {
        if (id) {
            return _.findWhere(nestedSet, {'id': id});
        }

        return _.findWhere(nestedSet, {'id': blockId});
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

    /**
     * @returns {object}
     */
    var getBlockDefinition = function (definitionId) {
        return _.findWhere(blockDefinitions, {'id': definitionId});
    };

    /**
     * @returns {array}
     */
    var getBlockDefinitions = function (definitionIds) {
        var definitions = [];

        _.each(definitionIds, function (value) {
            var definition = getBlockDefinition(value);

            if (definition) {
                definitions.push(getBlockDefinition(value));
            }
        });

        return definitions;
    };

    /**
     * @param {object} block
     * @param {string }message
     */
    var addErrorMessage = function (block, message) {
        if (blockId in errorMessages) {
            return;
        }

        block.find('> .list-item').addClass(errorClassName);
        errorMessages[blockId] = message;

        saveErrorMessages();
    };

    var saveErrorMessages = function () {
        $blocksField.data('errorMessages', errorMessages);
    };

    var showErrorMessages = function () {
        if (_.isEmpty(errorMessages)) {
            if (!errorsCleared) {
                getMessageContainer().html('');
                getBlocks().find('> .list-item').removeClass(errorClassName);
            }

            showSaveButtons();

            return;
        }

        errorsCleared = false;
        var messages = '';
        var messagesArray = [];
        var strictTreeStructureMessage = '';

        for (key in errorMessages) {
            messagesArray.push(errorMessages[key]);
        }

        if (strictTreeStructure) {
            hideSaveButtons();
            strictTreeStructureMessage = '<p>These issues must be resolved before the entry can be saved.</p>\n';
        }

        messagesArray = uniqueMessages(messagesArray);
        messages = messagesArray.join('</li><li>');

        var alertHtml = '<div class="app-notice app-notice--inline app-notice---' + noticeClass + '">\n' +
            '<div class="app-notice__tag">\n' +
            '<span class="app-notice__icon"></span>\n' +
            '</div>\n' +
            '<div class="app-notice__content">\n' +
            '<p><b>Invalid Bloq Tree</b></p>\n' +
            strictTreeStructureMessage +
            '<ul><li>'+ messages +'</li></ul>\n' +
            '</div>\n' +
            '<a href="#" class="app-notice__controls js-notice-dismiss"><span class="app-notice__dismiss"></span></a>\n' +
            '</div>';

        getMessageContainer().html(alertHtml);
    };

    var showSaveButtons = function() {
        $('.tab-bar .form-btns').show();
    };

    var hideSaveButtons = function() {
        $('.tab-bar .form-btns').hide();
    };

    /**
     * @param {array} array
     * @returns {array}
     */
    var uniqueMessages = function (array) {
        return array.filter(function (value, index, self) {
            return self.indexOf(value) === index;
        });
    };

    var clearErrorMessages = function () {
        getMessageContainer().html('');
        getBlocks().find('> .list-item').removeClass(errorClassName);
        errorMessages = {};
        saveErrorMessages();
    };

    /**
     * @param {int} blockId
     */
    var clearErrorMessagesForBlock = function (blockId) {
        if (blockId) {
            $blocks.find('[data-id='+ blockId +'] > .list-item').removeClass(errorClassName);
            delete errorMessages[blockId];
        }

        saveErrorMessages();
        showErrorMessages();
    };

    return {
        'validate': validate,
        'clearErrorMessages': clearErrorMessages
    };
}
