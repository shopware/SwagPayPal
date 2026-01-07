<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\DefaultPayment;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\Test\Generator;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Util\Lifecycle\Method\AbstractMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\Method\PayPalMethodData;
use Swag\PayPal\Util\PaymentMethodUtil;

/**
 * @internal
 */
#[Package('checkout')]
class PaymentMethodUtilTest extends TestCase
{
    use IntegrationTestBehaviour;

    public const SALESCHANNEL_WITHOUT_PAYPAL_PAYMENT_METHOD = '4ce46b49d1904a5db0b41573e9355b51';

    private PaymentMethodUtil $paymentMethodUtil;

    private PaymentMethodDataRegistry $paymentMethodDataRegistry;

    protected function setUp(): void
    {
        $this->paymentMethodUtil = static::getContainer()->get(PaymentMethodUtil::class);
        $this->paymentMethodDataRegistry = static::getContainer()->get(PaymentMethodDataRegistry::class);
    }

    public function testGetAllPaymentMethodIds(): void
    {
        foreach ($this->paymentMethodDataRegistry->getPaymentMethods() as $methodData) {
            $fetchedId = $this->paymentMethodUtil->getPaymentMethodId($methodData);

            $expectedId = static::getContainer()->get('payment_method.repository')
                ->searchIds(
                    (new Criteria())->addFilter(new EqualsFilter('handlerIdentifier', $methodData->getHandler())),
                    Context::createDefaultContext(),
                )
                ->firstId();

            static::assertNotNull($fetchedId, 'Failed to fetch payment method id for ' . $methodData->getHandler());
            static::assertNotNull($expectedId, 'Failed to fetch expected payment method id for ' . $methodData->getHandler());
            static::assertSame($expectedId, $fetchedId, 'Fetched payment method id returned from util does not match expected for ' . $methodData->getHandler());
        }
    }

    public function testGetPayPalPaymentMethodId(): void
    {
        $fetchedId = $this->paymentMethodUtil->getPayPalPaymentMethodId(Context::createDefaultContext());

        $expectedId = static::getContainer()->get('payment_method.repository')
            ->searchIds(
                (new Criteria())->addFilter(new EqualsFilter('handlerIdentifier', PayPalPaymentHandler::class)),
                Context::createDefaultContext(),
            )
            ->firstId();

        static::assertNotNull($fetchedId);
        static::assertNotNull($expectedId);
        static::assertSame($expectedId, $fetchedId);
    }

    public function testIsPaymentMethodActiveWithoutAssignedPaymentMethods(): void
    {
        $paypalPaymentMethods = $this->getSalesChannelPaymentMethods()->filter(fn (PaymentMethodEntity $pm) => \str_starts_with($pm->getHandlerIdentifier(), 'Swag\\PayPal'));

        static::assertCount(0, $paypalPaymentMethods);

        static::assertFalse($this->paymentMethodUtil->isPaymentMethodActive(Generator::generateSalesChannelContext(), null));
    }

    public function testIsPaymentMethodActiveWithoutActivePaymentMethods(): void
    {
        $this->assignPaymentMethods($this->paymentMethodDataRegistry->getPaymentMethods(), false);

        $scPaymentMethods = $this->getSalesChannelPaymentMethods();

        static::assertCount(17, $scPaymentMethods->filter(fn (PaymentMethodEntity $pm) => \str_starts_with($pm->getHandlerIdentifier(), 'Swag\\PayPal')));

        static::assertFalse($this->paymentMethodUtil->isPaymentMethodActive(Generator::generateSalesChannelContext(), null));
    }

    public function testIsPaymentMethodActiveWithActivePaymentMethods(): void
    {
        $this->assignPaymentMethods($this->paymentMethodDataRegistry->getPaymentMethods(), true);

        $scPaymentMethods = $this->getSalesChannelPaymentMethods();

        static::assertCount(
            \count($this->paymentMethodDataRegistry->getPaymentMethods()),
            $scPaymentMethods->filter(fn (PaymentMethodEntity $pm) => \str_starts_with($pm->getHandlerIdentifier(), 'Swag\\PayPal')),
        );

        foreach ($this->paymentMethodDataRegistry->getPaymentMethods() as $methodData) {
            static::assertTrue(
                $this->paymentMethodUtil->isPaymentMethodActive(Generator::generateSalesChannelContext(), [$methodData->getHandler()]),
                'Failed asserting that payment method ' . $methodData->getHandler() . ' is active',
            );
        }
    }

    public function testGetPaymentMethodIdWithWrongHandler(): void
    {
        static::assertNull($this->paymentMethodUtil->getPaymentMethodId('non.existing.handler'));
        static::assertNull($this->paymentMethodUtil->getPaymentMethodId(DefaultPayment::class));
    }

    #[DataProvider('providerHandlerIdentifiers')]
    public function testIntoHandlerIdentifier(mixed $paypalHandlerIdentifier): void
    {
        $reflection = new \ReflectionClass(PaymentMethodUtil::class);
        $result = $reflection
            ->getMethod('intoHandlerIdentifier')
            ->invoke($this->paymentMethodUtil, $paypalHandlerIdentifier);

        static::assertSame(PayPalPaymentHandler::class, $result);
    }

