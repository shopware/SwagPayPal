<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractRegisterRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountService;
use Shopware\Core\Content\Newsletter\Exception\SalesChannelDomainNotFoundException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\PayPalSDK\Struct\V2\Order;
use Swag\PayPal\Checkout\Exception\MissingPayloadException;

#[Package('checkout')]
class ExpressCustomerService
{
    public const EXPRESS_CHECKOUT_ACTIVE = 'payPalExpressCheckoutActive';
    public const EXPRESS_PAYER_ID = 'payPalExpressPayerId';
    private const ADDRESS_KEYS = [
        'firstName',
        'lastName',
        'street',
        'zipcode',
        'countryId',
        'city',
        'phoneNumber',
        'additionalAddressLine1',
    ];

    private AbstractRegisterRoute $registerRoute;

    private EntityRepository $countryRepository;

    private EntityRepository $countryStateRepository;

    private EntityRepository $salutationRepository;

    private EntityRepository $customerRepository;

    private AccountService $accountService;

    private SystemConfigService $systemConfigService;

    private LoggerInterface $logger;

    /**
     * @internal
     */
    public function __construct(
        AbstractRegisterRoute $registerRoute,
        EntityRepository $countryRepository,
        EntityRepository $countryStateRepository,
        EntityRepository $salutationRepository,
        EntityRepository $customerRepository,
        AccountService $accountService,
        SystemConfigService $systemConfigService,
        LoggerInterface $logger,
    ) {
        $this->registerRoute = $registerRoute;
        $this->countryRepository = $countryRepository;
        $this->countryStateRepository = $countryStateRepository;
        $this->salutationRepository = $salutationRepository;
        $this->customerRepository = $customerRepository;
        $this->accountService = $accountService;
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
    }

    public function loginCustomer(Order $paypalOrder, SalesChannelContext $salesChannelContext, RequestDataBag $data): string
    {
        $this->logger->debug('Searching for existing customer');
        $newContextToken = $this->findExistingCustomer($paypalOrder, $salesChannelContext);

        if ($newContextToken !== null) {
            return $newContextToken;
        }

        $this->logger->debug('No existing customer found');

        return $this->registerNewCustomer($paypalOrder, $salesChannelContext, $data);
    }

