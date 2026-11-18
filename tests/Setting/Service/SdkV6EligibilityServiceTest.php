<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Setting\Service;

use GuzzleHttp\Psr7\Response;
use Monolog\Level;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Contract\Context\ApiContextInterface;
use Shopware\PayPalSDK\Contract\Gateway\TokenGatewayInterface;
use Shopware\PayPalSDK\Exception\ApiException;
use Shopware\PayPalSDK\Struct\V1\Token;
use Swag\PayPal\RestApi\ApiContextFactoryInterface;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SdkV6EligibilityService;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\PayPalLogTrait;
use Swag\PayPal\Test\Mock\PayPalSDK\ApiContextFactoryMock;
use Swag\PayPal\Util\Log\LogCode;
use Swag\PayPal\Util\Log\LogKey;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(SdkV6EligibilityService::class)]
class SdkV6EligibilityServiceTest extends TestCase
{
    use PayPalLogTrait;

    public function testEligibleWhenTheEligibilityScopeIsGranted(): void
    {
        $service = $this->createService($this->createTokenGateway(\implode(' ', [
            'https://uri.paypal.com/services/payments/realtimepayment',
            SdkV6EligibilityService::SCOPE_CLIENT_PAYMENTS_ELIGIBILITY,
            'openid',
        ])));

        static::assertTrue($service->isEligible());
    }

    public function testIneligibleWhenOnlyOtherScopesAreGranted(): void
    {
        $service = $this->createService($this->createTokenGateway(
            'https://uri.paypal.com/services/payments/realtimepayment openid',
        ));

        static::assertFalse($service->isEligible());
    }

    public function testUndeterminedWhenTheTokenCarriesNoScope(): void
    {
        $gateway = $this->createMock(TokenGatewayInterface::class);
        $gateway->method('getToken')->willReturn((new Token())->assign(['expires_in' => 32389]));

        static::assertNull($this->createService($gateway)->isEligible());
    }

    public function testUndeterminedWhenPayPalIsNotReachable(): void
    {
        $gateway = $this->createMock(TokenGatewayInterface::class);
        $exception = new ApiException(ApiException::CODE_SERVICE_UNAVAILABLE, 'Service Unavailable', new Response(503));
        $gateway->method('getToken')->willThrowException($exception);

        $service = new SdkV6EligibilityService($gateway, new ApiContextFactoryMock(), $this->createPayPalLogger());

        static::assertNull($service->isEligible('sales-channel-id'));

        $record = $this->assertPayPalLogged(
            LogCode::SETTINGS_SDK_V6_ELIGIBILITY_UNKNOWN,
            Level::Warning,
            [LogKey::SALES_CHANNEL_ID => 'sales-channel-id'],
        );
        static::assertSame($exception::class, $record->context[LogKey::EXCEPTION]['class'] ?? null);
        static::assertSame($exception->getMessage(), $record->context[LogKey::EXCEPTION]['message'] ?? null);
    }

    public function testUndeterminedWithoutCredentials(): void
    {
        $apiContextFactory = $this->createMock(ApiContextFactoryInterface::class);
        $apiContextFactory
            ->method('getApiContext')
            ->willThrowException(new PayPalSettingsInvalidException(Settings::CLIENT_ID));

        $gateway = $this->createMock(TokenGatewayInterface::class);
        $gateway->expects($this->never())->method('getToken');

        $service = new SdkV6EligibilityService($gateway, $apiContextFactory, $this->createPayPalLogger());

        static::assertNull($service->isEligible());
        static::assertSame([], $this->getPayPalLogRecords());
    }

    public function testAnUncachedClientTokenIsRequestedForTheGivenSalesChannel(): void
    {
        $apiContextFactory = $this->createMock(ApiContextFactoryInterface::class);
        $apiContextFactory
            ->expects($this->once())
            ->method('getApiContext')
            ->with('sales-channel-id')
            ->willReturn((new ApiContextFactoryMock())->getApiContext('sales-channel-id'));

        $gateway = $this->createMock(TokenGatewayInterface::class);
        $gateway
            ->expects($this->once())
            ->method('getToken')
            ->with(static::callback(static function (ApiContextInterface $context): bool {
                $oauthContext = $context->getOAuthContext();

                // the granted scopes differ between the client token and the plain access token
                static::assertSame('client_token', $oauthContext->getBody()['response_type'] ?? null);

                // a cached token would still carry the scopes granted when it was issued
                static::assertNull($oauthContext->getCacheKey($context));

                return true;
            }))
            ->willReturn($this->createToken(SdkV6EligibilityService::SCOPE_CLIENT_PAYMENTS_ELIGIBILITY));

        $service = new SdkV6EligibilityService($gateway, $apiContextFactory, new NullLogger());

        static::assertTrue($service->isEligible('sales-channel-id'));
    }

    private function createService(TokenGatewayInterface $tokenGateway): SdkV6EligibilityService
    {
        return new SdkV6EligibilityService($tokenGateway, new ApiContextFactoryMock(), new NullLogger());
    }

    private function createTokenGateway(string $scope): TokenGatewayInterface
    {
        $gateway = $this->createMock(TokenGatewayInterface::class);
        $gateway->method('getToken')->willReturn($this->createToken($scope));

        return $gateway;
    }

    private function createToken(string $scope): Token
    {
        return (new Token())->assign([
            'scope' => $scope,
            'access_token' => 'testAccessToken',
            'token_type' => 'Bearer',
            'expires_in' => 32389,
        ]);
    }
}
