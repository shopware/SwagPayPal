<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Webhook\Handler;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Struct\Webhook;
use SwagPayPal\Test\Mock\Repositories\OrderTransactionRepositoryMock;
use SwagPayPal\Webhook\Exception\WebhookOrderTransactionNotFoundException;
use SwagPayPal\Webhook\Handler\AuthorizationVoided;
use SwagPayPal\Webhook\WebhookEventTypes;

class AuthorizationVoidedTest extends TestCase
{
    public const WEBHOOK_PAYMENT_ID = 'Test123idWithTransaction';

    public const WEBHOOK_PAYMENT_ID_WITHOUT_TRANSACTION = 'Test123idWithoutTransaction';

    /**
     * @var AuthorizationVoided
     */
    private $webhookHandler;

    /**
     * @var OrderTransactionRepositoryMock
     */
    private $orderTransactionRepositoryMock;

    protected function setUp()
    {
        $this->orderTransactionRepositoryMock = new OrderTransactionRepositoryMock();
        $this->webhookHandler = $this->createWebhookHandler();
    }

    public function testGetEventType(): void
    {
        self::assertSame(WebhookEventTypes::PAYMENT_AUTHORIZATION_VOIDED, $this->webhookHandler->getEventType());
    }

    public function testInvoke(): void
    {
        $webhook = new Webhook();
        $webhook->setResource(['parent_payment' => self::WEBHOOK_PAYMENT_ID]);
        $context = Context::createDefaultContext();
        $this->webhookHandler->invoke($webhook, $context);

        $result = $this->orderTransactionRepositoryMock->getData();

        self::assertSame(OrderTransactionRepositoryMock::ORDER_TRANSACTION_ID, $result['id']);
        self::assertSame(Defaults::ORDER_TRANSACTION_FAILED, $result['orderTransactionStateId']);
    }

    public function testInvokeWithoutTransaction(): void
    {
        $webhook = new Webhook();
        $webhook->setResource(['parent_payment' => self::WEBHOOK_PAYMENT_ID_WITHOUT_TRANSACTION]);
        $context = Context::createDefaultContext();

        $this->expectException(WebhookOrderTransactionNotFoundException::class);
        $this->expectExceptionMessage(
            sprintf(
                '[PayPal PAYMENT.AUTHORIZATION.VOIDED Webhook] Could not find associated order with the PayPal ID "%s"',
                self::WEBHOOK_PAYMENT_ID_WITHOUT_TRANSACTION
            )
        );
        $this->webhookHandler->invoke($webhook, $context);
    }

    private function createWebhookHandler(): AuthorizationVoided
    {
        return new AuthorizationVoided($this->orderTransactionRepositoryMock);
    }
}
