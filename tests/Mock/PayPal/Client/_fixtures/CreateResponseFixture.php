<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures;

class CreateResponseFixture
{
    public const CREATE_PAYMENT_ID = 'PAY-9FS21791UL732760GLP2ASLY';
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
                    'related_resources' => [],
                ],
            ],
            'createTime' => '2018-11-20T13:16:30Z',
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-9FS21791UL732760GLP2ASLY',
                    'rel' => 'self',
                    'method' => 'GET',
                ],
                1 => [
                    'href' => self::CREATE_PAYMENT_APPROVAL_URL,
                    'rel' => 'approval_url',
                    'method' => 'REDIRECT',
                ],
                2 => [
                    'href' => 'https://api.sandbox.paypal.com/v1/payments/payment/PAY-9FS21791UL732760GLP2ASLY/execute',
                    'rel' => 'execute',
                    'method' => 'POST',
                ],
            ],
        ];
    }
}
