<?php

namespace BoldMinded\Bloqs\Service;

use Bloqs_ft;

require_once APPPATH . 'fieldtypes/EE_Fieldtype.php';
require_once PATH_THIRD . 'bloqs/ft.bloqs.php';

class StandaloneField
{
    /**
     * @var array
     */
    private $blockDefinitions = [];

    /**
     * Misleading, but to EE $data is either a string, or an array of blocks from a revision or validation state
     *
     * @var array|string|null
     */
    private $data;

    /**
     * An actual collection of Block[] data from the Adapter
     *
     * @var array|null
     */
    private $blocks;

    /**
     * @var string
     */
    private $fieldName;

    /**
     * @var int
     */
    private $fieldId;

    /**
     * @var array
     */
    private $settings = [];

    /**
     * @param string $fieldName
     * @param int    $fieldId
     * @param array  $settings
     * @param array  $blocks
     */
    public function __construct(string $fieldName, int $fieldId, array $settings = [])
    {
        $this->fieldName = $fieldName;
        $this->fieldId = $fieldId;
        $this->settings = $settings;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function render()
    {
        $bloqsFt = new Bloqs_ft;
        $bloqsFt->settings = $this->settings;
        $bloqsFt->field_name = $this->fieldName;
        $bloqsFt->field_id = $this->fieldId;

        return $bloqsFt->display_field($this->data, $this->blockDefinitions, $this->blocks);
    }

    /**
     * @param array|string $data
     * @return StandaloneField
     */
    public function setData($data): StandaloneField
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param array $blocks
     * @return StandaloneField
     */
    public function setBlocks($blocks): StandaloneField
    {
        $this->blocks = $blocks;

        return $this;
    }

    /**
     * @param array $blockDefinitions
     * @return $this
     */
    public function setBlockDefinitions(array $blockDefinitions = [])
    {
        $this->blockDefinitions = $blockDefinitions;

        return $this;
    }
}
