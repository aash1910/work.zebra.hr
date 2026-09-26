<?php

namespace Store\Dependency\Academe\AuthorizeNet\Request\Transaction;

class AuthOnly extends AuthCapture
{
    protected $transactionType = 'authOnlyTransaction';
}
