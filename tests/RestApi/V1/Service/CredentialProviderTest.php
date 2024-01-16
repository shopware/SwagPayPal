<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\V1\Service;

use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\RestApi\V1\Api\Token;
use Swag\PayPal\RestApi\V1\Service\CredentialProvider;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Setting\Settings;

/**
 * @internal
 */
class CredentialProviderTest extends TestCase
{
    public function testCreateCredentialsObject(): void
    {
        $settingsValidationService = $this->createMock(SettingsValidationServiceInterface::class);
        $settingsValidationService
            ->expects(static::once())
            ->method('validate')
            ->with('salesChannelId');
        $systemConfigService = $this->createMock(SystemConfigService::class);
        $systemConfigService
            ->expects(static::once())
            ->method('getString')
            ->with(Settings::CLIENT_SECRET, 'salesChannelId')
            ->willReturn('testClientSecret');
        $credentialsUtil = $this->createMock(CredentialsUtilInterface::class);
        $credentialsUtil
            ->expects(static::once())
            ->method('getBaseUrl')
            ->with('salesChannelId')
            ->willReturn('testUrl');
        $credentialsUtil
            ->expects(static::once())
            ->method('getClientId')
            ->with('salesChannelId')
            ->willReturn('testClientId');
        $credentialsUtil
            ->expects(static::once())
            ->method('isSandbox')
            ->with('salesChannelId')
            ->willReturn(false);

        $credentialProvider = new CredentialProvider(
            $settingsValidationService,
            $systemConfigService,
            $credentialsUtil
        );

        $credentials = $credentialProvider->createCredentialsObject('salesChannelId');

        static::assertSame($credentials->getRestId(), 'testClientId');
        static::assertSame($credentials->getRestSecret(), 'testClientSecret');
        static::assertSame($credentials->getUrl(), 'testUrl');
    }

    public function testCreateAuthorizationHeaders(): void
    {
        $credentialProvider = new CredentialProvider(
            $this->createMock(SettingsValidationServiceInterface::class),
            $this->createMock(SystemConfigService::class),
            $this->createMock(CredentialsUtilInterface::class)
        );

        $token = new Token();
        $token->setTokenType('testTokenType');
        $token->setAccessToken('testAccessToken');

        $headers = $credentialProvider->createAuthorizationHeaders($token, null);

        static::assertSame([
            'Authorization' => \sprintf('%s %s', $token->getTokenType(), $token->getAccessToken()),
        ], $headers);
    }
}
