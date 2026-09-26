<?php

namespace Store\Dependency\Illuminate\Contracts\Container;

use Exception;
use Store\Dependency\Psr\Container\ContainerExceptionInterface;
class CircularDependencyException extends Exception implements ContainerExceptionInterface
{
    //
}
