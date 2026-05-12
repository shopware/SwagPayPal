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
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Util\Lifecycle\State\PosStateService;

/**
 * @internal
 */
#[Package('checkout')]
class PosStateServiceTest extends TestCase
{
    private EntityRepository&MockObject $salesChannelRepository;

    public function testPosSalesChannelsExistsReturnsTrueWhenSalesChannelsWereFound(): void
    {
        $context = Context::createDefaultContext();
        $service = $this->createService();

        $this->expectPosSalesChannelSearch($context, 1);

        static::assertTrue($service->posSalesChannelsExists($context));
    }

    public function testPosSalesChannelsExistsReturnsFalseWhenNoSalesChannelsWereFound(): void
    {
        $context = Context::createDefaultContext();
        $service = $this->createService();

        $this->expectPosSalesChannelSearch($context, 0);

        static::assertFalse($service->posSalesChannelsExists($context));
    }

    private function expectPosSalesChannelSearch(Context $context, int $total): void
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
            ->willReturn(new IdSearchResult($total, [], new Criteria(), Context::createDefaultContext()));
    }

    private function createService(): PosStateService
    {
        $this->salesChannelRepository = $this->createMock(EntityRepository::class);

        return new PosStateService(
            $this->salesChannelRepository,
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
        );
    }
}
