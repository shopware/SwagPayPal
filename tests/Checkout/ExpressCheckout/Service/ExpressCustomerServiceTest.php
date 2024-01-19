<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\ExpressCheckout\Service;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountService;
use Shopware\Core\Checkout\Customer\SalesChannel\RegisterRoute;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Checkout\ExpressCheckout\Service\ExpressCustomerService;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\CheckoutRouteTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetOrderCapture;

/**
 * @internal
 */
#[Package('checkout')]
class ExpressCustomerServiceTest extends TestCase
{
    use CheckoutRouteTrait;
    use IntegrationTestBehaviour;

    public const TEST_PAYMENT_ID_WITHOUT_STATE = 'testPaymentIdWithoutState';
    public const TEST_PAYMENT_ID_WITH_COUNTRY_WITHOUT_STATES = 'testPaymentIdWithCountryWithoutStates';
    public const TEST_PAYMENT_ID_WITH_STATE_NOT_FOUND = 'testPaymentIdWithStateNotFound';

    public function testLoginNewCustomer(): void
    {
        $order = new Order();
        $order->assign(GetOrderCapture::get());
        $customer = $this->doLogin($order);

        static::assertSame($customer->getDefaultBillingAddressId(), $customer->getDefaultShippingAddressId());

        static::assertSame(GetOrderCapture::PAYER_NAME_GIVEN_NAME, $customer->getFirstName());
        static::assertSame(GetOrderCapture::PAYER_NAME_SURNAME, $customer->getLastName());

        $addresses = $customer->getAddresses();
        static::assertNotNull($addresses);

        $address = $addresses->first();
        static::assertNotNull($address);

        static::assertSame(GetOrderCapture::PAYER_ADDRESS_ADDRESS_LINE_1, $address->getStreet());
        static::assertSame(GetOrderCapture::PAYER_ADDRESS_ADMIN_AREA_2, $address->getCity());
        $country = $address->getCountry();
        static::assertNotNull($country);
        static::assertSame('USA', $country->getIso3());
        $countryState = $address->getCountryState();
        static::assertNotNull($countryState);
        static::assertSame('New York', $countryState->getName());
        static::assertSame(GetOrderCapture::PAYER_PHONE_NUMBER, $address->getPhoneNumber());
    }

    public function testLoginSameAccount(): void
    {
        $order = new Order();
        $order->assign(GetOrderCapture::get());
        $firstCustomer = $this->doLogin($order);
        $secondCustomer = $this->doLogin($order);

        static::assertSame($firstCustomer->getId(), $secondCustomer->getId());
        static::assertSame($secondCustomer->getDefaultBillingAddressId(), $secondCustomer->getDefaultShippingAddressId());

        $addresses = $secondCustomer->getAddresses();
        static::assertNotNull($addresses);
        static::assertCount(1, $addresses);
    }

    public function testLoginSameAccountWithDifferentAddress(): void
    {
        $order = new Order();
        $order->assign(GetOrderCapture::get());
        $firstCustomer = $this->doLogin($order);

        $order->getPurchaseUnits()->first()?->getShipping()->getAddress()->setPostalCode('abcde');
        $secondCustomer = $this->doLogin($order);

        static::assertSame($firstCustomer->getId(), $secondCustomer->getId());

        $addresses = $secondCustomer->getAddresses();
        static::assertNotNull($addresses);
        static::assertCount(2, $addresses);
    }

    public function testLoginDifferentAccountSameEmail(): void
    {
        $order = new Order();
        $order->assign(GetOrderCapture::get());
        $firstCustomer = $this->doLogin($order);

        $order->getPaymentSource()?->getPaypal()?->setAccountId('aDifferentPayerId');
        $secondCustomer = $this->doLogin($order);

        static::assertNotSame($firstCustomer->getId(), $secondCustomer->getId());

        $addresses = $firstCustomer->getAddresses();
        static::assertNotNull($addresses);
        static::assertCount(1, $addresses);

        $addresses = $secondCustomer->getAddresses();
        static::assertNotNull($addresses);
        static::assertCount(1, $addresses);
    }

    private function doLogin(Order $order): CustomerEntity
    {
        $contextToken = $this->createCustomerService()->loginCustomer($order, $this->getSalesChannelContext());

        $context = $this->getContainer()->get(SalesChannelContextService::class)->get(
            new SalesChannelContextServiceParameters(
                TestDefaults::SALES_CHANNEL,
                $contextToken,
            )
        );

        /** @var EntityRepository $customerRepo */
        $customerRepo = $this->getContainer()->get('customer.repository');

        $customer = $context->getCustomer();
        static::assertNotNull($customer);

        $criteria = (new Criteria([$customer->getId()]))
            ->addAssociation('addresses.country')
            ->addAssociation('addresses.countryState');

        /** @var CustomerEntity|null $customer */
        $customer = $customerRepo->search($criteria, $context->getContext())->first();
        static::assertNotNull($customer);

        return $customer;
    }

    private function createCustomerService(): ExpressCustomerService
    {
        $settings = $this->createSystemConfigServiceMock([
            Settings::CLIENT_ID => 'testClientId',
            Settings::CLIENT_SECRET => 'testClientSecret',
        ]);
        /** @var EntityRepository $countryRepo */
        $countryRepo = $this->getContainer()->get('country.repository');
        /** @var EntityRepository $countryStateRepo */
        $countryStateRepo = $this->getContainer()->get('country_state.repository');
        /** @var EntityRepository $salutationRepo */
        $salutationRepo = $this->getContainer()->get('salutation.repository');
        /** @var EntityRepository $customerRepo */
        $customerRepo = $this->getContainer()->get('customer.repository');

        return new ExpressCustomerService(
            $this->getContainer()->get(RegisterRoute::class),
            $countryRepo,
            $countryStateRepo,
            $salutationRepo,
            $customerRepo,
            $this->getContainer()->get(AccountService::class),
            $settings,
            new NullLogger()
        );
    }
}
