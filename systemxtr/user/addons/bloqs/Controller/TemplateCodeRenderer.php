<?php

namespace BoldMinded\Bloqs\Controller;

use BoldMinded\Bloqs\Database\Adapter;
use BoldMinded\Bloqs\Entity\AtomDefinition;
use BoldMinded\Bloqs\Entity\BlockDefinition;

class TemplateCodeRenderer
{
    /**
     * @var Adapter
     */
    private $adapter;

    /**
     * @var array
     */
    private $installedFieldtypes = [];

    /**
     * @param Adapter $adapter
     * @param array $installedFieldtypes
     */
    public function __construct(Adapter $adapter, $installedFieldtypes = [])
    {
        $this->adapter = $adapter;
        $this->installedFieldtypes = $installedFieldtypes;
    }

    public function renderBlockTemplate($blockDefinitionId)
    {
        $blockDefinition = $this->adapter->getBlockDefinitionById($blockDefinitionId);
        $blocks = $this->getBlocks([$blockDefinition]);

        return $this->renderBlocks($blocks, [], 1);
    }

    /**
     * @param string $fieldName
     * @param int $fieldId
     * @param array $includeBlocks
     * @param bool $isNestable
     * @return string
     */
    public function renderFieldTemplate(string $fieldName, array $includeBlocks = [], $isNestable = false)
    {
        $blocks = $this->getBlocks($this->adapter->getBlockDefinitions());

        $output = LD . 'exp:channel:entries channel="your_channel_here"' . RD . PHP_EOL;
        $output .= '    ' . LD . $fieldName . RD . PHP_EOL;
        $output .= $this->renderBlocks($blocks, $includeBlocks, 2, $isNestable);
        $output .= PHP_EOL . '    ' . LD . '/' . $fieldName . RD;
        $output .= PHP_EOL . LD . '/exp:channel:entries' . RD;

        return $output;
    }

    /**
     * @param array $blockDefinitions
     */
    public function getBlocks($blockDefinitions = [])
    {
        $blocks = [];
        $installedFieldtypes = $this->installedFieldtypes;

        /** @var BlockDefinition $definition */
        foreach ($blockDefinitions as $definition) {
            $blocks[$definition->shortname] = [];
            /**
             * @var string $atomName
             * @var AtomDefinition $atom
             */
            foreach ($definition->getAtomDefinitions() as $atomName => $atom) {
                if (!array_key_exists($atom->getType(), $installedFieldtypes)) {
                    continue;
                }

                $path = $installedFieldtypes[$atom->getType()]['path'] . $installedFieldtypes[$atom->getType()]['file'];
                if (!file_exists($path)) {
                    continue;
                }
                require_once $path;
                $fieldtype = new $installedFieldtypes[$atom->getType()]['class'];
                $hasArrayData = $fieldtype->has_array_data ?? false;
                $blocks[$definition->getShortName()][$atomName] = [
                    'isPair' => ($hasArrayData || $atom->getType() === 'relationship'),
                    'type' => $atom->getType(),
                ];
            }
        }

        return $blocks;
    }

    /**
     * @param array $blocks
     * @param array $includeBlocks
     * @param int $indentMultiplier
     * @param bool $isNestable
     * @return string
     */
    private function renderBlocks($blocks = [], $includeBlocks = [], $indentMultiplier = 1, $isNestable = false)
    {
        $output = '';
        $indent = '    ';

        foreach ($blocks as $blockName => $atoms) {
            if (!empty($includeBlocks) && !in_array($blockName, $includeBlocks)) {
                continue;
            }

            $output .= str_repeat($indent, $indentMultiplier) . LD . $blockName . RD . PHP_EOL;

            if ($isNestable) {
                $output .= str_repeat($indent, $indentMultiplier) . '<div data-block-name="' . $blockName . '">' . PHP_EOL;
            }

            foreach ($atoms as $atomName => $atom) {
                $output .= str_repeat($indent, $indentMultiplier * 2) . LD . $atomName . RD . PHP_EOL;
                if ($atom['isPair']) {
                    $output .= str_repeat($indent, $indentMultiplier * 2) . LD . '/' . $atomName . RD . PHP_EOL;
                }
            }

            if ($isNestable) {
                $output .= str_repeat($indent, $indentMultiplier * 2) . LD . 'bloqs:children' . RD . PHP_EOL;
                $output .= str_repeat($indent, $indentMultiplier) . '</div>' . PHP_EOL;
            }

            $output .= str_repeat($indent, $indentMultiplier) . LD . '/' . $blockName . RD . PHP_EOL . PHP_EOL;
        }

        return rtrim($output);
    }

}
