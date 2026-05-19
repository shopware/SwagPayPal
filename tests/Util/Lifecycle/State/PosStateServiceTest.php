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
use Swag\PayPal\Pos\Setting\Service\InformationDefaultService;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Util\Lifecycle\State\PosStateService;

/**
 * @internal
 */
#[Package('checkout')]
class PosStateServiceTest extends TestCase
{
    private EntityRepository&MockObject $salesChannelRepository;

    private EntityRepository&MockObject $salesChannelTypeRepository;

    private EntityRepository&MockObject $shippingRepository;

    private EntityRepository&MockObject $paymentMethodRepository;

    public function testAddPosSalesChannelTypeUpsertsType(): void
    {
        $context = Context::createDefaultContext();
        $service = $this->createService();

        $this->salesChannelTypeRepository
            ->expects($this->once())
            ->method('upsert')
            ->with(static::callback(static function (array $payload): bool {
                static::assertCount(1, $payload);
                static::assertSame(SwagPayPal::SALES_CHANNEL_TYPE_POS, $payload[0]['id']);
                static::assertSame('regular-money-bill', $payload[0]['iconName']);
                static::assertSame('Point of Sale – Zettle by PayPal', $payload[0]['name']);
                static::assertSame('Shopware', $payload[0]['manufacturer']);
                static::assertSame('Tools to build your business', $payload[0]['description']);
                static::assertArrayHasKey('de-DE', $payload[0]['translations']);
                static::assertArrayHasKey('en-GB', $payload[0]['translations']);
                static::assertArrayHasKey('descriptionLong', $payload[0]['translations']['de-DE']);
                static::assertArrayHasKey('descriptionLong', $payload[0]['translations']['en-GB']);

                return true;
            }), static::identicalTo($context));

        $service->addPosSalesChannelType($context);
    }

    public function testHandleUninstallDeletesSalesChannelTypeAndDefaultsWithoutPosSalesChannels(): void
    {
        $context = Context::createDefaultContext();
        $service = $this->createService();

        $this->expectPosSalesChannelSearch($context, []);

        $this->salesChannelTypeRepository
            ->expects($this->once())
            ->method('delete')
            ->with([['id' => SwagPayPal::SALES_CHANNEL_TYPE_POS]], static::identicalTo($context));
        $this->shippingRepository
            ->expects($this->once())
            ->method('delete')
            ->with([['id' => InformationDefaultService::POS_SHIPPING_METHOD_ID]], static::identicalTo($context));
        $this->paymentMethodRepository
            ->expects($this->once())
            ->method('searchIds')
            ->with(static::callback(static function (Criteria $criteria): bool {
                static::assertSame([InformationDefaultService::POS_PAYMENT_METHOD_ID], $criteria->getIds());

                return true;
            }), static::identicalTo($context))
            ->willReturn($this->createIdSearchResult([InformationDefaultService::POS_PAYMENT_METHOD_ID]));
        $this->paymentMethodRepository
            ->expects($this->once())
            ->method('update')
            ->with([[
                'id' => InformationDefaultService::POS_PAYMENT_METHOD_ID,
                'pluginId' => null,
            ]], static::identicalTo($context));
        $this->paymentMethodRepository
            ->expects($this->once())
            ->method('delete')
            ->with([['id' => InformationDefaultService::POS_PAYMENT_METHOD_ID]], static::identicalTo($context));

        $service->handleUninstallPos($context);
    }

    public function testHandleUninstallKeepsSalesChannelTypeAndDefaultsWithPosSalesChannels(): void
    {
        $context = Context::createDefaultContext();
        $service = $this->createService();

        $this->expectPosSalesChannelSearch($context, ['pos-sales-channel-id']);

        $this->salesChannelTypeRepository
            ->expects($this->never())
            ->method('delete');
        $this->shippingRepository
            ->expects($this->never())
            ->method('delete');
        $this->paymentMethodRepository
            ->expects($this->never())
            ->method('searchIds');
        $this->paymentMethodRepository
            ->expects($this->never())
            ->method('update');
        $this->paymentMethodRepository
            ->expects($this->never())
            ->method('delete');

        $service->handleUninstallPos($context);
    }

    public function testHandleUninstallSkipsMissingDefaultPaymentMethod(): void
    {
        $context = Context::createDefaultContext();
        $service = $this->createService();

        $this->expectPosSalesChannelSearch($context, []);

        $this->salesChannelTypeRepository
            ->expects($this->once())
            ->method('delete')
            ->with([['id' => SwagPayPal::SALES_CHANNEL_TYPE_POS]], static::identicalTo($context));
        $this->shippingRepository
            ->expects($this->once())
            ->method('delete')
            ->with([['id' => InformationDefaultService::POS_SHIPPING_METHOD_ID]], static::identicalTo($context));
        $this->paymentMethodRepository
            ->expects($this->once())
            ->method('searchIds')
            ->willReturn($this->createIdSearchResult([]));
        $this->paymentMethodRepository
            ->expects($this->never())
            ->method('update');
        $this->paymentMethodRepository
            ->expects($this->never())
            ->method('delete');

        $service->handleUninstallPos($context);
    }

    public function testDeactivatePosSalesChannelUpdatesExistingSalesChannels(): void
    {
        $context = Context::createDefaultContext();
        $service = $this->createService();

        $this->expectPosSalesChannelSearch($context, ['first-id', 'second-id']);

        $this->salesChannelRepository
            ->expects($this->once())
            ->method('update')
            ->with([
                ['id' => 'first-id', 'active' => false],
                ['id' => 'second-id', 'active' => false],
            ], static::identicalTo($context));

        $service->deactivatePosSalesChannel($context);
    }

    public function testDeactivatePosSalesChannelDoesNothingWithoutSalesChannels(): void
    {
        $context = Context::createDefaultContext();
        $service = $this->createService();

        $this->expectPosSalesChannelSearch($context, []);

        $this->salesChannelRepository
            ->expects($this->never())
            ->method('update');

        $service->deactivatePosSalesChannel($context);
    }

    /**
     * @param list<string> $ids
     */
    private function expectPosSalesChannelSearch(Context $context, array $ids): void
    {
        $this->salesChannelRepository
            ->expects($this->once())
            ->method('searchIds')
            ->with(static::callback(static function (Criteria $criteria): bool {
                static::assertCount(1, $criteria->getFilters());
                static::assertInstanceOf(EqualsFilter::class, $criteria->getFilters()[0]);
                static::assertSame('typeId', $criteria->getFilters()[0]->getField());
                static::assertSame(SwagPayPal::SALES_CHANNEL_TYPE_POS, $criteria->getFilters()[0]->getValue());

                return true;
            }), static::identicalTo($context))
            ->willReturn($this->createIdSearchResult($ids));
    }

    private function createService(): PosStateService
    {
        $this->salesChannelRepository = $this->createMock(EntityRepository::class);
        $this->salesChannelTypeRepository = $this->createMock(EntityRepository::class);
        $this->shippingRepository = $this->createMock(EntityRepository::class);
        $this->paymentMethodRepository = $this->createMock(EntityRepository::class);

        return new PosStateService(
            $this->salesChannelRepository,
            $this->salesChannelTypeRepository,
            $this->shippingRepository,
            $this->paymentMethodRepository,
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
