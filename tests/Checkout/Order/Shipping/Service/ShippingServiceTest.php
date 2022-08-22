<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Order\Shipping\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Cart\Exception\OrderDeliveryNotFoundException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Checkout\Shipping\ShippingMethodDefinition;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Checkout\Order\Shipping\Service\ShippingService;
use Swag\PayPal\RestApi\V1\Api\Shipping;
use Swag\PayPal\RestApi\V1\Api\Shipping\Tracker;
use Swag\PayPal\RestApi\V1\Resource\ShippingResource;
use Swag\PayPal\SwagPayPal;

class ShippingServiceTest extends TestCase
{
    private const TEST_CODE_A = 'test_code_a';
    private const TEST_CODE_B = 'test_code_b';
    private const TEST_CODE_C = 'test_code_c';
    private const TEST_CODE_D = 'test_code_d';
    private const TEST_CODE_E = 'test_code_e';
    private const RESOURCE_ID = 'resource_id';
    private const CARRIER_NAME = 'carrier_name';

    /**
     * @var ShippingResource&MockObject
     */
    private $shippingResource;

    /**
     * @var EntityRepository&MockObject
     */
    private $salesChannelRepository;

    /**
     * @var EntityRepository&MockObject
     */
    private $orderTransactionRepository;

    /**
     * @var EntityRepository&MockObject
     */
    private $shippingMethodRepository;

    protected function setUp(): void
    {
        $this->shippingResource = $this->createMock(ShippingResource::class);
        $this->salesChannelRepository = $this->createMock(EntityRepository::class);
        $this->orderTransactionRepository = $this->createMock(EntityRepository::class);
        $this->shippingMethodRepository = $this->createMock(EntityRepository::class);
    }

    public function testUpdateWithoutChanges(): void
    {
        $this->shippingResource->expects(static::never())->method('batch');

        $this->getService()->updateTrackingCodes(Uuid::randomHex(), [], [], Context::createDefaultContext());
        $this->getService()->updateTrackingCodes(Uuid::randomHex(), [self::TEST_CODE_A], [self::TEST_CODE_A], Context::createDefaultContext());
    }

    public function testUpdateWithoutOrderTransaction(): void
    {
        $this->orderTransactionRepository->expects(static::once())->method('search')->willReturn($this->getOrderTransactionResult(false, false));
        $this->shippingResource->expects(static::never())->method('batch');

        $this->getService()->updateTrackingCodes(Uuid::randomHex(), [self::TEST_CODE_A], [], Context::createDefaultContext());
    }

    public function testUpdateWithNonPayPalOrderTransaction(): void
    {
        $this->orderTransactionRepository->expects(static::once())->method('search')->willReturn($this->getOrderTransactionResult(true, false));
        $this->shippingResource->expects(static::never())->method('batch');

        $this->getService()->updateTrackingCodes(Uuid::randomHex(), [self::TEST_CODE_A], [], Context::createDefaultContext());
    }

    public function testUpdateWithoutShippingMethod(): void
    {
        $this->orderTransactionRepository->expects(static::once())->method('search')->willReturn($this->getOrderTransactionResult());
        $this->shippingMethodRepository->expects(static::once())->method('search')->willReturn($this->getShippingMethodResult(false, false));
        $this->shippingResource->expects(static::never())->method('batch');

        $this->getService()->updateTrackingCodes(Uuid::randomHex(), [self::TEST_CODE_A], [], Context::createDefaultContext());
    }

    public function testUpdateWithoutCarrier(): void
    {
        $this->orderTransactionRepository->expects(static::once())->method('search')->willReturn($this->getOrderTransactionResult());
        $this->shippingMethodRepository->expects(static::once())->method('search')->willReturn($this->getShippingMethodResult(true, false));
        $this->shippingResource->expects(static::never())->method('batch');

        $this->getService()->updateTrackingCodes(Uuid::randomHex(), [self::TEST_CODE_A], [], Context::createDefaultContext());
    }

    public function testUpdateWithoutSalesChannel(): void
    {
        $this->orderTransactionRepository->expects(static::once())->method('search')->willReturn($this->getOrderTransactionResult());
        $this->shippingMethodRepository->expects(static::once())->method('search')->willReturn($this->getShippingMethodResult());
        $this->salesChannelRepository->expects(static::once())->method('searchIds')->willReturn($this->getSalesChannelIdResult(false));
        $this->shippingResource->expects(static::never())->method('batch');

        $this->expectException(OrderDeliveryNotFoundException::class);
        $this->getService()->updateTrackingCodes(Uuid::randomHex(), [self::TEST_CODE_A], [], Context::createDefaultContext());
    }

