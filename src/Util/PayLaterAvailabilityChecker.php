<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util;

use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
class PayLaterAvailabilityChecker
{
    /**
     * @var array<string, array<string, float>>
     *
     * @see https://developer.paypal.com/studio/checkout/pay-later/{{countryCode}}
     */
    public const PAYPAL_PAY_LATER_CRITERIA = [
        'DE' => ['currency' => 'EUR', 'minAmount' => 1.00, 'maxAmount' => 5000.00],
        'FR' => ['currency' => 'EUR', 'minAmount' => 30.00, 'maxAmount' => 2000.00],
        'IT' => ['currency' => 'EUR', 'minAmount' => 30.00, 'maxAmount' => 2000.00],
        'ES' => ['currency' => 'EUR', 'minAmount' => 30.00, 'maxAmount' => 2000.00],
        'GB' => ['currency' => 'GBP', 'minAmount' => 30.00, 'maxAmount' => 2000.00],
        'US' => ['currency' => 'USD', 'minAmount' => 30.00, 'maxAmount' => 10000.00],
        'AU' => ['currency' => 'AUD', 'minAmount' => 30.00, 'maxAmount' => 2000.00],
    ];

    public static function isPayLaterAvailable(string $countryCode, string $currencyCode, float $totalAmount): bool
    {
        if (!isset(self::PAYPAL_PAY_LATER_CRITERIA[$countryCode])) {
            return false;
        }

        $criteria = self::PAYPAL_PAY_LATER_CRITERIA[$countryCode];

        return $currencyCode === $criteria['currency']
            && $totalAmount >= $criteria['minAmount']
            && $totalAmount <= $criteria['maxAmount'];
    }
}
