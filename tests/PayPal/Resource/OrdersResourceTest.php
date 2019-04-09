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
use SwagPayPal\PayPal\Resource\OrdersResource;
use SwagPayPal\Test\Helper\ServicesTrait;
use SwagPayPal\Test\Mock\PayPal\Client\_fixtures\VoidOrderResponseFixture;

class OrdersResourceTest extends TestCase
{
    use ServicesTrait;

    public function testCapture(): void
    {
        $capture = new Capture();
        $context = Context::createDefaultContext();
        $captureResponse = $this->createOrdersResource()->capture('captureId', $capture, $context);

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
        $voidResponse = $this->createOrdersResource()->void('voidId', $context);

        $void = json_encode($voidResponse);
        static::assertNotFalse($void);
        if ($void === false) {
            return;
        }

        $voidArray = json_decode($void, true);

        static::assertSame(VoidOrderResponseFixture::VOID_ID, $voidArray['id']);
    }

    private function createOrdersResource(): OrdersResource
    {
        return new OrdersResource(
            $this->createPayPalClientFactory()
        );
    }
}
