<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Service;

use Store\Dependency\Symfony\Component\HttpFoundation\Request;

/**
 * Easy access to the Symfony Request class
 */
class RequestService extends Request
{
    public function __construct($ee)
    {
        // similar to Request::createFromGlobals(), but allows lazy initialization
        parent::__construct(
            $_GET ?? [],
            $_POST ?? [],
            [], // attributes
            $_COOKIE ?? [],
            $_FILES ?? [],
            $_SERVER ?? []
        );
    }
}
