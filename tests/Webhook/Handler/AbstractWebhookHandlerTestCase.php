<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook\Handler;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Webhook as WebhookV1;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Capture;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Payment;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Refund;
use Swag\PayPal\RestApi\V2\Api\Webhook as WebhookV2;
use Swag\PayPal\Test\Helper\OrderTransactionTrait;
use Swag\PayPal\Test\Helper\StateMachineStateTrait;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Swag\PayPal\Webhook\Exception\ParentPaymentNotFoundException;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Swag\PayPal\Webhook\Exception\WebhookOrderTransactionNotFoundException;
use Swag\PayPal\Webhook\Handler\AbstractWebhookHandler;

/**
 * @internal
 */
#[Package('checkout')]
abstract class AbstractWebhookHandlerTestCase extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;
    use OrderTransactionTrait;
    use StateMachineStateTrait;

    protected EntityRepository $orderTransactionRepository;

    protected StateMachineRegistry $stateMachineRegistry;

    private AbstractWebhookHandler $webhookHandler;

    protected function setUp(): void
    {
        /** @var EntityRepository $orderTransactionRepository */
        $orderTransactionRepository = $this->getContainer()->get('order_transaction.repository');
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->stateMachineRegistry = $this->getContainer()->get(StateMachineRegistry::class);
        $this->webhookHandler = $this->createWebhookHandler();
    }

    protected function assertEventType(string $type): void
    {
        static::assertSame($type, $this->webhookHandler->getEventType());
    }

    /**
     * @param WebhookV1|WebhookV2 $webhook
     */
    protected function assertInvoke(
        string $expectedStateName,
        PayPalApiStruct $webhook,
        string $initialStateName = OrderTransactionStates::STATE_OPEN
    ): void {
        $context = Context::createDefaultContext();
        $container = $this->getContainer();
        $transactionId = $this->getTransactionId($context, $container, $initialStateName);
        if ($webhook instanceof WebhookV2) {
            $resource = $webhook->getResource();
            if ($resource instanceof Payment) {
                $resource->setCustomId(\json_encode(['orderTransactionId' => $transactionId]) ?: null);
            }
        }
        $this->webhookHandler->invoke($webhook, $context);

        $expectedStateId = $this->getOrderTransactionStateIdByTechnicalName(
            $expectedStateName,
            $container,
            $context
        );

        $transaction = $this->getTransaction($transactionId, $container, $context);
        static::assertNotNull($transaction);
        static::assertNotNull($expectedStateId);
        static::assertSame($expectedStateId, $transaction->getStateId());
    }

    protected function assertInvokeWithoutParentPayment(string $webhookName): void
    {
        $webhook = $this->createWebhookV1(null);
        $context = Context::createDefaultContext();

        $this->expectException(ParentPaymentNotFoundException::class);
        $this->expectExceptionMessage(\sprintf('[PayPal %s Webhook] Could not find parent payment ID', $webhookName));
        $this->webhookHandler->invoke($webhook, $context);
    }

    /**
     * @param WebhookV1|WebhookV2 $webhook
     */
    protected function assertInvokeWithoutTransaction(string $webhookName, PayPalApiStruct $webhook, string $reason): void
    {
        $context = Context::createDefaultContext();

        $this->expectException(WebhookOrderTransactionNotFoundException::class);
        $this->expectExceptionMessage(
            \sprintf('[PayPal %s Webhook] Could not find associated order transaction %s', $webhookName, $reason)
        );
        $this->webhookHandler->invoke($webhook, $context);
    }

    protected function assertInvokeWithoutResource(): void
    {
        $webhook = $this->createWebhookV2('no-valid-resource-type');
        $context = Context::createDefaultContext();

        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('Given webhook does not have needed resource data');
        $this->webhookHandler->invoke($webhook, $context);
    }

    protected function assertInvokeWithoutCustomId(string $resourceType): void
    {
        $webhook = $this->createWebhookV2($resourceType);
        $context = Context::createDefaultContext();

        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('Given webhook resource data does not contain needed custom ID');
        $this->webhookHandler->invoke($webhook, $context);
    }

    protected function createWebhookV1(?string $parentPayment = OrderTransactionRepoMock::WEBHOOK_PAYMENT_ID): WebhookV1
    {
        return (new WebhookV1())->assign(['resource' => ['parent_payment' => $parentPayment]]);
    }

    protected function createWebhookV2(string $resourceType, ?string $orderTransactionId = null): WebhookV2
    {
        $customId = \json_encode(['orderTransactionId' => $orderTransactionId]);

        $webhook = new WebhookV2();
        $webhook->assign(['resource_type' => $resourceType, 'resource' => ['custom_id' => $customId]]);
        $resource = $webhook->getResource();
        if ($resource instanceof Capture) {
            $resource->setFinalCapture(true);
        } elseif ($resource instanceof Refund) {
            $resource->assign([
                'seller_payable_breakdown' => [
                    'total_refunded_amount' => ['currency_code' => 'EUR', 'value' => '40.00'],
                ],
            ]);
        }

        return $webhook;
    }

    /**
     * @return AbstractWebhookHandler
     */
    abstract protected function createWebhookHandler();
}
