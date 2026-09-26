<?php

namespace Store\Dependency\Illuminate\Container;

use Exception;
use Store\Dependency\Psr\Container\NotFoundExceptionInterface;
class EntryNotFoundException extends Exception implements NotFoundExceptionInterface
{
    //
}
