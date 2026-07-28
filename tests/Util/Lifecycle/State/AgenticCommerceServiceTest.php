<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Lifecycle\State;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgenticCommerce\Exception\HoneyWebhookException;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Util\Lifecycle\State\AgenticCommerceService;

/**
 * @internal
 */
#[Package('checkout')]
class AgenticCommerceServiceTest extends TestCase
{
    private EntityRepository&MockObject $salesChannelRepository;

    private EntityRepository&MockObject $salesChannelTypeRepository;

    public function testAddAgenticSalesChannelTypeUpsertsType(): void
    {
        $context = Context::createDefaultContext();

        $service = $this->createService();

        $this->salesChannelTypeRepository
            ->expects(static::once())
            ->method('upsert')
            ->with(static::callback(static function (array $payload): bool {
                static::assertCount(1, $payload);
                static::assertSame(SwagPayPal::SALES_CHANNEL_TYPE_AGENTIC_COMMERCE, $payload[0]['id']);
                static::assertSame('regular-artificial-intelligence', $payload[0]['iconName']);
                static::assertSame('PayPal Agentic Commerce', $payload[0]['name']);
                static::assertSame('shopware AG', $payload[0]['manufacturer']);
                static::assertSame('PayPal Agentic Commerce Sales Channel', $payload[0]['description']);
                static::assertArrayHasKey('de-DE', $payload[0]['translations']);
                static::assertArrayHasKey('en-GB', $payload[0]['translations']);
                static::assertArrayHasKey('descriptionLong', $payload[0]['translations']['de-DE']);
                static::assertArrayHasKey('descriptionLong', $payload[0]['translations']['en-GB']);

                return true;
            }), static::identicalTo($context));

        $service->addAgenticSalesChannelType($context);
    }

    public function testHandleUninstallDeletesSalesChannelTypeWithoutAgenticSalesChannels(): void
    {
        $context = Context::createDefaultContext();
        $service = $this->createService();

        $this->expectAgenticSalesChannelSearch($context, []);

        $this->salesChannelTypeRepository
            ->expects(static::once())
            ->method('delete')
            ->with([['id' => SwagPayPal::SALES_CHANNEL_TYPE_AGENTIC_COMMERCE]], static::identicalTo($context));

        $service->handleUninstallAgentic($context);
    }

    public function testHandleUninstallKeepsSalesChannelTypeWithAgenticSalesChannels(): void
    {
        $context = Context::createDefaultContext();
        $service = $this->createService();

        $this->expectAgenticSalesChannelSearch($context, ['agentic-sales-channel-id']);

        $this->salesChannelTypeRepository
            ->expects(static::never())
            ->method('delete');

        $service->handleUninstallAgentic($context);
    }

    public function testDeactivateAgenticSalesChannelStateUpdatesExistingSalesChannels(): void
    {
        $context = Context::createDefaultContext();
        $service = $this->createService();

        $this->expectAgenticSalesChannelSearch($context, ['first-id', 'second-id']);

        $this->salesChannelRepository
            ->expects(static::once())
            ->method('update')
            ->with([
                ['id' => 'first-id', 'active' => false],
                ['id' => 'second-id', 'active' => false],
            ], static::identicalTo($context));

        $service->deactivateAgenticSalesChannelState($context);
    }

    public function testDeactivateAgenticSalesChannelStateDoesNothingWithoutSalesChannels(): void
    {
        $context = Context::createDefaultContext();
        $service = $this->createService();

        $this->expectAgenticSalesChannelSearch($context, []);

        $this->salesChannelRepository
            ->expects(static::never())
            ->method('update');

        $service->deactivateAgenticSalesChannelState($context);
    }

    public function testDeactivateAgenticSalesChannelStateIgnoresMissingWebhookRegistration(): void
    {
        $context = Context::createDefaultContext();
        $service = $this->createService();

        $this->expectAgenticSalesChannelSearch($context, ['agentic-sales-channel-id']);

        $this->salesChannelRepository
            ->expects(static::once())
            ->method('update')
            ->willThrowException(HoneyWebhookException::salesChannelNotRegistered());

        $service->deactivateAgenticSalesChannelState($context);
    }

    public function testDeactivateAgenticSalesChannelStateRethrowsOtherWebhookErrors(): void
    {
        $context = Context::createDefaultContext();
        $service = $this->createService();

        $this->expectAgenticSalesChannelSearch($context, ['agentic-sales-channel-id']);

        $this->salesChannelRepository
            ->expects(static::once())
            ->method('update')
            ->willThrowException(HoneyWebhookException::invalidSalesChannel());

        $this->expectException(HoneyWebhookException::class);

        $service->deactivateAgenticSalesChannelState($context);
    }

    /**
     * @param list<string> $ids
     */
    private function expectAgenticSalesChannelSearch(Context $context, array $ids): void
    {
        $this->salesChannelRepository
            ->expects(static::once())
            ->method('searchIds')
            ->with(static::callback(static function (Criteria $criteria): bool {
                static::assertCount(1, $criteria->getFilters());
                static::assertInstanceOf(EqualsFilter::class, $criteria->getFilters()[0]);
                static::assertSame('typeId', $criteria->getFilters()[0]->getField());
                static::assertSame(SwagPayPal::SALES_CHANNEL_TYPE_AGENTIC_COMMERCE, $criteria->getFilters()[0]->getValue());

                return true;
            }), static::identicalTo($context))
            ->willReturn($this->createIdSearchResult($ids));
    }

    private function createService(): AgenticCommerceService
    {
        $this->salesChannelRepository = $this->createMock(EntityRepository::class);
        $this->salesChannelTypeRepository = $this->createMock(EntityRepository::class);

        return new AgenticCommerceService(
            $this->salesChannelRepository,
            $this->salesChannelTypeRepository,
        );
    }

    /**
     * @param list<string> $ids
     */
    private function createIdSearchResult(array $ids): IdSearchResult
    {
        $data = [];
        foreach ($ids as $id) {
            $data[$id] = ['primaryKey' => $id, 'data' => []];
        }

        return new IdSearchResult(\count($ids), $data, new Criteria(), Context::createDefaultContext());
    }
}
