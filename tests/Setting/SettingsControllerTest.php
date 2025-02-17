<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Setting;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SystemConfig\Validation\SystemConfigValidator;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V1\Resource\CredentialsResource;
use Swag\PayPal\RestApi\V1\Resource\MerchantIntegrationsResource;
use Swag\PayPal\RestApi\V1\Service\CredentialProvider;
use Swag\PayPal\RestApi\V1\Service\TokenValidator;
use Swag\PayPal\Setting\Service\ApiCredentialService;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Service\MerchantIntegrationsService;
use Swag\PayPal\Setting\Service\SettingsSaver;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Setting\SettingsController;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\CredentialsClientFactoryMock;
use Swag\PayPal\Test\Mock\PayPal\Client\GuzzleClientMock;
use Swag\PayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;
use Swag\PayPal\Test\Mock\PayPal\Client\TokenClientFactoryMock;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Webhook\Registration\WebhookSystemConfigHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
class SettingsControllerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use ServicesTrait;

    public function testTestApiWithValidData(): void
    {
        $controller = $this->createApiValidationController();

        $request = new RequestDataBag(
            [
                'clientId' => ConstantsForTesting::VALID_CLIENT_ID,
                'clientSecret' => ConstantsForTesting::VALID_CLIENT_SECRET,
                'sandboxActive' => true,
            ]
        );

        $content = $controller->testApiCredentials($request)->getContent();
        static::assertNotFalse($content);

        $result = \json_decode($content, true);
        static::assertSame(['valid' => true, 'errors' => []], $result);
    }

    public function testTestApiWithInvalidData(): void
    {
        $controller = $this->createApiValidationController();

        $request = new RequestDataBag(
            [
                'clientId' => ConstantsForTesting::INVALID_CLIENT_ID,
                'clientSecret' => ConstantsForTesting::INVALID_CLIENT_SECRET,
                'sandboxActive' => false,
            ]
        );

        $content = $controller->testApiCredentials($request)->getContent();
        static::assertNotFalse($content);

        $result = \json_decode($content, true);
        static::assertSame([
            'valid' => false,
            'errors' => [
                [
                    'status' => '400',
                    'code' => 'SWAG_PAYPAL__API_EXCEPTION',
                    'title' => 'Bad Request',
                    'detail' => 'The error "TEST" occurred with the following message: generalClientExceptionMessage',
                    'meta' => [
                        'parameters' => [
                            'name' => 'TEST',
                            'message' => 'generalClientExceptionMessage',
                            'issue' => null,
                        ],
                    ],
                ],
            ],
        ], $result);
    }

    private function createApiValidationController(): SettingsController
    {
        $logger = new NullLogger();
        $systemConfigService = $this->createDefaultSystemConfig();
        $apiCredentialsService = new ApiCredentialService(
            new CredentialsResource(
                new CredentialsClientFactoryMock($logger),
            ),
            new TokenClientFactoryMock($logger),
            new TokenValidator(),
            new CredentialProvider(
                new SettingsValidationService($systemConfigService, $logger),
                $systemConfigService,
                new CredentialsUtil($systemConfigService)
            ),
            $logger,
        );

        return new SettingsController(
            $apiCredentialsService,
            new MerchantIntegrationsService(
                new MerchantIntegrationsResource(new PayPalClientFactoryMock($logger)),
                new CredentialsUtil($systemConfigService),
                $this->getContainer()->get(PaymentMethodDataRegistry::class),
                new PayPalClientFactoryMock($logger)
            ),
            $this->getContainer()->get(SystemConfigValidator::class),
            new SettingsSaver(
                $systemConfigService,
                $apiCredentialsService,
                $this->createMock(WebhookSystemConfigHelper::class),
            )
        );
    }
}
