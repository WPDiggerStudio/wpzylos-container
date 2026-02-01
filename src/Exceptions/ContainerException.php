<?php

declare(strict_types=1);

namespace WPZylos\Framework\Container\Exceptions;

use Psr\Container\ContainerExceptionInterface;

/**
 * Container exception.
 *
 * Thrown when the container encounters an error during resolution.
 *
 * @package WPZylos\Framework\Container
 */
class ContainerException extends \Exception implements ContainerExceptionInterface
{
}
