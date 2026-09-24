<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\Client;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Client\Client;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(Client::class)]
class ClientTest extends TestCase
{
    protected TestHandler $logger;

    protected MockHandler $guzzleClient;

    protected Client $client;

    protected function setUp(): void
    {
        $this->logger = new TestHandler();
        $this->guzzleClient = new MockHandler();
        $this->client = new Client(
            new Logger('test', [$this->logger]),
            new GuzzleHttpClient(['handler' => HandlerStack::create($this->guzzleClient)]),
        );
    }

    public function testSendRequest(): void
    {
        $requestHeader = [
            'some-header' => 'some-value',
            'paypal-request-id' => '1234567',
            'content-type' => 'application/json',
            'date' => '2000-01-01',
            'authorization' => 'basic client:secret',
            'some-other-header' => 'some-value',
        ];
        $body = ['some-body' => 'some-value'];
        $jsonBody = \json_encode($body, \JSON_THROW_ON_ERROR);

        $responseHeader = [
            ...$requestHeader,
            'authorization' => 'bearer sujdbnfusn',
            'paypal-debug-id' => 'some-debug-id',
        ];

        $response = new Response(200, $responseHeader, $jsonBody);

        $this->guzzleClient->append($response);

        $request = new Request('POST', 'http://example.com/some/endpoint', $requestHeader, $jsonBody);

        static::assertSame($response, $this->client->sendRequest($request));

        $logs = $this->logger->getRecords();
        static::assertCount(1, $logs);
        static::assertSame('Requesting PayPal: [{debugId}] {method} {target} {code}', $logs[0]->message);
        static::assertEquals([
            'method' => 'POST',
            'target' => 'http://example.com/some/endpoint',
            'code' => 200,
            'debugId' => 'some-debug-id',
            'requestId' => '1234567',
            'request' => $body,
            'requestHeaders' => [
                'paypal-request-id' => '1234567',
                'content-type' => 'application/json',
                'date' => '2000-01-01',
                'authorization' => 'Basic <redacted>',
            ],
            'response' => $body,
            'responseHeaders' => [
                'paypal-request-id' => '1234567',
                'content-type' => 'application/json',
                'date' => '2000-01-01',
                'authorization' => 'Basic <redacted>',
            ],
        ], $logs[0]->context);
    }

    public function testSendRequest400(): void
    {
        $responseHeader = [
            'some-header' => 'some-value',
            'paypal-request-id' => '1234567',
            'content-type' => 'application/json',
            'date' => '2000-01-01',
            'authorization' => 'bearer sujdbnfusn',
            'some-other-header' => 'some-value',
        ];
        $body = ['some-body' => 'some-value'];
        $jsonBody = \json_encode($body, \JSON_THROW_ON_ERROR);

        $requestHeader = [
            ...$responseHeader,
            'authorization' => 'basic client:secret',
        ];

        $response = new Response(400, $responseHeader, $jsonBody);

        $this->guzzleClient->append($response);

        $request = new Request('POST', 'http://example.com/some/endpoint', $requestHeader, $jsonBody);

        static::assertSame($response, $this->client->sendRequest($request));

        $logs = $this->logger->getRecords();
        static::assertCount(2, $logs);
        static::assertSame('Requesting PayPal: [{debugId}] {method} {target} {code}', $logs[0]->message);
        static::assertEquals([
            'method' => 'POST',
            'target' => 'http://example.com/some/endpoint',
            'code' => 400,
            'debugId' => '',
            'requestId' => '1234567',
        ], $logs[0]->context);
    }

    public function testSendRequestWrapsNetworkException(): void
    {
        $request = new Request('GET', 'https://api-m.paypal.com/v2/checkout/orders/ORDER-ID', [
            'paypal-request-id' => 'req-1',
        ]);

        $networkException = new class($request) extends \RuntimeException implements NetworkExceptionInterface {
            public function __construct(private readonly RequestInterface $request)
            {
                parent::__construct('Could not resolve host: api-m.paypal.com');
            }

            public function getRequest(): RequestInterface
            {
                return $this->request;
            }
        };

        $innerClient = $this->createMock(ClientInterface::class);
        $innerClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($request)
            ->willThrowException($networkException);

        $client = new Client(
            new Logger('test', [$this->logger]),
            $innerClient,
        );

        try {
            $client->sendRequest($request);
            static::fail('Expected PayPalApiException to be thrown');
        } catch (PayPalApiException $exception) {
            static::assertSame(HttpResponse::HTTP_BAD_GATEWAY, $exception->getStatusCode());
            static::assertTrue($exception->is(PayPalApiException::ISSUE_NETWORK_ERROR));
            static::assertTrue($exception->is('SERVICE_UNAVAILABLE'));
            static::assertSame('SWAG_PAYPAL__API_NETWORK_ERROR', $exception->getErrorCode());
        }

        $logs = $this->logger->getRecords();
        static::assertCount(1, $logs);
        static::assertSame('PayPal network error: {message}', $logs[0]->message);
        static::assertSame('Could not resolve host: api-m.paypal.com', $logs[0]->context['message']);
        static::assertSame('GET', $logs[0]->context['method']);
        static::assertSame('https://api-m.paypal.com/v2/checkout/orders/ORDER-ID', $logs[0]->context['target']);
        static::assertSame('req-1', $logs[0]->context['requestId']);
    }
}
