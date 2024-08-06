<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Setting\Service;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V1\Resource\CredentialsResource;
use Swag\PayPal\RestApi\V1\Service\CredentialProvider;
use Swag\PayPal\RestApi\V1\Service\TokenValidator;
use Swag\PayPal\Setting\Service\ApiCredentialService;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Mock\PayPal\Client\CredentialsClientFactoryMock;
use Swag\PayPal\Test\Mock\PayPal\Client\GuzzleClientMock;
use Swag\PayPal\Test\Mock\PayPal\Client\TokenClientFactoryMock;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;

/**
 * @internal
 */
#[Package('checkout')]
class ApiCredentialServiceTest extends TestCase
{
    public function testValidApiCredentials(): void
    {
        $apiService = $this->createApiCredentialService();
        $clientId = ConstantsForTesting::VALID_CLIENT_ID;
        $clientSecret = ConstantsForTesting::VALID_CLIENT_SECRET;
        $sandboxActive = true;

        $apiCredentialsValid = $apiService->testApiCredentials($clientId, $clientSecret, $sandboxActive);

        static::assertTrue($apiCredentialsValid);
    }

    public function testApiCredentialsThrowsException(): void
    {
        $apiService = $this->createApiCredentialService();
        $clientId = ConstantsForTesting::VALID_CLIENT_ID;
        $clientSecret = ConstantsForTesting::INVALID_CLIENT_SECRET;
        $sandboxActive = false;

        $this->expectException(PayPalApiException::class);
        $this->expectExceptionMessage(GuzzleClientMock::GENERAL_CLIENT_EXCEPTION_MESSAGE);
        $apiService->testApiCredentials($clientId, $clientSecret, $sandboxActive);
    }

    public function testApiCredentialsThrowsInvalidApiCredentialsException(): void
    {
        $apiService = $this->createApiCredentialService();
        $clientId = ConstantsForTesting::INVALID_CLIENT_ID;
        $clientSecret = ConstantsForTesting::VALID_CLIENT_SECRET;
        $sandboxActive = false;

        $this->expectException(PayPalApiException::class);
        $this->expectExceptionMessage('The error "TEST" occurred with the following message: generalClientExceptionMessage');
        $apiService->testApiCredentials($clientId, $clientSecret, $sandboxActive);
    }

    public function testGetApiCredentials(): void
    {
        $credentials = $this->createApiCredentialService()->getApiCredentials('authCode', 'sharedId', 'nonce', true);

        static::assertSame(ConstantsForTesting::VALID_CLIENT_ID, $credentials['client_id']);
        static::assertSame(ConstantsForTesting::VALID_CLIENT_SECRET, $credentials['client_secret']);
    }

    private function createApiCredentialService(): ApiCredentialService
    {
        $logger = new NullLogger();
        $systemConfigService = new SystemConfigServiceMock();

        return new ApiCredentialService(
            new CredentialsResource(
                new TokenClientFactoryMock($logger),
                new CredentialsClientFactoryMock($logger),
                new TokenValidator()
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
    }
}
