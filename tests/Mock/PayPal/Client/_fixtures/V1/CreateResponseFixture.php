<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1;

use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
class CreateResponseFixture
{
    public const CREATE_PAYMENT_ID = 'PAYID-L4Z5SZA5FJ16145VJ547490N';
    public const CREATE_PAYMENT_APPROVAL_TOKEN = 'EC-44X706219E3526258';
    public const CREATE_PAYMENT_APPROVAL_URL = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=' . self::CREATE_PAYMENT_APPROVAL_TOKEN;

    public static function get(): array
    {
        return [
            'id' => self::CREATE_PAYMENT_ID,
            'intent' => 'sale',
            'state' => 'created',
            'payer' => [
                'payment_method' => 'paypal',
            ],
            'application_context' => [
                'locale' => 'en-GB',
                'landing_page' => 'Login',
                'brand_name' => 'Storefront',
                'shipping_preference' => 'SET_PROVIDED_ADDRESS',
                'user_action' => 'commit',
            ],
            'transactions' => [
                0 => [
                    'amount' => [
                        'total' => '970.00',
                        'currency' => 'EUR',
                        'details' => [
                            'subtotal' => '815.13',
                            'tax' => '154.87',
                            'shipping' => '0.00',
                        ],
                    ],
                    'item_list' => [
                        'items' => [
                            0 => [
                                'name' => 'Test',
                                'sku' => 'SW10000',
                                'price' => '970.00',
                                'currency' => 'EUR',
                                'tax' => '0.00',
                                'quantity' => 1,
                            ],
                        ],
                    ],
                    'related_resources' => [],
                ],
            ],
            'create_time' => '2020-08-12T11:58:27Z',
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAYID-L4Z5SZA5FJ16145VJ547490N',
                    'rel' => 'self',
                    'method' => 'GET',
                ],
                1 => [
                    'href' => self::CREATE_PAYMENT_APPROVAL_URL,
                    'rel' => 'approval_url',
                    'method' => 'REDIRECT',
                ],
                2 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAYID-L4Z5SZA5FJ16145VJ547490N/execute',
                    'rel' => 'execute',
                    'method' => 'POST',
                ],
            ],
        ];
    }
}
