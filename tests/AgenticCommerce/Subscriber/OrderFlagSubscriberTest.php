<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\AgenticCommerce\Subscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Order\CartConvertedEvent;
use Shopware\Core\Checkout\Cart\Order\OrderConversionContext;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Api\Context\AdminSalesChannelApiSource;
use Shopware\Core\Framework\Api\Context\ContextSource;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\AgenticCommerce\Routing\AgentSource;
use Swag\PayPal\AgenticCommerce\Subscriber\OrderFlagSubscriber;
use Swag\PayPal\SwagPayPal;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(OrderFlagSubscriber::class)]
class OrderFlagSubscriberTest extends TestCase
{
    public function testNoAgentSource(): void
    {
        $event = $this->createEvent(new SalesChannelApiSource(Uuid::randomHex()));

        (new OrderFlagSubscriber())->onCartConverted($event);

        static::assertArrayNotHasKey('customFields', $event->getConvertedCart());
    }

    public function testAdminSalesChannelSourceWithoutAgent(): void
    {
        $source = new AdminSalesChannelApiSource(Uuid::randomHex(), Context::createDefaultContext(new AdminApiSource(Uuid::randomHex())));
        $event = $this->createEvent($source);

        (new OrderFlagSubscriber())->onCartConverted($event);

        static::assertArrayNotHasKey('customFields', $event->getConvertedCart());
    }

    public function testFlagsOrderForAgentSource(): void
    {
        $agentSource = $this->createAgentSource();
        $event = $this->createEvent($agentSource);

        (new OrderFlagSubscriber())->onCartConverted($event);

        static::assertSame(
            [SwagPayPal::ORDER_CUSTOM_FIELDS_PAYPAL_AGENTIC_COMMERCE_SALES_CHANNEL_ID => $agentSource->salesChannelId],
            $event->getConvertedCart()['customFields'] ?? null,
        );
    }

    public function testFlagsOrderForAdminSalesChannelSource(): void
    {
        $agentSource = $this->createAgentSource();
        $source = new AdminSalesChannelApiSource(Uuid::randomHex(), Context::createDefaultContext($agentSource));
        $event = $this->createEvent($source);

        (new OrderFlagSubscriber())->onCartConverted($event);

        static::assertSame(
            [SwagPayPal::ORDER_CUSTOM_FIELDS_PAYPAL_AGENTIC_COMMERCE_SALES_CHANNEL_ID => $agentSource->salesChannelId],
            $event->getConvertedCart()['customFields'] ?? null,
        );
    }

    public function testKeepsExistingCustomFields(): void
    {
        $agentSource = $this->createAgentSource();
        $event = $this->createEvent($agentSource, ['customFields' => ['foo' => 'bar']]);

        (new OrderFlagSubscriber())->onCartConverted($event);

        static::assertSame(
            ['foo' => 'bar', SwagPayPal::ORDER_CUSTOM_FIELDS_PAYPAL_AGENTIC_COMMERCE_SALES_CHANNEL_ID => $agentSource->salesChannelId],
            $event->getConvertedCart()['customFields'] ?? null,
        );
    }

    /**
     * @param array<string, mixed> $convertedCart
     */
    private function createEvent(ContextSource $source, array $convertedCart = []): CartConvertedEvent
    {
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext
            ->method('getContext')
            ->willReturn(Context::createDefaultContext($source));

        return new CartConvertedEvent(new Cart('token'), $convertedCart, $salesChannelContext, new OrderConversionContext());
    }

    private function createAgentSource(): AgentSource
    {
        return new AgentSource('merchantId', new \DateTime(), new \DateTime(), [], Uuid::randomHex());
    }
}
