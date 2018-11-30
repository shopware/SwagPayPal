<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Setting;

use PHPUnit\Framework\TestCase;
use SwagPayPal\Setting\ApiCredentialTestService;
use SwagPayPal\Test\Helper\ConstantsForTesting;
use SwagPayPal\Test\Mock\CacheMock;
use SwagPayPal\Test\Mock\PayPal\Client\TokenClientFactoryMock;
use SwagPayPal\Test\Mock\PayPal\Resource\TokenResourceMock;

class ApiCredentialTestServiceTest extends TestCase
{
    public function testValidApiCredentials(): void
    {
        $apiService = $this->createApiCredentialTestService();
        $clientId = ConstantsForTesting::VALID_CLIENT_ID;
        $clientSecret = ConstantsForTesting::VALID_CLIENT_SECRET;
        $sandboxActive = true;

        $apiCredentialsValid = $apiService->testApiCredentials($clientId, $clientSecret, $sandboxActive);

        self::assertTrue($apiCredentialsValid);
    }

    public function testInvalidApiCredentials(): void
    {
        $apiService = $this->createApiCredentialTestService();
        $clientId = 'invalid-id';
        $clientSecret = 'invalid-secret';
        $sandboxActive = false;

        $apiCredentialsValid = $apiService->testApiCredentials($clientId, $clientSecret, $sandboxActive);

        self::assertFalse($apiCredentialsValid);
    }

    private function createApiCredentialTestService(): ApiCredentialTestService
    {
        return new ApiCredentialTestService(new TokenResourceMock(new CacheMock(), new TokenClientFactoryMock()));
    }
}
