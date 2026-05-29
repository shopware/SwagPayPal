<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Setting\Service;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\Setting\Service\ApiCredentialService;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Mock\PayPalSDK\GatewayTestBehaviour;
use Swag\PayPal\Test\Mock\PayPalSDK\MockRequestHandler;

/**
 * @internal
 */
#[Package('checkout')]
class ApiCredentialServiceTest extends TestCase
{
    use GatewayTestBehaviour;

    public function testValidApiCredentials(): void
    {
        $apiService = $this->createApiCredentialService();
        $clientId = ConstantsForTesting::VALID_CLIENT_ID;
        $clientSecret = ConstantsForTesting::VALID_CLIENT_SECRET;
        $sandboxActive = true;

        $apiCredentialsValid = $apiService->testApiCredentials($clientId, $clientSecret, $sandboxActive, null);

        static::assertTrue($apiCredentialsValid);
    }

    public function testApiCredentialsThrowsException(): void
    {
        $apiService = $this->createApiCredentialService();
        $clientId = ConstantsForTesting::VALID_CLIENT_ID;
        $clientSecret = ConstantsForTesting::INVALID_CLIENT_SECRET;
        $sandboxActive = false;

        $this->expectException(PayPalApiException::class);
        $this->expectExceptionMessageMatches('/\A' . \preg_quote(MockRequestHandler::GENERAL_CLIENT_EXCEPTION_MESSAGE, '/') . '\z/');
        $apiService->testApiCredentials($clientId, $clientSecret, $sandboxActive, null);
    }

    public function testApiCredentialsThrowsInvalidApiCredentialsException(): void
    {
        $apiService = $this->createApiCredentialService();
        $clientId = ConstantsForTesting::INVALID_CLIENT_ID;
        $clientSecret = ConstantsForTesting::VALID_CLIENT_SECRET;
        $sandboxActive = false;

        $this->expectException(PayPalApiException::class);
        $this->expectExceptionMessageMatches('/\A' . \preg_quote('The error "TEST" occurred with the following message: generalClientExceptionMessage', '/') . '\z/');
        $apiService->testApiCredentials($clientId, $clientSecret, $sandboxActive, null);
    }

    public function testGetApiCredentials(): void
    {
        $credentials = $this->createApiCredentialService()->getApiCredentials('authCode', 'sharedId', 'nonce', true);

        static::assertSame(ConstantsForTesting::VALID_CLIENT_ID, $credentials->getClientId());
        static::assertSame(ConstantsForTesting::VALID_CLIENT_SECRET, $credentials->getClientSecret());
    }

    private function createApiCredentialService(): ApiCredentialService
    {
        return new ApiCredentialService(
            self::tokenGateway(),
            self::customerGateway(),
        );
    }
}
