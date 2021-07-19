<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Client\_fixtures;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Swag\PayPal\Pos\Api\Common\PosStruct;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\Api\Webhook\Subscription\UpdateSubscription;

class WebhookUpdateFixture
{
    public static bool $sent = false;

    public static function put(string $resourceUri, PosStruct $data): ?array
    {
        TestCase::assertInstanceOf(UpdateSubscription::class, $data);

        $salesChannelId = (new UuidConverter())->convertUuidToV1(Defaults::SALES_CHANNEL);

        TestCase::assertSame(['InventoryBalanceChanged'], $data->getEventNames());
        TestCase::assertStringContainsString($salesChannelId, $resourceUri);
        TestCase::assertStringContainsString(Defaults::SALES_CHANNEL, $data->getDestination());

        self::$sent = true;

        return [];
    }
}
