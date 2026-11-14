<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\OrdersApi\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
use Shopware\Commercial\Subscription\Entity\Subscription\SubscriptionEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\RecurringPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenEntity;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\ACDCOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Card;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Card\StoredCredential;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\Attributes\Verification;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\OrdersApi\Builder\Trait\VaultableOrderBuildTrait;

/**
 * @internal
 */
#[Package('checkout')]
class ACDCOrderBuilderTest extends AbstractOrderBuilderTestCase
{
    use VaultableOrderBuildTrait;

    public function testGetOrderUsesScaWhenRequiredByDefault(): void
    {
        $paymentTransaction = new SyncPaymentTransactionStruct($this->createOrderTransaction(), $this->createOrder());

        $order = $this->getBuilder()->getOrder(
            $paymentTransaction,
            $this->createSalesChannelContext(),
            new RequestDataBag(),
        );

        $card = $order->getPaymentSource()?->first(Card::class);
        static::assertInstanceOf(Card::class, $card);
        static::assertSame(Verification::METHOD_SCA_WHEN_REQUIRED, $card->getAttributes()?->getVerification()?->getMethod());
    }

    public function testGetOrderUsesScaAlwaysIfForce3dsEnabled(): void
    {
        $this->systemConfig->set(Settings::ACDC_FORCE_3DS, true);

        $paymentTransaction = new SyncPaymentTransactionStruct($this->createOrderTransaction(), $this->createOrder());

        $order = $this->getBuilder()->getOrder(
            $paymentTransaction,
            $this->createSalesChannelContext(),
            new RequestDataBag(),
        );

        $card = $order->getPaymentSource()?->first(Card::class);
        static::assertInstanceOf(Card::class, $card);
        static::assertSame(Verification::METHOD_SCA_ALWAYS, $card->getAttributes()?->getVerification()?->getMethod());
    }

    #[DataProvider('force3dsProvider')]
    public function testRenewalIsMerchantInitiatedWithoutRequestingVerification(bool $force3ds): void
    {
        $this->systemConfig->set(Settings::ACDC_FORCE_3DS, $force3ds);

        $card = $this->buildCardWithSavedCard($this->createSubscription(), true);

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
        $card = $this->buildCardWithSavedCard($this->createSubscription());

        static::assertSame(Verification::METHOD_SCA_WHEN_REQUIRED, $card->getAttributes()?->getVerification()?->getMethod());
        $this->assertStoredCredential($card, StoredCredential::PAYMENT_INITIATOR_CUSTOMER, StoredCredential::PAYMENT_TYPE_RECURRING, StoredCredential::USAGE_SUBSEQUENT);
    }

    public function testRepeatPurchaseWithASavedCardIsUnscheduled(): void
    {
        $card = $this->buildCardWithSavedCard(null);

        static::assertSame(Verification::METHOD_SCA_WHEN_REQUIRED, $card->getAttributes()?->getVerification()?->getMethod());
        $this->assertStoredCredential($card, StoredCredential::PAYMENT_INITIATOR_CUSTOMER, StoredCredential::PAYMENT_TYPE_UNSCHEDULED, StoredCredential::USAGE_SUBSEQUENT);
    }

    public function testSubscriptionVaultingANewCardOpensTheRecurringSeries(): void
    {
        $this->vaultTokenService->method('getAvailableToken')->willReturn(null);
        $this->vaultTokenService->method('getSubscription')->willReturn($this->createSubscription());
        $this->vaultTokenService->expects(static::once())->method('requestVaulting');

        $card = $this->buildCard();

        $this->assertStoredCredential($card, StoredCredential::PAYMENT_INITIATOR_CUSTOMER, StoredCredential::PAYMENT_TYPE_RECURRING, StoredCredential::USAGE_FIRST);
    }

    public function testVaultingANewCardOutsideASubscriptionSendsNoStoredCredential(): void
    {
        $this->vaultTokenService->method('getAvailableToken')->willReturn(null);
        $this->vaultTokenService->method('getSubscription')->willReturn(null);
        $this->vaultTokenService->expects(static::once())->method('requestVaulting');

        $card = $this->buildCard(requestDataBag: new RequestDataBag([VaultTokenService::REQUEST_CREATE_VAULT => true]));

        static::assertNull($card->getStoredCredential());
    }

    public function testSubscriptionCartVaultingANewCardOpensTheRecurringSeries(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();
        $salesChannelContext->addExtension('subscription', new ArrayStruct());

        $this->vaultTokenService->expects(static::once())->method('requestVaulting');

        $card = $this->buildCardFromCart($salesChannelContext);

        static::assertSame(Verification::METHOD_SCA_WHEN_REQUIRED, $card->getAttributes()?->getVerification()?->getMethod());
        $this->assertStoredCredential($card, StoredCredential::PAYMENT_INITIATOR_CUSTOMER, StoredCredential::PAYMENT_TYPE_RECURRING, StoredCredential::USAGE_FIRST);
    }

    public function testCartVaultingOutsideASubscriptionSendsNoStoredCredential(): void
    {
        $this->vaultTokenService->expects(static::once())->method('requestVaulting');

        $card = $this->buildCardFromCart($this->createSalesChannelContext(), new RequestDataBag([VaultTokenService::REQUEST_CREATE_VAULT => true]));

        static::assertNull($card->getStoredCredential());
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

    private function buildCardWithSavedCard(?SubscriptionEntity $subscription, bool $isRecurring = false): Card
    {
        $vaultToken = new VaultTokenEntity();
        $vaultToken->setToken('testToken');

        $this->vaultTokenService->method('getAvailableToken')->willReturn($vaultToken);
        $this->vaultTokenService->method('getSubscription')->willReturn($subscription);

        $card = $this->buildCard($isRecurring);
        static::assertSame('testToken', $card->getVaultId());

        return $card;
    }

    private function buildCard(bool $isRecurring = false, RequestDataBag $requestDataBag = new RequestDataBag()): Card
    {
        // the checkout passes an async struct with the payer present, only renewals pass a recurring one
        $paymentTransaction = $isRecurring
            ? new RecurringPaymentTransactionStruct($this->createOrderTransaction(), $this->createOrder())
            : new AsyncPaymentTransactionStruct($this->createOrderTransaction(), $this->createOrder(), 'https://example.com/return');

        $card = $this->getBuilder()
            ->getOrder($paymentTransaction, $this->createSalesChannelContext(), $requestDataBag)
            ->getPaymentSource()?->first(Card::class);
        static::assertInstanceOf(Card::class, $card);

        return $card;
    }

    private function buildCardFromCart(SalesChannelContext $salesChannelContext, RequestDataBag $requestDataBag = new RequestDataBag()): Card
    {
        $card = $this->getBuilder()
            ->getOrderFromCart($this->createCart(''), $salesChannelContext, $requestDataBag)
            ->getPaymentSource()?->first(Card::class);
        static::assertInstanceOf(Card::class, $card);

        return $card;
    }

    private function createSubscription(): SubscriptionEntity
    {
        if (!\class_exists(SubscriptionEntity::class)) {
            static::markTestSkipped('Commercial is not installed');
        }

        $subscription = new SubscriptionEntity();
        $subscription->setId(Uuid::randomHex());

        return $subscription;
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
