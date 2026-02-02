<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Util;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryDate;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryTime;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Shipping\SalesChannel\AbstractShippingMethodRoute;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Address;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\AppliedCoupon;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\AppliedCouponCollection;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\BillingAddress;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\CartItem;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\CartItemCollection;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\CartTotals;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Customer;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Money;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\PayPalCart;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Phone;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Referral\CustomerName;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\ShippingAddress;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\ShippingOption;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\ShippingOptionCollection;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\ValidationIssueCollection;
use Swag\PayPal\AgentCommerce\Exception\AgentException;
use Swag\PayPal\AgentCommerce\Validation\ValidationIssues;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
class PayPalCartTransformer
{
    /**
     * @param EntityRepository<ProductCollection> $productRepository
     * @param EntityRepository<CountryCollection> $countryRepository
     */
    public function __construct(
        private readonly EntityRepository $productRepository,
        private readonly EntityRepository $countryRepository,
        private readonly AbstractShippingMethodRoute $shippingMethodRoute,
        private readonly ValidationIssues $validationIssues,
    ) {
    }

    public function convertToPayPalCart(Cart $cart, SalesChannelContext $context, ?PayPalCart $initialCart = null): PayPalCart
    {
        $payPalCart = new PayPalCart();

        ['validationIssues' => $issues, 'status' => $status] = $this->convertToValidationIssues($cart, $initialCart?->getItems() ?? new CartItemCollection(), $context);

        $customer = $context->getCustomer();
        $shippingAddress = $this->convertAddress($customer?->getDefaultShippingAddress(), ShippingAddress::class, $context->getContext());
        $billingAddress = $this->convertAddress($customer?->getDefaultBillingAddress(), BillingAddress::class, $context->getContext());

        $payPalCart->setId('CART-' . $cart->getToken());
        $payPalCart->setItems($this->convertToCartItems($cart->getLineItems()->filterFlatByType(LineItem::PRODUCT_LINE_ITEM_TYPE), $context));
        $payPalCart->setAppliedCoupons($this->convertToAppliedCoupons($cart->getLineItems()->filterFlatByType(LineItem::PROMOTION_LINE_ITEM_TYPE), $context));
        $payPalCart->setAvailableShippingOptions($this->convertToAvailableShippingMethods($cart, $context));
        $payPalCart->setValidationIssues($issues);
        $payPalCart->setValidationStatus($status);
        $payPalCart->setCustomer($this->convertCustomer($customer));
        $payPalCart->setShippingAddress($shippingAddress);
        $payPalCart->setBillingAddress($billingAddress);

        $payPalCart->setTotals($this->createTotals($cart, $context));

        return $payPalCart;
    }

    /**
     * @param LineItem[] $lineItems
     */
    public function convertToCartItems(array $lineItems, SalesChannelContext $context): CartItemCollection
    {
        $items = new CartItemCollection();

        foreach ($lineItems as $lineItem) {
            if (!$lineItem->getPrice()) {
                continue;
            }

            $itemPrice = new Money();
            $itemPrice->setValue((string) $lineItem->getPrice()->getUnitPrice());
            $itemPrice->setCurrencyCode($context->getCurrency()->getIsoCode());

            $cartItem = new CartItem();
            // itemId will be removed in the future.
            $cartItem->setItemId($lineItem->getReferencedId());
            $cartItem->setVariantId($lineItem->getReferencedId());
            $cartItem->setParentId($lineItem->getPayloadValue('parentId'));
            $cartItem->setQuantity($lineItem->getQuantity());
            $cartItem->setName($lineItem->getLabel());
            $cartItem->setPrice($itemPrice);

            $items->add($cartItem);
        }

        return $items;
    }

