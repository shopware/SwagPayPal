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
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\State\PaymentMethodStateService;

/**
 * @internal
 */
#[Package('checkout')]
class PaymentMethodStateServiceTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    private const EXPECTED_STATES = [
        'Swag\PayPal\Checkout\Payment\PayPalPaymentHandler' => true,
        'Swag\PayPal\Checkout\Payment\Method\PUIHandler' => false,
        'Swag\PayPal\Checkout\Payment\Method\ACDCHandler' => true,
        'Swag\PayPal\Checkout\Payment\Method\SEPAHandler' => true,
        'Swag\PayPal\Checkout\Payment\Method\BancontactAPMHandler' => true,
        'Swag\PayPal\Checkout\Payment\Method\BlikAPMHandler' => true,
        'Swag\PayPal\Checkout\Payment\Method\EpsAPMHandler' => true,
        'Swag\PayPal\Checkout\Payment\Method\IdealAPMHandler' => true,
        'Swag\PayPal\Checkout\Payment\Method\MultibancoAPMHandler' => true,
        'Swag\PayPal\Checkout\Payment\Method\MyBankAPMHandler' => false,
        'Swag\PayPal\Checkout\Payment\Method\OxxoAPMHandler' => true,
        'Swag\PayPal\Checkout\Payment\Method\P24APMHandler' => true,
        'Swag\PayPal\Checkout\Payment\Method\TrustlyAPMHandler' => true,
        'Swag\PayPal\Checkout\Payment\Method\VenmoHandler' => false,
        'Swag\PayPal\Checkout\Payment\Method\PayLaterHandler' => true,
        'Swag\PayPal\Checkout\Payment\Method\ApplePayHandler' => false,
        'Swag\PayPal\Checkout\Payment\Method\GooglePayHandler' => false,
    ];

    private EntityRepository&MockObject $paymentMethodRepository;

    public function testSetAllPaymentMethodsStateToActive(): void
    {
        $service = $this->createStateService();

        $this->paymentMethodRepository
            ->expects(static::once())
            ->method('searchIds')
            ->with(static::callback(function (Criteria $criteria): bool {
                static::assertCount(1, $criteria->getFilters());
                static::assertInstanceOf(EqualsAnyFilter::class, $criteria->getFilters()[0]);
                static::assertSame('handlerIdentifier', $criteria->getFilters()[0]->getField());
                static::assertEquals(\array_keys(\array_filter(self::EXPECTED_STATES)), $criteria->getFilters()[0]->getValue());

                return true;
            }), static::isInstanceOf(Context::class))
            ->willReturn(new IdSearchResult(1, [['primaryKey' => 'test-id', 'data' => []]], new Criteria(), Context::createDefaultContext()));

        $this->paymentMethodRepository->expects(static::once())
            ->method('update')
            ->with([['id' => 'test-id', 'active' => true]], static::isInstanceOf(Context::class));

        $service->setAllPaymentMethodsState(true, Context::createDefaultContext());
    }

    public function testSetAllPaymentMethodsStateToActiveWithoutMethods(): void
    {
        $service = $this->createStateService();

        $this->paymentMethodRepository
            ->expects(static::once())
            ->method('searchIds')
            ->with(static::callback(function (Criteria $criteria): bool {
                static::assertCount(1, $criteria->getFilters());
                static::assertInstanceOf(EqualsAnyFilter::class, $criteria->getFilters()[0]);
                static::assertSame('handlerIdentifier', $criteria->getFilters()[0]->getField());
                static::assertEquals(\array_keys(\array_filter(self::EXPECTED_STATES)), $criteria->getFilters()[0]->getValue());

                return true;
            }), static::isInstanceOf(Context::class))
            ->willReturn(new IdSearchResult(0, [], new Criteria(), Context::createDefaultContext()));

        $this->paymentMethodRepository->expects(static::never())->method('update');

        $service->setAllPaymentMethodsState(true, Context::createDefaultContext());
    }

    public function testSetAllPaymentMethodsStateToInactive(): void
    {
        $service = $this->createStateService();

        $this->paymentMethodRepository
            ->expects(static::once())
            ->method('searchIds')
            ->with(static::callback(function (Criteria $criteria): bool {
                static::assertCount(1, $criteria->getFilters());
                static::assertInstanceOf(EqualsAnyFilter::class, $criteria->getFilters()[0]);
                static::assertSame('handlerIdentifier', $criteria->getFilters()[0]->getField());
                static::assertEquals(\array_keys(self::EXPECTED_STATES), $criteria->getFilters()[0]->getValue());

                return true;
            }), static::isInstanceOf(Context::class))
            ->willReturn(new IdSearchResult(1, [['primaryKey' => 'test-id', 'data' => []]], new Criteria(), Context::createDefaultContext()));

        $this->paymentMethodRepository->expects(static::once())
            ->method('update')
            ->with([['id' => 'test-id', 'active' => false]], static::isInstanceOf(Context::class));

        $service->setAllPaymentMethodsState(false, Context::createDefaultContext());
    }

    private function createStateService(): PaymentMethodStateService
    {
        $this->paymentMethodRepository = $this->createMock(EntityRepository::class);

        return new PaymentMethodStateService(
            new PaymentMethodDataRegistry(
                $this->paymentMethodRepository,
                $this->getContainer(),
            ),
            $this->paymentMethodRepository,
        );
    }
}
