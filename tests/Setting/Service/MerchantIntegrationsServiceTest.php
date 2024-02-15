<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Setting\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Client\PayPalClient;
use Swag\PayPal\RestApi\Client\PayPalClientFactory;
use Swag\PayPal\RestApi\V1\Resource\MerchantIntegrationsResource;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Service\MerchantIntegrationsService;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetResourceMerchantIntegrations;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;
use Swag\PayPal\Util\Lifecycle\Method\AbstractMethodData;
use Swag\PayPal\Util\Lifecycle\Method\ACDCMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\Method\PayPalMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PUIMethodData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 */
#[Package('checkout')]
class MerchantIntegrationsServiceTest extends TestCase
{
    private SystemConfigServiceMock $systemConfigService;

    private PayPalClientFactory&MockObject $clientFactory;

    public function testGetInformation(): void
    {
        $merchantIntegrationService = $this->createMerchantIntegrationService();

        $client = $this->createMock(PayPalClient::class);
        $client->expects(static::once())
            ->method('sendGetRequest')
            ->willReturn(GetResourceMerchantIntegrations::get());
        $this->clientFactory->expects(static::once())
            ->method('getPayPalClient')
            ->willReturn($client);

        $information = $merchantIntegrationService->getMerchantInformation(Context::createDefaultContext());

        $integrations = $information->getMerchantIntegrations();
        static::assertNotNull($integrations);
        static::assertSame(GetResourceMerchantIntegrations::TRACKING_ID, $integrations->getTrackingId());
        static::assertSame(GetResourceMerchantIntegrations::LEGAL_NAME, $integrations->getLegalName());

        $capabilities = $information->getCapabilities();
        static::assertSame(AbstractMethodData::CAPABILITY_INELIGIBLE, $capabilities['pui']);
        static::assertSame(AbstractMethodData::CAPABILITY_ACTIVE, $capabilities['paypal']);
        static::assertSame(AbstractMethodData::CAPABILITY_ACTIVE, $capabilities['acdc']);
    }

    public function testGetInformationWithoutCredentials(): void
    {
        $merchantIntegrationService = $this->createMerchantIntegrationService();

        $this->clientFactory
            ->method('getPayPalClient')
            ->willThrowException(new PayPalSettingsInvalidException('clientId'));

        $information = $merchantIntegrationService->getMerchantInformation(Context::createDefaultContext());

        $capabilities = $information->getCapabilities();
        static::assertSame(AbstractMethodData::CAPABILITY_INACTIVE, $capabilities['pui']);
        static::assertSame(AbstractMethodData::CAPABILITY_INACTIVE, $capabilities['paypal']);
        static::assertSame(AbstractMethodData::CAPABILITY_INACTIVE, $capabilities['acdc']);

        $integrations = $information->getMerchantIntegrations();
        static::assertNull($integrations);
    }

    public function testGetInformationWithoutMerchantId(): void
    {
        $merchantIntegrationService = $this->createMerchantIntegrationService();
        $this->systemConfigService->set(Settings::MERCHANT_PAYER_ID, null);

        $information = $merchantIntegrationService->getMerchantInformation(Context::createDefaultContext());

        $capabilities = $information->getCapabilities();
        static::assertSame(AbstractMethodData::CAPABILITY_INACTIVE, $capabilities['pui']);
        static::assertSame(AbstractMethodData::CAPABILITY_ACTIVE, $capabilities['paypal']);
        static::assertSame(AbstractMethodData::CAPABILITY_INACTIVE, $capabilities['acdc']);

        $integrations = $information->getMerchantIntegrations();
        static::assertNull($integrations);
    }

    private function createMerchantIntegrationService(): MerchantIntegrationsService
    {
        $this->systemConfigService = SystemConfigServiceMock::createWithCredentials();
        $this->clientFactory = $this->createMock(PayPalClientFactory::class);

        $container = $this->createMock(ContainerInterface::class);

        $dataRegistry = $this->createMock(PaymentMethodDataRegistry::class);
        $dataRegistry
            ->expects(static::once())
            ->method('getPaymentMethods')
            ->willReturn([
                new ACDCMethodData($container),
                new PUIMethodData($container),
                new PayPalMethodData($container),
            ]);

        $dataRegistry
            ->expects(static::exactly(3))
            ->method('getEntityIdFromData')
            ->willReturnCallback(static function (AbstractMethodData $methodData) {
                if ($methodData instanceof ACDCMethodData) {
                    return 'acdc';
                }
                if ($methodData instanceof PUIMethodData) {
                    return 'pui';
                }
                if ($methodData instanceof PayPalMethodData) {
                    return 'paypal';
                }

                throw new \RuntimeException('Invalid method data');
            });

        return new MerchantIntegrationsService(
            new MerchantIntegrationsResource($this->clientFactory),
            new CredentialsUtil($this->systemConfigService),
            $dataRegistry,
            $this->clientFactory,
        );
    }
}
