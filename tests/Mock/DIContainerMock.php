<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock;

use Symfony\Component\DependencyInjection\ContainerInterface;

class DIContainerMock implements ContainerInterface
{
    /**
     * @param string      $id
     * @param object|null $service
     */
    public function set($id, $service): void
    {
    }

    /**
     * @param string $id
     * @param int    $invalidBehavior
     */
    public function get($id, $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE): ?object
    {
    }

    /**
     *  @param string $id
     */
    public function has($id): bool
    {
    }

    /**
     *  @param string $id
     */
    public function initialized($id): bool
    {
    }

    /**
     * @param string $name
     *
     * @return string|mixed
     */
    public function getParameter($name)
    {
    }

    /**
     * @param string $name
     */
    public function hasParameter($name): bool
    {
    }

    /**
     * @param string       $name
     * @param string|mixed $value
     */
    public function setParameter($name, $value): void
    {
    }
}
