<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Helper;

use Psr\Log\NullLogger;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\OrdersApi\Builder\PayPalOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\PaymentsApi\Builder\OrderPaymentBuilder;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Mock\CustomIdProviderMock;
use Swag\PayPal\Test\Mock\Repositories\CurrencyRepoMock;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;
use Swag\PayPal\Test\PaymentsApi\Builder\OrderPaymentBuilderTest;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('checkout')]
trait ServicesTrait
{
    /**
     * @return array<string, mixed>
     */
    protected function getDefaultConfigData(): array
    {
        return \array_merge(Settings::DEFAULT_VALUES, [
            Settings::CLIENT_ID => 'TestClientId',
            Settings::CLIENT_SECRET => 'TestClientSecret',
            Settings::MERCHANT_PAYER_ID => 'TestMerchantPayerId',
            Settings::ORDER_NUMBER_PREFIX => OrderPaymentBuilderTest::TEST_ORDER_NUMBER_PREFIX,
            Settings::ORDER_NUMBER_SUFFIX => OrderPaymentBuilderTest::TEST_ORDER_NUMBER_SUFFIX,
            Settings::BRAND_NAME => 'Test Brand',
        ]);
    }

    /**
     * @param array<string, mixed> $settings
     */
    protected function createDefaultSystemConfig(array $settings = []): SystemConfigServiceMock
    {
        return $this->createSystemConfigServiceMock(\array_merge($this->getDefaultConfigData(), $settings));
    }

    protected function createPaymentBuilder(?SystemConfigService $systemConfig = null): OrderPaymentBuilder
    {
        $systemConfig = $systemConfig ?? $this->createDefaultSystemConfig();

        return new OrderPaymentBuilder(
            $this->createMock(LocaleCodeProvider::class),
            new PriceFormatter(),
            $this->createMock(EventDispatcherInterface::class),
            new NullLogger(),
            $systemConfig,
            new CurrencyRepoMock()
        );
    }

    protected function createOrderBuilder(?SystemConfigService $systemConfig = null): PayPalOrderBuilder
    {
        $systemConfig = $systemConfig ?? $this->createDefaultSystemConfig();

        $priceFormatter = new PriceFormatter();
        $amountProvider = new AmountProvider($priceFormatter);
        $addressProvider = new AddressProvider();
        $customIdProvider = new CustomIdProviderMock();

        return new PayPalOrderBuilder(
            $systemConfig,
            new PurchaseUnitProvider($amountProvider, $addressProvider, $customIdProvider, $systemConfig),
            $addressProvider,
            $this->createMock(LocaleCodeProvider::class),
            new ItemListProvider($priceFormatter, $this->createMock(EventDispatcherInterface::class), new NullLogger()),
            $this->createMock(VaultTokenService::class),
        );
    }

    /**
     * @param array<string, mixed> $settings
     */
    protected function createSystemConfigServiceMock(array $settings = []): SystemConfigServiceMock
    {
        $systemConfigService = new SystemConfigServiceMock();
        foreach ($settings as $key => $value) {
            $systemConfigService->set($key, $value);
        }

        return $systemConfigService;
    }
}
