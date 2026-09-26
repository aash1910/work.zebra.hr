<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store;

use EE_Output;

/**
 * StubOutput class prevents ExpressionEngine member class from displaying errors
 */
class StubOutput extends EE_Output
{
    protected $oldOutput;

    public function __construct(EE_Output $oldOutput = null)
    {
        $this->oldOutput = $oldOutput;
        parent::__construct();
    }

    /**
     * Stub show_message() function
     */
    public function show_message($data, $xhtml = true, $redirect_url = false, $template_name = 'generic')
    {
    }

    /**
     * We still want show_user_error to call the real show_message function
     *
     * @param string $type
     * @param $errors
     * @param string $heading
     */
    public function show_user_error($type = 'submission', $errors = '', $heading = '', $redirect_url = '')
    {
        if ($this->oldOutput) {
            $this->oldOutput->show_user_error($type, $errors, $heading);
        }
    }
}
