<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock;

use Psr\Cache\CacheItemInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

class CacheItemWithTokenMock implements CacheItemInterface
{
    public const ACCESS_TOKEN = 'A21AAEaQMaSheELTFsynkQLwXBZIr-fObE9PtGjr6_SOVEBXWNaJu1DvwKfLiJdxZ1aNtyYwK0ToZEL1i6TL5Dq9Qm30ZQfkA';

    public function getKey(): string
    {
        return Uuid::randomHex();
    }

    public function get()
    {
        $expireDate = new \DateTime();
        $expireDate->add(new \DateInterval('PT5H'));
        $expireDate = $expireDate->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        return "O:32:\"Swag\PayPal\RestApi\V1\Api\Token\":7:{"
            . "s:39:\"\x00Swag\PayPal\RestApi\V1\Api\Token\x00scope\";s:275:\"https://uri.paypal.com/services/subscriptions https://api.paypal.com/v1/payments/.* https://api.paypal.com/v1/vault/credit-card https://uri.paypal.com/services/applications/webhooks openid https://uri.paypal.com/payments/payouts https://api.paypal.com/v1/vault/credit-card/.*\";"
            . "s:39:\"\x00Swag\PayPal\RestApi\V1\Api\Token\x00nonce\";s:63:\"2018-11-28T09:55:25Z-e1ZbHti0TBbVkqQDZSsZ7YkzM5rpibcPAsW8wcS-hw\";"
            . "s:45:\"\x00Swag\PayPal\RestApi\V1\Api\Token\x00accessToken\";s:97:\"" . self::ACCESS_TOKEN . '";'
            . "s:43:\"\x00Swag\PayPal\RestApi\V1\Api\Token\x00tokenType\";s:6:\"Bearer\";"
            . "s:39:\"\x00Swag\PayPal\RestApi\V1\Api\Token\x00appId\";s:21:\"APP-80W284485P519543T\";"
            . "s:43:\"\x00Swag\PayPal\RestApi\V1\Api\Token\x00expiresIn\";i:32389;"
            . "s:48:\"\x00Swag\PayPal\RestApi\V1\Api\Token\x00expireDateTime\";O:8:\"DateTime\":3:{s:4:\"date\";s:23:\"" . $expireDate . '";s:13:"timezone_type";i:3;s:8:"timezone";s:3:"UTC";}}';
    }

    public function isHit(): bool
    {
        return true;
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
        return $this;
    }

    /**
     * @param \DateInterval|int|null $time
     *
     * @return static
     */
    public function expiresAfter($time): CacheItemInterface
    {
        return $this;
    }
}
