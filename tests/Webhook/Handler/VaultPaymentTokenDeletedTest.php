<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook\Handler;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V1\Api\Webhook;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Swag\PayPal\Webhook\Handler\VaultPaymentTokenDeleted;
use Swag\PayPal\Webhook\WebhookEventTypes;

/**
 * @internal
 */
#[Package('checkout')]
class VaultPaymentTokenDeletedTest extends TestCase
{
    public function testGetEventType(): void
    {
        $handler = new VaultPaymentTokenDeleted(
            $this->createMock(EntityRepository::class),
            $this->createMock(OrderTransactionStateHandler::class),
            $this->createMock(EntityRepository::class),
        );

        static::assertSame(WebhookEventTypes::VAULT_PAYMENT_TOKEN_DELETED, $handler->getEventType());
    }

    public function testInvoke(): void
    {
        $context = Context::createDefaultContext();

        $vaultRepo = $this->createMock(EntityRepository::class);
        $vaultRepo
            ->expects(static::once())
            ->method('searchIds')
            ->with(static::equalTo((new Criteria())->addFilter(new EqualsFilter('token', 'hatoken'))))
            ->willReturn(new IdSearchResult(1, [['primaryKey' => 'token-id', 'data' => []]], new Criteria(), $context));

        $vaultRepo
            ->expects(static::once())
            ->method('delete')
            ->with([['id' => 'token-id']], $context);

        $webhook = new Webhook();
        $webhook->assign(['resource_type' => 'payment_token', 'resource_version' => '3.0', 'resource' => ['id' => 'hatoken']]);

        $handler = new VaultPaymentTokenDeleted(
            $this->createMock(EntityRepository::class),
            $this->createMock(OrderTransactionStateHandler::class),
            $vaultRepo,
        );

        $handler->invoke($webhook, $context);
    }

    public function testInvokeWithoutExistingToken(): void
    {
        $context = Context::createDefaultContext();

        $vaultRepo = $this->createMock(EntityRepository::class);
        $vaultRepo
            ->expects(static::once())
            ->method('searchIds')
            ->with(static::equalTo((new Criteria())->addFilter(new EqualsFilter('token', 'hatoken'))))
            ->willReturn(new IdSearchResult(0, [], new Criteria(), $context));

        $vaultRepo
            ->expects(static::never())
            ->method('delete');

        $webhook = new Webhook();
        $webhook->assign(['resource_type' => 'payment_token', 'resource_version' => '3.0', 'resource' => ['id' => 'hatoken']]);

        $handler = new VaultPaymentTokenDeleted(
            $this->createMock(EntityRepository::class),
            $this->createMock(OrderTransactionStateHandler::class),
            $vaultRepo,
        );

        $handler->invoke($webhook, $context);
    }

    public function testInvokeWithoutValidWebhook(): void
    {
        $context = Context::createDefaultContext();

        $vaultRepo = $this->createMock(EntityRepository::class);
        $vaultRepo->expects(static::never())->method('searchIds');
        $vaultRepo->expects(static::never())->method('delete');

        $webhook = new Webhook();
        $handler = new VaultPaymentTokenDeleted(
            $this->createMock(EntityRepository::class),
            $this->createMock(OrderTransactionStateHandler::class),
            $vaultRepo,
        );

        $this->expectException(WebhookException::class);
        $handler->invoke($webhook, $context);
    }
}
