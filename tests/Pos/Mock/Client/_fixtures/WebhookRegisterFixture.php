<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Client\_fixtures;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Pos\Api\Common\PosStruct;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\Api\Webhook\Subscription\CreateSubscription;

/**
 * @internal
 */
#[Package('checkout')]
class WebhookRegisterFixture
{
    public const WEBHOOK_SIGNING_KEY = 'G6MWAEw1Fc6FWPkfiJiZ3j8Ya76I5ZbEDVPtzcPl6L6scsylmK5AEDyNyMe8N5cy';

    public static function post(?PosStruct $data): ?array
    {
        TestCase::assertNotNull($data);
        TestCase::assertInstanceOf(CreateSubscription::class, $data);

        $salesChannelId = (new UuidConverter())->convertUuidToV1(TestDefaults::SALES_CHANNEL);

        TestCase::assertSame($salesChannelId, $data->getUuid());
        TestCase::assertSame(['InventoryBalanceChanged'], $data->getEventNames());
        TestCase::assertStringContainsString(TestDefaults::SALES_CHANNEL, $data->getDestination());

        return [
            'uuid' => $salesChannelId,
            'transportName' => 'WEBHOOK',
            'eventNames' => [
                'InventoryBalanceChanged',
            ],
            'updated' => '2020-08-05T19:40:24.285Z',
            'destination' => 'https://yoururl.domain',
            'contactEmail' => 'email_if_it_breaks@domain.com',
            'status' => 'ACTIVE',
            'signingKey' => self::WEBHOOK_SIGNING_KEY,
        ];
    }
}
