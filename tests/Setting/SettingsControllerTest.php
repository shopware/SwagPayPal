<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Setting;

use GuzzleHttp\Exception\ClientException;
use PHPUnit\Framework\TestCase;
use Swag\PayPal\Setting\Service\ApiCredentialService;
use Swag\PayPal\Setting\SettingsController;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Mock\CacheMock;
use Swag\PayPal\Test\Mock\PayPal\Client\OnboardingClientMock;
use Swag\PayPal\Test\Mock\PayPal\Client\TokenClientFactoryMock;
use Swag\PayPal\Test\Mock\PayPal\Resource\TokenResourceMock;
use Symfony\Component\HttpFoundation\Request;

class SettingsControllerTest extends TestCase
{
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

        $result = json_decode($controller->validateApiCredentials($request)->getContent(), true);

        static::assertSame(['credentialsValid' => true], $result);
    }

    public function testValidateApiWithInvalidData(): void
    {
        $controller = $this->createApiValidationController();

        $request = new Request(
            [
                'clientId' => 'invalid-id',
                'clientSecret' => 'invalid-secret',
                'sandboxActive' => false,
            ]
        );

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage(TokenResourceMock::GENERAL_CLIENT_EXCEPTION_MESSAGE);
        $controller->validateApiCredentials($request);
    }

    private function createApiValidationController(): SettingsController
    {
        return new SettingsController(
            new ApiCredentialService(
                new TokenResourceMock(
                    new CacheMock(),
                    new TokenClientFactoryMock()
                ),
                new OnboardingClientMock()
            )
        );
    }
}