    private function findExistingCustomer(Order $paypalOrder, SalesChannelContext $salesChannelContext): ?string
    {
        $paypal = $paypalOrder->getPaymentSource()?->getPaypal();
        if (!$paypal || !$paypal->isset('accountId')) {
            return null;
        }

        $criteria = new Criteria();
        $criteria->addAssociation('addresses');
        $criteria->addFilter(new EqualsFilter('guest', true));
        $criteria->addFilter(new EqualsFilter('email', $paypal->getEmailAddress()));
        $criteria->addFilter(new EqualsFilter(\sprintf('customFields.%s', self::EXPRESS_PAYER_ID), $paypal->getAccountId()));
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
            new EqualsFilter('boundSalesChannelId', null),
            new EqualsFilter('boundSalesChannelId', $salesChannelContext->getSalesChannel()->getId()),
        ]));

        /** @var CustomerEntity|null $customer */
        $customer = $this->customerRepository->search($criteria, $salesChannelContext->getContext())->first();

        if ($customer === null) {
            return null;
        }

        $this->logger->debug('Existing customer found, updating address data');

        $this->updateCustomer($customer, $paypalOrder, $salesChannelContext);

        $this->logger->debug('Logging in existing customer');

        return $this->accountService->loginById($customer->getId(), $salesChannelContext);
    }

    private function registerNewCustomer(Order $paypalOrder, SalesChannelContext $salesChannelContext, RequestDataBag $data): string
    {
        $salesChannelContext->getContext()->addExtension(self::EXPRESS_CHECKOUT_ACTIVE, new ArrayStruct());
        $customerDataBag = $this->getRegisterCustomerDataBag($paypalOrder, $salesChannelContext, $data);
        $response = $this->registerRoute->register($customerDataBag, $salesChannelContext, false);
        $salesChannelContext->getContext()->removeExtension(self::EXPRESS_CHECKOUT_ACTIVE);
        $this->logger->debug('Customer created and logged in');

        $newToken = $response->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN);

        if ($newToken === null || $newToken === '') {
            throw RoutingException::missingRequestParameter(PlatformRequest::HEADER_CONTEXT_TOKEN);
        }

        return $newToken;
    }

    private function getRegisterCustomerDataBag(Order $paypalOrder, SalesChannelContext $salesChannelContext, RequestDataBag $data): RequestDataBag
    {
        $salutationId = $this->getSalutationId($salesChannelContext->getContext());

        $paypal = $paypalOrder->getPaymentSource()?->getPaypal();
        if (!$paypal) {
            throw new MissingPayloadException($paypalOrder->getId(), 'paymentSource.paypal');
        }

        $data->add([
            'guest' => true,
            'storefrontUrl' => $this->getStorefrontUrl($salesChannelContext),
            'salutationId' => $salutationId,
            'email' => $paypal->getEmailAddress(),
            'firstName' => $paypal->getName()->getGivenName(),
            'lastName' => $paypal->getName()->getSurname(),
            'billingAddress' => $this->getAddressData($paypalOrder, $salesChannelContext->getContext(), $salutationId),
            'acceptedDataProtection' => true,
            self::EXPRESS_PAYER_ID => $paypal->isset('accountId') ? $paypal->getAccountId() : null,
        ]);

        return $data;
    }

    /**
     * @return array<string, string|null>
     */
    private function getAddressData(Order $order, Context $context, ?string $salutationId = null): array
    {
        $paypal = $order->getPaymentSource()?->getPaypal();
        if (!$paypal) {
            throw new MissingPayloadException($order->getId(), 'paymentSource.paypal');
        }
        $purchaseUnit = $order->getPurchaseUnits()->first();
        if ($purchaseUnit) {
            $shipping = $purchaseUnit->getShipping();
            $address = $shipping->getAddress();
            $names = \explode(' ', $shipping->getName()->getFullName());
            $lastName = \array_pop($names);
            $firstName = \implode(' ', $names);
        } else {
            $address = $paypal->getAddress();
            $firstName = $paypal->getName()->getGivenName();
            $lastName = $paypal->getName()->getSurname();
        }

        $countryCode = $address->getCountryCode();
        $countryId = $this->getCountryId($countryCode, $context);
        $countryStateId = $this->getCountryStateId($countryId, $countryCode, $address->getAdminArea1(), $context);
        $phone = $paypal->getPhoneNumber();

        return [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'salutationId' => $salutationId,
            'street' => $address->getAddressLine1(),
            'zipcode' => $address->getPostalCode(),
            'countryId' => $countryId,
            'countryStateId' => $countryStateId,
            'phoneNumber' => $phone?->getNationalNumber(),
            'city' => $address->getAdminArea2(),
            'additionalAddressLine1' => $address->getAddressLine2(),
        ];
    }

    private function getSalutationId(Context $context): string
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(new EqualsFilter('salutationKey', 'not_specified'));

        $salutationId = $this->salutationRepository->searchIds($criteria, $context)->firstId();

        if ($salutationId !== null) {
            return $salutationId;
        }

        $salutationId = $this->salutationRepository->searchIds($criteria->resetFilters(), $context)->firstId();

        if ($salutationId === null) {
            throw new \RuntimeException('No salutation found in Shopware');
        }

        return $salutationId;
    }

    private function getCountryId(string $code, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', $code));

        return $this->countryRepository->searchIds($criteria, $context)->firstId();
    }

    private function getCountryStateId(?string $countryId, string $countryCode, ?string $stateCode, Context $context): ?string
    {
        if ($countryId === null) {
            return null;
        }

        if ($stateCode === null || $stateCode === '') {
            return null;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('countryId', $countryId));
        $criteria->addFilter(new EqualsFilter('shortCode', \sprintf('%s-%s', $countryCode, $stateCode)));

        return $this->countryStateRepository->searchIds($criteria, $context)->firstId();
    }

    private function getStorefrontUrl(SalesChannelContext $salesChannelContext): string
    {
        $salesChannel = $salesChannelContext->getSalesChannel();
        $domainUrl = $this->systemConfigService->get('core.loginRegistration.doubleOptInDomain', $salesChannel->getId());

        if (\is_string($domainUrl) && $domainUrl !== '') {
            return $domainUrl;
        }

        $domains = $salesChannel->getDomains();
        if ($domains === null) {
            throw new SalesChannelDomainNotFoundException($salesChannel);
        }

        $domain = $domains->first();
        if ($domain === null) {
            throw new SalesChannelDomainNotFoundException($salesChannel);
        }

        return $domain->getUrl();
    }

    private function updateCustomer(CustomerEntity $customer, Order $paypalOrder, SalesChannelContext $salesChannelContext): void
    {
        $addressData = $this->getAddressData(
            $paypalOrder,
            $salesChannelContext->getContext()
        );

        $matchingAddress = null;

        $addresses = $customer->getAddresses();
        if ($addresses !== null) {
            foreach ($addresses as $address) {
                if ($this->isIdenticalAddress($address, $addressData)) {
                    $matchingAddress = $address;

                    break;
                }
            }
        }

        $addressId = $matchingAddress === null ? Uuid::randomHex() : $matchingAddress->getId();
        $salutationId = $this->getSalutationId($salesChannelContext->getContext());

        $paypal = $paypalOrder->getPaymentSource()?->getPaypal();
        if (!$paypal) {
            throw new MissingPayloadException($paypalOrder->getId(), 'paymentSource.paypal');
        }

        $customerData = [
            'id' => $customer->getId(),
            'defaultShippingAddressId' => $addressId,
            'defaultBillingAddressId' => $addressId,
            'firstName' => $paypal->getName()->getGivenName(),
            'lastName' => $paypal->getName()->getSurname(),
            'salutationId' => $salutationId,
            'addresses' => [
                \array_merge($addressData, [
                    'id' => $addressId,
                    'salutationId' => $salutationId,
                ]),
            ],
        ];

        $this->customerRepository->update([$customerData], $salesChannelContext->getContext());
    }

    /**
     * @param array<string, string|null> $addressData
     */
    private function isIdenticalAddress(CustomerAddressEntity $address, array $addressData): bool
    {
        foreach (self::ADDRESS_KEYS as $key) {
            if ($address->get($key) !== ($addressData[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
