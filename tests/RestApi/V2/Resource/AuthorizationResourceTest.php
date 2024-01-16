<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\V2\Resource;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Capture;
use Swag\PayPal\RestApi\V2\Resource\AuthorizationResource;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CaptureAuthorization;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetAuthorization;
use Swag\PayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;

/**
 * @internal
 */
#[Package('checkout')]
class AuthorizationResourceTest extends TestCase
{
    public function testGet(): void
    {
        $authorizationId = GetAuthorization::ID;
        $authorization = $this->createResource()->get($authorizationId, TestDefaults::SALES_CHANNEL);

        static::assertSame($authorizationId, $authorization->getId());
    }

    public function testCapture(): void
    {
        $capture = new Capture();
        $captureResponse = $this->createResource()->capture(
            'authorizationId',
            $capture,
            TestDefaults::SALES_CHANNEL,
            PartnerAttributionId::PAYPAL_CLASSIC,
            false
        );
        static::assertSame(CaptureAuthorization::ID, $captureResponse->getId());
        static::assertFalse($captureResponse->isFinalCapture());
    }

    private function createResource(): AuthorizationResource
    {
        return new AuthorizationResource(new PayPalClientFactoryMock(new NullLogger()));
    }
}
