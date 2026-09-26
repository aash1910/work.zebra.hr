<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store;

use EE_Functions;

/**
 * To prevent ExpressionEngine’s built‑in member registration flow from hard‑redirecting and aborting our request during embedded flows (e.g., checkout, AJAX).
 * EE’s Member_register calls ee()->functions->redirect() after processing; in our context that redirect would prematurely end the request and break Store’s flow.
 * Store\StubFunctions temporarily replaces ee()->functions so any redirect is captured (stored and logged) instead of executed, allowing our registration logic to complete and return the correct response.
 * We then restore the original ee()->functions.
 * You can see this used in MemberService::register_member_from_post where we swap in StubOutput and StubFunctions around the call to register_member().
 */
class StubFunctions extends EE_Functions
{
    protected $oldFunctions;
    public $lastRedirect;

    public function __construct(EE_Functions $oldFunctions = null)
    {
        $this->oldFunctions = $oldFunctions;
        // Do not call parent constructor; EE_Functions does not define one
    }

    /**
     * Capture redirect attempts instead of exiting the request
     */
    public function redirect($url, $method = 'auto', $code = 302)
    {
        if (function_exists('ee')) {
            ee()->load->library('logger');
            ee()->logger->developer('StubFunctions captured redirect: ' . $url);
        }
        $this->lastRedirect = $url;
        // Intentionally do not redirect to allow surrounding flow to continue
    }
}


