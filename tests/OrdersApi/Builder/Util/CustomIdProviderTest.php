<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\OrdersApi\Builder\Util;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Plugin\PluginCollection;
use Shopware\Core\Framework\Plugin\PluginEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\CurrencyEntity;
use Swag\PayPal\OrdersApi\Builder\Util\CustomIdProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item;
use Swag\PayPal\Test\Mock\LoggerMock;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\EventDispatcher\EventDispatcher;

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
        ]);

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
        ]);

        static::assertJsonStringEqualsJsonString($expected, $result);
    }
}