    public function convertToAvailableShippingMethods(Cart $cart, SalesChannelContext $context): ShippingOptionCollection
    {
        $availableShippingMethods = new ShippingOptionCollection();

        $selectedMethods = [];
        foreach ($cart->getDeliveries() as $delivery) {
            $selectedMethods[$delivery->getShippingMethod()->getId()] ??= 0;
            $selectedMethods[$delivery->getShippingMethod()->getId()] += $delivery->getShippingCosts()->getTotalPrice();
        }

        $shippingCriteria = new Criteria();
        $shippingCriteria->addAssociations(['appShippingMethod.app', 'deliveryTime']);
        $shippingMethods = $this->shippingMethodRoute->load(new Request(), $context, $shippingCriteria)->getShippingMethods();
        foreach ($shippingMethods as $shippingMethod) {
            // TODO: for now we remove all non selected shipping methods
            // TODO: paypal needs prices for all shipping methods, otherwise it will fail
            // TODO: only the selected shipping method has a calculated price (rule-system issue)
            // TODO: should be removed, once we figure out a solution
            if (!\array_key_exists($shippingMethod->getId(), $selectedMethods)) {
                continue;
            }

            $shippingOption = new ShippingOption();
            $shippingOption->setId($shippingMethod->getId());
            $shippingOption->setName($shippingMethod->getTranslation('name'));
            $shippingOption->setDescription($shippingMethod->getTranslation('description'));
            $shippingOption->setIsSelected(false);

            if ($shippingMethod->getDeliveryTime()) {
                $shippingOption->setName(\sprintf('%s (%s)', $shippingOption->getName(), $shippingMethod->getDeliveryTime()->getTranslation('name')));
                $deliveryTime = DeliveryDate::createFromDeliveryTime(DeliveryTime::createFromEntity($shippingMethod->getDeliveryTime()));

                $shippingOption->setEstimatedDelivery($deliveryTime->getLatest()->format('Y-m-d'));
            }

            if (\array_key_exists($shippingMethod->getId(), $selectedMethods)) {
                $price = new Money();
                $price->setValue((string) $selectedMethods[$shippingMethod->getId()]);
                $price->setCurrencyCode($context->getCurrency()->getIsoCode());

                $shippingOption->setIsSelected(true);
                $shippingOption->setPrice($price);
            }

            $availableShippingMethods->add($shippingOption);
        }

        return $availableShippingMethods;
    }

    /**
     * @return array{validationIssues: ValidationIssueCollection, status: string}
     */
    public function convertToValidationIssues(Cart $cart, CartItemCollection $cartItems, SalesChannelContext $context): array
    {
        $status = PayPalCart::VALIDATION_STATUS__VALID;
        $errors = new ValidationIssueCollection();

        foreach ($cart->getErrors() as $error) {
            if (!$error->blockOrder()) {
                // Not errors we want to add here
                continue;
            }

            $errors->add($this->validationIssues->cartError($error, $context->getLanguageInfo()->localeCode));
        }

        $lineItems = $cart->getLineItems()->filterFlatByType(LineItem::PRODUCT_LINE_ITEM_TYPE);

        $restockProducts = [];
        $productIds = [];
        foreach ($lineItems as $lineItem) {
            if ($lineItem->getReferencedId() !== null && Uuid::isValid($lineItem->getReferencedId())) {
                $productIds[] = $lineItem->getReferencedId();
            }
        }

        if (!empty($productIds)) {
            $criteria = new Criteria($productIds);
            $criteria->addFilter(
                new RangeFilter('stock', [RangeFilter::LTE => 0]),
                new NotFilter('AND', [new EqualsFilter('restockTime', null)])
            );
            $restockProducts = $this->productRepository->search($criteria, $context->getContext())->getElements();
        }

        $mapped = [];
        foreach ($cartItems as $cartItem) {
            $mapped[$cartItem->getVariantId()] = $cartItem;
        }

        foreach ($lineItems as $lineItem) {
            $stock = $lineItem->getPayloadValue('stock');
            if ($stock !== null && $stock < $lineItem->getQuantity()) {
                $issue = $this->validationIssues->outOfStock($lineItem, $restockProducts[$lineItem->getReferencedId()] ?? null, $context->getCurrency());

                $errors->add($issue);
            }

            $initItem = $mapped[$lineItem->getReferencedId()] ?? null;
            $initPrice = $initItem?->getPrice()?->getValue();
            if ($initPrice !== null && (float) $initPrice < $lineItem->getPrice()?->getUnitPrice()) {
                $errors->add($this->validationIssues->changedPrice($lineItem, $initPrice, $context->getCurrency(), $context->getItemRounding()));
            }
        }

        // Age verification would be "REQUIRES_ADDITIONAL_INFORMATION"
        if ($errors->count()) {
            $status = PayPalCart::VALIDATION_STATUS__INVALID;
        }

        return ['validationIssues' => $errors, 'status' => $status];
    }

