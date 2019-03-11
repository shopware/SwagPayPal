<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\PayPal\Resource;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Api\Capture;
use SwagPayPal\PayPal\Resource\AuthorizationResource;
use SwagPayPal\Test\Helper\ServicesTrait;

class AuthorizationResourceTest extends TestCase
{
    use ServicesTrait;

    public function testCapture(): void
    {
        $resource = $this->createSaleResource();

        $capture = new Capture();
        $context = Context::createDefaultContext();
        $captureResponse = $resource->capture('paymentId', $capture, $context);

        $capture = json_encode($captureResponse);
        static::assertNotFalse($capture);
        if ($capture === false) {
            return;
        }

        $captureArray = json_decode($capture, true);

        static::assertTrue($captureArray['is_final_capture']);
    }

    private function createSaleResource(): AuthorizationResource
    {
        return new AuthorizationResource(
            $this->createPayPalClientFactory()
        );
    }
}
