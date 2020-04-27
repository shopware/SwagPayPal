<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock;

use Psr\Cache\CacheItemInterface;

class CacheItemMock implements CacheItemInterface
{
    public function getKey(): string
    {
    }

    public function get()
    {
        return null;
    }

    public function isHit(): bool
    {
    }

    /**
     * @param string|mixed $value
     *
     * @return static
     */
    public function set($value): CacheItemInterface
    {
        return $this;
    }

    /**
     * @param \DateTimeInterface|null $expiration
     *
     * @return static
     */
    public function expiresAt($expiration): CacheItemInterface
    {
    }

    /**
     * @param int|\DateInterval|null $time
     *
     * @return static
     */
    public function expiresAfter($time): CacheItemInterface
    {
    }
}
