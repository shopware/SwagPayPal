<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Mock;

use Symfony\Component\DependencyInjection\ContainerInterface;

class DIContainerMock implements ContainerInterface
{
    public function set($id, $service): void
    {
    }

    /**
     * @return object
     */
    public function get($id, $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE)
    {
    }

    /**
     * @return bool
     */
    public function has($id)
    {
    }

    /**
     * @return bool
     */
    public function initialized($id)
    {
    }

    /**
     * @return mixed|void
     */
    public function getParameter($name)
    {
    }

    /**
     * @return bool
     */
    public function hasParameter($name)
    {
    }

    public function setParameter($name, $value): void
    {
    }
}
