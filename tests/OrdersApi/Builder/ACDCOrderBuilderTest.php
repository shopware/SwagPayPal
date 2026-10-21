<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\OrdersApi\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
use Shopware\Commercial\Subscription\Entity\Subscription\SubscriptionCollection;
use Shopware\Commercial\Subscription\Entity\Subscription\SubscriptionDefinition;
use Shopware\Commercial\Subscription\Entity\Subscription\SubscriptionEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Card;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Card\StoredCredential;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Common\Attributes\Verification;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenEntity;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\ACDCOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\OrdersApi\Builder\Trait\VaultableOrderBuildTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
class ACDCOrderBuilderTest extends AbstractOrderBuilderTestCase
{
    use VaultableOrderBuildTrait;

    public function testGetOrderUsesScaWhenRequiredByDefault(): void
    {
        $orderTransaction = $this->createOrderTransaction();
        $order = $this->createOrder();
        $paymentTransaction = new PaymentTransactionStruct($orderTransaction->getId());

        $paypalOrder = $this->getBuilder()->getOrder(
            $paymentTransaction,
            $orderTransaction,
            $order,
            Context::createDefaultContext(),
            new Request(),
        );

        $card = $paypalOrder->getPaymentSource()?->first(Card::class);
        static::assertSame(Verification::METHOD_SCA_WHEN_REQUIRED, $card?->getAttributes()?->getVerification()?->getMethod());
    }

    public function testGetOrderUsesScaAlwaysIfForce3dsEnabled(): void
    {
        $this->systemConfig->set(Settings::ACDC_FORCE_3DS, true);
        $orderTransaction = $this->createOrderTransaction();
        $order = $this->createOrder();
        $paymentTransaction = new PaymentTransactionStruct($orderTransaction->getId());

        $paypalOrder = $this->getBuilder()->getOrder(
            $paymentTransaction,
            $orderTransaction,
            $order,
            Context::createDefaultContext(),
            new Request(),
        );

        $card = $paypalOrder->getPaymentSource()?->first(Card::class);
        static::assertSame(Verification::METHOD_SCA_ALWAYS, $card?->getAttributes()?->getVerification()?->getMethod());
    }

    #[DataProvider('force3dsProvider')]
    public function testRenewalIsMerchantInitiatedWithoutRequestingVerification(bool $force3ds): void
    {
        $this->systemConfig->set(Settings::ACDC_FORCE_3DS, $force3ds);

        $card = $this->buildCardWithSavedCard(
            new Request([], [], [AbstractOrderBuilder::RECURRING_ATTRIBUTE => true]),
            $this->createSubscriptions(),
        );

        static::assertNull($card->getAttributes());
        $this->assertStoredCredential($card, StoredCredential::PAYMENT_INITIATOR_MERCHANT, StoredCredential::PAYMENT_TYPE_RECURRING, StoredCredential::USAGE_SUBSEQUENT);
    }

    public static function force3dsProvider(): \Generator
    {
        yield 'SCA when required' => [false];
        yield 'SCA always' => [true];
    }

    public function testSubscriptionCheckoutWithASavedCardIsACustomerInitiatedRecurringPayment(): void
    {
        $card = $this->buildCardWithSavedCard(new Request(), $this->createSubscriptions());

        static::assertSame(Verification::METHOD_SCA_WHEN_REQUIRED, $card->getAttributes()?->getVerification()?->getMethod());
        $this->assertStoredCredential($card, StoredCredential::PAYMENT_INITIATOR_CUSTOMER, StoredCredential::PAYMENT_TYPE_RECURRING, StoredCredential::USAGE_SUBSEQUENT);
    }

    public function testRepeatPurchaseWithASavedCardIsUnscheduled(): void
    {
        $card = $this->buildCardWithSavedCard(new Request(), null);

        $this->assertStoredCredential($card, StoredCredential::PAYMENT_INITIATOR_CUSTOMER, StoredCredential::PAYMENT_TYPE_UNSCHEDULED, StoredCredential::USAGE_SUBSEQUENT);
    }

