<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Log;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Util\Log\LogArea;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(LogArea::class)]
class LogAreaTest extends TestCase
{
    #[DataProvider('areaProvider')]
    public function testForClass(string $class, string $expected): void
    {
        static::assertSame($expected, LogArea::forClass($class));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function areaProvider(): iterable
    {
        $areas = [
            'Checkout\ExpressCheckout\SalesChannel\ExpressCreateOrderRoute' => LogArea::EXPRESS,
            'Checkout\Payment\MessageQueue\TransactionStatusSyncMessageHandler' => LogArea::STATUS_SYNC,
            'Checkout\Payment\ScheduledTask\TransactionStatusSyncTaskHandler' => LogArea::STATUS_SYNC,
            'Checkout\Order\Shipping\MessageQueue\ShippingInformationMessageHandler' => LogArea::TRACKING,
            'Checkout\PUI\PUISubscriber' => LogArea::PUI,
            'Checkout\Payment\Method\AbstractPaymentMethodHandler' => LogArea::CHECKOUT,
            'Checkout\SalesChannel\CreateOrderRoute' => LogArea::CHECKOUT,
            'OrdersApi\Builder\ItemListProvider' => LogArea::CHECKOUT,
            'OrdersApi\Administration\PayPalOrdersController' => LogArea::ADMIN,
            'Webhook\WebhookController' => LogArea::WEBHOOK,
            'RestApi\Client\Client' => LogArea::API,
            'Setting\Service\SettingsSaver' => LogArea::SETTINGS,
            'Storefront\Controller\PayPalController' => LogArea::STOREFRONT,
            'Installment\Banner\InstallmentBannerSubscriber' => LogArea::STOREFRONT,
            'AgenticCommerce\HoneyWebhookService' => LogArea::AGENTIC_COMMERCE,
            'Pos\Sync\ImageSyncer' => LogArea::POS,
            'Reporting\ScheduledTask\TurnoverReportingTaskHandler' => LogArea::REPORTING,
            'Dispute\Something' => LogArea::OTHER,
            'Test\Something' => LogArea::OTHER,
        ];

        foreach ($areas as $class => $area) {
            yield $class => ['Swag\PayPal\\' . $class, $area];
        }

        yield 'outside the plugin' => ['Shopware\Core\Checkout\Payment\PaymentProcessor', LogArea::OTHER];
    }
}
