<?php

/**
 * @package     ExpressionEngine
 * @subpackage  TemplateGenerators
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

namespace BoldMinded\Bloqs\TemplateGenerators;

use BoldMinded\Bloqs\Database\Adapter;
use BoldMinded\Bloqs\Entity\AtomDefinition;
use BoldMinded\Bloqs\Entity\BlockDefinition;
use ExpressionEngine\Service\TemplateGenerator\AbstractFieldTemplateGenerator;

class Bloqs extends AbstractFieldTemplateGenerator
{
    public function getVariables(): array
    {
        $adapter = new Adapter(ee());
        $blockDefinitions = $adapter->getBlockDefinitionsForField($this->field->field_id);

        return $this->getBlocks($blockDefinitions);
    }

    private function getBlocks(array $blockDefinitions = []): array
    {
        $installedFieldtypes = ee()->addons->get_installed('fieldtypes');

        /** @var BlockDefinition $definition */
        foreach ($blockDefinitions as $definition) {
            $atoms = [];
            $canHaveChildren = $definition->canHaveChildren();

            /** @var AtomDefinition $atom */
            foreach ($definition->getAtomDefinitions() as $atom) {
                if (!array_key_exists($atom->getType(), $installedFieldtypes)) {
                    continue;
                }

                $atoms[$atom->getShortName()] = $this->getFieldVars($atom);
            }

            $blocks[$definition->getShortName()] = [
                'atoms' => $atoms,
                'canHaveChildren' => $canHaveChildren,
            ];
        }

        return [
            'bloqs' => $blocks,
        ];
    }

    private function getFieldVars(AtomDefinition $atom) {
        $fieldtypeGenerator = ee('TemplateGenerator')->getFieldtype($atom->getType());

        $vars = [
            'field_type' => $atom->getType(),
            'field_name' => $atom->getShortName(),
            'field_label' => $atom->getName(),
            'stub' => $fieldtypeGenerator['stub'],
            'docs_url' => $fieldtypeGenerator['docs_url'],
            'is_tag_pair' => $fieldtypeGenerator['is_tag_pair'],
            'modifiers_string' => '',
        ];

        $generator = $this->makeField($atom->getType(), $atom, $atom->getSettings());

        // if the field has its own generator, instantiate the field and pass to generator
        if ($generator) {
            $vars = array_merge(
                $vars, $generator->getVariables()
            );
        }

        return $vars;
    }
}
