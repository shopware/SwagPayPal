<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\AgenticCommerce\Subscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\Events\ProductGatewayCriteriaEvent;
use Shopware\Core\Framework\Api\Context\AdminSalesChannelApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\AgenticCommerce\Routing\AgentSource;
use Swag\PayPal\AgenticCommerce\Subscriber\ProductFilterSubscriber;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ProductFilterSubscriber::class)]
class ProductFilterSubscriberTest extends TestCase
{
    public function testNoAgentSource(): void
    {
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext
            ->method('getContext')
            ->willReturn(Context::createCLIContext());

        $criteria = new Criteria();
        $event = new ProductGatewayCriteriaEvent([], $criteria, $salesChannelContext);

        (new ProductFilterSubscriber())->onProductGatewayCriteria($event);

        static::assertCount(0, $criteria->getFilters());
    }

    public function testProductAddCriteria(): void
    {
        $source = new AgentSource('merchantId', new \DateTime(), new \DateTime(), [], Uuid::randomHex());
        $source->setStreamId(Uuid::randomHex());

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext
            ->method('getContext')
            ->willReturn(Context::createCLIContext($source));

        $criteria = new Criteria();
        $event = new ProductGatewayCriteriaEvent([], $criteria, $salesChannelContext);

        (new ProductFilterSubscriber())->onProductGatewayCriteria($event);

        static::assertCount(1, $criteria->getFilters());
        $filter = $criteria->getFilters()[0];

        static::assertInstanceOf(EqualsFilter::class, $filter);
        static::assertSame('streams.id', $filter->getField());
        static::assertSame($source->getStreamId(), $filter->getValue());
    }

    public function testProductAddCriteriaAdminSalesChannelSource(): void
    {
        $originalSource = new AgentSource('merchantId', new \DateTime(), new \DateTime(), [], Uuid::randomHex());
        $originalSource->setStreamId(Uuid::randomHex());
        $source = new AdminSalesChannelApiSource(Uuid::randomHex(), Context::createCLIContext($originalSource));

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext
            ->method('getContext')
            ->willReturn(Context::createCLIContext($source));

        $criteria = new Criteria();
        $event = new ProductGatewayCriteriaEvent([], $criteria, $salesChannelContext);

        (new ProductFilterSubscriber())->onProductGatewayCriteria($event);

        static::assertCount(1, $criteria->getFilters());
        $filter = $criteria->getFilters()[0];

        static::assertInstanceOf(EqualsFilter::class, $filter);
        static::assertSame('streams.id', $filter->getField());
        static::assertSame($originalSource->getStreamId(), $filter->getValue());
    }
}
