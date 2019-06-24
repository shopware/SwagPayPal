<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Checkout\SPBCheckout;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Checkout\SPBCheckout\SPBCheckoutController;
use Swag\PayPal\Checkout\SPBCheckout\SPBCheckoutData;
use Swag\PayPal\Payment\Builder\CartPaymentBuilder;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Swag\PayPal\Util\LocaleCodeProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class SPBCheckoutControllerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use ServicesTrait;

    public function testOnApprove(): void
    {
        /** @var CartService $cartService */
        $cartService = $this->getContainer()->get(CartService::class);

        $salesChannelContext = Generator::createSalesChannelContext(
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $this->getShippingMethod(),
            $this->createCustomer()
        );

        $request = new Request([], [
            SPBCheckoutController::PAYPAL_SPB_PARAMETER_PAYER_ID => 'testPayerId',
            SPBCheckoutController::PAYPAL_SPB_PARAMETER_PAYMENT_ID => 'testPaymentId',
        ]);
        $response = $this->createController($cartService)->onApprove($salesChannelContext, $request);

        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $cart = $cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
        static::assertTrue($cart->hasExtension('spbCheckoutData'));

        /** @var SPBCheckoutData|null $spbCheckoutDataExtension */
        $spbCheckoutDataExtension = $cart->getExtension('spbCheckoutData');
        static::assertNotNull($spbCheckoutDataExtension);

        if ($spbCheckoutDataExtension === null) {
            return;
        }

        static::assertSame('testPayerId', $spbCheckoutDataExtension->getPayerId());
        static::assertSame('testPaymentId', $spbCheckoutDataExtension->getPaymentId());
    }

    public function testCreatePayment(): void
    {
        /** @var CartService $cartService */
        $cartService = $this->getContainer()->get(CartService::class);

        $salesChannelContext = Generator::createSalesChannelContext(
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $this->getShippingMethod(),
            $this->createCustomer()
        );

        $response = $this->createController($cartService)->createPayment($salesChannelContext);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertStringContainsString('{"token":"EC-', $response->getContent());
    }

    private function createCustomer(): CustomerEntity
    {
        $customerId = Uuid::randomHex();
        $addressId = Uuid::randomHex();

        $customer = [
            'id' => $customerId,
            'number' => '1337',
            'salutationId' => $this->getValidSalutationId(),
            'firstName' => 'Max',
            'lastName' => 'Mustermann',
            'customerNumber' => '1337',
            'email' => Uuid::randomHex() . '@example.com',
            'password' => 'shopware',
            'defaultPaymentMethodId' => $this->getValidPaymentMethodId(),
            'groupId' => Defaults::FALLBACK_CUSTOMER_GROUP,
            'salesChannelId' => Defaults::SALES_CHANNEL,
            'defaultBillingAddressId' => $addressId,
            'defaultShippingAddressId' => $addressId,
            'addresses' => [
                [
                    'id' => $addressId,
                    'customerId' => $customerId,
                    'countryId' => $this->getValidCountryId(),
                    'salutationId' => $this->getValidSalutationId(),
                    'firstName' => 'Max',
                    'lastName' => 'Mustermann',
                    'street' => 'Ebbinghoff 10',
                    'zipcode' => '48624',
                    'city' => 'Schöppingen',
                ],
            ],
        ];

        /** @var EntityRepositoryInterface $customerRepo */
        $customerRepo = $this->getContainer()->get('customer.repository');
        $context = Context::createDefaultContext();
        $customerRepo->upsert([$customer], $context);

        $criteria = new Criteria([$customerId]);
        $criteria->addAssociationPath('defaultBillingAddress.country');

        return $customerRepo->search($criteria, $context)->first();
    }

    private function getShippingMethod(): ShippingMethodEntity
    {
        /** @var EntityRepositoryInterface $shippingMethodRepo */
        $shippingMethodRepo = $this->getContainer()->get('shipping_method.repository');

        return $shippingMethodRepo->search(new Criteria(), Context::createDefaultContext())->first();
    }

    private function createController(CartService $cartService): SPBCheckoutController
    {
        $settings = new SwagPayPalSettingStruct();
        $settings->setClientId('testClientId');
        $settings->setClientSecret('testClientSecret');

        $settingsService = new SettingsServiceMock($settings);
        /** @var LocaleCodeProvider $localeCodeProvider */
        $localeCodeProvider = $this->getContainer()->get(LocaleCodeProvider::class);
        /** @var RouterInterface $router */
        $router = $this->getContainer()->get('router');
        /** @var EntityRepositoryInterface $salesChannelRepo */
        $salesChannelRepo = $this->getContainer()->get('sales_channel.repository');

        $cartPaymentBuilder = new CartPaymentBuilder(
            $settingsService,
            $salesChannelRepo,
            $localeCodeProvider
        );

        return new SPBCheckoutController($cartPaymentBuilder, $cartService, $this->createPaymentResource());
    }
}
