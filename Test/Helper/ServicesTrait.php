<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Helper;

use SwagPayPal\PayPal\Payment\PaymentBuilderService;
use SwagPayPal\PayPal\Resource\PaymentResource;
use SwagPayPal\Setting\Service\SettingsProviderInterface;
use SwagPayPal\Test\Mock\CacheMock;
use SwagPayPal\Test\Mock\DummyCollection;
use SwagPayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;
use SwagPayPal\Test\Mock\PayPal\Client\TokenClientFactoryMock;
use SwagPayPal\Test\Mock\PayPal\Resource\TokenResourceMock;
use SwagPayPal\Test\Mock\Repositories\LanguageRepoMock;
use SwagPayPal\Test\Mock\Repositories\OrderRepoMock;
use SwagPayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use SwagPayPal\Test\Mock\Repositories\SalesChannelRepoMock;
use SwagPayPal\Test\Mock\Setting\Service\SettingsProviderMock;
use SwagPayPal\Test\Mock\Webhook\Handler\DummyWebhook;
use SwagPayPal\Webhook\WebhookRegistry;

trait ServicesTrait
{
    protected function createPaymentResource(SettingsProviderInterface $settingsProvider = null): PaymentResource
    {
        if ($settingsProvider === null) {
            $settingsProvider = new SettingsProviderMock();
        }

        return new PaymentResource(
            new PayPalClientFactoryMock(
                new TokenResourceMock(
                    new CacheMock(),
                    new TokenClientFactoryMock()
                ),
                $settingsProvider
            )
        );
    }

    protected function createPaymentBuilder(SettingsProviderInterface $settingsProvider = null): PaymentBuilderService
    {
        if ($settingsProvider === null) {
            $settingsProvider = new SettingsProviderMock();
        }

        return new PaymentBuilderService(
            new LanguageRepoMock(),
            new SalesChannelRepoMock(),
            new OrderRepoMock(),
            $settingsProvider
        );
    }

    protected function createWebhookRegistry(OrderTransactionRepoMock $orderTransactionRepo = null): WebhookRegistry
    {
        return new WebhookRegistry(new DummyCollection([$this->createDummyWebhook($orderTransactionRepo)]));
    }

    private function createDummyWebhook(OrderTransactionRepoMock $orderTransactionRepo = null): DummyWebhook
    {
        if ($orderTransactionRepo === null) {
            $orderTransactionRepo = new OrderTransactionRepoMock();
        }

        return new DummyWebhook($orderTransactionRepo);
    }
}
