<?php

/**
 * @package     ExpressionEngine
 * @subpackage  Extensions
 * @category    Bloqs
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2012, 2024 - BoldMinded, LLC
 * @link        http://boldminded.com/add-ons/bloqs
 * @license
 *
 * Copyright (c) 2019. BoldMinded, LLC
 * All rights reserved.
 *
 * This source is commercial software. Use of this software requires a
 * site license for each domain it is used on. Use of this software or any
 * of its source code without express written permission in the form of
 * a purchased commercial or other license is prohibited.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE.
 *
 * As part of the license agreement for this software, all modifications
 * to this source must be submitted to the original author for review and
 * possible inclusion in future releases. No compensation will be provided
 * for patches, although where possible we will attribute each contribution
 * in file revision notes. Submitting such modifications constitutes
 * assignment of copyright to the original author (Brian Litzinger and
 * BoldMinded, LLC) for such modifications. If you do not wish to assign
 * copyright to the original author, your license to  use and modify this
 * source is null and void. Use of this software constitutes your agreement
 * to this clause.
 */

namespace BoldMinded\Bloqs\Entity;

use BoldMinded\Bloqs\Controller\TagOutputBlockContext;

class Block
{
    const PREFIX_NEW_BLOCK_ID = 'blocks_new_block_';
    const PREFIX_EXISTING_BLOCK_ID = 'blocks_block_id_';

    // Properties should be marked as private, but for backwards compatibility keeping as public

    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $order;

    /**
     * @var int
     */
    public $depth;

    /**
     * @var int
     */
    public $lft;

    /**
     * @var int
     */
    public $rgt;

    /**
     * @var int
     */
    public $parent_id = 0;

    /**
     * @var BlockDefinition
     */
    public $definition;

    /**
     * @var Atom[] array
     */
    public $atoms = [];

    /**
     * @var array
     */
    public $children = [];

    /**
     * @var bool
     */
    public $deleted = false;

    /**
     * @var bool
     */
    public $isNew = false;

    /**
     * @var string
     */
    public $originalBlockName = '';

    /**
     * @var string
     */
    public $prefix = '';

    /**
     * @var string
     */
    public $revisionId = '';

    /**
     * @var bool
     */
    public $draft = 0;

    /**
     * @var int
     */
    public $cloneable = 0;

    /**
     * @var int
     */
    private $componentDefinitionId = 0;

    /**
     * @var TagOutputBlockContext
     */
    private $context;

    public function __get(string $name = ''): \Stringable|string|array
    {
        return $this->getAtom($name);
    }

    public function __isset(string $name = ''): bool
    {
        return isset($this->atoms[$name]);
    }

    public function toArray(callable|null $callback): array
    {
        return array_map(static fn ($atom) => $atom->toArray($callback), $this->atoms);
    }

    public function getAtom(string $name = ''): \Stringable|string|array
    {
        if (isset($this->atoms[$name])) {
            return $this->atoms[$name]->getValue();
        }

        return '';
    }

    public function setContext(TagOutputBlockContext $context): Block
    {
        $this->context = $context;

        return $this;
    }

    public function getContext(): TagOutputBlockContext
    {
        return $this->context;
    }

    public function addChild(Block $child)
    {
        $this->children[] = $child;
    }

    public function hasChildren(): bool
    {
        return count($this->children) > 0 ? true : false;
    }

    public function getId(): int|string
    {
        return $this->id;
    }

    /**
     * @param int|string $id Do not add an int typehint! Thanks, EE.
     */
    public function setId($id): Block
    {
        $this->id = $id;

        return $this;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): Block
    {
        $this->order = $order;

        return $this;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function setDepth(int $depth): Block
    {
        $this->depth = $depth;

        return $this;
    }

    public function getLft(): int
    {
        return $this->lft;
    }

    public function setLft(int $lft): Block
    {
        $this->lft = $lft;

        return $this;
    }

    public function getRgt():int
    {
        return $this->rgt;
    }

    public function setRgt(int $rgt): Block
    {
        $this->rgt = $rgt;

        return $this;
    }

    public function getParentId(): int|string
    {
        return $this->parent_id;
    }

    /**
     * @param int|string $parent_id Do not add an int typehint! Thanks, EE.
     */
    public function setParentId($parent_id): Block
    {
        $this->parent_id = $parent_id;

        return $this;
    }

    public function getDefinition(): BlockDefinition
    {
        return $this->definition;
    }

    public function setDefinition(BlockDefinition $definition): Block
    {
        $this->definition = $definition;

        return $this;
    }

    /**
     * @return Atom[]
     */
    public function getAtoms(): array
    {
        return $this->atoms;
    }

    /**
     * @param Atom[] $atoms
     */
    public function setAtoms(array $atoms): Block
    {
        $this->atoms = $atoms;

        return $this;
    }

    public function addAtom(string $shortName, Atom $definition): Block
    {
        $this->atoms[$shortName] = $definition;

        return $this;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function getTotalChildren(): int
    {
        return count($this->children);
    }

    public function setChildren(array $children): Block
    {
        $this->children = $children;

        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): Block
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function isNew(): bool
    {
        return $this->isNew;
    }

    public function setIsNew(bool $isNew): Block
    {
        $this->isNew = $isNew;

        return $this;
    }

    public function getOriginalBlockName(): string
    {
        return $this->originalBlockName ?? '';
    }

    public function setOriginalBlockName(string $originalBlockName): Block
    {
        $this->originalBlockName = $originalBlockName;

        return $this;
    }

    public function getPrefix(): string
    {
        return $this->prefix ?? '';
    }

    public function setPrefix(string $prefix): Block
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function getRevisionId(): string
    {
        return $this->revisionId ?? '';
    }

    /**
     * @param string $revisionId
     */
    public function setRevisionId(string|int $revisionId): Block
    {
        $this->revisionId = (string) $revisionId;

        return $this;
    }

    public function setTreeData(array $data): Block
    {
        $this->setOrder($data['order'] ?? 0);
        $this->setParentId($data['parent_id'] ?? 0);
        $this->setDepth($data['depth'] ?? 0);
        $this->setLft($data['lft'] ?? 0);
        $this->setRgt($data['rgt'] ?? 0);

        return $this;
    }

    public function getTreeData(): array
    {
        return [
            'order' => $this->getOrder(),
            'parent_id' => $this->getParentId(),
            'depth' => $this->getDepth(),
            'lft' => $this->getLft(),
            'rgt' => $this->getRgt(),
        ];
    }

    public function isDraft(): bool
    {
        return $this->draft === 1;
    }

    public function setDraft(int $draft): Block
    {
        $this->draft = $draft;

        return $this;
    }

    public function isCloneable(): bool
    {
        return $this->cloneable === 1;
    }

    public function setCloneable(bool $cloneable): Block
    {
        $this->cloneable = $cloneable;

        return $this;
    }

    public function getComponentDefinitionId(): int
    {
        return $this->componentDefinitionId;
    }

    public function setComponentDefinitionId(int $componentDefinitionId): Block
    {
        $this->componentDefinitionId = $componentDefinitionId;

        return $this;
    }

    /**
     * @param string|int $id
     */
    public static function isNewBlock($id): bool
    {
        return strpos($id, self::PREFIX_NEW_BLOCK_ID) === 0;
    }

    /**
     * @param string|int $id
     */
    public static function isExistingBlock($id): bool
    {
        return strpos($id, self::PREFIX_EXISTING_BLOCK_ID) === 0;
    }
}
