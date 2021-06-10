<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Helper;

use Psr\Log\NullLogger;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\OrdersApi\Builder\OrderFromOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\PaymentsApi\Builder\OrderPaymentBuilder;
use Swag\PayPal\RestApi\V1\Resource\PaymentResource;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\SettingsService;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Mock\DummyCollection;
use Swag\PayPal\Test\Mock\EventDispatcherMock;
use Swag\PayPal\Test\Mock\LoggerMock;
use Swag\PayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;
use Swag\PayPal\Test\Mock\Repositories\CurrencyRepoMock;
use Swag\PayPal\Test\Mock\Repositories\EntityRepositoryMock;
use Swag\PayPal\Test\Mock\Repositories\LanguageRepoMock;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;
use Swag\PayPal\Test\Mock\Util\LocaleCodeProviderMock;
use Swag\PayPal\Test\Mock\Webhook\Handler\DummyWebhook;
use Swag\PayPal\Test\PaymentsApi\Builder\OrderPaymentBuilderTest;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PriceFormatter;
use Swag\PayPal\Webhook\WebhookRegistry;

trait ServicesTrait
{
    use IntegrationTestBehaviour;

    protected function createPayPalClientFactory(): PayPalClientFactoryMock
    {
        return $this->createPayPalClientFactoryWithService($this->createDefaultSystemConfig());
    }

    protected function createPayPalClientFactoryWithService(SystemConfigService $systemConfigService): PayPalClientFactoryMock
    {
        $logger = new LoggerMock();

        return new PayPalClientFactoryMock(
            $systemConfigService,
            $logger
        );
    }

    protected function createPaymentResource(?SystemConfigService $systemConfig = null): PaymentResource
    {
        $systemConfig = $systemConfig ?? $this->createSystemConfigServiceMock();

        return new PaymentResource($this->createPayPalClientFactoryWithService($systemConfig));
    }

    protected function createOrderResource(?SystemConfigService $systemConfig = null): OrderResource
    {
        $systemConfig = $systemConfig ?? $this->createSystemConfigServiceMock();

        return new OrderResource($this->createPayPalClientFactoryWithService($systemConfig));
    }

    protected function getDefaultConfigData(): array
    {
        return \array_merge(Settings::DEFAULT_VALUES, [
            Settings::CLIENT_ID => 'TestClientId',
            Settings::CLIENT_SECRET => 'TestClientSecret',
            Settings::ORDER_NUMBER_PREFIX => OrderPaymentBuilderTest::TEST_ORDER_NUMBER_PREFIX,
            Settings::BRAND_NAME => 'Test Brand',
        ]);
    }

    protected function createDefaultSystemConfig(array $settings = []): SystemConfigServiceMock
    {
        return $this->createSystemConfigServiceMock(\array_merge($this->getDefaultConfigData(), $settings));
    }

    protected function createPaymentBuilder(?SystemConfigService $systemConfig = null): OrderPaymentBuilder
    {
        $systemConfig = $systemConfig ?? $this->createDefaultSystemConfig();

        $settingsService = new SettingsService($systemConfig, new NullLogger());

        return new OrderPaymentBuilder(
            $settingsService,
            new LocaleCodeProviderMock(new EntityRepositoryMock()),
            new PriceFormatter(),
            new EventDispatcherMock(),
            new LoggerMock(),
            $systemConfig,
            new CurrencyRepoMock()
        );
    }

    protected function createOrderBuilder(?SystemConfigService $systemConfig = null): OrderFromOrderBuilder
    {
        $systemConfig = $systemConfig ?? $this->createDefaultSystemConfig();

        $settingsService = new SettingsService($systemConfig, new NullLogger());
        $priceFormatter = new PriceFormatter();
        $amountProvider = new AmountProvider($priceFormatter);

        return new OrderFromOrderBuilder(
            $settingsService,
            $priceFormatter,
            $amountProvider,
            $systemConfig,
            new PurchaseUnitProvider($amountProvider, $systemConfig),
            new ItemListProvider($priceFormatter, new EventDispatcherMock(), new LoggerMock())
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
        $systemConfigService = new SystemConfigServiceMock();
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
