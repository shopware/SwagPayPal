<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Reporting\Subscriber;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PrePayment;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\IdsCollection;
use Shopware\Core\Framework\Test\TestCaseBase\EventDispatcherBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;
use Swag\PayPal\Test\Helper\OrderTransactionTrait;
use Swag\PayPal\Util\PaymentMethodUtil;

/**
 * @internal
 */
#[Package('checkout')]
class OrderTransactionSubscriberIntegrationTest extends TestCase
{
    use EventDispatcherBehaviour;
    use IntegrationTestBehaviour;
    use OrderTransactionTrait;

    private const PAID_EVENT = 'state_enter.order_transaction.state.paid';

    protected function setUp(): void
    {
        // Reports left over by other tests would otherwise show up in the assertions, this is rolled back afterwards
        $this->getContainer()->get(Connection::class)->executeStatement('DELETE FROM `swag_paypal_transaction_report`');
    }

    public function testRestrictedIntegrationReportsPaidPayPalTransactionAndRunsPaidFlows(): void
    {
        $context = Context::createDefaultContext();
        $paypalTransactionId = $this->getTransactionId($context, $this->getContainer());

        $paidEvents = $this->listenToPaidEvents();

        $this->transitionToPaid($paypalTransactionId);

        static::assertSame([$paypalTransactionId], $this->getReportedTransactionIds());
        static::assertSame(1, $paidEvents->count);
    }

    public function testPaidPrepaymentAfterFailedPayPalTransactionIsNotReported(): void
    {
        $context = Context::createDefaultContext();
        $container = $this->getContainer();

        $failedStateId = $this->getOrderTransactionStateIdByTechnicalName(OrderTransactionStates::STATE_FAILED, $container, $context);
        $openStateId = $this->getOrderTransactionStateIdByTechnicalName(OrderTransactionStates::STATE_OPEN, $container, $context);
        $paypalPaymentMethodId = $container->get(PaymentMethodUtil::class)->getPayPalPaymentMethodId($context);
        static::assertNotNull($failedStateId);
        static::assertNotNull($openStateId);
        static::assertNotNull($paypalPaymentMethodId);

        $orderData = $this->getOrderData(new IdsCollection());
        $this->getValidTransactionId($orderData, $paypalPaymentMethodId, $failedStateId, $container, $context, true);

        $prepaymentTransactionId = Uuid::randomHex();
        /** @var EntityRepository $orderTransactionRepository */
        $orderTransactionRepository = $container->get(OrderTransactionDefinition::ENTITY_NAME . '.repository');
        $orderTransactionRepository->create([[
            'id' => $prepaymentTransactionId,
            'orderId' => $orderData[0]['id'],
            'paymentMethodId' => $this->getPrepaymentMethodId($context),
            'amount' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
            'stateId' => $openStateId,
        ]], $context);

        $paidEvents = $this->listenToPaidEvents();

        $this->transitionToPaid($prepaymentTransactionId);

        static::assertSame([], $this->getReportedTransactionIds());
        static::assertSame(1, $paidEvents->count);
    }

    private function transitionToPaid(string $transactionId): void
    {
        // An integration without any privileges, e.g. none on swag_paypal_transaction_report
        $context = new Context(new AdminApiSource(null));

        $this->getContainer()->get(StateMachineRegistry::class)->transition(
            new Transition(OrderTransactionDefinition::ENTITY_NAME, $transactionId, 'paid', 'stateId'),
            $context,
        );
    }

    private function listenToPaidEvents(): \stdClass
    {
        $paidEvents = new \stdClass();
        $paidEvents->count = 0;

        $this->addEventListener(
            $this->getContainer()->get('event_dispatcher'),
            self::PAID_EVENT,
            static function () use ($paidEvents): void {
                ++$paidEvents->count;
            },
        );

        return $paidEvents;
    }

    /**
     * @return list<string>
     */
    private function getReportedTransactionIds(): array
    {
        /** @var list<string> $ids */
        $ids = $this->getContainer()->get(Connection::class)
            ->fetchFirstColumn('SELECT LOWER(HEX(`order_transaction_id`)) FROM `swag_paypal_transaction_report`');

        return $ids;
    }

    private function getPrepaymentMethodId(Context $context): string
    {
        /** @var EntityRepository $paymentMethodRepository */
        $paymentMethodRepository = $this->getContainer()->get('payment_method.repository');

        $id = $paymentMethodRepository->searchIds(
            (new Criteria())->addFilter(new EqualsFilter('handlerIdentifier', PrePayment::class)),
            $context,
        )->firstId();
        static::assertNotNull($id);

        return $id;
    }
}