    public function testSubscriptionVaultingANewCardOpensTheRecurringSeries(): void
    {
        $this->vaultTokenService->method('getAvailableToken')->willReturn(null);
        $this->vaultTokenService->method('shouldRequestVaulting')->willReturn(true);
        $this->vaultTokenService->method('getSubscriptions')->willReturn($this->createSubscriptions());

        $card = $this->buildCard(new Request());

        $this->assertStoredCredential($card, StoredCredential::PAYMENT_INITIATOR_CUSTOMER, StoredCredential::PAYMENT_TYPE_RECURRING, StoredCredential::USAGE_FIRST);
    }

    public function testVaultingANewCardOutsideASubscriptionSendsNoStoredCredential(): void
    {
        $this->vaultTokenService->method('getAvailableToken')->willReturn(null);
        $this->vaultTokenService->method('shouldRequestVaulting')->willReturn(true);
        $this->vaultTokenService->method('getSubscriptions')->willReturn(null);

        static::assertNull($this->buildCard(new Request())->getStoredCredential());
    }

    public function testSubscriptionCartVaultingANewCardOpensTheRecurringSeries(): void
    {
        $this->vaultTokenService->method('shouldRequestVaulting')->willReturn(true);
        $this->vaultTokenService->method('isSubscriptionContext')->willReturn(true);

        $card = $this->buildCardFromCart();

        $this->assertStoredCredential($card, StoredCredential::PAYMENT_INITIATOR_CUSTOMER, StoredCredential::PAYMENT_TYPE_RECURRING, StoredCredential::USAGE_FIRST);
    }

    public function testCartVaultingOutsideASubscriptionSendsNoStoredCredential(): void
    {
        $this->vaultTokenService->method('shouldRequestVaulting')->willReturn(true);
        $this->vaultTokenService->method('isSubscriptionContext')->willReturn(false);

        static::assertNull($this->buildCardFromCart()->getStoredCredential());
    }

    protected function getBuilder(): AbstractOrderBuilder
    {
        return new ACDCOrderBuilder(
            $this->systemConfig,
            $this->purchaseUnitProvider,
            new AddressProvider(),
            $this->localeCodeProvider,
            $this->itemListProvider,
            $this->vaultTokenService,
        );
    }

    protected function getPaymentSourceClass(): string
    {
        return Card::class;
    }

    private function buildCardWithSavedCard(Request $request, ?SubscriptionCollection $subscriptions): Card
    {
        $vaultToken = new VaultTokenEntity();
        $vaultToken->setToken('testToken');

        $this->vaultTokenService->method('getAvailableToken')->willReturn($vaultToken);
        $this->vaultTokenService->method('getSubscriptions')->willReturn($subscriptions);

        $card = $this->buildCard($request);
        static::assertSame('testToken', $card->getVaultId());

        return $card;
    }

    private function buildCard(Request $request): Card
    {
        $orderTransaction = $this->createOrderTransaction();

        $card = $this->getBuilder()->getOrder(
            new PaymentTransactionStruct($orderTransaction->getId()),
            $orderTransaction,
            $this->createOrder(),
            Context::createDefaultContext(),
            $request,
        )->getPaymentSource()?->first(Card::class);
        static::assertInstanceOf(Card::class, $card);

        return $card;
    }

    private function buildCardFromCart(): Card
    {
        $card = $this->getBuilder()
            ->getOrderFromCart($this->createCart(''), $this->createSalesChannelContext(), new RequestDataBag())
            ->getPaymentSource()?->first(Card::class);
        static::assertInstanceOf(Card::class, $card);

        return $card;
    }

    private function createSubscriptions(): SubscriptionCollection
    {
        if (!\class_exists(SubscriptionDefinition::class)) {
            static::markTestSkipped('Commercial is not installed');
        }

        $subscription = new SubscriptionEntity();
        $subscription->setId(Uuid::randomHex());

        return new SubscriptionCollection([$subscription]);
    }

    private function assertStoredCredential(Card $card, string $paymentInitiator, string $paymentType, string $usage): void
    {
        $storedCredential = $card->getStoredCredential();
        static::assertNotNull($storedCredential);
        static::assertSame($paymentInitiator, $storedCredential->getPaymentInitiator());
        static::assertSame($paymentType, $storedCredential->getPaymentType());
        static::assertSame($usage, $storedCredential->getUsage());
    }
}
