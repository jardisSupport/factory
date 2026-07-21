<?php

declare(strict_types=1);

namespace JardisSupport\Factory;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

/**
 * Generic container exception.
 */
class ContainerException extends RuntimeException implements ContainerExceptionInterface
{
}
