<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Mock\Client\_fixtures;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;

class WebhookUnregisterFixture
{
    /**
     * @var bool
     */
    public static $sent = false;

    public static function delete(string $resourceUri): ?array
    {
        $salesChannelId = (new UuidConverter())->convertUuidToV1(Defaults::SALES_CHANNEL);

        TestCase::assertStringContainsString($salesChannelId, $resourceUri);

        self::$sent = true;

        return [];
    }
}
