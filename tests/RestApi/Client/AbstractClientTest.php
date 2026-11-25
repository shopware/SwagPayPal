<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\Client;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\RequestException;
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
use Swag\PayPal\RestApi\Client\AbstractClient;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\Util\Log\LogCode;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(AbstractClient::class)]
class AbstractClientTest extends TestCase
{
    private const MESSAGE = 'Requested the PayPal API: {httpMethod} {httpPath} returned {httpStatus}';

    private TestHandler $logs;

    private MockHandler $guzzleHandler;

    private TestClient $client;

    protected function setUp(): void
    {
        $this->logs = new TestHandler();
        $this->guzzleHandler = new MockHandler();

        $this->client = new TestClient(
            new GuzzleHttpClient(['handler' => HandlerStack::create($this->guzzleHandler), 'base_uri' => 'https://api.paypal.test']),
            new Logger('test', [$this->logs]),
        );
    }

    public function testRequestLogsOneDebugLineWithoutTheQuery(): void
    {
        $body = ['id' => 'ORDER', 'status' => 'CREATED'];
        $this->guzzleHandler->append(new Response(201, ['PayPal-Debug-Id' => 'debug-id'], \json_encode($body, \JSON_THROW_ON_ERROR)));

        static::assertSame($body, $this->client->send('POST', '/v2/checkout/orders?token=secret', [
            'headers' => ['PayPal-Request-Id' => 'request-id'],
            'json' => ['intent' => 'CAPTURE'],
        ]));

        $logs = $this->logs->getRecords();
        static::assertCount(1, $logs);
        static::assertSame(self::MESSAGE, $logs[0]->message);
        static::assertSame(Level::Debug, $logs[0]->level);
        static::assertEquals([
            'httpMethod' => 'POST',
            'httpPath' => '/v2/checkout/orders',
            'httpStatus' => 201,
            'paypalDebugId' => 'debug-id',
            'paypalRequestId' => 'request-id',
            'request' => ['intent' => 'CAPTURE'],
            'response' => $body,
        ], $logs[0]->context);
        static::assertStringNotContainsString('secret', \json_encode($logs[0], \JSON_THROW_ON_ERROR));
    }

    public function testRequestHeadersLogsOneDebugLine(): void
    {
        $this->guzzleHandler->append(new Response(200, ['PayPal-Debug-Id' => 'debug-id']));

        $this->client->sendForHeaders('/v1/something');

        $logs = $this->logs->getRecords();
        static::assertCount(1, $logs);
        static::assertSame(self::MESSAGE, $logs[0]->message);
        static::assertSame('GET', $logs[0]->context['httpMethod']);
        static::assertSame('/v1/something', $logs[0]->context['httpPath']);
    }

    public function testTokenRequestsNeverLogTheirBodies(): void
    {
        $this->guzzleHandler->append(new Response(200, [], '{"access_token":"secret-token"}'));

        $this->client->send('POST', '/v1/oauth2/token', ['form_params' => ['grant_type' => 'client_credentials', 'client_secret' => 'secret']]);

        $context = $this->logs->getRecords()[0]->context;
        static::assertSame('<redacted>', $context['request']);
        static::assertSame('<redacted>', $context['response']);
    }

    public function testFormBodiesAreRedacted(): void
    {
        $this->guzzleHandler->append(new Response(200, [], 'ok'));

        $this->client->send('POST', '/some/endpoint', ['form_params' => ['client_secret' => 'abc', 'grant_type' => 'x']]);

        static::assertSame('<redacted>', $this->logs->getRecords()[0]->context['request']);
    }

