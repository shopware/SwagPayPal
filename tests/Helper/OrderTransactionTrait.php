<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Helper;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryStates;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\IdsCollection;
use Shopware\Core\Framework\Test\TestCaseBase\BasicTestDataBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\StateMachine\Loader\InitialStateIdLoader;
use Shopware\Core\System\StateMachine\StateMachineException;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetCapturedOrderCapture;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 */
#[Package('checkout')]
trait OrderTransactionTrait
{
    use BasicTestDataBehaviour;
    use StateMachineStateTrait;

    public function getTransactionId(
        Context $context,
        ContainerInterface $container,
        string $transactionStateTechnicalName = OrderTransactionStates::STATE_OPEN
    ): string {
        $stateId = $this->getOrderTransactionStateIdByTechnicalName($transactionStateTechnicalName, $container, $context);
        if (!$stateId) {
            throw StateMachineException::stateMachineStateNotFound(OrderTransactionStates::STATE_MACHINE, $transactionStateTechnicalName);
        }

        $paymentMethodId = $container->get(PaymentMethodUtil::class)->getPayPalPaymentMethodId($context);
        static::assertNotNull($paymentMethodId);

        return $this->getValidTransactionId(
            $this->getOrderData(new IdsCollection()),
            $paymentMethodId,
            $stateId,
            $container,
            $context,
            true
        );
    }

    public function getTransaction(
        string $transactionId,
        ContainerInterface $container,
        Context $context
    ): ?OrderTransactionEntity {
        /** @var EntityRepository $orderTransactionRepo */
        $orderTransactionRepo = $container->get(OrderTransactionDefinition::ENTITY_NAME . '.repository');

        /** @var OrderTransactionEntity|null $transaction */
        $transaction = $orderTransactionRepo->search(new Criteria([$transactionId]), $context)->get($transactionId);

        return $transaction;
    }

    protected function assertOrderTransactionState(string $state, string $transactionId, Context $context): void
    {
        $container = $this->getContainer();
        $expectedStateId = $this->getOrderTransactionStateIdByTechnicalName(
            $state,
            $container,
            $context
        );

        $transaction = $this->getTransaction($transactionId, $container, $context);
        static::assertNotNull($transaction);
        static::assertNotNull($expectedStateId);
        static::assertSame($expectedStateId, $transaction->getStateId());
    }

    private function getValidTransactionId(
        array $orderData,
        string $paymentMethodId,
        string $stateId,
        ContainerInterface $container,
        Context $context,
        bool $withCustomField = false
    ): string {
        /** @var EntityRepository $orderRepo */
        $orderRepo = $container->get('order.repository');
        /** @var EntityRepository $orderTransactionRepo */
        $orderTransactionRepo = $container->get('order_transaction.repository');

        $orderId = $orderData[0]['id'];
        $orderRepo->create($orderData, $context);

        $orderTransactionId = Uuid::randomHex();
        $orderTransactionData = [
            'id' => $orderTransactionId,
            'orderId' => $orderId,
            'paymentMethodId' => $paymentMethodId,
            'amount' => [
                'quantity' => 1,
                'taxRules' => [
                    0 => [
                        'taxRate' => 19.0,
                        'extensions' => [],
                        'percentage' => 100.0,
                    ],
                    1 => [
                        'taxRate' => 7.0,
                        'extensions' => [],
                        'percentage' => 100.0,
                    ],
                ],
                'unitPrice' => 20028.0,
                'totalPrice' => 20028.0,
                'referencePrice' => null,
                'calculatedTaxes' => [
                    0 => [
                        'tax' => 2137.57,
                        'price' => 11250.420168067229,
                        'taxRate' => 19.0,
                        'extensions' => [],
                    ],
                    1 => [
                        'tax' => 434.39,
                        'price' => 6205.607476635514,
                        'taxRate' => 7.0,
                        'extensions' => [],
                    ],
                ],
            ],
            'stateId' => $stateId,
        ];

        if ($withCustomField) {
            $orderTransactionData['customFields'] = [
                SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_TRANSACTION_ID => OrderTransactionRepoMock::WEBHOOK_PAYMENT_ID,
                SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => GetCapturedOrderCapture::ID,
            ];
        }

        $orderTransactionRepo->create([$orderTransactionData], $context);

        return $orderTransactionId;
    }

