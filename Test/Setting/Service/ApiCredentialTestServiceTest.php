<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Setting\Service;

use GuzzleHttp\Exception\ClientException;
use PHPUnit\Framework\TestCase;
use SwagPayPal\Setting\Exception\PayPalInvalidApiCredentialsException;
use SwagPayPal\Setting\Service\ApiCredentialTestService;
use SwagPayPal\Test\Helper\ConstantsForTesting;
use SwagPayPal\Test\Mock\CacheMock;
use SwagPayPal\Test\Mock\PayPal\Client\TokenClientFactoryMock;
use SwagPayPal\Test\Mock\PayPal\Resource\TokenResourceMock;

class ApiCredentialTestServiceTest extends TestCase
{
    public const INVALID_API_CLIENT_ID = 'invalid-id';

    public function testValidApiCredentials(): void
    {
        $apiService = $this->createApiCredentialTestService();
        $clientId = ConstantsForTesting::VALID_CLIENT_ID;
        $clientSecret = ConstantsForTesting::VALID_CLIENT_SECRET;
        $sandboxActive = true;

        $apiCredentialsValid = $apiService->testApiCredentials($clientId, $clientSecret, $sandboxActive);

        self::assertTrue($apiCredentialsValid);
    }

    public function testApiCredentialsThrowsException(): void
    {
        $apiService = $this->createApiCredentialTestService();
        $clientId = ConstantsForTesting::VALID_CLIENT_ID;
        $clientSecret = 'invalid-secret';
        $sandboxActive = false;

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage(TokenResourceMock::GENERAL_CLIENT_EXCEPTION_MESSAGE);
        $apiService->testApiCredentials($clientId, $clientSecret, $sandboxActive);
    }

    public function testApiCredentialsThrowsInvalidApiCredentialsException(): void
    {
        $apiService = $this->createApiCredentialTestService();
        $clientId = self::INVALID_API_CLIENT_ID;
        $clientSecret = ConstantsForTesting::VALID_CLIENT_SECRET;
        $sandboxActive = false;

        $this->expectException(PayPalInvalidApiCredentialsException::class);
        $this->expectExceptionMessage('Provided API credentials are invalid');
        $apiService->testApiCredentials($clientId, $clientSecret, $sandboxActive);
    }

    private function createApiCredentialTestService(): ApiCredentialTestService
    {
        return new ApiCredentialTestService(new TokenResourceMock(new CacheMock(), new TokenClientFactoryMock()));
    }
}
