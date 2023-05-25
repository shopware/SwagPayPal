<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\OrdersApi\Builder\Util;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Plugin\PluginCollection;
use Shopware\Core\Framework\Plugin\PluginEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\OrdersApi\Builder\Util\CustomIdProvider;

/**
 * @internal
 */
class CustomIdProviderTest extends TestCase
{
    public function testCustomId(): void
    {
        $pluginRepository = $this->createMock(EntityRepository::class);
        $plugin = new PluginEntity();
        $plugin->setId(Uuid::randomHex());
        $plugin->setVersion('7.1.0');
        $pluginRepository->expects(static::once())
            ->method('search')
            ->willReturn(new EntitySearchResult(
                PaymentMethodDefinition::ENTITY_NAME,
                1,
                new PluginCollection([$plugin]),
                null,
                new Criteria(),
                Context::createDefaultContext(),
            ));
        $provider = new CustomIdProvider($pluginRepository, '6.5.0.0');
        $transaction = new OrderTransactionEntity();
        $transaction->setId(Uuid::randomHex());

        $result = $provider->createCustomId($transaction, Context::createDefaultContext());
        $expected = \json_encode([
            'orderTransactionId' => $transaction->getId(),
            'pluginVersion' => '7.1.0',
            'shopwareVersion' => '6.5.0.0',
        ]) ?: '[]';

        static::assertJsonStringEqualsJsonString($expected, $result);
    }

    public function testCustomIdWithoutPlugin(): void
    {
        $pluginRepository = $this->createMock(EntityRepository::class);
        $pluginRepository->expects(static::once())
            ->method('search')
            ->willReturn(new EntitySearchResult(
                PaymentMethodDefinition::ENTITY_NAME,
                0,
                new PluginCollection(),
                null,
                new Criteria(),
                Context::createDefaultContext(),
            ));

        $provider = new CustomIdProvider($pluginRepository, '6.5.0.0');
        $transaction = new OrderTransactionEntity();
        $transaction->setId(Uuid::randomHex());

        $result = $provider->createCustomId($transaction, Context::createDefaultContext());
        $expected = \json_encode([
            'orderTransactionId' => $transaction->getId(),
            'pluginVersion' => '0.0.0',
            'shopwareVersion' => '6.5.0.0',
        ]) ?: '[]';

        static::assertJsonStringEqualsJsonString($expected, $result);
    }
}
