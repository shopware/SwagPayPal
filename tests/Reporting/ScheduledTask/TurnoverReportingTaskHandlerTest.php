<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Reporting\ScheduledTask;

use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\AndFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\BasicTestDataBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\StateMachine\Loader\InitialStateIdLoader;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Reporting\ScheduledTask\TurnoverReportingTaskHandler;

/**
 * @internal
 */
#[Package('checkout')]
class TurnoverReportingTaskHandlerTest extends TestCase
{
    use BasicTestDataBehaviour;
    use KernelTestBehaviour;

    private EntityRepository $transactionReportRepository;

    private TurnoverReportingTaskHandler $handler;

    protected function setUp(): void
    {
        $this->transactionReportRepository = $this->getContainer()->get('swag_paypal_transaction_report.repository');

        $this->handler = $this->getContainer()->get(TurnoverReportingTaskHandler::class);

        $this->getContainer()->get(Connection::class)->executeStatement('TRUNCATE TABLE `swag_paypal_transaction_report`');
    }

    public function testRun(): void
    {
        $this->transactionReportRepository->upsert([
            $this->createTransactionReport($this->createTransaction($this->createOrder())),
            $this->createTransactionReport($this->createTransaction($this->createOrder(), 42.59), 'GBP'),
            $this->createTransactionReport($this->createTransaction($this->createOrder(), 57.41), 'GBP'),
        ], Context::createDefaultContext());

        $clientHistory = [];
        $clientHandler = new MockHandler([new Response(), new Response()]);
        $clientHandlerStack = HandlerStack::create($clientHandler);
        $clientHandlerStack->push(Middleware::history($clientHistory));
        $client = new Client(['handler' => $clientHandlerStack]);

        $this->replaceGuzzleClient($client);

        $this->handler->run();

        static::assertEquals([], $this->transactionReportRepository->search(new Criteria(), Context::createDefaultContext())->getElements());

        static::assertEquals([[
            'reportDataKeys' => ['turnover' => 20],
            'currency' => 'EUR',
        ], [
            'reportDataKeys' => ['turnover' => 100],
            'currency' => 'GBP',
        ]], $this->extractTurnoverReports($clientHistory));
    }

    public function testRunWithoutAnyTransactionsDoesNothing(): void
    {
        $clientHistory = [];
        $clientHandler = new MockHandler([new Response()]);
        $clientHandlerStack = HandlerStack::create($clientHandler);
        $clientHandlerStack->push(Middleware::history($clientHistory));
        $client = new Client(['handler' => $clientHandlerStack]);

        $this->replaceGuzzleClient($client);

        $this->handler->run();

        static::assertEquals([], $clientHistory);

        static::assertEquals([], $this->transactionReportRepository->search(new Criteria(), Context::createDefaultContext())->getElements());
    }

    public function testRunWithFailedGuzzleRequestWillNotDeleteFailedReports(): void
    {
        $rejectedTransactionReport = $this->createTransactionReport($this->createTransaction($this->createOrder()));
        $this->transactionReportRepository->upsert([
            $rejectedTransactionReport,
            $this->createTransactionReport($this->createTransaction($this->createOrder(), 42.59), 'GBP'),
            $this->createTransactionReport($this->createTransaction($this->createOrder(), 57.41), 'GBP'),
        ], Context::createDefaultContext());

        $clientHistory = [];
        $clientHandler = new MockHandler([new Response(400), new Response()]);
        $clientHandlerStack = HandlerStack::create($clientHandler);
        $clientHandlerStack->push(Middleware::history($clientHistory));
        $client = new Client(['handler' => $clientHandlerStack]);

        $this->replaceGuzzleClient($client);

        $logHandler = new TestHandler();
        $this->getContainer()->get('monolog.logger.paypal')->setHandlers([$logHandler]);

        $this->handler->run();

        static::assertEquals([[
            'reportDataKeys' => ['turnover' => 20],
            'currency' => 'EUR',
        ], [
            'reportDataKeys' => ['turnover' => 100],
            'currency' => 'GBP',
        ]], $this->extractTurnoverReports($clientHistory));

        $leftoverReportIds = $this->transactionReportRepository->searchIds(new Criteria(), Context::createDefaultContext())->getIds();
        static::assertEquals([$rejectedTransactionReport['orderTransactionId']], $leftoverReportIds);

        static::assertCount(1, $logHandler->getRecords(), 'Missing log entry for failed turnover report "EUR"');
        static::assertStringContainsString(
            'Failed to report turnover for "EUR": Client error: `POST /shopwarepartners/reports/technology` resulted in a `400 Bad Request` response',
            $logHandler->getRecords()[0]->message
        );
    }

    public function testRunWithTransactionsNotInPaidStateWillBeIgnoredButDeleted(): void
    {
        $firstTransactionReport = $this->createTransactionReport($this->createTransaction($this->createOrder(), 42.59, OrderTransactionStates::STATE_UNCONFIRMED), 'GBP');
        $secondTransactionReport = $this->createTransactionReport($this->createTransaction($this->createOrder(), 57.41, OrderTransactionStates::STATE_REFUNDED), 'GBP');

        $this->transactionReportRepository->upsert([
            $this->createTransactionReport($this->createTransaction($this->createOrder())),
            $firstTransactionReport,
            $secondTransactionReport,
        ], Context::createDefaultContext());

        $clientHistory = [];
        $clientHandler = new MockHandler([new Response()]);
        $clientHandlerStack = HandlerStack::create($clientHandler);
        $clientHandlerStack->push(Middleware::history($clientHistory));
        $client = new Client(['handler' => $clientHandlerStack]);

        $this->replaceGuzzleClient($client);

        $this->handler->run();

        static::assertEquals([[
            'reportDataKeys' => ['turnover' => 20],
            'currency' => 'EUR',
        ]], $this->extractTurnoverReports($clientHistory));

        static::assertEquals([], $this->transactionReportRepository->search(new Criteria(), Context::createDefaultContext())->getElements());
    }