    public function convertCustomer(?CustomerEntity $customerEntity): ?Customer
    {
        if (!$customerEntity) {
            return null;
        }

        $customer = new Customer();
        $name = new CustomerName();
        $name->setGivenName($customerEntity->getFirstName());
        $name->setSurname($customerEntity->getLastName());

        $customer->setName($name);
        $customer->setEmailAddress($customerEntity->getEmail());

        $phoneNumber = $customerEntity->getDefaultShippingAddress()?->getPhoneNumber();
        if ($phoneNumber && Phone::isValidPhoneNumber($phoneNumber)) {
            $phone = new Phone();
            $phone->setPhoneNumber($phoneNumber);
            $customer->setPhone($phone);
        }

        return $customer;
    }

    /**
     * @template TAddress as Address
     *
     * @param class-string<TAddress> $className
     *
     * @return TAddress
     */
    public function convertAddress(?CustomerAddressEntity $addressEntity, string $className, Context $context): ?Address
    {
        if (!$addressEntity) {
            return null;
        }

        $iso = $addressEntity->getCountry()?->getIso();
        if (!$iso) {
            $criteria = new Criteria([$addressEntity->getCountryId()]);
            $criteria->addFields(['iso']);

            $iso = $this->countryRepository->search($criteria, $context)->first()?->get('iso');
            if (!$iso) {
                throw AgentException::requiredFieldInvalid('address.countryCode', 'Country not found');
            }
        }

        $address = new $className();
        $address->setCountryCode($iso);
        $address->setPostalCode($addressEntity->__isset('zipcode') ? $addressEntity->getZipcode() : null);
        $address->setAddressLine1($addressEntity->__isset('street') ? $addressEntity->getStreet() : null);
        $address->setAddressLine2($addressEntity->__isset('additionalAddressLine1') ? $addressEntity->getAdditionalAddressLine1() : null);
        $address->setAdminArea1($addressEntity->__isset('countryState') ? $addressEntity->getCountryState()?->getShortCode() : null);
        $address->setAdminArea2($addressEntity->__isset('city') ? $addressEntity->getCity() : null);

        return $address;
    }

    public function createTotals(Cart $cart, SalesChannelContext $context): CartTotals
    {
        $iso = $context->getCurrency()->getIsoCode();
        $cartPrice = $cart->getPrice();

        $promotions = $cart->getLineItems()->filterFlatByType(LineItem::PROMOTION_LINE_ITEM_TYPE);
        $promotionDiscount = (new LineItemCollection($promotions))->getPrices()->sum();

        $subtotal = new Money();
        $subtotal->setValue((string) ($cartPrice->getPositionPrice() - $promotionDiscount->getTotalPrice()));
        $subtotal->setCurrencyCode($iso);

        $shipping = new Money();
        $shipping->setValue((string) $cart->getDeliveries()->getShippingCosts()->sum()->getTotalPrice());
        $shipping->setCurrencyCode($iso);

        $tax = new Money();
        $tax->setValue((string) $cartPrice->getCalculatedTaxes()->getAmount());
        $tax->setCurrencyCode($iso);

        $total = new Money();
        $total->setValue((string) $cartPrice->getTotalPrice());
        $total->setCurrencyCode($iso);

        $discount = new Money();
        $discount->setValue((string) ($promotionDiscount->getTotalPrice() * -1));
        $discount->setCurrencyCode($iso);

        $totals = new CartTotals();
        $totals->setSubtotal($subtotal);
        $totals->setShipping($shipping);
        $totals->setTax($tax);
        $totals->setTotal($total);
        $totals->setDiscount($discount);

        return $totals;
    }

    /**
     * @param LineItem[] $lineItems
     */
    public function convertToAppliedCoupons(array $lineItems, SalesChannelContext $context): ?AppliedCouponCollection
    {
        if (empty($lineItems)) {
            return null;
        }

        $appliedCoupons = new AppliedCouponCollection();
        foreach ($lineItems as $lineItem) {
            if (!$lineItem->getPrice()) {
                continue;
            }

            $discount = new Money();
            $discount->setValue((string) $lineItem->getPrice()->getTotalPrice());
            $discount->setCurrencyCode($context->getCurrency()->getIsoCode());

            $coupon = new AppliedCoupon();
            $coupon->setCode($lineItem->getPayloadValue('code'));
            $coupon->setDescription($lineItem->getDescription());
            $coupon->setDiscountAmount($discount);

            $appliedCoupons->add($coupon);
        }

        return $appliedCoupons->count() ? $appliedCoupons : null;
    }
}
