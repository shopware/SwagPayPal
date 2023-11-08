<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Setting;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V1\Resource\CredentialsResource;
use Swag\PayPal\RestApi\V1\Resource\MerchantIntegrationsResource;
use Swag\PayPal\RestApi\V1\Service\TokenValidator;
use Swag\PayPal\Setting\Service\ApiCredentialService;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Service\MerchantIntegrationsService;
use Swag\PayPal\Setting\SettingsController;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\LoggerMock;
use Swag\PayPal\Test\Mock\PayPal\Client\CredentialsClientFactoryMock;
use Swag\PayPal\Test\Mock\PayPal\Client\GuzzleClientMock;
use Swag\PayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;
use Swag\PayPal\Test\Mock\PayPal\Client\TokenClientFactoryMock;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
class SettingsControllerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use ServicesTrait;

    public function testValidateApiWithValidData(): void
    {
        $controller = $this->createApiValidationController();

        $request = new Request(
            [
                'clientId' => ConstantsForTesting::VALID_CLIENT_ID,
                'clientSecret' => ConstantsForTesting::VALID_CLIENT_SECRET,
                'sandboxActive' => true,
            ]
        );

        $content = $controller->validateApiCredentials($request)->getContent();
        static::assertNotFalse($content);

        $result = \json_decode($content, true);
        static::assertSame(['credentialsValid' => true], $result);
    }

    public function testValidateApiWithInvalidData(): void
    {
        $controller = $this->createApiValidationController();

        $request = new Request(
            [
                'clientId' => ConstantsForTesting::INVALID_CLIENT_ID,
                'clientSecret' => ConstantsForTesting::INVALID_CLIENT_SECRET,
                'sandboxActive' => false,
            ]
        );

        $this->expectException(PayPalApiException::class);
        $this->expectExceptionMessage(GuzzleClientMock::GENERAL_CLIENT_EXCEPTION_MESSAGE);
        $controller->validateApiCredentials($request);
    }

    private function createApiValidationController(): SettingsController
    {
        $logger = new LoggerMock();

        return new SettingsController(
            new ApiCredentialService(
                new CredentialsResource(
                    new TokenClientFactoryMock($logger),
                    new CredentialsClientFactoryMock($logger),
                    new TokenValidator()
                )
            ),
            new MerchantIntegrationsService(
                new MerchantIntegrationsResource($this->createPayPalClientFactory()),
                new CredentialsUtil($this->createDefaultSystemConfig()),
                $this->getContainer()->get(PaymentMethodDataRegistry::class),
                new PayPalClientFactoryMock($this->createDefaultSystemConfig(), $logger)
            )
        );
    }
}