    public function testFailedRequestLogsInfoAndDebug(): void
    {
        $body = ['name' => 'RESOURCE_NOT_FOUND', 'message' => 'The specified resource does not exist.', 'details' => [['issue' => 'INVALID_RESOURCE_ID']]];
        $this->guzzleHandler->append(new Response(404, ['PayPal-Debug-Id' => 'debug-id'], \json_encode($body, \JSON_THROW_ON_ERROR)));

        try {
            $this->client->send('GET', '/v2/checkout/orders/5O1?x=1', ['headers' => ['PayPal-Request-Id' => 'request-id']]);
            static::fail('Expected an exception');
        } catch (PayPalApiException $e) {
            static::assertTrue($e->is(PayPalApiException::ERROR_CODE_RESOURCE_NOT_FOUND));
        }

        $logs = $this->logs->getRecords();
        static::assertCount(2, $logs);
        static::assertSame(self::MESSAGE, $logs[0]->message);
        static::assertSame(Level::Info, $logs[0]->level);
        static::assertEquals([
            'code' => LogCode::API_REQUEST_FAILED,
            'httpMethod' => 'GET',
            'httpPath' => '/v2/checkout/orders/5O1',
            'httpStatus' => 404,
            'paypalDebugId' => 'debug-id',
            'paypalRequestId' => 'request-id',
            'paypalError' => 'RESOURCE_NOT_FOUND',
            'paypalIssues' => ['INVALID_RESOURCE_ID'],
        ], $logs[0]->context);
        static::assertSame(Level::Debug, $logs[1]->level);
        static::assertSame(self::MESSAGE, $logs[1]->message);
        static::assertEquals($body, $logs[1]->context['response']);
        static::assertStringNotContainsString('x=1', \json_encode($logs, \JSON_THROW_ON_ERROR));
    }

    public function testRejectionBecauseOfTheMerchantAccountIsCritical(): void
    {
        $body = ['name' => 'UNPROCESSABLE_ENTITY', 'message' => 'The requested action could not be performed.', 'details' => [['issue' => 'PAYEE_ACCOUNT_RESTRICTED']]];
        $this->guzzleHandler->append(new Response(422, [], \json_encode($body, \JSON_THROW_ON_ERROR)));

        try {
            $this->client->send('POST', '/v2/checkout/orders');
            static::fail('Expected an exception');
        } catch (PayPalApiException) {
        }

        $logs = $this->logs->getRecords();
        static::assertCount(2, $logs);
        static::assertSame(self::MESSAGE, $logs[0]->message);
        static::assertSame(Level::Critical, $logs[0]->level);
        static::assertSame(LogCode::API_MERCHANT_ACCOUNT_REJECTED, $logs[0]->context['code']);
        static::assertSame(['PAYEE_ACCOUNT_RESTRICTED'], $logs[0]->context['paypalIssues']);
    }

    public function testFailedTokenRequestNeverLogsTheBodies(): void
    {
        $this->guzzleHandler->append(new Response(401, [], '{"error":"invalid_client","error_description":"Client Authentication failed"}'));

        try {
            $this->client->send('POST', '/v1/oauth2/token', ['form_params' => ['grant_type' => 'client_credentials']]);
            static::fail('Expected an exception');
        } catch (PayPalApiException $e) {
            static::assertTrue($e->is(PayPalApiException::ERROR_CODE_INVALID_CREDENTIALS));
        }

        $logs = $this->logs->getRecords();
        static::assertCount(2, $logs);
        static::assertSame('invalid_client', $logs[0]->context['paypalError']);
        static::assertArrayNotHasKey('paypalIssues', $logs[0]->context);
        static::assertSame('<redacted>', $logs[1]->context['request']);
        static::assertSame('<redacted>', $logs[1]->context['response']);
    }

    public function testFailureWithoutResponseLogsOneLineWithoutTheUri(): void
    {
        $this->guzzleHandler->append(new RequestException('cURL error 28: Operation timed out for https://api.paypal.test/v2/checkout/orders?token=secret', new Request('GET', 'https://api.paypal.test/v2/checkout/orders?token=secret')));

        try {
            $this->client->send('GET', '/v2/checkout/orders?token=secret');
            static::fail('Expected an exception');
        } catch (PayPalApiException $e) {
            static::assertSame('General Error', $e->getName());
        }

        $logs = $this->logs->getRecords();
        static::assertCount(1, $logs);
        static::assertSame('Requesting the PayPal API failed without a response: {httpMethod} {httpPath}', $logs[0]->message);
        static::assertSame(Level::Error, $logs[0]->level);
        static::assertSame(LogCode::API_REQUEST_FAILED, $logs[0]->context['code']);
        static::assertSame('/v2/checkout/orders', $logs[0]->context['httpPath']);
        static::assertSame(RequestException::class, $logs[0]->context['exception']['class']);
    }
}
