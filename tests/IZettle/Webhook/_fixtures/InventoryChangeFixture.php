<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Webhook\_fixtures;

use Swag\PayPal\Test\IZettle\ConstantsForTesting;

class InventoryChangeFixture
{
    public const TIMESTAMP = '2020-08-12T06:42:09.938Z';

    public static function getWebhookFixture(string $eventName = 'InventoryBalanceChanged'): array
    {
        return [
            'organizationUuid' => '1ce52f60-645f-11ea-a80e-0cec44ada668',
            'messageUuid' => 'f2601720-dc66-11ea-9c1c-05298cb0156a',
            'eventName' => $eventName,
            'messageId' => '6e6d5284-1d31-544f-9c1c-05298cb0156a',
            'payload' => \json_encode(self::getPayloadFixture()),
            'timestamp' => self::TIMESTAMP,
        ];
    }

    public static function getPayloadFixture(): array
    {
        return [
            'organizationUuid' => '1ce52f60-645f-11ea-a80e-0cec44ada668',
            'updated' => [
                'uuid' => '1ce66164-645f-11ea-b7ec-61d218b4153c',
                'timestamp' => '2020-08-12T06:42:09.893+0000',
                'userType' => 'USER',
                'clientUuid' => '51e57b0d-1ea2-48a4-b0a0-91d90424e62c',
            ],
            'balanceBefore' => [
                [
                    'organizationUuid' => '1ce52f60-645f-11ea-a80e-0cec44ada668',
                    'locationUuid' => ConstantsForTesting::LOCATION_STORE,
                    'productUuid' => ConstantsForTesting::PRODUCT_A_ID_CONVERTED,
                    'variantUuid' => ConstantsForTesting::PRODUCT_A_ID_VARIANT,
                    'created' => '2020-08-12T06:36:05.412+0000',
                    'balance' => '6',
                ],
                [
                    'organizationUuid' => '1ce52f60-645f-11ea-a80e-0cec44ada668',
                    'locationUuid' => ConstantsForTesting::LOCATION_STORE,
                    'productUuid' => ConstantsForTesting::PRODUCT_B_ID_CONVERTED,
                    'variantUuid' => ConstantsForTesting::VARIANT_A_ID_CONVERTED,
                    'created' => '2020-08-12T06:36:05.412+0000',
                    'balance' => '17',
                ],
                [
                    'organizationUuid' => '1ce52f60-645f-11ea-a80e-0cec44ada668',
                    'locationUuid' => ConstantsForTesting::LOCATION_STORE,
                    'productUuid' => ConstantsForTesting::PRODUCT_B_ID_CONVERTED,
                    'variantUuid' => ConstantsForTesting::VARIANT_B_ID_CONVERTED,
                    'created' => '2020-08-12T06:36:05.412+0000',
                    'balance' => '13',
                ],
            ],
            'balanceAfter' => [
                [
                    'organizationUuid' => '1ce52f60-645f-11ea-a80e-0cec44ada668',
                    'locationUuid' => ConstantsForTesting::LOCATION_STORE,
                    'productUuid' => ConstantsForTesting::PRODUCT_A_ID_CONVERTED,
                    'variantUuid' => ConstantsForTesting::PRODUCT_A_ID_VARIANT,
                    'balance' => '5',
                ],
                [
                    'organizationUuid' => '1ce52f60-645f-11ea-a80e-0cec44ada668',
                    'locationUuid' => ConstantsForTesting::LOCATION_STORE,
                    'productUuid' => ConstantsForTesting::PRODUCT_B_ID_CONVERTED,
                    'variantUuid' => ConstantsForTesting::VARIANT_A_ID_CONVERTED,
                    'balance' => '15',
                ],
                [
                    'organizationUuid' => '1ce52f60-645f-11ea-a80e-0cec44ada668',
                    'locationUuid' => ConstantsForTesting::LOCATION_STORE,
                    'productUuid' => ConstantsForTesting::PRODUCT_B_ID_CONVERTED,
                    'variantUuid' => ConstantsForTesting::VARIANT_B_ID_CONVERTED,
                    'balance' => '10',
                ],
            ],
            'externalUuid' => null,
        ];
    }

    public static function getSignature(): string
    {
        $payloadToSign = \stripslashes(self::TIMESTAMP . '.' . \json_encode(self::getPayloadFixture()));

        return \hash_hmac('sha256', $payloadToSign, ConstantsForTesting::WEBHOOK_SIGNING_KEY);
    }
}
