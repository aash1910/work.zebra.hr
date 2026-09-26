<?php

namespace BoldMinded\Bloqs\Service;

use BoldMinded\Bloqs\Controller\HookExecutor;

/**
 * @package     ExpressionEngine
 * @subpackage  Services
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

class FormSecret
{
    const KEY_PREFIX = 'bloqsFormSecret_';

    /**
     * @var int
     */
    private $entryId = 0;

    /**
     * @var int
     */
    private $fieldId = 0;

    /**
     * @var HookExecutor
     */
    private $hookExecutor;

    /**
     * @var string
     */
    private $secret = '';

    /**
     * @param HookExecutor $hookExecutor
     */
    public function __construct(HookExecutor $hookExecutor)
    {
        if (session_id() === '' && REQ === 'CP') {
            @session_start();
        }

        $this->hookExecutor = $hookExecutor;
    }

    /**
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * @return $this
     */
    public function setSecret(): FormSecret
    {
        $this->secret = md5(uniqid(rand(), true));
        $this->setSessionSecret();

        return $this;
    }

    /**
     * @return string
     */
    public function getKeyName(): string
    {
        $key = self::KEY_PREFIX . $this->getFieldId() . '_' . $this->getEntryid();

        // Possibly append additional unique identifiers to the key with a hook
        return $this->hookExecutor->secretFormKey($key);
    }

    /**
     * @return string|null
     */
    public function getSessionSecret()
    {
        return $_SESSION[$this->getKeyName()] ?? null;
    }

    /**
     * @return void
     */
    private function setSessionSecret()
    {
        $_SESSION[$this->getKeyName()] = $this->secret;
    }

    /**
     * @return string|null
     */
    public function getPostSecret()
    {
        return $_POST[$this->getKeyName()] ?? null;
    }

    /**
     * @return void
     */
    private function clearSecret()
    {
        unset($_SESSION[$this->getKeyName()]);
    }

    /**
     * @return bool
     */
    public function isSecretValid()
    {
        $postSecret = $this->getPostSecret();
        $sessionSecret = $this->getSessionSecret();

        // Secret is valid only once, so clear it as soon as we validate.
        $this->clearSecret();

        return $postSecret === $sessionSecret;
    }

    /**
     * @return int
     */
    public function getFieldId(): int
    {
        return $this->fieldId;
    }

    /**
     * @param int $fieldId
     * @return $this
     */
    public function setFieldId(int $fieldId): FormSecret
    {
        $this->fieldId = $fieldId;

        return $this;
    }

    public function getEntryid(): int
    {
        return $this->entryId;
    }

    /**
     * @param int $entryId
     * @return $this
     */
    public function setEntryId(int $entryId): FormSecret
    {
        $this->entryId = $entryId;

        return $this;
    }
}
