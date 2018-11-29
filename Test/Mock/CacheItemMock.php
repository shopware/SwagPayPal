<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Mock;

use Psr\Cache\CacheItemInterface;

class CacheItemMock implements CacheItemInterface
{
    public function getKey()
    {
    }

    public function get()
    {
        return null;
    }

    public function isHit()
    {
    }

    public function set($value)
    {
    }

    public function expiresAt($expiration)
    {
    }

    public function expiresAfter($time)
    {
    }
}
