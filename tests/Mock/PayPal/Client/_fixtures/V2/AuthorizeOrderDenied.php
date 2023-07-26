<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;

/**
 * @internal
 */
#[Package('checkout')]
class AuthorizeOrderDenied
{
    public const ID = '5YK02325A213639FF';

    public static function get(): array
    {
        return [
            'id' => self::ID,
            'status' => 'COMPLETED',
            'intent' => PaymentIntentV2::AUTHORIZE,
            'purchase_units' => [
                0 => [
                    'reference_id' => 'default',
                    'shipping' => [
                        'name' => [
                            'full_name' => 'Test Test',
                        ],
                        'address' => [
                            'address_line_1' => 'Ebbinghoff 10',
                            'admin_area_2' => 'Schöppingen',
                            'postal_code' => '48624',
                            'country_code' => 'DE',
                        ],
                    ],
                    'payments' => [
                        'authorizations' => [
                            0 => \array_merge(
                                GetAuthorization::get(),
                                ['status' => 'DENIED'],
                            ),
                        ],
                    ],
                ],
            ],
            'payment_source' => [
                'paypal' => [
                    'email_address' => 'customer-de@shopware.com',
                    'account_id' => 'XTW5U37QPXKJE',
                    'account_status' => 'VERIFIED',
                    'name' => [
                        'given_name' => 'Kunde',
                        'surname' => 'Deutschland',
                    ],
                    'address' => [
                        'address_line_1' => 'Ebbinghoff 10',
                        'admin_area_2' => 'Schöppingen',
                        'postal_code' => '48624',
                        'country_code' => 'DE',
                    ],
                ],
            ],
            'links' => [
                0 => [
                    'href' => 'https://api.sandbox.paypal.com/v2/checkout/orders/' . self::ID,
                    'rel' => 'self',
                    'method' => 'GET',
                ],
            ],
        ];
    }
}
