<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Helper;

use SwagPayPal\Payment\PaymentBuilderService;
use SwagPayPal\PayPal\Resource\PaymentResource;
use SwagPayPal\PayPal\Resource\TokenResource;
use SwagPayPal\Setting\Service\SettingsServiceInterface;
use SwagPayPal\Test\Mock\CacheMock;
use SwagPayPal\Test\Mock\DIContainerMock;
use SwagPayPal\Test\Mock\DummyCollection;
use SwagPayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;
use SwagPayPal\Test\Mock\PayPal\Client\TokenClientFactoryMock;
use SwagPayPal\Test\Mock\Repositories\DefinitionRegistryMock;
use SwagPayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use SwagPayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use SwagPayPal\Test\Mock\Webhook\Handler\DummyWebhook;
use SwagPayPal\Webhook\WebhookRegistry;

trait ServicesTrait
{
    protected function createPayPalClientFactory(
        ?SettingsServiceInterface $settingsProvider = null
    ): PayPalClientFactoryMock {
        if ($settingsProvider === null) {
            $settingsProvider = new SettingsServiceMock(new DefinitionRegistryMock([], new DIContainerMock()));
        }

        return new PayPalClientFactoryMock(
            new TokenResource(
                new CacheMock(),
                new TokenClientFactoryMock()
            ),
            $settingsProvider
        );
    }

    protected function createPaymentResource(?SettingsServiceInterface $settingsProvider = null): PaymentResource
    {
        return new PaymentResource(
            $this->createPayPalClientFactory($settingsProvider)
        );
    }

    protected function createPaymentBuilder(?SettingsServiceInterface $settingsProvider = null): PaymentBuilderService
    {
        if ($settingsProvider === null) {
            $settingsProvider = new SettingsServiceMock(new DefinitionRegistryMock([], new DIContainerMock()));
        }

        return new PaymentBuilderService(
            new DefinitionRegistryMock([], new DIContainerMock()),
            $settingsProvider
        );
    }

    protected function createWebhookRegistry(?OrderTransactionRepoMock $orderTransactionRepo = null): WebhookRegistry
    {
        return new WebhookRegistry(new DummyCollection([$this->createDummyWebhook($orderTransactionRepo)]));
    }

    private function createDummyWebhook(?OrderTransactionRepoMock $orderTransactionRepo = null): DummyWebhook
    {
        if ($orderTransactionRepo === null) {
            $orderTransactionRepo = new OrderTransactionRepoMock();
        }

        return new DummyWebhook($orderTransactionRepo);
    }
}