    private function getOrderData(IdsCollection $ids, string $idsPrefix = 'order'): array
    {
        $orderId = $ids->get($idsPrefix . '-id');
        $orderCustomerId = $ids->get($idsPrefix . '-customer-id');
        $addressId = $ids->get($idsPrefix . '-address-id');
        $orderLineItemId = $ids->get($idsPrefix . '-line-item-id');
        $countryStateId = $ids->get($idsPrefix . '-country-state-id');
        $customerId = $ids->get($idsPrefix . '-customer-id');
        $orderNumber = $ids->get($idsPrefix . '-order-number');

        /** @var EntityRepository $salesChannelRepository */
        $salesChannelRepository = $this->getContainer()->get('sales_channel.repository');

        /** @var SalesChannelEntity $salesChannel */
        $salesChannel = $salesChannelRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('id', TestDefaults::SALES_CHANNEL)),
            Context::createDefaultContext()
        )->first();

        $paymentMethodId = $salesChannel->getPaymentMethodId();
        $shippingMethodId = $salesChannel->getShippingMethodId();
        $salutationId = $this->getValidSalutationId();
        $countryId = $this->getValidCountryId(TestDefaults::SALES_CHANNEL);

        return [
            [
                'id' => $orderId,
                'orderNumber' => $orderNumber,
                'price' => new CartPrice(10, 10, 10, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_NET),
                'shippingCosts' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
                'stateId' => $this->getContainer()->get(InitialStateIdLoader::class)->get(OrderStates::STATE_MACHINE),
                'versionId' => Defaults::LIVE_VERSION,
                'paymentMethodId' => $paymentMethodId,
                'currencyId' => Defaults::CURRENCY,
                'currencyFactor' => 1,
                'salesChannelId' => TestDefaults::SALES_CHANNEL,
                'orderDateTime' => '2019-04-01 08:36:43.267',
                'itemRounding' => \json_decode(\json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
                'totalRounding' => \json_decode(\json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
                'deliveries' => [
                    [
                        'stateId' => $this->getContainer()->get(InitialStateIdLoader::class)->get(OrderDeliveryStates::STATE_MACHINE),
                        'shippingMethodId' => $shippingMethodId,
                        'shippingCosts' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
                        'shippingDateEarliest' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_FORMAT),
                        'shippingDateLatest' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_FORMAT),
                        'shippingOrderAddress' => [
                            'salutationId' => $salutationId,
                            'firstName' => 'Floy',
                            'lastName' => 'Glover',
                            'zipcode' => '59438-0403',
                            'city' => 'Stellaberg',
                            'street' => 'street',
                            'country' => [
                                'name' => 'kasachstan',
                                'id' => $countryId,
                            ],
                        ],
                        'trackingCodes' => [
                            'CODE-1',
                            'CODE-2',
                        ],
                        'positions' => [
                            [
                                'price' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
                                'orderLineItemId' => $orderLineItemId,
                            ],
                        ],
                    ],
                ],
                'lineItems' => [
                    [
                        'id' => $orderLineItemId,
                        'identifier' => 'test',
                        'quantity' => 1,
                        'type' => 'test',
                        'label' => 'test',
                        'price' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
                        'priceDefinition' => new QuantityPriceDefinition(10, new TaxRuleCollection()),
                        'priority' => 100,
                        'good' => true,
                    ],
                ],
                'deepLinkCode' => 'BwvdEInxOHBbwfRw6oHF1Q_orfYeo9RY',
                'orderCustomerId' => $orderCustomerId,
                'orderCustomer' => [
                    'id' => $orderCustomerId,
                    'email' => 'test@example.com',
                    'firstName' => 'Noe',
                    'lastName' => 'Hill',
                    'salutationId' => $salutationId,
                    'title' => 'Doc',
                    'customerNumber' => 'Test',
                    'orderVersionId' => Defaults::LIVE_VERSION,
                    'customer' => [
                        'id' => $customerId,
                        'email' => 'test@example.com',
                        'firstName' => 'Noe',
                        'lastName' => 'Hill',
                        'salutationId' => $salutationId,
                        'title' => 'Doc',
                        'customerNumber' => 'Test',
                        'guest' => true,
                        'group' => ['name' => 'testse2323'],
                        'defaultPaymentMethodId' => $paymentMethodId,
                        'salesChannelId' => TestDefaults::SALES_CHANNEL,
                        'defaultBillingAddressId' => $addressId,
                        'defaultShippingAddressId' => $addressId,
                        'addresses' => [
                            [
                                'id' => $addressId,
                                'salutationId' => $salutationId,
                                'firstName' => 'Floy',
                                'lastName' => 'Glover',
                                'zipcode' => '59438-0403',
                                'city' => 'Stellaberg',
                                'street' => 'street',
                                'countryStateId' => $countryStateId,
                                'country' => [
                                    'name' => 'kasachstan',
                                    'id' => $countryId,
                                    'states' => [
                                        [
                                            'id' => $countryStateId,
                                            'name' => 'oklahoma',
                                            'shortCode' => 'OH',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'billingAddressId' => $addressId,
                'addresses' => [
                    [
                        'salutationId' => $salutationId,
                        'firstName' => 'Floy',
                        'lastName' => 'Glover',
                        'zipcode' => '59438-0403',
                        'city' => 'Stellaberg',
                        'street' => 'street',
                        'countryId' => $countryId,
                        'id' => $addressId,
                    ],
                ],
            ],
        ];
    }
}
