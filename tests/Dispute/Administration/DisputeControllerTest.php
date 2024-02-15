<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Dispute\Administration;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Api\Exception\InvalidSalesChannelIdException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\RoutingException;
use Swag\PayPal\Dispute\Administration\DisputeController;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item;
use Swag\PayPal\RestApi\V1\Resource\DisputeResource;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetDispute;
use Swag\PayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
class DisputeControllerTest extends TestCase
{
    use ServicesTrait;

    public function testDisputeList(): void
    {
        $response = $this->createController()->disputeList(new Request());

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = $response->getContent();
        static::assertNotFalse($content);
        $data = \json_decode($content, true);
        static::assertArrayHasKey('items', $data);
        static::assertArrayHasKey('links', $data);
        static::assertCount(3, $data['items']);
    }

    public function testDisputeListInvalidSalesChannelId(): void
    {
        $this->expectException(InvalidSalesChannelIdException::class);
        $this->expectExceptionMessage('The provided salesChannelId "invalidSalesChannelId" is invalid.');
        $this->createController()->disputeList(new Request(['salesChannelId' => 'invalidSalesChannelId']));
    }

    public function testDisputeListValidDisputeStateFilter(): void
    {
        // does not filter the result, because of the Guzzle Mock and the hardcoded response ;-)
        $response = $this->createController()->disputeList(new Request(['disputeStateFilter' => Item::DISPUTE_STATE_RESOLVED]));

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = $response->getContent();
        static::assertNotFalse($content);
        $data = \json_decode($content, true);
        static::assertArrayHasKey('items', $data);
        static::assertArrayHasKey('links', $data);
        static::assertCount(3, $data['items']);
    }

    public function testDisputeListInvalidDisputeStateFilter(): void
    {
        $this->expectException(RoutingException::class);
        $this->expectExceptionMessage('The parameter "disputeStateFilter" is invalid.');
        $this->createController()->disputeList(new Request(['disputeStateFilter' => Item::DISPUTE_STATE_RESOLVED . ',InvalidState']));
    }

    public function testDisputeDetails(): void
    {
        $response = $this->createController()->disputeDetails(GetDispute::ID, new Request());

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = $response->getContent();
        static::assertNotFalse($content);
        $data = \json_decode($content, true);
        static::assertArrayHasKey('dispute_id', $data);
        static::assertSame(GetDispute::ID, $data['dispute_id']);
    }

    private function createController(): DisputeController
    {
        return new DisputeController(new DisputeResource(new PayPalClientFactoryMock(new NullLogger())));
    }
}
