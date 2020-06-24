<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Helper;

use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\BasicTestDataBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait SalesChannelContextTrait
{
    use BasicTestDataBehaviour;

    protected function createSalesChannelContext(
        ContainerInterface $container,
        PaymentMethodCollection $paymentCollection,
        ?string $paymentMethodId = null,
        bool $withCustomer = true,
        bool $withOtherDefaultPayment = false
    ): SalesChannelContext {
        /** @var EntityRepositoryInterface $languageRepo */
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

        /** @var SalesChannelContextFactory $salesChannelContextFactory */
        $salesChannelContextFactory = $container->get(SalesChannelContextFactory::class);
        $salesChannelContext = $salesChannelContextFactory->create(
            'token',
            Defaults::SALES_CHANNEL,
            $options
        );

        if ($withOtherDefaultPayment) {
            $paymentMethod = new PaymentMethodEntity();
            $paymentMethod->setId('test-id');
            $salesChannelContext = new SalesChannelContext(
                $salesChannelContext->getContext(),
                $salesChannelContext->getToken(),
                $salesChannelContext->getSalesChannel(),
                $salesChannelContext->getCurrency(),
                $salesChannelContext->getCurrentCustomerGroup(),
                $salesChannelContext->getFallbackCustomerGroup(),
                $salesChannelContext->getTaxRules(),
                $paymentMethod,
                $salesChannelContext->getShippingMethod(),
                $salesChannelContext->getShippingLocation(),
                $salesChannelContext->getCustomer(),
                $salesChannelContext->getRuleIds()
            );
            $paymentCollection->add($paymentMethod);
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
            'email' => Uuid::randomHex() . '@example.com',
            'password' => 'shopware',
            'defaultPaymentMethodId' => $this->getValidPaymentMethodId(),
            'groupId' => Defaults::FALLBACK_CUSTOMER_GROUP,
            'salesChannelId' => Defaults::SALES_CHANNEL,
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
                ],
            ],
        ];

        /** @var EntityRepositoryInterface $customerRepo */
        $customerRepo = $this->getContainer()->get('customer.repository');
        $customerRepo->upsert([$customer], Context::createDefaultContext());

        return $customerId;
    }
}
