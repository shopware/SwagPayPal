<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\V1\Resource;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\RestApi\V1\Resource\CaptureResource;
use Swag\PayPal\Test\Mock\PayPalSDK\ApiContextFactoryMock;
use Swag\PayPal\Test\Mock\PayPalSDK\GatewayTestBehaviour;

/**
 * @internal
 */
#[Package('checkout')]
class CaptureResourceTest extends TestCase
{
    use GatewayTestBehaviour;

    public function testGet(): void
    {
        $captureResponse = $this->createCaptureResource()->get(
            'captureId',
            TestDefaults::SALES_CHANNEL
        );

        $capture = \json_encode($captureResponse);
        static::assertNotFalse($capture);

        $captureArray = \json_decode($capture, true);

        static::assertTrue($captureArray['is_final_capture']);
    }

    private function createCaptureResource(): CaptureResource
    {
        return new CaptureResource(self::paymentV1Gateway(), new ApiContextFactoryMock());
    }
}
