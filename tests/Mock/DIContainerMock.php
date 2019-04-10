<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Mock;

use Symfony\Component\DependencyInjection\ContainerInterface;

class DIContainerMock implements ContainerInterface
{
    public function set($id, $service): void
    {
    }

    public function get($id, $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE)
    {
    }

    public function has($id)
    {
    }

    public function initialized($id)
    {
    }

    public function getParameter($name)
    {
    }

    public function hasParameter($name)
    {
    }

    public function setParameter($name, $value): void
    {
    }
}
