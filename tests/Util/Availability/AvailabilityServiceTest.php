<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Lifecycle;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Util\Availability\AvailabilityService;
use Swag\PayPal\Util\Lifecycle\Method\AbstractMethodData;
use Swag\PayPal\Util\Lifecycle\Method\ACDCMethodData;
use Swag\PayPal\Util\Lifecycle\Method\BancontactMethodData;
use Swag\PayPal\Util\Lifecycle\Method\BlikMethodData;
use Swag\PayPal\Util\Lifecycle\Method\EpsMethodData;
use Swag\PayPal\Util\Lifecycle\Method\IdealMethodData;
use Swag\PayPal\Util\Lifecycle\Method\MultibancoMethodData;
use Swag\PayPal\Util\Lifecycle\Method\MyBankMethodData;
use Swag\PayPal\Util\Lifecycle\Method\OxxoMethodData;
use Swag\PayPal\Util\Lifecycle\Method\P24MethodData;
use Swag\PayPal\Util\Lifecycle\Method\PayLaterMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\Method\PayPalMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PUIMethodData;
use Swag\PayPal\Util\Lifecycle\Method\SEPAMethodData;
use Swag\PayPal\Util\Lifecycle\Method\TrustlyMethodData;
use Swag\PayPal\Util\Lifecycle\Method\VenmoMethodData;

/**
 * @internal
 */
#[Package('checkout')]
class AvailabilityServiceTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    private PaymentMethodDataRegistry $methodDataRegistry;

    private AvailabilityService $availabilityService;

    protected function setUp(): void
    {
        $this->methodDataRegistry = $this->getContainer()->get(PaymentMethodDataRegistry::class);
        $this->availabilityService = $this->getContainer()->get(AvailabilityService::class);
    }

    /**
     * @param class-string<AbstractMethodData> $methodDataClass
     */
    #[DataProvider('dataProviderPaymentMethod')]
    public function testIsPaymentMethodAvailable(string $methodDataClass, bool $shouldBeAvailable): void
    {
        $salesChannelContext = $this->createSalesChannelContext();
        $paymentMethod = $this->methodDataRegistry->getEntityFromData($this->methodDataRegistry->getPaymentMethod($methodDataClass), $salesChannelContext->getContext());
        static::assertNotNull($paymentMethod);

        static::assertSame(
            $shouldBeAvailable,
            $this->availabilityService->isPaymentMethodAvailable($paymentMethod, $this->createCart(), $salesChannelContext)
        );
    }

    /**
     * @param class-string<AbstractMethodData> $methodDataClass
     */
    #[DataProvider('dataProviderPaymentMethod')]
    public function testFilterPaymentMethods(string $methodDataClass, bool $shouldBeAvailable): void
    {
        $salesChannelContext = $this->createSalesChannelContext();
        $paymentMethod = $this->methodDataRegistry->getEntityFromData($this->methodDataRegistry->getPaymentMethod($methodDataClass), $salesChannelContext->getContext());
        static::assertNotNull($paymentMethod);

        static::assertSame(
            $shouldBeAvailable ? [] : [$paymentMethod->getHandlerIdentifier()],
            $this->availabilityService->filterPaymentMethods(new PaymentMethodCollection([$paymentMethod]), $this->createCart(), $salesChannelContext)
        );
    }

    /**
     * @param class-string<AbstractMethodData> $methodDataClass
     */
    #[DataProvider('dataProviderPaymentMethod')]
    public function testFilterPaymentMethodByOrder(string $methodDataClass, bool $shouldBeAvailable): void
    {
        $salesChannelContext = $this->createSalesChannelContext();
        $paymentMethod = $this->methodDataRegistry->getEntityFromData($this->methodDataRegistry->getPaymentMethod($methodDataClass), $salesChannelContext->getContext());
        static::assertNotNull($paymentMethod);

        $order = new OrderEntity();
        $order->setPrice(new CartPrice(5, 5, 0, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_GROSS));

        static::assertSame(
            $shouldBeAvailable ? [] : [$paymentMethod->getHandlerIdentifier()],
            $this->availabilityService->filterPaymentMethodsByOrder(new PaymentMethodCollection([$paymentMethod]), $this->createCart(0), $order, $salesChannelContext)
        );
    }

    public static function dataProviderPaymentMethod(): iterable
    {
        return [
            [ACDCMethodData::class, true],
            [BancontactMethodData::class, false],
            [BlikMethodData::class, false],
            [EpsMethodData::class, false],
            [IdealMethodData::class, false],
            [MultibancoMethodData::class, false],
            [MyBankMethodData::class, false],
            [OxxoMethodData::class, false],
            [P24MethodData::class, false],
            [PayLaterMethodData::class, true],
            [PayPalMethodData::class, true],
            [PUIMethodData::class, true],
            [SEPAMethodData::class, true],
            [TrustlyMethodData::class, false],
            [VenmoMethodData::class, false],
        ];
    }

    private function createCart(float $amount = 5.0): Cart
    {
        $cart = new Cart(Uuid::randomHex());
        $cart->setPrice(new CartPrice($amount, $amount, 0, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_GROSS));

        return $cart;
    }

    private function createSalesChannelContext(): SalesChannelContext
    {
        return $this->getContainer()->get(SalesChannelContextFactory::class)->create(
            Uuid::randomHex(),
            TestDefaults::SALES_CHANNEL,
            []
        );
    }
}
