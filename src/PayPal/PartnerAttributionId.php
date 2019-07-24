<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal;

final class PartnerAttributionId
{
    /**
     * Shopware Partner Id for PayPal Classic or Express-Checkout
     */
    public const PAYPAL_CLASSIC = 'Shopware_Cart_EC_6native';

    /**
     * Shopware Partner Id for PayPal Plus
     */
    public const PAYPAL_PLUS = 'Shopware_Cart_Plus_6native';

    /**
     * Shopware Partner Id for Smart Payment Buttons
     */
    public const SMART_PAYMENT_BUTTONS = 'Shopware_Cart_SPB_6native';

    /**
     * Shopware Partner Id for Express-Checkout
     */
    public const PAYPAL_EXPRESS_CHECKOUT = 'Shopware_Cart_ECS_6native';

    /**
     * Shopware Partner Id for Installments
     */
    public const PAYPAL_RATE = 'Shopware_Cart_Rate_6native';

    private function __construct()
    {
    }
}
