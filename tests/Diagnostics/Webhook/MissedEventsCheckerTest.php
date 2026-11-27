<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics\Webhook;

use GuzzleHttp\Psr7\Response;
use Monolog\Level;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\PayPalSDK\RequestService;
use Shopware\PayPalSDK\Test\Gateway\TestGateways;
use Shopware\PayPalSDK\Test\Request\TestClient;
use Swag\PayPal\Diagnostics\Webhook\Api\WebhookEventGateway;
use Swag\PayPal\Diagnostics\Webhook\MissedEvents;
use Swag\PayPal\Diagnostics\Webhook\MissedEventsChecker;
use Swag\PayPal\Diagnostics\Webhook\Recording\LastWebhook;
use Swag\PayPal\Diagnostics\Webhook\Recording\WebhookOutcome;
use Swag\PayPal\Test\Helper\PayPalLogTrait;
use Swag\PayPal\Test\Mock\PayPalSDK\ApiContextFactoryMock;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;
use Symfony\Component\Clock\MockClock;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(MissedEventsChecker::class)]
#[CoversClass(MissedEvents::class)]
class MissedEventsCheckerTest extends TestCase
{
    use PayPalLogTrait;

    private const LAST_EVENT_ID = 'WH-LAST';

    private TestClient $client;

    private MockClock $clock;

    private MissedEventsChecker $checker;

    private string $salesChannelId;

    protected function setUp(): void
    {
        $this->salesChannelId = Uuid::randomHex();
        $this->client = new TestClient();
        $this->clock = new MockClock('2026-11-12 10:00:00', 'UTC');

        $contextFactory = new ApiContextFactoryMock();
        $gateways = new TestGateways($this->client);
        $gateways->setCachedToken($contextFactory->getApiContext($this->salesChannelId));

        $this->checker = new MissedEventsChecker(
            new WebhookEventGateway($this->client, $gateways->tokenGateway(), new RequestService()),
            $contextFactory,
            $this->clock,
            $this->createPayPalLogger(),
        );
    }

    public function testMissedEvents(): void
    {
        $this->client->addResponse($this->createEventsResponse([
            ['WH-3', '2026-11-12T09:40:00.000Z', 'PAYMENT.CAPTURE.REFUNDED'],
            ['WH-2', '2026-11-12T09:30:00.000Z', 'CHECKOUT.ORDER.APPROVED'],
            [self::LAST_EVENT_ID, '2026-11-12T09:00:00.000Z', 'PAYMENT.CAPTURE.COMPLETED'],
        ]));

        $missedEvents = $this->checker->check($this->salesChannelId, $this->createLastWebhook('2026-11-12 09:00:00'));

        static::assertEquals(new MissedEvents(2, false, '2026-11-12T09:40:00.000Z', 'PAYMENT.CAPTURE.REFUNDED'), $missedEvents);
    }

    public function testTheWindowStartsASecondAfterTheLastWebhook(): void
    {
        $this->client->addResponse($this->createEventsResponse([]));

        $this->checker->check($this->salesChannelId, $this->createLastWebhook('2026-11-12 09:00:00.600'));

        static::assertSame(
            ['page_size' => '20', 'start_time' => '2026-11-12T09:00:01Z', 'end_time' => '2026-11-12T09:50:00Z'],
            $this->getLastQuery(),
        );
    }

    public function testTheLastWebhooksOwnEventIsNoMissedOne(): void
    {
        $this->client->addResponse($this->createEventsResponse([[self::LAST_EVENT_ID, '2026-11-12T09:00:00.000Z', 'PAYMENT.CAPTURE.COMPLETED']]));

        static::assertNull($this->checker->check($this->salesChannelId, $this->createLastWebhook('2026-11-12 09:00:00')));
    }

    public function testNoMissedEventsWithoutEvents(): void
    {
        $this->client->addResponse($this->createEventsResponse([]));

        static::assertNull($this->checker->check($this->salesChannelId, $this->createLastWebhook('2026-11-12 09:00:00')));
    }

    public function testMayHaveMore(): void
    {
        $events = [];
        for ($i = 20; $i > 0; --$i) {
            $events[] = ['WH-' . $i, '2026-11-12T09:30:00.000Z', 'CHECKOUT.ORDER.APPROVED'];
        }
        $this->client->addResponse($this->createEventsResponse($events));

        $missedEvents = $this->checker->check($this->salesChannelId, $this->createLastWebhook('2026-11-12 09:00:00'));

        static::assertSame(20, $missedEvents?->count);
        static::assertTrue($missedEvents->more);
    }

    public function testPayPalIsNotAskedWithinTheGracePeriod(): void
    {
        $this->clock->modify('2026-11-12 09:05:00');

        static::assertNull($this->checker->check($this->salesChannelId, $this->createLastWebhook('2026-11-12 09:00:00')));
        static::assertNull($this->client->getLast());
    }

    public function testToleratesPayPalErrors(): void
    {
        $this->client->addResponse(new Response(500, ['Content-Type' => 'application/json'], '{"name":"INTERNAL_SERVER_ERROR"}'));

        static::assertNull($this->checker->check($this->salesChannelId, $this->createLastWebhook('2026-11-12 09:00:00')));
        $this->assertPayPalLogged(
            LogCode::DIAGNOSTICS_WEBHOOK_EVENTS_UNAVAILABLE,
            Level::Warning,
            [Log::SALES_CHANNEL_ID => $this->salesChannelId],
            'Could not fetch the webhook events from PayPal, the diagnostics don\'t check for missed webhook events',
        );
    }

    private function createLastWebhook(string $receivedAt): LastWebhook
    {
        return new LastWebhook(new \DateTimeImmutable($receivedAt, new \DateTimeZone('UTC')), 'PAYMENT.CAPTURE.COMPLETED', WebhookOutcome::Handled, self::LAST_EVENT_ID);
    }

    /**
     * @return array<string, string>
     */
    private function getLastQuery(): array
    {
        $last = $this->client->getLast();
        static::assertNotNull($last);
        \parse_str($last->getRequest()->getUri()->getQuery(), $query);

        $parameters = [];
        foreach ($query as $name => $value) {
            $parameters[(string) $name] = \is_string($value) ? $value : '';
        }

        return $parameters;
    }

    /**
     * @param list<array{string, string, string}> $events id, create time, event type
     */
    private function createEventsResponse(array $events): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], \json_encode([
            'events' => \array_map(static fn (array $event) => ['id' => $event[0], 'create_time' => $event[1], 'event_type' => $event[2]], $events),
            'count' => \count($events),
        ], \JSON_THROW_ON_ERROR));
    }
}
