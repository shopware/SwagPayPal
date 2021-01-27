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
     * {@inheritdoc}
     */
    public function set($id, $service): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function get($id, $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE): ?object
    {
    }

    /**
     * {@inheritdoc}
     */
    public function has($id): bool
    {
    }

    /**
     * {@inheritdoc}
     */
    public function initialized($id): bool
    {
    }

    /**
     * {@inheritdoc}
     *
     * @return array|bool|float|int|string|null
     */
    public function getParameter($name)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function hasParameter($name): bool
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setParameter($name, $value): void
    {
    }
}
