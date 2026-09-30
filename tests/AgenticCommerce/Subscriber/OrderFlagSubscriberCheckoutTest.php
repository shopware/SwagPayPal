<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\AgenticCommerce\Subscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\AgenticCommerce\Routing\AgentSource;
use Swag\PayPal\AgenticCommerce\Subscriber\OrderFlagSubscriber;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Helper\FullCheckoutTrait;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(OrderFlagSubscriber::class)]
class OrderFlagSubscriberCheckoutTest extends TestCase
{
    use FullCheckoutTrait;
    use IntegrationTestBehaviour;

    public function testAgentOrderIsFlagged(): void
    {
        $token = $this->registerUser()->getToken();
        $agenticSalesChannelId = Uuid::randomHex();

        // built the same way as in AgentRequestContextResolver
        $agentSource = new AgentSource('merchantId', new \DateTime(), new \DateTime('+1 hour'), [], $agenticSalesChannelId);
        $context = $this->getContainer()->get(SalesChannelContextService::class)->get(new SalesChannelContextServiceParameters(
            salesChannelId: TestDefaults::SALES_CHANNEL,
            token: $token,
            originalContext: new Context($agentSource),
        ));

        $order = $this->placeOrder($this->addToCart($this->createProduct(), $context), $context);

        static::assertSame(TestDefaults::SALES_CHANNEL, $order->getSalesChannelId());
        static::assertSame($agenticSalesChannelId, $order->getCustomFieldsValue(SwagPayPal::ORDER_CUSTOM_FIELDS_PAYPAL_AGENTIC_COMMERCE_SALES_CHANNEL_ID));
    }

    public function testStoreApiOrderIsNotFlagged(): void
    {
        $context = $this->registerUser();

        $order = $this->placeOrder($this->addToCart($this->createProduct(), $context), $context);

        static::assertNull($order->getCustomFieldsValue(SwagPayPal::ORDER_CUSTOM_FIELDS_PAYPAL_AGENTIC_COMMERCE_SALES_CHANNEL_ID));
    }
}
