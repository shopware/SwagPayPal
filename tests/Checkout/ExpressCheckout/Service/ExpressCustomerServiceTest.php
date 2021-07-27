<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\ExpressCheckout\SalesChannel;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountService;
use Shopware\Core\Checkout\Customer\SalesChannel\RegisterRoute;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Swag\PayPal\Checkout\ExpressCheckout\Service\ExpressCustomerService;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\CheckoutRouteTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetOrderCapture;

class ExpressCustomerServiceTest extends TestCase
{
    use CheckoutRouteTrait;

    public const TEST_PAYMENT_ID_WITHOUT_STATE = 'testPaymentIdWithoutState';
    public const TEST_PAYMENT_ID_WITH_COUNTRY_WITHOUT_STATES = 'testPaymentIdWithCountryWithoutStates';
    public const TEST_PAYMENT_ID_WITH_STATE_NOT_FOUND = 'testPaymentIdWithStateNotFound';

    public function testLoginNewCustomer(): void
    {
        $order = new Order();
        $order->assign(GetOrderCapture::get());
        $customer = $this->doLogin($order);

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
        static::assertSame('USA', $country->getTranslation('name'));
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

        $addresses = $secondCustomer->getAddresses();
        static::assertNotNull($addresses);
        static::assertCount(1, $addresses);
    }

    public function testLoginSameAccountWithDifferentAddress(): void
    {
        $order = new Order();
        $order->assign(GetOrderCapture::get());
        $firstCustomer = $this->doLogin($order);

        $order->getPayer()->getAddress()->setPostalCode('abcde');
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

        $order->getPayer()->setPayerId('aDifferentPayerId');
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

        /** @var SalesChannelContextService $salesChannelContextService */
        $salesChannelContextService = $this->getContainer()->get(SalesChannelContextService::class);
        $context = $salesChannelContextService->get(
            new SalesChannelContextServiceParameters(
                Defaults::SALES_CHANNEL,
                $contextToken,
            )
        );

        /** @var EntityRepositoryInterface $customerRepo */
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
        /** @var RegisterRoute $registerRoute */
        $registerRoute = $this->getContainer()->get(RegisterRoute::class);
        /** @var EntityRepositoryInterface $countryRepo */
        $countryRepo = $this->getContainer()->get('country.repository');
        /** @var EntityRepositoryInterface $countryStateRepo */
        $countryStateRepo = $this->getContainer()->get('country_state.repository');
        /** @var EntityRepositoryInterface $salutationRepo */
        $salutationRepo = $this->getContainer()->get('salutation.repository');
        /** @var EntityRepositoryInterface $customerRepo */
        $customerRepo = $this->getContainer()->get('customer.repository');
        /** @var AccountService $accountService */
        $accountService = $this->getContainer()->get(AccountService::class);

        return new ExpressCustomerService(
            $registerRoute,
            $countryRepo,
            $countryStateRepo,
            $salutationRepo,
            $customerRepo,
            $accountService,
            $settings,
            new NullLogger()
        );
    }
}
