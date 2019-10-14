<?php declare(strict_types=1);

namespace Swag\PayPal\Test\PayPal\Resource;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Swag\PayPal\PayPal\Api\Capture;
use Swag\PayPal\PayPal\Resource\AuthorizationResource;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\VoidAuthorizationResponseFixture;

class AuthorizationResourceTest extends TestCase
{
    use ServicesTrait;

    public function testCapture(): void
    {
        $capture = new Capture();
        $captureResponse = $this->createAuthorizationResource()->capture(
            'captureId',
            $capture,
            Defaults::SALES_CHANNEL
        );

        $capture = json_encode($captureResponse);
        static::assertNotFalse($capture);

        $captureArray = json_decode($capture, true);

        static::assertTrue($captureArray['is_final_capture']);
    }

    public function testVoid(): void
    {
        $voidResponse = $this->createAuthorizationResource()->void('voidId', Defaults::SALES_CHANNEL);

        $void = json_encode($voidResponse);
        static::assertNotFalse($void);

        $voidArray = json_decode($void, true);

        static::assertSame(VoidAuthorizationResponseFixture::VOID_ID, $voidArray['id']);
    }

    private function createAuthorizationResource(): AuthorizationResource
    {
        return new AuthorizationResource(
            $this->createPayPalClientFactory()
        );
    }
}
