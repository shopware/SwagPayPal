<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Mock;

use Psr\Cache\CacheItemInterface;

class CacheItemMock implements CacheItemInterface
{
    /**
     * @return string
     */
    public function getKey()
    {
    }

    public function get()
    {
        return null;
    }

    /**
     * @return bool
     */
    public function isHit()
    {
    }

    /**
     * @return CacheItemInterface
     */
    public function set($value)
    {
    }

    /**
     * @return CacheItemInterface
     */
    public function expiresAt($expiration)
    {
    }

    /**
     * @return CacheItemInterface
     */
    public function expiresAfter($time)
    {
    }
}
