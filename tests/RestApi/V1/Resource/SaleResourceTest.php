<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\V1\Resource;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\RestApi\V1\Resource\SaleResource;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetResourceSaleResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;

/**
 * @internal
 */
#[Package('checkout')]
class SaleResourceTest extends TestCase
{
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
        return new SaleResource(new PayPalClientFactoryMock(new NullLogger()));
    }
}
