<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Util;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItemFactoryHandler\ProductLineItemFactory;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupCollection;
use Shopware\Core\Checkout\Promotion\Cart\PromotionItemBuilder;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\SuffixFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Salutation\SalutationCollection;
use Shopware\Core\System\Salutation\SalutationDefinition;
use Swag\PayPal\AgentCommerce\Exception\AgentException;
use Swag\PayPal\AgentCommerce\Struct\V1\Address;
use Swag\PayPal\AgentCommerce\Struct\V1\Coupon;
use Swag\PayPal\AgentCommerce\Struct\V1\CouponCollection;
use Swag\PayPal\AgentCommerce\Struct\V1\Customer;
use Swag\PayPal\AgentCommerce\Struct\V1\PayPalCart;
use Swag\PayPal\AgentCommerce\Struct\V1\Phone;
use Swag\PayPal\AgentCommerce\Struct\V1\Referral\CustomerName;
use Swag\PayPal\AgentCommerce\Struct\V1\ShippingAddress;

/**
 * @internal
 */
#[Package('checkout')]
class ShopwareCartTransformer
{
    /**
     * @param SalesChannelRepository<CountryCollection> $countryRepository
     * @param EntityRepository<SalutationCollection> $salutationRepository
     * @param EntityRepository<CustomerGroupCollection> $groupRepository
     */
    public function __construct(
        private readonly SalesChannelRepository $countryRepository,
        private readonly EntityRepository $salutationRepository,
        private readonly EntityRepository $groupRepository,
        private readonly ProductLineItemFactory $lineItemFactory,
        private readonly PromotionItemBuilder $promotionItemBuilder,
    ) {
    }

    /**
     * @return array{firstName: string, lastName: string, email: string|null, salesChannelId: string, groupId: string|null, shippingAddress: array, guest: true, billingAddress?: array}
     */
    public function extractCustomerData(PayPalCart $cart, string $salesChannelId, SalesChannelContext $context): array
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
            'groupId' => $this->groupRepository->searchIds($groupCriteria, $context->getContext())->firstId(),
            'shippingAddress' => $this->formatAddress($shippingAddress, $customer->getName(), $customer->getPhone(), $context),
            'guest' => true,
        ];

        if ($cart->getBillingAddress()) {
            $options['billingAddress'] = $this->formatAddress($cart->getBillingAddress(), $customer->getName(), $customer->getPhone(), $context);
        }

        return $options;
    }

    /**
     * @return LineItem[]
     */
    public function getLineItems(PayPalCart $payPalCart, SalesChannelContext $salesChannelContext): array
    {
        $lineItems = [];
        foreach ($payPalCart->getItems() as $item) {
            $lineItems[] = $this->lineItemFactory->create(['id' => $item->getVariantId(), 'quantity' => $item->getQuantity()], $salesChannelContext);
        }

        if ($payPalCart->isset('coupons')) {
            $lineItems = array_merge($lineItems, $this->handleCoupons($payPalCart->getCoupons()));
        }

        return $lineItems;
    }

    private function formatAddress(Address $address, CustomerName $name, ?Phone $phone, SalesChannelContext $context): array
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('iso', $address->getCountryCode()));

        if ($address->getAdminArea1()) {
            $criteria->getAssociation('states')
                ->setLimit(1)
                ->addFilter(new OrFilter([
                    new EqualsFilter('shortCode', $address->getAdminArea1()),
                    new SuffixFilter('shortCode', '-' . $address->getAdminArea1()),
                ]));
        }

        $country = $this->countryRepository->search($criteria, $context)->first();
        if (!$country instanceof CountryEntity) {
            throw AgentException::requiredFieldInvalid('address.countryCode', 'Country not found');
        }

        $criteria = (new Criteria())->addFilter(new EqualsFilter('salutationKey', SalutationDefinition::NOT_SPECIFIED));
        $salutationId = $this->salutationRepository->searchIds($criteria, $context->getContext())->firstId();

        return [
            'id' => Uuid::randomHex(),
            'salutationId' => $salutationId,
            'countryId' => $country->getId(),
            'countryStateId' => $country->getStates()?->first()?->getId(),
            'firstName' => $name->getGivenName(),
            'lastName' => $name->getSurname(),
            'zipcode' => $address->getPostalCode(),
            'city' => $address->getAdminArea2(),
            'street' => $address->getAddressLine1(),
            'phoneNumber' => $phone?->getFullPhoneNumber(),
        ];
    }

    /**
     * @return LineItem[]
     */
    private function handleCoupons(CouponCollection $coupons): array
    {
        $items = [];
        foreach ($coupons as $coupon) {
            if ($coupon->getAction() === Coupon::APPLY) {
                $items[] = $this->promotionItemBuilder->buildPlaceholderItem($coupon->getCode());
            }
        }

        return $items;
    }
}
