<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Controller;

use PHPUnit\Framework\TestCase;
use SwagPayPal\Controller\SettingsController;
use SwagPayPal\Setting\ApiCredentialTestService;
use SwagPayPal\Test\Helper\ConstantsForTesting;
use SwagPayPal\Test\Mock\CacheMock;
use SwagPayPal\Test\Mock\PayPal\Client\TokenClientFactoryMock;
use SwagPayPal\Test\Mock\PayPal\Resource\TokenResourceMock;
use Symfony\Component\HttpFoundation\Request;

class SettingsControllerTest extends TestCase
{
    public function testValidateApi_withValidData(): void
    {
        $controller = $this->createApiValidationController();

        $request = new Request([], [
            'clientId' => ConstantsForTesting::VALID_CLIENT_ID,
            'clientSecret' => ConstantsForTesting::VALID_CLIENT_SECRET,
            'sandboxActive' => true,
        ]);

        $result = json_decode($controller->validateApiCredentials($request)->getContent(), true);

        self::assertSame(['credentialsValid' => true], $result);
    }

    public function testValidateApi_withInvalidData(): void
    {
        $controller = $this->createApiValidationController();

        $request = new Request([], [
            'clientId' => 'invalid-id',
            'clientSecret' => 'invalid-secret',
            'sandboxActive' => false,
        ]);

        $result = json_decode($controller->validateApiCredentials($request)->getContent(), true);

        self::assertSame(['credentialsValid' => false], $result);
    }

    private function createApiValidationController(): SettingsController
    {
        return new SettingsController(new ApiCredentialTestService(new TokenResourceMock(new CacheMock(), new TokenClientFactoryMock())));
    }
}
