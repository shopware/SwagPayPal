<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\V1\Resource;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Swag\PayPal\RestApi\V1\Resource\DisputeResource;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetDispute;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetDisputesList;

class DisputeResourceTest extends TestCase
{
    use ServicesTrait;

    public function testList(): void
    {
        $disputes = $this->createResource()->list(Defaults::SALES_CHANNEL)->getItems();
        static::assertNotNull($disputes);
        $disputesCount = \count($disputes);
        static::assertSame(3, $disputesCount);

        static::assertSame(GetDisputesList::LAST_ID, $disputes[$disputesCount - 1]->getDisputeId());
    }

    public function testGet(): void
    {
        $dispute = $this->createResource()->get(GetDispute::ID, Defaults::SALES_CHANNEL);

        static::assertSame(GetDispute::ID, $dispute->getDisputeId());
    }

    private function createResource(): DisputeResource
    {
        $clientFactory = $this->createPayPalClientFactory();

        return new DisputeResource($clientFactory);
    }
}
