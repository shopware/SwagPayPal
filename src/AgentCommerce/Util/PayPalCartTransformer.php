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
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Address;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\BillingAddress;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\CartItem;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\CartItemCollection;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\CartTotals;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Context\InventoryIssueContext;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Customer;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Money;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\PayPalCart;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Referral\CustomerName;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\Referral\MetaData;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\ResolutionOption;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\ResolutionOptionCollection;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\ShippingAddress;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\ShippingOption;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\ShippingOptionCollection;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\ValidationIssue;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\ValidationIssueCollection;
use Swag\PayPal\AgentCommerce\Exception\AgentException;
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
    ) {
    }

    public function convertToPayPalCart(Cart $cart, SalesChannelContext $context): PayPalCart
    {
        $payPalCart = new PayPalCart();

        ['validationIssues' => $issues, 'status' => $status] = $this->convertToValidationIssues($cart, $context->getContext());

        $customer = $context->getCustomer();
        $shippingAddress = $this->convertAddress($customer?->getDefaultShippingAddress(), ShippingAddress::class, $context->getContext());
        $billingAddress = $this->convertAddress($customer?->getDefaultBillingAddress(), BillingAddress::class, $context->getContext());

        $payPalCart->setId('CART-' . $cart->getToken());
        $payPalCart->setItems($this->convertToCartItems($cart->getLineItems(), $context));
        $payPalCart->setAvailableShippingOptions($this->convertToAvailableShippingMethods($cart, $context));
        $payPalCart->setValidationIssues($issues);
        $payPalCart->setValidationStatus($status);
        $payPalCart->setCustomer($this->convertCustomer($customer));
        $payPalCart->setShippingAddress($shippingAddress);
        $payPalCart->setBillingAddress($billingAddress);

        $payPalCart->setTotals($this->createTotals($cart, $context));

        return $payPalCart;
    }

    public function convertToCartItems(LineItemCollection $lineItems, SalesChannelContext $context): CartItemCollection
    {
        $items = new CartItemCollection();

        foreach ($lineItems as $lineItem) {
            if ($lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                // TODO: ERROR?
                continue;
            }

            $options = [
                'itemId' => $lineItem->getPayloadValue('productNumber'), // @phpstan-ignore method.deprecated
                'quantity' => $lineItem->getQuantity(),
                'name' => $lineItem->getLabel(),
                'price' => (new Money())->assign([
                    'value' => (string) $lineItem->getPrice()?->getUnitPrice(),
                    'currency' => $context->getCurrency()->getIsoCode(),
                ]),
            ];

            $items->add((new CartItem())->assign($options));
        }

        return $items;
    }

    public function convertToAvailableShippingMethods(Cart $cart, SalesChannelContext $context): ShippingOptionCollection
    {
        $availableShippingMethods = new ShippingOptionCollection();

        $selectedMethods = [];
        foreach ($cart->getDeliveries() as $delivery) {
            $cost = ($selectedMethods[$delivery->getShippingMethod()->getId()] ?? 0) + $delivery->getShippingCosts()->getTotalPrice();

            $selectedMethods[$delivery->getShippingMethod()->getId()] = $cost;
        }

        $shippingCriteria = new Criteria();
        $shippingCriteria->addAssociations(['appShippingMethod.app', 'deliveryTime']);
        $shippingMethods = $this->shippingMethodRoute->load(new Request(), $context, $shippingCriteria)->getShippingMethods();
        foreach ($shippingMethods as $shippingMethod) {
            $options = [
                'id' => $shippingMethod->getId(),
                'name' => \sprintf('%s (%s)', $shippingMethod->getName(), $shippingMethod->getDeliveryTime()?->getName()),
                'description' => $shippingMethod->getDescription(),
                'isSelected' => false,
            ];

            if ($shippingMethod->getDeliveryTime()) {
                $deliveryTime = DeliveryDate::createFromDeliveryTime(DeliveryTime::createFromEntity($shippingMethod->getDeliveryTime()));

                $options['estimatedDelivery'] = $deliveryTime->getLatest()->format('Y-m-d');
            }

            if (\array_key_exists($shippingMethod->getId(), $selectedMethods)) {
                $options['selected'] = true;
                $options['price'] = (new Money())->assign([
                    'value' => (string) $selectedMethods[$shippingMethod->getId()],
                    'currency' => $context->getCurrency()->getIsoCode(),
                ]);
            }

            $availableShippingMethods->add((new ShippingOption())->assign($options));
        }

        return $availableShippingMethods;
    }

    /**
     * @return array{validationIssues: ValidationIssueCollection, status: string}
     */
    public function convertToValidationIssues(Cart $cart, Context $context): array
    {
        $status = PayPalCart::VALIDATION_STATUS__VALID;
        $errors = new ValidationIssueCollection();

        foreach ($cart->getErrors() as $error) {
            $validationIssue = new ValidationIssue();
            // TODO: Add some properties

            $errors->add($validationIssue);
        }

        $restockProducts = [];
        $productIds = array_filter($cart->getLineItems()->getReferenceIds());
        if (!empty($productIds)) {
            $criteria = new Criteria($productIds);
            $criteria->addFilter(
                new RangeFilter('stock', [RangeFilter::LTE => 0]),
                new NotFilter('AND', [new EqualsFilter('restockTime', null)])
            );
            $restockProducts = $this->productRepository->search($criteria, $context)->getElements();
        }

        foreach ($cart->getLineItems() as $lineItem) {
            $stock = $lineItem->getPayloadValue('stock'); // @phpstan-ignore method.deprecated
            if ($stock < $lineItem->getQuantity()) {
                $errors->add($validationIssue = new ValidationIssue());
                $validationIssue->setCode(ValidationIssue::CODE__INVENTORY_ISSUE);
                $validationIssue->setType(ValidationIssue::TYPE__BUSINESS_RULE);
                $validationIssue->setMessage('Product is no longer available'); // TODO: Snippet
                $validationIssue->setUserMessage(\sprintf('%s are temporarily out of stock.', $lineItem->getLabel())); // TODO: Snippet
                $validationIssue->setItemId($lineItem->getPayloadValue('productNumber')); // @phpstan-ignore method.deprecated
                $validationIssue->setContext($inventoryContext = new InventoryIssueContext());
                $validationIssue->setResolutionOptions(
                    new ResolutionOptionCollection([
                        $wait = new ResolutionOption(),
                        $remove = new ResolutionOption(),
                    ])
                );

                $inventoryContext->setSpecificIssue($stock > 0 ? InventoryIssueContext::ISSUE__INSUFFICIENT_INVENTORY : InventoryIssueContext::ISSUE__ITEM_OUT_OF_STOCK);
                $inventoryContext->setAvailableQuantity($stock);
                $inventoryContext->setRequestedQuantity($lineItem->getQuantity());

                $remove->setAction(ResolutionOption::ACTION__REMOVE_ITEM);
                $remove->setLabel('Remove from Cart'); // TODO: Snippet
                $remove->setMetadata($metaData = new MetaData());

                $metaData->setCostImpact((string) (-1 * $lineItem->getPrice()?->getTotalPrice())); // TODO: add currency?
                // $metaData->setPriority() // TODO: Add property?

                $wait->setAction(ResolutionOption::ACTION__WAIT_FOR_RESTOCK);
                $wait->setLabel(\sprintf('Notify when %s is back in stock', $lineItem->getLabel())); // TODO: Snippet

                if ($product = $restockProducts[(string) $lineItem->getReferencedId()] ?? null) {
                    // TODO: might not be final
                    $inventoryContext->setRestockDate(date('Y-m-d\T00:00:00', (int) strtotime('+' . $product->getRestockTime() . ' days')));

                    $wait->setMetadata($metaData = new MetaData());
                    $metaData->setEstimatedTime($product->getRestockTime() . ' Days'); // TODO: Snippet
                }
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
        if (!$addressEntity || !is_subclass_of($className, Address::class)) {
            return null;
        }

        $criteria = new Criteria([$addressEntity->getCountryId()]);
        $criteria->addFields(['iso']);

        $iso = $this->countryRepository->search($criteria, $context)->first()?->get('iso');
        if (!$iso) {
            throw AgentException::requiredFieldsMissing('countryCode');
        }

        $address = new $className();
        $address->setCountryCode($iso);
        $address->setPostalCode($addressEntity->__isset('zipcode') ? $addressEntity->getZipcode() : null);
        $address->setAddressLine1($addressEntity->__isset('street') ? $addressEntity->getStreet() : null);
        $address->setAddressLine2($addressEntity->__isset('city') ? $addressEntity->getCity() : null);

        return $address;
    }

    public function createTotals(Cart $cart, SalesChannelContext $context): CartTotals
    {
        $cartPrice = $cart->getPrice();

        $subtotal = new Money();
        $subtotal->setValue((string) $cartPrice->getPositionPrice());
        $subtotal->setCurrencyCode($context->getCurrency()->getIsoCode());

        $shipping = new Money();
        $shipping->setValue((string) $cart->getDeliveries()->getShippingCosts()->sum()->getTotalPrice());
        $shipping->setCurrencyCode($context->getCurrency()->getIsoCode());

        $tax = new Money();
        $tax->setValue((string) $cartPrice->getCalculatedTaxes()->getAmount());
        $tax->setCurrencyCode($context->getCurrency()->getIsoCode());

        $total = new Money();
        $total->setValue((string) $cartPrice->getTotalPrice());
        $total->setCurrencyCode($context->getCurrency()->getIsoCode());

        $totals = new CartTotals();
        $totals->setSubtotal($subtotal);
        $totals->setShipping($shipping);
        $totals->setTax($tax);
        $totals->setTotal($total);

        return $totals;
    }
}
