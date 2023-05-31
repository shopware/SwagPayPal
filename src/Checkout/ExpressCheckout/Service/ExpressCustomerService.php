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
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\RestApi\V2\Api\Order;

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
        LoggerInterface $logger
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

    public function loginCustomer(Order $paypalOrder, SalesChannelContext $salesChannelContext): string
    {
        $this->logger->debug('Searching for existing customer');
        $newContextToken = $this->findExistingCustomer($paypalOrder, $salesChannelContext);

        if ($newContextToken !== null) {
            return $newContextToken;
        }

        $this->logger->debug('No existing customer found');

        return $this->registerNewCustomer($paypalOrder, $salesChannelContext);
    }

    private function findExistingCustomer(Order $paypalOrder, SalesChannelContext $salesChannelContext): ?string
    {
        $criteria = new Criteria();
        $criteria->addAssociation('addresses');
        $criteria->addFilter(new EqualsFilter('guest', true));
        $criteria->addFilter(new EqualsFilter('email', $paypalOrder->getPayer()->getEmailAddress()));
        $criteria->addFilter(new EqualsFilter(\sprintf('customFields.%s', self::EXPRESS_PAYER_ID), $paypalOrder->getPayer()->getPayerId()));
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
        if (!\method_exists($this->accountService, 'loginById')) {
            return $this->accountService->login($customer->getEmail(), $salesChannelContext, true);
        }

        return $this->accountService->loginById($customer->getId(), $salesChannelContext);
    }

    private function registerNewCustomer(Order $paypalOrder, SalesChannelContext $salesChannelContext): string
    {
        $salesChannelContext->getContext()->addExtension(self::EXPRESS_CHECKOUT_ACTIVE, new ArrayStruct());
        $customerDataBag = $this->getRegisterCustomerDataBag($paypalOrder, $salesChannelContext);
        $response = $this->registerRoute->register($customerDataBag, $salesChannelContext, false);
        $salesChannelContext->getContext()->removeExtension(self::EXPRESS_CHECKOUT_ACTIVE);
        $this->logger->debug('Customer created and logged in');

        $newToken = $response->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN);

        if ($newToken === null || $newToken === '') {
            if (\class_exists(RoutingException::class)) {
                throw RoutingException::missingRequestParameter(PlatformRequest::HEADER_CONTEXT_TOKEN);
            } else {
                /** @phpstan-ignore-next-line remove condition and keep if branch with min-version 6.5.2.0 */
                throw new MissingRequestParameterException(PlatformRequest::HEADER_CONTEXT_TOKEN);
            }
        }

        return $newToken;
    }

    private function getRegisterCustomerDataBag(Order $paypalOrder, SalesChannelContext $salesChannelContext): RequestDataBag
    {
        $salutationId = $this->getSalutationId($salesChannelContext->getContext());

        return new RequestDataBag([
            'guest' => true,
            'storefrontUrl' => $this->getStorefrontUrl($salesChannelContext),
            'salutationId' => $salutationId,
            'email' => $paypalOrder->getPayer()->getEmailAddress(),
            'firstName' => $paypalOrder->getPayer()->getName()->getGivenName(),
            'lastName' => $paypalOrder->getPayer()->getName()->getSurname(),
            'billingAddress' => $this->getAddressData($paypalOrder, $salesChannelContext->getContext(), $salutationId),
            'acceptedDataProtection' => true,
            self::EXPRESS_PAYER_ID => $paypalOrder->getPayer()->getPayerId(),
        ]);
    }

    /**
     * @return array<string, string|null>
     */
    private function getAddressData(Order $order, Context $context, ?string $salutationId = null): array
    {
        $payer = $order->getPayer();
        if (!empty($order->getPurchaseUnits())) {
            $shipping = $order->getPurchaseUnits()[0]->getShipping();
            $address = $shipping->getAddress();
            $names = \explode(' ', $shipping->getName()->getFullName());
            $lastName = \array_pop($names);
            $firstName = \implode(' ', $names);
        } else {
            $address = $payer->getAddress();
            $firstName = $payer->getName()->getGivenName();
            $lastName = $payer->getName()->getSurname();
        }

        $countryCode = $address->getCountryCode();
        $countryId = $this->getCountryId($countryCode, $context);
        $countryStateId = $this->getCountryStateId($countryId, $countryCode, $address->getAdminArea1(), $context);
        $phone = $payer->getPhone();

        return [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'salutationId' => $salutationId,
            'street' => $address->getAddressLine1(),
            'zipcode' => $address->getPostalCode(),
            'countryId' => $countryId,
            'countryStateId' => $countryStateId,
            'phoneNumber' => $phone !== null ? $phone->getPhoneNumber()->getNationalNumber() : null,
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

        $customerData = [
            'id' => $customer->getId(),
            'defaultShippingAddressId' => $addressId,
            'defaultBillingAddressId' => $addressId,
            'firstName' => $paypalOrder->getPayer()->getName()->getGivenName(),
            'lastName' => $paypalOrder->getPayer()->getName()->getSurname(),
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
