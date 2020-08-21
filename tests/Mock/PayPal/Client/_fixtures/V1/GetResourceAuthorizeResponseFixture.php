<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1;

class GetResourceAuthorizeResponseFixture
{
    public const ID = '86616545JW445624A';

    public static function get(): array
    {
        return [
            'id' => self::ID,
            'state' => 'captured',
            'amount' => [
                'total' => '21.85',
                'currency' => 'EUR',
                'details' => [
                    'subtotal' => '17.95',
                    'tax' => '0.00',
                    'shipping' => '3.90',
                    'insurance' => '0.00',
                    'handling_fee' => '0.00',
                    'shipping_discount' => '0.00',
                ],
            ],
            'payment_mode' => 'INSTANT_TRANSFER',
            'protection_eligibility' => 'ELIGIBLE',
            'protection_eligibility_type' => 'ITEM_NOT_RECEIVED_ELIGIBLE,UNAUTHORIZED_PAYMENT_ELIGIBLE',
            'parent_payment' => 'PAYID-LSAT63Q0NM35123M7232615L',
            'valid_until' => '2019-04-05T14:59:33Z',
            'create_time' => '2019-03-07T15:59:33Z',
            'update_time' => '2019-03-08T09:05:23Z',
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/authorization/86616545JW445624A',
                    'rel' => 'self',
                    'method' => 'GET',
                ],
                1 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/authorization/86616545JW445624A/capture',
                    'rel' => 'capture',
                    'method' => 'POST',
                ],
                2 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/authorization/86616545JW445624A/void',
                    'rel' => 'void',
                    'method' => 'POST',
                ],
                3 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/authorization/86616545JW445624A/reauthorize',
                    'rel' => 'reauthorize',
                    'method' => 'POST',
                ],
                4 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAYID-LSAT63Q0NM35123M7232615L',
                    'rel' => 'parent_payment',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}
