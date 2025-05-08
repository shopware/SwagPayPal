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
use Swag\PayPal\RestApi\V1\Resource\SaleResource;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetResourceSaleResponseFixture;
use Swag\PayPal\Test\Mock\PayPalSDK\ApiContextFactoryMock;
use Swag\PayPal\Test\Mock\PayPalSDK\GatewayTestBehaviour;

/**
 * @internal
 */
#[Package('checkout')]
class SaleResourceTest extends TestCase
{
    use GatewayTestBehaviour;

    public function testGet(): void
    {
        $saleResponse = $this->createSaleResource()->get(
            'saleId',
            TestDefaults::SALES_CHANNEL
        );

        $sale = \json_encode($saleResponse);
        static::assertNotFalse($sale);

        $saleArray = \json_decode($sale, true);

        static::assertSame(GetResourceSaleResponseFixture::ID, $saleArray['id']);
    }

    private function createSaleResource(): SaleResource
    {
        return new SaleResource(self::paymentV1Gateway(), new ApiContextFactoryMock());
    }
}
