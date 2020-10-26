<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Helper;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SystemConfig\SystemConfigDefinition;
use Shopware\Core\System\SystemConfig\Util\ConfigReader;
use Swag\PayPal\Payment\Builder\OrderPaymentBuilder;
use Swag\PayPal\PayPal\PaymentIntent;
use Swag\PayPal\PayPal\Resource\PaymentResource;
use Swag\PayPal\PayPal\Resource\TokenResource;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Mock\CacheMock;
use Swag\PayPal\Test\Mock\DIContainerMock;
use Swag\PayPal\Test\Mock\DummyCollection;
use Swag\PayPal\Test\Mock\LoggerMock;
use Swag\PayPal\Test\Mock\PayPal\Client\CredentialsClientFactoryMock;
use Swag\PayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;
use Swag\PayPal\Test\Mock\PayPal\Client\TokenClientFactoryMock;
use Swag\PayPal\Test\Mock\Repositories\DefinitionInstanceRegistryMock;
use Swag\PayPal\Test\Mock\Repositories\EntityRepositoryMock;
use Swag\PayPal\Test\Mock\Repositories\LanguageRepoMock;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;
use Swag\PayPal\Test\Mock\Util\LocaleCodeProviderMock;
use Swag\PayPal\Test\Mock\Webhook\Handler\DummyWebhook;
use Swag\PayPal\Test\Payment\Builder\OrderPaymentBuilderTest;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Webhook\WebhookRegistry;

trait ServicesTrait
{
    use KernelTestBehaviour;

    protected function createPayPalClientFactory(
        ?SwagPayPalSettingStruct $settings = null
    ): PayPalClientFactoryMock {
        $settings = $settings ?? $this->createDefaultSettingStruct();
        $settingsService = new SettingsServiceMock($settings);

        return $this->createPayPalClientFactoryWithService($settingsService);
    }

    protected function createPayPalClientFactoryWithService(SettingsServiceInterface $settingsService): PayPalClientFactoryMock
    {
        $logger = new LoggerMock();

        return new PayPalClientFactoryMock(
            new TokenResource(
                new CacheMock(),
                new TokenClientFactoryMock($logger),
                new CredentialsClientFactoryMock($logger)
            ),
            $settingsService,
            $logger
        );
    }

    protected function createPaymentResource(?SwagPayPalSettingStruct $settings = null): PaymentResource
    {
        return new PaymentResource($this->createPayPalClientFactory($settings));
    }

    protected function createDefaultSettingStruct(): SwagPayPalSettingStruct
    {
        $settingsStruct = new SwagPayPalSettingStruct();

        $settingsStruct->setClientId('TestClientId');
        $settingsStruct->setClientSecret('TestClientSecret');
        $settingsStruct->setIntent(PaymentIntent::SALE);
        $settingsStruct->setSubmitCart(false);
        $settingsStruct->setSendOrderNumber(true);
        $settingsStruct->setOrderNumberPrefix(OrderPaymentBuilderTest::TEST_ORDER_NUMBER_PREFIX);
        $settingsStruct->setBrandName('Test Brand');
        $settingsStruct->setLandingPage('Login');

        return $settingsStruct;
    }

    protected function createPaymentBuilder(?SwagPayPalSettingStruct $settings = null): OrderPaymentBuilder
    {
        $settings = $settings ?? $this->createDefaultSettingStruct();

        $settingsService = new SettingsServiceMock($settings);

        return new OrderPaymentBuilder(
            $settingsService,
            new LocaleCodeProviderMock(new EntityRepositoryMock()),
            new EntityRepositoryMock()
        );
    }

    protected function createWebhookRegistry(?OrderTransactionRepoMock $orderTransactionRepo = null): WebhookRegistry
    {
        return new WebhookRegistry(new DummyCollection([$this->createDummyWebhook($orderTransactionRepo)]));
    }

    protected function createLocaleCodeProvider(): LocaleCodeProvider
    {
        return new LocaleCodeProvider(new LanguageRepoMock());
    }

    protected function createSystemConfigServiceMock(array $settings = []): SystemConfigServiceMock
    {
        $definitionRegistry = new DefinitionInstanceRegistryMock([], new DIContainerMock());
        $systemConfigRepo = $definitionRegistry->getRepository(
            (new SystemConfigDefinition())->getEntityName()
        );

        /** @var Connection $connection */
        $connection = $this->getContainer()->get(Connection::class);
        $systemConfigService = new SystemConfigServiceMock($connection, $systemConfigRepo, new ConfigReader());
        foreach ($settings as $key => $value) {
            $systemConfigService->set($key, $value);
        }

        return $systemConfigService;
    }

    private function createDummyWebhook(?OrderTransactionRepoMock $orderTransactionRepo = null): DummyWebhook
    {
        if ($orderTransactionRepo === null) {
            $orderTransactionRepo = new OrderTransactionRepoMock();
        }

        return new DummyWebhook($orderTransactionRepo);
    }
}
