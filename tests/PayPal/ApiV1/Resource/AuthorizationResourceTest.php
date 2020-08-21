<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\PayPal\ApiV1\Resource;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Swag\PayPal\PayPal\ApiV1\Api\Capture;
use Swag\PayPal\PayPal\ApiV1\Resource\AuthorizationResource;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetResourceAuthorizeResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\VoidAuthorizationResponseFixture;

class AuthorizationResourceTest extends TestCase
{
    use ServicesTrait;

    public function testGet(): void
    {
        $authorizationResponse = $this->createAuthorizationResource()->get(
            'authorizationId',
            Defaults::SALES_CHANNEL
        );

        $authorization = \json_encode($authorizationResponse);
        static::assertNotFalse($authorization);

        $authorizationArray = \json_decode($authorization, true);

        static::assertSame(GetResourceAuthorizeResponseFixture::ID, $authorizationArray['id']);
    }

    public function testCapture(): void
    {
        $capture = new Capture();
        $captureResponse = $this->createAuthorizationResource()->capture(
            'captureId',
            $capture,
            Defaults::SALES_CHANNEL
        );

        $capture = \json_encode($captureResponse);
        static::assertNotFalse($capture);

        $captureArray = \json_decode($capture, true);

        static::assertTrue($captureArray['is_final_capture']);
    }

    public function testVoid(): void
    {
        $voidResponse = $this->createAuthorizationResource()->void('voidId', Defaults::SALES_CHANNEL);

        $void = \json_encode($voidResponse);
        static::assertNotFalse($void);

        $voidArray = \json_decode($void, true);

        static::assertSame(VoidAuthorizationResponseFixture::VOID_ID, $voidArray['id']);
    }

    private function createAuthorizationResource(): AuthorizationResource
    {
        return new AuthorizationResource(
            $this->createPayPalClientFactory()
        );
    }
}
