<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util;

use Shopware\Core\Framework\Log\Package;

/**
 * @url https://developer.paypal.com/reference/locale-codes/
 */
#[Package('checkout')]
final class SupportedLocales
{
    public const LOCALES = [
        'AT' => [
            0 => 'de_DE',
            1 => 'en_US',
        ],
        'ZA' => [
            0 => 'en_US',
            1 => 'fr_XC',
            2 => 'es_XC',
            3 => 'zh_XC',
        ],
        'US' => [
            0 => 'en_US',
            1 => 'fr_XC',
            2 => 'es_XC',
            3 => 'zh_XC',
        ],
    ];
}