    /**
     * @param array<string, mixed> $transaction
     *
     * @return array<string, mixed>
     */
    private function createTransactionReport(array $transaction, string $iso = 'EUR'): array
    {
        return [
            'orderTransactionId' => $transaction['id'],
            'orderTransactionVersionId' => Defaults::LIVE_VERSION,
            'totalPrice' => $transaction['amount']['totalPrice'],
            'currencyIso' => $iso,
            'orderTransaction' => $transaction,
        ];
    }

    /**
     * @param array<string, mixed> $order
     *
     * @return array<string, mixed>
     */
    private function createTransaction(array $order, float $totalPrice = 20, string $transactionState = OrderTransactionStates::STATE_PAID): array
    {
        $stateMachineStateRepository = $this->getContainer()->get('state_machine_state.repository');
        $stateCriteria = (new Criteria())
            ->addAssociation('stateMachine')
            ->addFilter(new AndFilter([
                new EqualsFilter('stateMachine.technicalName', OrderTransactionStates::STATE_MACHINE),
                new EqualsFilter('technicalName', $transactionState),
            ]));

        return [
            'id' => Uuid::randomHex(),
            'versionId' => Defaults::LIVE_VERSION,
            'orderId' => $order['id'],
            'order' => $order,
            'paymentMethodId' => $this->getValidPaymentMethodId(TestDefaults::SALES_CHANNEL),
            'amount' => [
                'unitPrice' => $totalPrice,
                'quantity' => 1,
                'totalPrice' => $totalPrice,
                'calculatedTaxes' => [],
                'taxRules' => [],
            ],
            'stateId' => $stateMachineStateRepository->searchIds($stateCriteria, Context::createDefaultContext())->firstId(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createOrder(): array
    {
        $addressId = Uuid::randomHex();

        $customer = [
            'id' => Uuid::randomHex(),
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'defaultShippingAddress' => [
                'id' => $addressId,
                'firstName' => 'Max',
                'lastName' => 'Mustermann',
                'street' => 'Musterstraße 1',
                'city' => 'Schöppingen',
                'zipcode' => '12345',
                'salutationId' => $this->getValidSalutationId(),
                'countryId' => $this->getValidCountryId(),
            ],
            'defaultBillingAddressId' => $addressId,
            'defaultPaymentMethodId' => $this->getValidPaymentMethodId(),
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'email' => Uuid::randomHex() . 'foo@bar.de',
            'password' => 'password',
            'firstName' => 'Max',
            'lastName' => 'Mustermann',
            'salutationId' => $this->getValidSalutationId(),
            'customerNumber' => '12345',
        ];

        $lineItem = [
            'id' => Uuid::randomHex(),
            'versionId' => Defaults::LIVE_VERSION,
            'identifier' => 'test',
            'quantity' => 1,
            'type' => LineItem::CUSTOM_LINE_ITEM_TYPE,
            'label' => 'test',
            'price' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
            'priceDefinition' => new QuantityPriceDefinition(10, new TaxRuleCollection()),
            'priority' => 100,
            'good' => true,
        ];

        return [
            'id' => Uuid::randomHex(),
            'versionId' => Defaults::LIVE_VERSION,
            'price' => [
                'rawTotal' => 10,
                'positionPrice' => 10,
                'netPrice' => 10,
                'totalPrice' => 10,
                'calculatedTaxes' => [],
                'taxRules' => [],
                'taxStatus' => CartPrice::TAX_STATE_FREE,
            ],
            'shippingCosts' => [
                'unitPrice' => 10,
                'quantity' => 1,
                'totalPrice' => 10,
                'calculatedTaxes' => [],
                'taxRules' => [],
            ],
            'stateId' => $this->getContainer()->get(InitialStateIdLoader::class)->get(OrderStates::STATE_MACHINE),
            'paymentMethodId' => $this->getValidPaymentMethodId(),
            'billingAddressId' => $addressId,
            'shippingAddressId' => $addressId,
            'customer' => $customer,
            'customerId' => $customer['id'],
            'currencyId' => Defaults::CURRENCY,
            'currencyFactor' => 1,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'orderDateTime' => '2019-04-01 08:36:43.267',
            'lineItems' => [$lineItem],
            'itemRounding' => ['decimals' => 2, 'interval' => 0.01, 'roundForNet' => true],
            'totalRounding' => ['decimals' => 2, 'interval' => 0.01, 'roundForNet' => true],
        ];
    }

    private function replaceGuzzleClient(Client $client): void
    {
        $property = new \ReflectionProperty(TurnoverReportingTaskHandler::class, 'client');

        $property->setValue($this->handler, $client);
    }

    /**
     * Extracts all turnover reports, successful and failed ones
     *
     * @param array<int, array{request: Request, response: Response}> $history
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractTurnoverReports(array $history): array
    {
        return \array_map(
            function (array $entry) {
                $body = \json_decode($entry['request']->getBody()->getContents(), true);
                static::assertIsArray($body);

                unset($body['identifier']);
                unset($body['instanceId']);
                unset($body['shopwareVersion']);
                unset($body['reportDate']);

                return $body;
            },
            $history,
        );
    }
}
