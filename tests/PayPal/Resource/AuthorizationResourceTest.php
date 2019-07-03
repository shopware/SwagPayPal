<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\PayPal\Resource;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
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
        $context = Context::createDefaultContext();
        $captureResponse = $this->createAuthorizationResource()->capture(
            'captureId',
            $capture,
            Defaults::SALES_CHANNEL
        );

        $capture = json_encode($captureResponse);
        static::assertNotFalse($capture);
        if ($capture === false) {
            return;
        }

        $captureArray = json_decode($capture, true);

        static::assertTrue($captureArray['is_final_capture']);
    }

    public function testVoid(): void
    {
        $context = Context::createDefaultContext();
        $voidResponse = $this->createAuthorizationResource()->void('voidId', Defaults::SALES_CHANNEL);

        $void = json_encode($voidResponse);
        static::assertNotFalse($void);
        if ($void === false) {
            return;
        }

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
