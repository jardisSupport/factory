<?php

declare(strict_types=1);

namespace JardisSupport\Factory;

use Psr\Container\NotFoundExceptionInterface;
use InvalidArgumentException;

/**
 * Entry not found in the container.
 */
class NotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface
{
    public function __construct(string $id, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Entry "%s" not found in container.', $id), $code, $previous);
    }
}
