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

class Atom
{
    // Properties should be marked as private, but for backwards compatibility keeping as public

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $value;

    /**
     * @var AtomDefinition
     */
    public $definition;

    /**
     * @var
     */
    public $error;

    public function __toString(): string
    {
        return $this->getValue();
    }

    public function toArray(callable|null $callback): array
    {
        $value = $this->getValue();

        if ($callback) {
            $resp = $callback($this->definition, $value);

            if (!is_array($resp)) {
                return [$resp];
            }

            return $resp;
        }

        if (is_array($value)) {
            return array_map(static fn ($v) => $v, $value);
        }

        if ($value === '') {
            return [];
        }

        return [$value];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Atom
    {
        $this->id = $id;

        return $this;
    }

    public function getValue(): \Stringable|string|array
    {
        return $this->value;
    }

    public function setValue(\Stringable|string|array $value): Atom
    {
        $this->value = $value;

        return $this;
    }

    public function getDefinition(): AtomDefinition
    {
        return $this->definition;
    }

    public function setDefinition(AtomDefinition $definition): Atom
    {
        $this->definition = $definition;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param mixed $error
     * @return $this
     */
    public function setError($error)
    {
        $this->error = $error;

        return $this;
    }
}
