<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Mock;

use Psr\Cache\CacheItemInterface;
use Shopware\Core\Defaults;

class CacheItemWithTokenMock implements CacheItemInterface
{
    public const ACCESS_TOKEN = 'A21AAEaQMaSheELTFsynkQLwXBZIr-fObE9PtGjr6_SOVEBXWNaJu1DvwKfLiJdxZ1aNtyYwK0ToZEL1i6TL5Dq9Qm30ZQfkA';

    public function getKey()
    {
    }

    public function get()
    {
        $expireDate = new \DateTime();
        $expireDate->add(new \DateInterval('PT5H'));
        $expireDate = $expireDate->format(Defaults::STORAGE_DATE_FORMAT);

        return 'O:27:"SwagPayPal\\PayPal\\Api\\Token":7:{s:34:"'
            . "\0" . 'SwagPayPal\\PayPal\\Api\\Token' . "\0" . 'scope";s:275:"https://uri.paypal.com/services/subscriptions https://api.paypal.com/v1/payments/.* https://api.paypal.com/v1/vault/credit-card https://uri.paypal.com/services/applications/webhooks openid https://uri.paypal.com/payments/payouts https://api.paypal.com/v1/vault/credit-card/.*";s:34:"'
            . "\0" . 'SwagPayPal\\PayPal\\Api\\Token' . "\0" . 'nonce";s:63:"2018-12-05T13:50:21ZJu6_H5bB4xm1hUngie3jG7DGmb1p1OHDlJXSjGAbaPw";s:40:"'
            . "\0" . 'SwagPayPal\\PayPal\\Api\\Token' . "\0" . 'accessToken";s:97:"' . self::ACCESS_TOKEN . '";s:38:"'
            . "\0" . 'SwagPayPal\\PayPal\\Api\\Token' . "\0" . 'tokenType";s:6:"Bearer";s:34:"'
            . "\0" . 'SwagPayPal\\PayPal\\Api\\Token' . "\0" . 'appId";s:21:"APP-80W284485P519543T";s:38:"'
            . "\0" . 'SwagPayPal\\PayPal\\Api\\Token' . "\0" . 'expiresIn";i:32385;s:43:"'
            . "\0" . 'SwagPayPal\\PayPal\\Api\\Token' . "\0" . 'expireDateTime";O:8:"DateTime":3:{s:4:"date";s:23:"' . $expireDate . '";s:13:"timezone_type";i:3;s:8:"timezone";s:13:"Europe/Berlin";}}';
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
