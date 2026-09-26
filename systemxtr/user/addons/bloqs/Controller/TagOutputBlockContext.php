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

namespace BoldMinded\Bloqs\Controller;

use \BoldMinded\Bloqs\Entity\Block;

/**
 * The context for a single block when being outputted by the TagController.
 */
class TagOutputBlockContext
{
    private $_currentBlock;
    private $_nextContext = null;
    private $_previousContext = null;
    private $_rootBlock = null;
    private $_index;
    private $_total;
    private $_indexOfType;
    private $_totalOfType;
    private $_siblings = [];
    private $_children = [];
    private $_parentShortName;
    private $_parentId;
    private $_parentIds = [];
    private $_countAtDepth;
    private $_depth;

    public function __construct(
        $currentBlock,
        $index,
        $total,
        $indexOfType,
        $totalOfType
    )
    {
        $this->_currentBlock  = $currentBlock;
        $this->_index         = $index;
        $this->_total         = $total;
        $this->_indexOfType   = $indexOfType;
        $this->_totalOfType   = $totalOfType;
    }

    /**
     * @return Block
     */
    public function getCurrentBlock()
    {
        return $this->_currentBlock;
    }

    /**
     * @return Block|null
     */
    public function getPreviousBlock()
    {
        /** @var TagOutputBlockContext $context */
        $context = $this->getPreviousContext();

        if ($context === null) {
            return null;
        }

        return $context->getCurrentBlock();
    }

    /**
     * @return int
     */
    public function getRootBlockId(): int
    {
        $this->findRootContext($this);

        if ($this->_rootBlock) {
            return $this->_rootBlock->getId();
        }

        return 0;
    }

    /**
     * @return string
     */
    public function getRootShortName(): string
    {
        $this->findRootContext($this);

        if ($this->_rootBlock) {
            return $this->_rootBlock->getDefinition()->getShortName();
        }

        return '';
    }

    /**
     * @param TagOutputBlockContext $requestedContext
     */
    private function findRootContext(TagOutputBlockContext $requestedContext)
    {
        $currentBlock = $requestedContext->getCurrentBlock();

        if (!$currentBlock->getParentId()) {
            $this->_rootBlock = $currentBlock;

            return;
        }

        // Walk backwards until we find the root
        $previousContext = $requestedContext->getPreviousContext();

        if ($previousContext) {
            $this->findRootContext($previousContext);
        }
    }

    /**
     * @return Block|null
     */
    public function getNextBlock()
    {
        /** @var TagOutputBlockContext $context */
        $context = $this->getNextContext();

        if ($context === null) {
            return null;
        }

        return $context->getCurrentBlock();
    }

    /**
     * @return bool
     */
    public function isFirstChild(): bool
    {
        $siblings = $this->getSiblings();

        $first = reset($siblings);
        $current = $this->getCurrentBlock();

        return $first === $current;
    }

    /**
     * @return bool
     */
    public function isLastChild(): bool
    {
        $siblings = $this->getSiblings();

        $last = end($siblings);
        $current = $this->getCurrentBlock();

        return $last === $current;
    }

    /**
     * @return int
     */
    public function getBlockId()
    {
        $currentBlock = $this->getCurrentBlock();

        return $currentBlock->id;
    }

    /**
     * @return int
     */
    public function getPreviousBlockId()
    {
        /** @var TagOutputBlockContext $context */
        $context = $this->getPreviousContext();

        if ($context === null) {
            return null;
        }

        return $context->getBlockId();
    }

    /**
     * @return int
     */
    public function getNextBlockId()
    {
        /** @var TagOutputBlockContext $context */
        $context = $this->getNextContext();

        if ($context === null) {
            return null;
        }

        return $context->getBlockId();
    }

    /**
     * Get the shortname for the associated block
     *
     * Provides a simple (and abstract) way to get the block's shortname, so
     * that the caller doesn't have to have a huge chain of property lookups
     * and function calls.
     *
     * @return string
     */
    public function getShortname(): string
    {
        return $this->_currentBlock->definition->shortname;
    }

    /**
     * @param $previousContext
     */
    public function setPreviousContext($previousContext)
    {
        $this->_previousContext = $previousContext;
    }

    /**
     * @return $this
     */
    public function getPreviousContext()
    {
        return $this->_previousContext;
    }

    /**
     * @param $nextContext
     */
    public function setNextContext($nextContext)
    {
        $this->_nextContext = $nextContext;
    }

    /**
     * @return $this
     */
    public function getNextContext()
    {
        return $this->_nextContext;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->_index + 1;
    }

    /**
     * @return int
     */
    public function getIndex()
    {
        return $this->_index;
    }

    /**
     * @return int
     */
    public function getTotal()
    {
        return $this->_total;
    }

    /**
     * @return int
     */
    public function getIndexOfType()
    {
        return $this->_indexOfType;
    }

    /**
     * @return int
     */
    public function getCountOfType()
    {
        return $this->_indexOfType + 1;
    }

    /**
     * @return int
     */
    public function getTotalOfType()
    {
        return $this->_totalOfType;
    }

    /**
     * @return mixed
     */
    public function getTotalSiblings()
    {
        return count($this->_siblings);
    }

    /**
     * @return mixed
     */
    public function getTotalChildren()
    {
        return count($this->_children);
    }

    /**
     * @return array
     */
    public function getSiblings(): array
    {
        return $this->_siblings;
    }

    /**
     * @param array $siblings
     */
    public function setSiblings(array $siblings = [])
    {
        $this->_siblings = $siblings;
    }

    /**
     * @return array
     */
    public function getChildren(): array
    {
        return $this->_children;
    }

    /**
     * @param array $children
     */
    public function setChildren(array $children = [])
    {
        $this->_children = $children;
    }

    /**
     * @return mixed
     */
    public function getParentShortName()
    {
        return $this->_parentShortName;
    }

    /**
     * @param mixed $parentShortName
     */
    public function setParentShortName($parentShortName)
    {
        $this->_parentShortName = $parentShortName;
    }

    /**
     * @return mixed
     */
    public function getParentId()
    {
        return $this->_parentId;
    }

    /**
     * @param mixed $parentId
     */
    public function setParentId($parentId)
    {
        $this->_parentId = $parentId;
    }

    /**
     * @return mixed
     */
    public function getParentIds()
    {
        return array_unique($this->_parentIds);
    }

    /**
     * @param array $parentIds
     */
    public function setParentIds($parentIds)
    {
        $this->_parentIds = $parentIds;
    }

    /**
     * @return mixed
     */
    public function getCountAtDepth()
    {
        return $this->_countAtDepth;
    }

    /**
     * @param int $countAtDepth
     */
    public function setCountAtDepth(int $countAtDepth)
    {
        $this->_countAtDepth = $countAtDepth;
    }

    /**
     * @return int
     */
    public function getDepth()
    {
        return $this->_depth;
    }

    /**
     * @param int $depth
     */
    public function setDepth(int $depth)
    {
        $this->_depth = $depth;
    }
}