    /**
     * @dataProvider updateDataProvider
     */
    public function testUpdate(array $after, array $before, array $expectedAdded, array $expectedRemoved): void
    {
        $this->orderTransactionRepository->expects(static::once())->method('search')->willReturn($this->getOrderTransactionResult());
        $this->shippingMethodRepository->expects(static::once())->method('search')->willReturn($this->getShippingMethodResult());
        $this->salesChannelRepository->expects(static::once())->method('searchIds')->willReturn($this->getSalesChannelIdResult());
        $this->shippingResource->expects($expectedAdded ? static::once() : static::never())->method('batch')->with(static::callback(
            static function (Shipping $return) use ($expectedAdded): bool {
                $encoded = \json_decode(\json_encode($return->getTrackers()) ?: '[]', true) ?: [];

                return $expectedAdded === $encoded;
            }
        ), static::isType('string'));
        $this->shippingResource->expects(static::exactly(\count($expectedRemoved)))->method('update')->with(static::callback(
            static function (Tracker $tracker) use (&$expectedRemoved): bool {
                $encoded = \json_decode(\json_encode($tracker) ?: '[]', true) ?: [];

                return \array_shift($expectedRemoved) === $encoded;
            }
        ), static::isType('string'));

        $this->getService()->updateTrackingCodes(Uuid::randomHex(), $after, $before, Context::createDefaultContext());
    }

    public function updateDataProvider(): array
    {
        return [
            'add code' => [
                [self::TEST_CODE_A],
                [],
                [
                    [
                        'transaction_id' => self::RESOURCE_ID,
                        'tracking_number' => self::TEST_CODE_A,
                        'status' => 'SHIPPED',
                        'carrier' => self::CARRIER_NAME,
                    ],
                ],
                [],
            ],
            'remove code' => [
                [],
                [self::TEST_CODE_A],
                [],
                [
                    [
                        'transaction_id' => self::RESOURCE_ID,
                        'tracking_number' => self::TEST_CODE_A,
                        'status' => 'CANCELLED',
                        'carrier' => self::CARRIER_NAME,
                    ],
                ],
            ],
            'complex' => [
                [self::TEST_CODE_C, self::TEST_CODE_D, self::TEST_CODE_B],
                [self::TEST_CODE_A, self::TEST_CODE_B, self::TEST_CODE_E],
                [
                    [
                        'transaction_id' => self::RESOURCE_ID,
                        'tracking_number' => self::TEST_CODE_C,
                        'status' => 'SHIPPED',
                        'carrier' => self::CARRIER_NAME,
                    ],
                    [
                        'transaction_id' => self::RESOURCE_ID,
                        'tracking_number' => self::TEST_CODE_D,
                        'status' => 'SHIPPED',
                        'carrier' => self::CARRIER_NAME,
                    ],
                ],
                [
                    [
                        'transaction_id' => self::RESOURCE_ID,
                        'tracking_number' => self::TEST_CODE_A,
                        'status' => 'CANCELLED',
                        'carrier' => self::CARRIER_NAME,
                    ],
                    [
                        'transaction_id' => self::RESOURCE_ID,
                        'tracking_number' => self::TEST_CODE_E,
                        'status' => 'CANCELLED',
                        'carrier' => self::CARRIER_NAME,
                    ],
                ],
            ],
        ];
    }

    private function getService(): ShippingService
    {
        return new ShippingService(
            $this->shippingResource,
            $this->salesChannelRepository,
            $this->orderTransactionRepository,
            $this->shippingMethodRepository,
            new NullLogger()
        );
    }

    private function getOrderTransactionResult(bool $withEntity = true, bool $withCustomField = true): EntitySearchResult
    {
        $entity = new OrderTransactionEntity();
        $entity->setId(Uuid::randomHex());
        if ($withCustomField) {
            $entity->setCustomFields([SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_RESOURCE_ID => self::RESOURCE_ID]);
        }

        return new EntitySearchResult(
            OrderTransactionDefinition::ENTITY_NAME,
            (int) $withEntity,
            new OrderTransactionCollection($withEntity ? [$entity] : []),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );
    }

    private function getShippingMethodResult(bool $withEntity = true, bool $withCustomField = true): EntitySearchResult
    {
        $entity = new ShippingMethodEntity();
        $entity->setId(Uuid::randomHex());
        if ($withCustomField) {
            $entity->addTranslated('customFields', [SwagPayPal::SHIPPING_METHOD_CUSTOM_FIELDS_CARRIER => self::CARRIER_NAME]);
        }

        return new EntitySearchResult(
            ShippingMethodDefinition::ENTITY_NAME,
            (int) $withEntity,
            new ShippingMethodCollection($withEntity ? [$entity] : []),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );
    }

    private function getSalesChannelIdResult(bool $withEntity = true): IdSearchResult
    {
        return new IdSearchResult(
            (int) $withEntity,
            $withEntity ? [['primaryKey' => Uuid::randomHex(), 'data' => []]] : [],
            new Criteria(),
            Context::createDefaultContext()
        );
    }
}