    public static function providerHandlerIdentifiers(): \Generator
    {
        $paymentMethodEntity = static::getContainer()->get('payment_method.repository')
            ->search((new Criteria())->addFilter(new EqualsFilter('handlerIdentifier', PayPalPaymentHandler::class)), Context::createDefaultContext())
            ->first();

        yield 'entity' => [$paymentMethodEntity];
        yield 'method data' => [new PayPalMethodData(static::getContainer())];
        yield 'class string' => [PayPalMethodData::class];
        yield 'handler identifier' => [PayPalPaymentHandler::class];
    }

    /**
     * @deprecated tag:v11.0.0 - Will be removed
     */
    public function testGetPaypalPaymentMethodInSalesChannel(): void
    {
        $this->assignPaymentMethods($this->paymentMethodDataRegistry->getPaymentMethods(), true);

        $salesChannelContext = Generator::generateSalesChannelContext();
        static::assertTrue($this->paymentMethodUtil->isPaypalPaymentMethodInSalesChannel($salesChannelContext));
    }

    public function testSetPayPalAsDefaultPaymentMethodForASpecificSalesChannel(): void
    {
        $paypalPaymentMethodId = $this->paymentMethodUtil->getPayPalPaymentMethodId(Context::createDefaultContext());
        static::assertNotNull($paypalPaymentMethodId);

        $scPaymentMethods = $this->getSalesChannelPaymentMethods();
        static::assertFalse($scPaymentMethods->has($paypalPaymentMethodId));

        $context = Context::createDefaultContext();
        $this->paymentMethodUtil->setPayPalAsDefaultPaymentMethod($context, TestDefaults::SALES_CHANNEL);

        $scPaymentMethods = $this->getSalesChannelPaymentMethods();
        $paypalPaymentMethod = $scPaymentMethods->get($paypalPaymentMethodId);
        static::assertNotNull($paypalPaymentMethod);
        static::assertTrue($paypalPaymentMethod->getSalesChannelDefaultAssignments()?->has(TestDefaults::SALES_CHANNEL));
    }

    public function testSetPayPalAsDefaultPaymentMethodForAllCompatibleSalesChannels(): void
    {
        $paypalPaymentMethodId = $this->paymentMethodUtil->getPayPalPaymentMethodId(Context::createDefaultContext());
        static::assertNotNull($paypalPaymentMethodId);

        $scPaymentMethods = $this->getSalesChannelPaymentMethods();
        static::assertFalse($scPaymentMethods->has($paypalPaymentMethodId));

        $context = Context::createDefaultContext();
        $this->paymentMethodUtil->setPayPalAsDefaultPaymentMethod($context, null);

        $scPaymentMethods = $this->getSalesChannelPaymentMethods();
        $paypalPaymentMethod = $scPaymentMethods->get($paypalPaymentMethodId);
        static::assertNotNull($paypalPaymentMethod);
        static::assertTrue($paypalPaymentMethod->getSalesChannelDefaultAssignments()?->has(TestDefaults::SALES_CHANNEL));
    }

    public function testSetPayPalAsDefaultPaymentWithPaymentMethodAlreadyAssigned(): void
    {
        $paypalPaymentMethodId = $this->paymentMethodUtil->getPayPalPaymentMethodId(Context::createDefaultContext());
        static::assertNotNull($paypalPaymentMethodId);

        $this->assignPaymentMethods([new PayPalMethodData(static::getContainer())], true);

        $context = Context::createDefaultContext();
        $this->paymentMethodUtil->setPayPalAsDefaultPaymentMethod($context, TestDefaults::SALES_CHANNEL);

        $scPaymentMethods = $this->getSalesChannelPaymentMethods();
        $paypalPaymentMethod = $scPaymentMethods->get($paypalPaymentMethodId);
        static::assertNotNull($paypalPaymentMethod);
        static::assertTrue($paypalPaymentMethod->getSalesChannelDefaultAssignments()?->has(TestDefaults::SALES_CHANNEL));
    }

    private function getSalesChannelPaymentMethods(): PaymentMethodCollection
    {
        /** @var SalesChannelEntity|null $salesChannel */
        $salesChannel = static::getContainer()->get('sales_channel.repository')
            ->search((new Criteria([TestDefaults::SALES_CHANNEL]))->addAssociation('paymentMethods.salesChannelDefaultAssignments'), Context::createDefaultContext())
            ->first();

        static::assertNotNull($salesChannel);
        static::assertNotNull($salesChannel->getPaymentMethods());

        return $salesChannel->getPaymentMethods();
    }

    /**
     * @param array<AbstractMethodData> $methodData
     */
    private function assignPaymentMethods(array $methodData, bool $active): void
    {
        $update = [
            'id' => TestDefaults::SALES_CHANNEL,
            'paymentMethods' => \array_map(
                fn ($md) => [
                    'id' => $this->paymentMethodUtil->getPaymentMethodId($md),
                    'active' => $active,
                ],
                $methodData,
            ),
        ];

        static::getContainer()->get('sales_channel.repository')->update([$update], Context::createDefaultContext());
    }
}
