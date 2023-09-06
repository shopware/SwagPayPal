<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Client\_fixtures;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;

/**
 * @internal
 */
#[Package('checkout')]
class WebhookUnregisterFixture
{
    public static bool $sent = false;

    public static function delete(string $resourceUri): ?array
    {
        $salesChannelId = (new UuidConverter())->convertUuidToV1(TestDefaults::SALES_CHANNEL);

        TestCase::assertStringContainsString($salesChannelId, $resourceUri);

        self::$sent = true;

        return [];
    }
}
