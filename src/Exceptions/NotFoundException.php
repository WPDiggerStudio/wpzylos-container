<?php

declare(strict_types=1);

namespace WPZylos\Framework\Container\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Not found exception.
 *
 * Thrown when a requested service is not found in the container.
 *
 * @package WPZylos\Framework\Container
 */
class NotFoundException extends \Exception implements NotFoundExceptionInterface
{
}
