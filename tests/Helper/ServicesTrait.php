<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Helper;

use Swag\PayPal\Payment\PaymentBuilderService;
use Swag\PayPal\PayPal\Resource\PaymentResource;
use Swag\PayPal\PayPal\Resource\TokenResource;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Test\Mock\CacheMock;
use Swag\PayPal\Test\Mock\DIContainerMock;
use Swag\PayPal\Test\Mock\DummyCollection;
use Swag\PayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;
use Swag\PayPal\Test\Mock\PayPal\Client\TokenClientFactoryMock;
use Swag\PayPal\Test\Mock\Repositories\DefinitionRegistryMock;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Swag\PayPal\Test\Mock\Webhook\Handler\DummyWebhook;
use Swag\PayPal\Webhook\WebhookRegistry;

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
