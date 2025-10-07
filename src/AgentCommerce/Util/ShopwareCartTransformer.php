<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Util;

use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\System\Salutation\SalutationCollection;
use Shopware\Core\System\Salutation\SalutationDefinition;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Address;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Customer;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\PayPalCart;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Phone;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Referral\CustomerName;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\ShippingAddress;
use Swag\PayPal\AgentCommerce\Exception\AgentException;

/**
 * @internal
 */
#[Package('checkout')]
class ShopwareCartTransformer
{
    /**
     * @param EntityRepository<CountryCollection> $countryRepository
     * @param EntityRepository<SalutationCollection> $salutationRepository
     * @param EntityRepository<CustomerGroupCollection> $groupRepository
     */
    public function __construct(
        private readonly EntityRepository $countryRepository,
        private readonly EntityRepository $salutationRepository,
        private readonly EntityRepository $groupRepository,
    ) {
    }

    /**
     * @return array{firstName: string, lastName: string, email: string|null, salesChannelId: string, groupId: string|null, shippingAddress: array, guest: true, billingAddress?: array}
     */
    public function extractCustomerData(PayPalCart $cart, string $salesChannelId, Context $context): array
    {
        /** @var Customer $customer */
        $customer = $cart->getCustomer();
        /** @var ShippingAddress $shippingAddress */
        $shippingAddress = $cart->getShippingAddress();

        $groupCriteria = new Criteria();
        $groupCriteria->addFilter(new EqualsFilter('salesChannels.id', $salesChannelId));

        $options = [
            'firstName' => $customer->getName()->getGivenName(),
            'lastName' => $customer->getName()->getSurname(),
            'email' => $customer->getEmailAddress(),
            'salesChannelId' => $salesChannelId,
            'groupId' => $this->groupRepository->searchIds($groupCriteria, $context)->firstId(),
            'shippingAddress' => $this->formatAddress($shippingAddress, $customer->getName(), $customer->getPhone(), $context),
            'guest' => true,
        ];

        if ($cart->getBillingAddress()) {
            $options['billingAddress'] = $this->formatAddress($cart->getBillingAddress(), $customer->getName(), $customer->getPhone(), $context);
        }

        return $options;
    }

    private function formatAddress(Address $address, CustomerName $name, ?Phone $phone, Context $context): array
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('iso', $address->getCountryCode()));
        $countryId = $this->countryRepository->searchIds($criteria, $context)->firstId();

        if (!$countryId) {
            throw AgentException::requiredFieldInvalid('address.countryCode', 'Country not found');
        }

        $criteria = (new Criteria())->addFilter(new EqualsFilter('salutationKey', SalutationDefinition::NOT_SPECIFIED));
        $salutationId = $this->salutationRepository->searchIds($criteria, $context)->firstId();

        return [
            'id' => Uuid::randomHex(),
            'salutationId' => $salutationId,
            'countryId' => $countryId,
            'firstName' => $name->getGivenName(),
            'lastName' => $name->getSurname(),
            'zipcode' => $address->getPostalCode(),
            'city' => $address->getAdminArea2(),
            'street' => $address->getAddressLine1(),
            'phoneNumber' => $phone?->getFullPhoneNumber(),
        ];
    }
}
