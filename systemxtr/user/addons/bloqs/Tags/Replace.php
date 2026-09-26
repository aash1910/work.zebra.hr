<?php
namespace BoldMinded\Bloqs\Tags;

use BoldMinded\Bloqs\Controller\FieldTypeFilter;
use BoldMinded\Bloqs\Controller\HookExecutor;
use BoldMinded\Bloqs\Controller\TagController;
use BoldMinded\Bloqs\Controller\TagOutputBlockContext;
use BoldMinded\Bloqs\Controller\FieldTypeManager as BloqsFieldTypeManager;
use BoldMinded\Bloqs\Helper\TreeHelper;
use BoldMinded\Bloqs\Entity\Block;
use Expressionengine\Coilpack\Api\Graph\Support\FieldtypeRegistrar;
use Expressionengine\Coilpack\Contracts\GeneratesGraphType;
use Expressionengine\Coilpack\Contracts\ListsGraphType;
use Expressionengine\Coilpack\FieldtypeManager;
use Expressionengine\Coilpack\Fieldtypes\Fieldtype;
use Expressionengine\Coilpack\FieldtypeOutput;
use Expressionengine\Coilpack\Models\Channel\ChannelField;
use Expressionengine\Coilpack\Models\FieldContent;

/**
 * This class is called by Coilpack, if installed, and used to render Bloqs
 * fields in Twig or Blade templates.
 *
 * @see https://expressionengine.github.io/coilpack-docs/docs/advanced/addons
 */
class Replace extends Fieldtype implements GeneratesGraphType, ListsGraphType
{
    private array $blocks = [];
    private BloqsFieldTypeManager $bloqsFieldTypeManager;
    private FieldContent $content;
    private array $contexts = [];
    private int $entryId;
    private int $fieldId;
    private FieldTypeFilter $filter;
    private HookExecutor $hookExecutor;
    private TreeHelper $treeHelper;

    public function __construct($name, $id = null)
    {
        parent::__construct($name, $id);

        $this->filter = new FieldTypeFilter();
        $this->filter->load(PATH_THIRD.'bloqs/fieldtypes.xml');
        $this->hookExecutor = new HookExecutor(ee());
        $this->treeHelper = new TreeHelper();
        $this->bloqsFieldTypeManager = new BloqsFieldTypeManager(ee(), $this->filter, $this->hookExecutor);
    }

    public function apply(FieldContent $content, $parameters = [])
    {
        $this->content = $content;
        $attributes = $content->getAttributes();
        $fieldSettings = $content->field->field_settings;

        $this->entryId = $attributes['entry_id'];
        $this->fieldId = $attributes['field_type_id'];
        $this->blocks = array_filter(
            ee('bloqs:Adapter')->getBlocks($this->entryId, $this->fieldId),
            static fn (Block $block) => !$block->isDraft()
        );

        $controller = new TagController(
            ee(),
            $attributes['field_type_id'],
            $this->bloqsFieldTypeManager,
            $fieldSettings,
            $this->hookExecutor
        );

        // Contexts aren't usually indexed by blockId, but in this case we need to do so.
        // This is different from the EE template parsing version because there it's actually
        // iterating over the context objects, not the blocks themselves, so all the context
        // properties can be added to the output tagdata as string variables. Since we aren't
        // messing with string parsing we'll just build our block tree as normal, then below
        // attach the context to each block in-case someone wants additional data.
        foreach ($controller->buildContexts($this->blocks) as $context) {
            $this->contexts[$context->getCurrentBlock()->getId()] = $context;
        }

        $this->prepareContexts();

        foreach ($this->blocks as $block) {
            $context = $this->contexts[$block->getId()];
            // buildContexts() also sets children, but not in tree form, so blank it out
            // and let buildBlockTree() determine the proper children for each block.
            $block->setChildren([]);
            $block->setContext($context);

            foreach ($block->getAtoms() as &$atom) {
                $atom->setValue(new FieldContent(
                    array_merge($this->content->getAttributes(), [
                        'data' => $atom->getValue(),
                        'grid_row_id' => $block->getId(),
                        'grid_col_id' => $atom->getDefinition()->getId(),
                        'blocks_atom_id'  => $atom->getDefinition()->getId(),
                        'blocks_block_id' => $block->getId(),
                        'blocks_block_name' => $block->getDefinition()->getShortName(),
                        'fieldtype' => app(FieldtypeManager::class)->make($atom->getDefinition()->getType()),
                    ])
                ));
            }
        }

        $tree = $this->treeHelper->buildBlockTree($this->blocks);

        return FieldtypeOutput::make()->array($tree);
    }

    /**
     * @param int $depth
     */
    private function prepareContexts(int $depth = 0)
    {
        $depthCount = 1;
        $preparedContexts = [];

        /** @var TagOutputBlockContext $context */
        foreach ($this->contexts as &$context) {
            $currentBlock = $context->getCurrentBlock();

            // Back at root left, so correct our depth
            if ($currentBlock->getDepth() === 0) {
                $depthCount = 1;
            }

            // Chart a new course if the depth has changed in either direction
            if ($currentBlock->getDepth() !== $depth) {
                $depthCount = 1;
                continue;
            }

            $blockId = $currentBlock->getId();
            $parentId = $currentBlock->getParentId();

            $parent = $this->treeHelper->findParent($this->blocks, $blockId);
            $parents = $this->treeHelper->findParents($this->blocks, $blockId);
            $children = $this->treeHelper->findChildren($this->blocks, $blockId);
            $siblings = $this->treeHelper->findSiblings($this->blocks, $blockId);
            $parentShortName = $parent ? $parent->getDefinition()->getShortName() : '';

            $context->setChildren($children);
            $context->setSiblings($siblings);
            $context->setParentId($parentId);
            $context->setParentIds(array_column($parents,'id'));
            $context->setParentShortName($parentShortName);
            $context->setCountAtDepth($depthCount);
            $context->setDepth($currentBlock->getDepth());

            $depthCount++;
            $preparedContexts[$blockId] = $context;
        }

        if (empty($preparedContexts)) {
            return;
        }

        $this->prepareContexts($depth + 1);
    }

    public function generateGraphType(ChannelField $field)
    {
        return []; // @todo

        //return $field->gridColumns->flatmap(function($column) {
        //    return [
        //        $column->col_name => new \Expressionengine\Coilpack\Api\Graph\Fields\Fieldtype([
        //            'description' => $column->col_instructions,
        //            'fieldtype' => app(FieldtypeManager::class)->make($column->col_type),
        //            'type' => app(FieldtypeRegistrar::class)->getType($column->col_type) ?: \GraphQL\Type\Definition\Type::string(),
        //            'selectable' => false,
        //            // 'is_relation' => false,
        //            'resolve' => function ($root, array $args) use ($column) {
        //
        //                $value = $root->{$column->col_name};//->value();
        //                return $value;
        //            }
        //        ])
        //    ];
        //})->toArray();
    }
}
