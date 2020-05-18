<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\ExpressCheckout;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountRegistrationService;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountService;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannel\SalesChannelContextSwitcher;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutController;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutData;
use Swag\PayPal\Checkout\ExpressCheckout\Route\ExpressApprovePaymentRoute;
use Swag\PayPal\Payment\Builder\CartPaymentBuilder;
use Swag\PayPal\Payment\PayPalPaymentHandler;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\CreateResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\GetSaleResponseFixture;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExpressCheckoutControllerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use ServicesTrait;

    public function testCreateNewCart(): void
    {
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
            $this->getShippingMethod()
        );
        $salesChannelContext->assign([
            'customer' => null,
        ]);

        /** @var CartService $cartService */
        $cartService = $this->getContainer()->get(CartService::class);
        $response = $this->createController($cartService)->createNewCart($salesChannelContext);

        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $cart = $cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);

        static::assertSame(0.0, $cart->getPrice()->getTotalPrice());
        static::assertCount(0, $cart->getLineItems());
    }

    public function testCreatePayment(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext(
            null,
            null,
            null,
            null,
            $this->getCurrency(),
            null,
            null,
            null,
            null,
            null,
            $this->getShippingMethod()
        );
        $salesChannelContext->assign([
            'customer' => null,
        ]);

        $response = $this->createController()->createPayment($salesChannelContext);
        $content = $response->getContent();
        static::assertNotFalse($content);

        $token = \json_decode($content, true)['token'];

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertSame(CreateResponseFixture::CREATE_PAYMENT_APPROVAL_TOKEN, $token);
    }

    public function testOnApprove(): void
    {
        $paymentMethod = $this->getAvailablePaymentMethod();
        $salesChannelContext = Generator::createSalesChannelContext(
            null,
            null,
            null,
            $this->getSalesChannel(),
            null,
            null,
            null,
            null,
            null,
            $paymentMethod,
            $this->getShippingMethod()
        );
        $salesChannelContext->assign([
            'customer' => null,
        ]);

        $testPaymentId = 'testPaymentId';
        $request = new Request([], [
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYMENT_ID => $testPaymentId,
        ]);

        /** @var CartService $cartService */
        $cartService = $this->getContainer()->get(CartService::class);
        $response = $this->createController($cartService)->onApprove($salesChannelContext, $request);
        $content = $response->getContent();
        static::assertNotFalse($content);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());

        /** @var EntityRepositoryInterface $customerRepo */
        $customerRepo = $this->getContainer()->get('customer.repository');

        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('email', GetSaleResponseFixture::PAYER_PAYER_INFO_EMAIL))
            ->addAssociation('addresses');
        /** @var CustomerEntity|null $customer */
        $customer = $customerRepo->search($criteria, $salesChannelContext->getContext())->first();
        static::assertNotNull($customer);

        static::assertSame(GetSaleResponseFixture::PAYER_PAYER_INFO_FIRST_NAME, $customer->getFirstName());
        static::assertSame(GetSaleResponseFixture::PAYER_PAYER_INFO_LAST_NAME, $customer->getLastName());

        $addresses = $customer->getAddresses();
        static::assertNotNull($addresses);

        $address = $addresses->first();
        static::assertNotNull($address);

        static::assertSame(GetSaleResponseFixture::PAYER_PAYER_INFO_SHIPPING_ADDRESS_STREET, $address->getStreet());
        static::assertSame(GetSaleResponseFixture::PAYER_PAYER_INFO_SHIPPING_ADDRESS_CITY, $address->getCity());

        $cartToken = \json_decode($content, true)['cart_token'];
        /** @var ExpressCheckoutData|null $ecsCartExtension */
        $ecsCartExtension = $cartService->getCart($cartToken, $salesChannelContext)
            ->getExtension(ExpressCheckoutController::PAYPAL_EXPRESS_CHECKOUT_CART_EXTENSION_ID);

        static::assertInstanceOf(ExpressCheckoutData::class, $ecsCartExtension);
        static::assertSame(GetSaleResponseFixture::PAYER_PAYER_INFO_PAYER_ID, $ecsCartExtension->getPayerId());
        static::assertSame($testPaymentId, $ecsCartExtension->getPaymentId());
    }

    private function createController(?CartService $cartService = null): ExpressCheckoutController
    {
        $settings = new SwagPayPalSettingStruct();
        $settings->setClientId('testClientId');
        $settings->setClientSecret('testClientSecret');

        $settingsService = new SettingsServiceMock($settings);
        /** @var EntityRepositoryInterface $salesChannelRepo */
        $salesChannelRepo = $this->getContainer()->get('sales_channel.repository');
        /** @var LocaleCodeProvider $localeCodeProvider */
        $localeCodeProvider = $this->getContainer()->get(LocaleCodeProvider::class);

        $cartPaymentBuilder = new CartPaymentBuilder(
            $settingsService,
            $salesChannelRepo,
            $localeCodeProvider
        );
        if ($cartService === null) {
            /** @var CartService $cartService */
            $cartService = $this->getContainer()->get(CartService::class);
        }
        /** @var AccountRegistrationService $accountRegistrationService */
        $accountRegistrationService = $this->getContainer()->get(AccountRegistrationService::class);
        /** @var EntityRepositoryInterface $countryRepo */
        $countryRepo = $this->getContainer()->get('country.repository');
        /** @var EntityRepositoryInterface $salutationRepo */
        $salutationRepo = $this->getContainer()->get('salutation.repository');
        /** @var AccountService $accountService */
        $accountService = $this->getContainer()->get(AccountService::class);
        /** @var SalesChannelContextFactory $salesChannelContextFactory */
        $salesChannelContextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);
        /** @var PaymentMethodUtil $paymentMethodUtil */
        $paymentMethodUtil = $this->getContainer()->get(PaymentMethodUtil::class);
        /** @var SalesChannelContextSwitcher $salesChannelContextSwitcher */
        $salesChannelContextSwitcher = $this->getContainer()->get(SalesChannelContextSwitcher::class);

        $paymentResource = $this->createPaymentResource($settings);
        $route = new ExpressApprovePaymentRoute(
            $accountRegistrationService,
            $countryRepo,
            $salutationRepo,
            $accountService,
            $salesChannelContextFactory,
            $paymentMethodUtil,
            $salesChannelContextSwitcher,
            $paymentResource,
            $cartService
        );

        return new ExpressCheckoutController(
            $cartPaymentBuilder,
            $cartService,
            $paymentResource,
            $route
        );
    }

    private function getSalesChannel(): SalesChannelEntity
    {
        $context = Context::createDefaultContext();
        $container = $this->getContainer();
        /** @var EntityRepositoryInterface $salesChannelRepo */
        $salesChannelRepo = $container->get('sales_channel.repository');
        /** @var EntityRepositoryInterface $paymentRepository */
        $paymentRepository = $container->get('payment_method.repository');
        $paymentMethodUtil = new PaymentMethodUtil($paymentRepository, $salesChannelRepo);

        /** @var SalesChannelEntity|null $salesChannel */
        $salesChannel = $salesChannelRepo->search(new Criteria(), $context)->first();
        if ($salesChannel === null) {
            throw new \RuntimeException('No SalesChannelFound');
        }

        $salesChannelRepo->update([
            [
                'id' => $salesChannel->getId(),
                'domains' => [
                    [
                        'url' => 'https://example.com',
                        'languageId' => Defaults::LANGUAGE_SYSTEM,
                        'currencyId' => Defaults::CURRENCY,
                        'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                    ],
                ],
                'paymentMethods' => [
                    [
                        'id' => $paymentMethodUtil->getPayPalPaymentMethodId($context),
                    ],
                ],
            ],
        ], $context);

        return $salesChannel;
    }

    private function getCurrency(): CurrencyEntity
    {
        /** @var EntityRepositoryInterface $currencyRepo */
        $currencyRepo = $this->getContainer()->get('currency.repository');

        return $currencyRepo->search(new Criteria(), Context::createDefaultContext())->first();
    }

    private function getShippingMethod(): ShippingMethodEntity
    {
        /** @var EntityRepositoryInterface $shippingMethodRepo */
        $shippingMethodRepo = $this->getContainer()->get('shipping_method.repository');

        return $shippingMethodRepo->search(new Criteria(), Context::createDefaultContext())->first();
    }
}
