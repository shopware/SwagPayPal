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
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Client\Client;
use Swag\PayPal\Util\Log\LogCode;

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
        static::assertSame('Requested the PayPal API: {httpMethod} {httpPath} returned {httpStatus}', $logs[0]->message);
        static::assertSame(Level::Debug, $logs[0]->level);
        static::assertEquals([
            'httpMethod' => 'POST',
            'httpPath' => '/some/endpoint',
            'httpStatus' => 200,
            'paypalDebugId' => 'some-debug-id',
            'paypalRequestId' => '1234567',
            'request' => $jsonBody,
            'response' => $jsonBody,
        ], $logs[0]->context);
    }

    public function testSendRequestPassesRawBodies(): void
    {
        $jsonBody = \json_encode(['payer' => ['email_address' => 'buyer@example.com', 'payer_id' => 'ABC']], \JSON_THROW_ON_ERROR);
        $this->guzzleClient->append(new Response(200, [], $jsonBody));

        $this->client->sendRequest(new Request('POST', 'http://example.com/v2/checkout/orders', [], $jsonBody));

        // the redaction is done by the `PayPalLogProcessor`
        $context = $this->logger->getRecords()[0]->context;
        static::assertSame($jsonBody, $context['request']);
        static::assertSame($jsonBody, $context['response']);
    }

    public function testSendRequestNeverLogsTokenBodies(): void
    {
        $this->guzzleClient->append(new Response(200, [], '{"access_token":"secret-token"}'));

        $this->client->sendRequest(new Request('POST', 'http://example.com/v1/oauth2/token', [], 'grant_type=client_credentials'));

        $context = $this->logger->getRecords()[0]->context;
        static::assertSame('<redacted>', $context['request']);
        static::assertSame('<redacted>', $context['response']);
    }

    public function testSendRequestPassesFormBodiesRaw(): void
    {
        $this->guzzleClient->append(new Response(200, [], 'ok'));

        $this->client->sendRequest(new Request('POST', 'http://example.com/some/endpoint', [], 'client_secret=abc&grant_type=x'));

        $context = $this->logger->getRecords()[0]->context;
        static::assertSame('client_secret=abc&grant_type=x', $context['request']);
        static::assertSame('ok', $context['response']);
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
        static::assertSame('Requested the PayPal API: {httpMethod} {httpPath} returned {httpStatus}', $logs[0]->message);
        static::assertSame(Level::Info, $logs[0]->level);
        static::assertEquals([
            'code' => LogCode::API_REQUEST_FAILED,
            'httpMethod' => 'POST',
            'httpPath' => '/some/endpoint',
            'httpStatus' => 400,
            'paypalDebugId' => null,
            'paypalRequestId' => '1234567',
            'paypalError' => 'UNKNOWN',
            'paypalIssues' => null,
        ], $logs[0]->context);
        static::assertSame(Level::Debug, $logs[1]->level);
    }

    public function testSendRequestRejectedBecauseOfTheMerchantAccountIsCritical(): void
    {
        $body = ['name' => 'UNPROCESSABLE_ENTITY', 'message' => 'The requested action could not be performed.', 'details' => [['issue' => 'PAYEE_ACCOUNT_RESTRICTED']]];
        $this->guzzleClient->append(new Response(422, [], \json_encode($body, \JSON_THROW_ON_ERROR)));

        $this->client->sendRequest(new Request('POST', 'http://example.com/v2/checkout/orders'));

        $logs = $this->logger->getRecords();
        static::assertCount(2, $logs);
        static::assertSame('Requested the PayPal API: {httpMethod} {httpPath} returned {httpStatus}', $logs[0]->message);
        static::assertSame(Level::Critical, $logs[0]->level);
        static::assertSame(LogCode::API_MERCHANT_ACCOUNT_REJECTED, $logs[0]->context['code']);
        static::assertSame(['PAYEE_ACCOUNT_RESTRICTED'], $logs[0]->context['paypalIssues']);
    }

    public function testSendRequestPassesTheRawPath(): void
    {
        $this->guzzleClient->append(new Response(404));

        $this->client->sendRequest(new Request('DELETE', 'http://example.com/v3/vault/payment-tokens/8kk8451t?x=1'));

        $logs = $this->logger->getRecords();
        static::assertSame('/v3/vault/payment-tokens/8kk8451t', $logs[0]->context['httpPath']);
        static::assertSame('/v3/vault/payment-tokens/8kk8451t', $logs[1]->context['httpPath']);
    }

    public function testSendRequest400WithIssue(): void
    {
        $body = ['name' => 'UNPROCESSABLE_ENTITY', 'message' => 'The requested action could not be performed.', 'details' => [['issue' => 'FIRST'], ['issue' => 'INSTRUMENT_DECLINED']]];
        $this->guzzleClient->append(new Response(422, ['paypal-debug-id' => 'dbg'], \json_encode($body, \JSON_THROW_ON_ERROR)));

        $this->client->sendRequest(new Request('POST', 'http://example.com/v2/checkout/orders?x=1'));

        $context = $this->logger->getRecords()[0]->context;
        static::assertSame('UNPROCESSABLE_ENTITY', $context['paypalError']);
        static::assertSame(['FIRST', 'INSTRUMENT_DECLINED'], $context['paypalIssues']);
        static::assertSame('dbg', $context['paypalDebugId']);
        static::assertSame('/v2/checkout/orders', $context['httpPath']);
        static::assertNull($context['paypalRequestId']);
    }

    public function testSendRequest400WithNameAndOAuthError(): void
    {
        $this->guzzleClient->append(new Response(404, [], '{"name":"RESOURCE_NOT_FOUND","message":"The specified resource does not exist."}'));
        $this->guzzleClient->append(new Response(401, [], '{"error":"invalid_client"}'));

        $this->client->sendRequest(new Request('GET', 'http://example.com/a'));
        $this->client->sendRequest(new Request('POST', 'http://example.com/v1/oauth2/token'));

        $logs = $this->logger->getRecords();
        static::assertSame('RESOURCE_NOT_FOUND', $logs[0]->context['paypalError']);
        static::assertNull($logs[0]->context['paypalIssues']);
        static::assertSame('invalid_client', $logs[2]->context['paypalError']);
    }

    public function testSendRequestWithoutPayPalErrorBodyUsesTheErrorOfTheSdk(): void
    {
        $this->guzzleClient->append(new Response(503, [], '<html>Service Unavailable</html>'));

        $this->client->sendRequest(new Request('GET', 'http://example.com/v2/checkout/orders/5O1'));

        $context = $this->logger->getRecords()[0]->context;
        static::assertSame('SERVICE_UNAVAILABLE', $context['paypalError']);
        static::assertNull($context['paypalIssues']);
    }
}
