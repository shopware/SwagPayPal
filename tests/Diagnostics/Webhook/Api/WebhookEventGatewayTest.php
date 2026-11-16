<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics\Webhook\Api;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Context\ApiContext;
use Shopware\PayPalSDK\Context\CredentialsOAuthContext;
use Shopware\PayPalSDK\Exception\ApiException;
use Shopware\PayPalSDK\RequestService;
use Shopware\PayPalSDK\Test\Gateway\TestGateways;
use Shopware\PayPalSDK\Test\Request\TestClient;
use Swag\PayPal\Diagnostics\Webhook\Api\SimulateEvent;
use Swag\PayPal\Diagnostics\Webhook\Api\WebhookEventCollection;
use Swag\PayPal\Diagnostics\Webhook\Api\WebhookEventGateway;
use Swag\PayPal\Diagnostics\Webhook\Api\WebhookEventList;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(WebhookEventGateway::class)]
#[CoversClass(WebhookEventList::class)]
#[CoversClass(WebhookEventCollection::class)]
#[CoversClass(SimulateEvent::class)]
class WebhookEventGatewayTest extends TestCase
{
    private TestClient $client;

    private ApiContext $context;

    private WebhookEventGateway $gateway;

    protected function setUp(): void
    {
        $this->client = new TestClient();
        $this->context = new ApiContext(new CredentialsOAuthContext('client-id', 'client-secret'), true);

        $gateways = new TestGateways($this->client);
        $gateways->setCachedToken($this->context);

        $this->gateway = new WebhookEventGateway($this->client, $gateways->tokenGateway(), new RequestService());
    }

    public function testGetEvents(): void
    {
        $this->client->addResponse($this->createResponse(200, [
            'events' => [
                ['id' => 'WH-2', 'create_time' => '2026-10-23T12:16:46.205Z', 'event_type' => 'CHECKOUT.ORDER.APPROVED', 'resource_type' => 'checkout-order', 'summary' => 'An order has been approved by buyer', 'event_version' => '1.0', 'resource_version' => '2.0'],
                ['id' => 'WH-1', 'create_time' => '2026-10-23T12:10:00.000Z', 'event_type' => 'PAYMENT.CAPTURE.COMPLETED', 'resource_type' => 'capture', 'summary' => 'Payment completed', 'event_version' => '1.0', 'resource_version' => '2.0'],
            ],
            'count' => 2,
        ]));

        $list = $this->gateway->getEvents(
            $this->context,
            new \DateTimeImmutable('2026-10-23 14:10:00', new \DateTimeZone('Europe/Berlin')),
            new \DateTimeImmutable('2026-10-23T12:30:00.789+00:00'),
            20,
        );

        $last = $this->client->getLast();
        static::assertNotNull($last);
        $request = $last->getRequest();
        static::assertSame('GET', $request->getMethod());
        static::assertSame('/v1/notifications/webhooks-events', $request->getUri()->getPath());
        \parse_str($request->getUri()->getQuery(), $query);
        static::assertSame(['page_size' => '20', 'start_time' => '2026-10-23T12:10:00Z', 'end_time' => '2026-10-23T12:30:00Z'], $query);
        static::assertSame('Bearer ACCESS-TOKEN', $request->getHeaderLine('Authorization'));
        static::assertNull($last->getRequestBody());

        static::assertCount(2, $list->getEvents());
        $newest = $list->getEvents()->first();
        static::assertNotNull($newest);
        static::assertSame('WH-2', $newest->getId());
        static::assertSame('2026-10-23T12:16:46.205Z', $newest->getCreateTime());
        static::assertSame('CHECKOUT.ORDER.APPROVED', $newest->getEventType());
    }

    public function testGetEventsWithoutEvents(): void
    {
        $this->client->addResponse($this->createResponse(200, ['events' => [], 'count' => 0]));

        $list = $this->gateway->getEvents($this->context, new \DateTimeImmutable(), new \DateTimeImmutable(), 20);

        static::assertCount(0, $list->getEvents());
    }

    public function testSimulateEventToAWebhookId(): void
    {
        $this->client->addResponse($this->createSimulatedEventResponse());

        $event = $this->gateway->simulateEvent($this->createBody(webhookId: 'WEBHOOK-ID'), $this->context);

        $last = $this->client->getLast();
        static::assertNotNull($last);
        $request = $last->getRequest();
        static::assertSame('POST', $request->getMethod());
        static::assertSame('/v1/notifications/simulate-event', $request->getUri()->getPath());
        static::assertSame('', $request->getUri()->getQuery());
        static::assertSame(['webhook_id' => 'WEBHOOK-ID', 'event_type' => 'CHECKOUT.ORDER.APPROVED', 'resource_version' => '2.0'], $last->getRequestBody());

        static::assertSame('WH-MOCK-1', $event->getId());
    }

    public function testSimulateEventToAUrl(): void
    {
        $this->client->addResponse($this->createSimulatedEventResponse());

        $event = $this->gateway->simulateEvent($this->createBody(url: 'https://shop.example/execute?sw-token=abc'), $this->context);

        $last = $this->client->getLast();
        static::assertNotNull($last);
        static::assertSame(['url' => 'https://shop.example/execute?sw-token=abc', 'event_type' => 'CHECKOUT.ORDER.APPROVED', 'resource_version' => '2.0'], $last->getRequestBody());

        static::assertSame('WH-MOCK-1', $event->getId());
    }

    public function testSimulateEventFailure(): void
    {
        $this->client->addResponse($this->createResponse(400, ['name' => 'INVALID_RESOURCE_ID', 'message' => 'Requested resource ID was not found.']));

        $this->expectException(ApiException::class);

        $this->gateway->simulateEvent($this->createBody(webhookId: 'WEBHOOK-ID'), $this->context);
    }

    private function createBody(?string $webhookId = null, ?string $url = null): SimulateEvent
    {
        $body = new SimulateEvent();
        $body->setEventType('CHECKOUT.ORDER.APPROVED');
        $body->setResourceVersion('2.0');
        if ($webhookId !== null) {
            $body->setWebhookId($webhookId);
        }
        if ($url !== null) {
            $body->setUrl($url);
        }

        return $body;
    }

    private function createSimulatedEventResponse(): Response
    {
        return $this->createResponse(202, [
            'id' => 'WH-MOCK-1',
            'create_time' => '2026-10-23T12:16:46.205Z',
            'event_type' => 'CHECKOUT.ORDER.APPROVED',
            'summary' => 'An order has been approved by buyer',
            'event_version' => '1.0',
            'resource_version' => '2.0',
        ]);
    }

    /**
     * @param array<mixed> $body
     */
    private function createResponse(int $status, array $body): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], \json_encode($body, \JSON_THROW_ON_ERROR));
    }
}
