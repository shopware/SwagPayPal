<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Helper;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\DefaultPayment;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\TestDefaults;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Make sure you also implement the BasicTestDataBehaviour trait, while using this trait
 */
trait SalesChannelContextTrait
{
    protected function createSalesChannelContext(
        ContainerInterface $container,
        PaymentMethodCollection $paymentCollection,
        ?string $paymentMethodId = null,
        bool $withCustomer = true,
        bool $withOtherDefaultPayment = false,
        bool $withCartLineItems = false
    ): SalesChannelContext {
        /** @var EntityRepository $languageRepo */
        $languageRepo = $container->get('language.repository');
        $criteria = new Criteria();
        $criteria->addAssociation('language.locale');
        $criteria->addFilter(new EqualsFilter('language.locale.code', 'de-DE'));

        $languageId = $languageRepo->searchIds($criteria, Context::createDefaultContext())->firstId();

        $options = [
            SalesChannelContextService::LANGUAGE_ID => $languageId,
        ];

        if ($paymentMethodId !== null) {
            $options[SalesChannelContextService::PAYMENT_METHOD_ID] = $paymentMethodId;
        }

        if ($withCustomer) {
            $options[SalesChannelContextService::CUSTOMER_ID] = $this->createCustomer();
        }

        $salesChannelContext = $container->get(SalesChannelContextFactory::class)->create(
            Uuid::randomHex(),
            TestDefaults::SALES_CHANNEL,
            $options
        );

        if ($withOtherDefaultPayment) {
            $paymentMethod = $salesChannelContext->getPaymentMethod();
            $paymentCollection->add(clone $paymentMethod);
            $paymentMethod->setId('test-id');
            $paymentMethod->setHandlerIdentifier(DefaultPayment::class);
        }

        if ($withCartLineItems) {
            $productId = Uuid::randomHex();
            $this->createProduct($productId, $salesChannelContext->getContext());

            $lineItem = new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE, $productId);

            $cartService = $this->getContainer()->get(CartService::class);
            $cart = $cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
            $cartService->add($cart, $lineItem, $salesChannelContext);
        }

        $salesChannelContext->getSalesChannel()->setPaymentMethods($paymentCollection);

        return $salesChannelContext;
    }

    private function createCustomer(): string
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
            'birthday' => new \DateTime('-30 years'),
            'email' => Uuid::randomHex() . '@example.com',
            'password' => 'shopware',
            'defaultPaymentMethodId' => $this->getValidPaymentMethodId(),
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
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
                    'phoneNumber' => '+49123456789',
                ],
            ],
        ];

        /** @var EntityRepository $customerRepo */
        $customerRepo = $this->getContainer()->get('customer.repository');
        $customerRepo->upsert([$customer], Context::createDefaultContext());

        return $customerId;
    }

    private function createProduct(string $productId, Context $context): void
    {
        /** @var EntityRepository $productRepo */
        $productRepo = $this->getContainer()->get('product.repository');

        $productRepo->create([
            [
                'id' => $productId,
                'name' => 'foo bar',
                'manufacturer' => [
                    'id' => Uuid::randomHex(),
                    'name' => 'amazing brand',
                    'translations' => [
                        Defaults::LANGUAGE_SYSTEM => [
                            'name' => 'amazing brand',
                        ],
                    ],
                ],
                'productNumber' => 'P1234',
                'taxId' => $this->getValidTaxId(),
                'price' => [
                    [
                        'currencyId' => Defaults::CURRENCY,
                        'gross' => 10,
                        'net' => 12,
                        'linked' => false,
                    ],
                ],
                'stock' => 0,
                'active' => true,
                'visibilities' => [
                    [
                        'salesChannelId' => TestDefaults::SALES_CHANNEL,
                        'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                    ],
                ],
                'translations' => [
                    Defaults::LANGUAGE_SYSTEM => [
                        'name' => 'foo bar',
                    ],
                ],
            ],
        ], $context);
    }
}
