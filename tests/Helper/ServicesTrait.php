<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Helper;

use Shopware\Core\Framework\Language\LanguageDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Swag\PayPal\Payment\Builder\OrderPaymentBuilder;
use Swag\PayPal\PayPal\Resource\PaymentResource;
use Swag\PayPal\PayPal\Resource\TokenResource;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\SwagPayPalSettingGeneralDefinition;
use Swag\PayPal\Test\Mock\CacheMock;
use Swag\PayPal\Test\Mock\DIContainerMock;
use Swag\PayPal\Test\Mock\DummyCollection;
use Swag\PayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;
use Swag\PayPal\Test\Mock\PayPal\Client\TokenClientFactoryMock;
use Swag\PayPal\Test\Mock\Repositories\DefinitionInstanceRegistryMock;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Swag\PayPal\Test\Mock\Webhook\Handler\DummyWebhook;
use Swag\PayPal\Webhook\WebhookRegistry;

trait ServicesTrait
{
    protected function createPayPalClientFactory(
        ?SettingsServiceInterface $settingsService = null
    ): PayPalClientFactoryMock {
        if ($settingsService === null) {
            $settingsService = new SettingsServiceMock(new DefinitionInstanceRegistryMock([], new DIContainerMock()), new SwagPayPalSettingGeneralDefinition());
        }

        return new PayPalClientFactoryMock(
            new TokenResource(
                new CacheMock(),
                new TokenClientFactoryMock()
            ),
            $settingsService
        );
    }

    protected function createPaymentResource(?SettingsServiceInterface $settingsService = null): PaymentResource
    {
        return new PaymentResource(
            $this->createPayPalClientFactory($settingsService)
        );
    }

    protected function createPaymentBuilder(?SettingsServiceInterface $settingsService = null): OrderPaymentBuilder
    {
        $definitionInstanceRegistry = new DefinitionInstanceRegistryMock([], new DIContainerMock());
        if ($settingsService === null) {
            $settingsService = new SettingsServiceMock($definitionInstanceRegistry, new SwagPayPalSettingGeneralDefinition());
        }

        return new OrderPaymentBuilder(
            $settingsService,
            $definitionInstanceRegistry->getRepository((new LanguageDefinition())->getEntityName()),
            $definitionInstanceRegistry->getRepository((new SalesChannelDefinition())->getEntityName())
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
