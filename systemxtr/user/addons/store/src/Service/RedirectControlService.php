<?php
namespace Store\Service;

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

use Store\StubOutput;
use Store\StubFunctions;

class RedirectControlService extends AbstractService
{
    protected $orig_output;
    protected $orig_functions;
    protected $redirectsOn = true;

    public function turnOffRedirects()
    {
        $this->orig_output = ee()->output;
        $this->orig_functions = ee()->functions;
        ee()->remove('output');
        ee()->set('output', new StubOutput($this->orig_output));
        ee()->remove('functions');
        ee()->set('functions', new StubFunctions($this->orig_functions));
        $this->redirectsOn = false;
    }

    public function restoreRedirects()
    {
        ee()->remove('output');
        ee()->set('output', $this->orig_output);
        ee()->remove('functions');
        ee()->set('functions', $this->orig_functions);
        $this->redirectsOn = true;
    }

    public function isRedirectsOn()
    {
        return $this->redirectsOn;
    }
}
