<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics\Reachability;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Diagnostics\Reachability\HostResolver;
use Swag\PayPal\Diagnostics\Reachability\ReachabilityError;
use Swag\PayPal\Diagnostics\Reachability\ReachabilityProbe;
use Swag\PayPal\Diagnostics\Severity;
use Symfony\Component\HttpClient\Exception\TimeoutException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ReachabilityProbe::class)]
class ReachabilityProbeTest extends TestCase
{
    private const URL = 'https://shop.example/paypal/test';

    private const TOKEN = '3c7f1d2e9a8b4c5d6e7f8091a2b3c4d5';

    public function testSendsACallbackWithoutPayloadOrRedirects(): void
    {
        $requests = [];
        $httpClient = new MockHttpClient(static function (string $method, string $url, array $options) use (&$requests) {
            $requests[] = ['method' => $method, 'options' => $options];

            return self::createPluginAnswer();
        });

        $results = $this->createProbe($httpClient)->probe([self::URL], self::TOKEN);

        static::assertNull($results[0]->error);
        static::assertCount(1, $requests);
        static::assertSame('POST', $requests[0]['method']);
        static::assertSame('{}', $requests[0]['options']['body']);
        static::assertSame(0, $requests[0]['options']['max_redirects']);
        static::assertContains(ReachabilityProbe::HEADER . ': 1', $requests[0]['options']['headers']);
    }

    /**
     * @param array<string, mixed> $info
     */
    #[DataProvider('answerProvider')]
    public function testEvaluatesTheAnswer(string $body, array $info, ?ReachabilityError $error, ?string $location = null): void
    {
        $httpClient = new MockHttpClient(new MockResponse($body, $info));

        $results = $this->createProbe($httpClient)->probe([self::URL], self::TOKEN);

        static::assertCount(1, $results);
        static::assertSame($error, $results[0]->error);
        static::assertSame($location, $results[0]->location);
        static::assertSame($error === null ? null : $info['http_code'] ?? null, $results[0]->statusCode);
    }

    public static function answerProvider(): \Generator
    {
        yield 'the plugin\'s answer' => ['', ['http_code' => 400, 'response_headers' => [ReachabilityProbe::HEADER . ': 1']], null];
        yield 'the plugin\'s answer without an error status' => ['', ['http_code' => 204, 'response_headers' => [ReachabilityProbe::HEADER . ': 1']], null];
        yield 'core\'s answer for an unassigned domain' => ['<html>Bad Request</html>', ['http_code' => 400], ReachabilityError::UnexpectedAnswer];
        yield 'the texts of the plugin\'s routes without the header' => ['{"error":"Invalid payload"}', ['http_code' => 400], ReachabilityError::UnexpectedAnswer];
        yield 'the text of the webhook route without the header' => ['Shopware token is invalid', ['http_code' => 400], ReachabilityError::UnexpectedAnswer];
        yield 'redirect to HTTPS' => ['', ['http_code' => 301, 'response_headers' => ['Location: https://shop.example/']], ReachabilityError::Redirected, 'https://shop.example/'];
        yield 'basic auth' => ['', ['http_code' => 401], ReachabilityError::Denied];
        yield 'firewall' => ['', ['http_code' => 403], ReachabilityError::Denied];
        yield 'blocked route' => ['', ['http_code' => 404], ReachabilityError::NotFound];
        yield 'server error' => ['', ['http_code' => 502], ReachabilityError::ServerError];
        yield 'success without the plugin\'s answer' => ['ok', ['http_code' => 200], ReachabilityError::UnexpectedAnswer];
        yield 'DNS error' => ['', ['error' => 'Could not resolve host: shop.example'], ReachabilityError::ConnectionFailed];
    }

    #[DataProvider('transportErrorProvider')]
    public function testMapsTransportErrors(string $message, ReachabilityError $error): void
    {
        $httpClient = new MockHttpClient(new MockResponse('', ['error' => $message]));

        $results = $this->createProbe($httpClient)->probe([self::URL], self::TOKEN);

        static::assertSame($error, $results[0]->error);
        static::assertNotNull($results[0]->detail);
        static::assertStringContainsString($message, $results[0]->detail);
    }

    public static function transportErrorProvider(): \Generator
    {
        yield 'curl, self-signed' => ['SSL certificate OpenSSL verify result: self-signed certificate (18) for "https://shop.example/x"', ReachabilityError::CertificateInvalid];
        yield 'curl, expired' => ['SSL certificate OpenSSL verify result: certificate has expired (10)', ReachabilityError::CertificateInvalid];
        yield 'curl, wrong name' => ['SSL: no alternative certificate subject name matches target hostname \'shop.example\'', ReachabilityError::CertificateInvalid];
        yield 'native client' => ['SSL operation failed with code 1. OpenSSL Error messages: error:0A000086:SSL routines::Certificate verify failed', ReachabilityError::CertificateInvalid];
        yield 'DNS error' => ['Could not resolve host: shop.example', ReachabilityError::ConnectionFailed];
        yield 'refused' => ['Failed to connect to 127.0.0.1:1 after 0 ms: Could not connect to server', ReachabilityError::ConnectionFailed];
    }

    public function testKeepsTheTimeoutMessage(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willThrowException(new TimeoutException('Idle timeout reached'));
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($response);

        $results = $this->createProbe($httpClient)->probe([self::URL], self::TOKEN);

        static::assertSame('Idle timeout reached', $results[0]->detail);
    }

    public function testAnswersHaveNoDetail(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('', ['http_code' => 404]));

        $results = $this->createProbe($httpClient)->probe([self::URL], self::TOKEN);

        static::assertNull($results[0]->detail);
        static::assertSame(404, $results[0]->statusCode);
    }

    public function testNoAnswerWithinPayPalsTimeLimit(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willThrowException(new TimeoutException('Idle timeout reached'));
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($response);

        $results = $this->createProbe($httpClient)->probe([self::URL], self::TOKEN);

        static::assertSame(ReachabilityError::Timeout, $results[0]->error);
    }

    #[DataProvider('provideLocalAddresses')]
    public function testSendsNoRequestToAHostOfTheServerItself(string $address): void
    {
        $httpClient = new MockHttpClient(self::createPluginAnswer());
        $probe = new ReachabilityProbe($httpClient, new HostResolver(static fn () => [$address]));

        $results = $probe->probe([self::URL], self::TOKEN);

        static::assertSame(0, $httpClient->getRequestsCount());
        static::assertSame(ReachabilityError::HostLocal, $results[0]->error);
        static::assertSame([$address], $results[0]->addresses);
        static::assertNull($results[0]->statusCode);
        static::assertSame(Severity::Error, $results[0]->error->severity());
    }

    public static function provideLocalAddresses(): \Generator
    {
        yield 'loopback' => ['127.0.0.1'];
        yield 'link-local' => ['169.254.169.254'];
    }

    public function testResolvesEachHostOnce(): void
    {
        $hosts = [];
        $resolver = new HostResolver(static function (string $host) use (&$hosts) {
            $hosts[] = $host;

            return ['93.184.216.34'];
        });
        $httpClient = new MockHttpClient(static fn () => self::createPluginAnswer());

        $results = (new ReachabilityProbe($httpClient, $resolver))->probe(
            ['https://a.example/x', 'https://a.example/y', 'https://b.example/x'],
            self::TOKEN,
        );

        static::assertSame(['a.example', 'b.example'], $hosts);
        static::assertCount(3, $results);
    }

    public function testAUrlWithoutHostIsUnresolved(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('', ['error' => 'Invalid URL']));

        $results = $this->createProbe($httpClient)->probe(['/just/a/path'], self::TOKEN);

        static::assertSame(0, $httpClient->getRequestsCount());
        static::assertSame(ReachabilityError::HostUnresolved, $results[0]->error);
        static::assertSame([], $results[0]->addresses);
    }

    public function testSendsNoRequestToAnUnresolvedHost(): void
    {
        $httpClient = new MockHttpClient(self::createPluginAnswer());
        $probe = new ReachabilityProbe($httpClient, new HostResolver(static fn () => []));

        $results = $probe->probe([self::URL], self::TOKEN);

        static::assertSame(0, $httpClient->getRequestsCount());
        static::assertSame(ReachabilityError::HostUnresolved, $results[0]->error);
    }

    public function testStillProbesAPrivateHost(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('', ['http_code' => 404]));
        $probe = new ReachabilityProbe($httpClient, new HostResolver(static fn () => ['10.0.0.5']));

        $results = $probe->probe([self::URL], self::TOKEN);

        static::assertSame(1, $httpClient->getRequestsCount());
        static::assertSame(ReachabilityError::NotFound, $results[0]->error);
    }

    public function testRecognizesTheRoutesByTheirHeader(): void
    {
        $request = new Request();
        static::assertFalse(ReachabilityProbe::isProbe($request));

        $request->headers->set(ReachabilityProbe::HEADER, '1');
        static::assertTrue(ReachabilityProbe::isProbe($request));

        $response = ReachabilityProbe::answer(new Response('Rejected', 400));
        static::assertSame('1', $response->headers->get(ReachabilityProbe::HEADER));
        static::assertSame('Rejected', $response->getContent());
    }

    public function testShowsTheTokenOfTheUrlsAsAPlaceholder(): void
    {
        $httpClient = new MockHttpClient(static fn () => self::createPluginAnswer());

        $results = $this->createProbe($httpClient)->probe([
            'https://a.example/paypal/' . self::TOKEN,
            'https://b.example/execute?sw-token=' . self::TOKEN,
        ], self::TOKEN);

        static::assertSame(['https://a.example/paypal/<token>', 'https://b.example/execute?sw-token=<token>'], \array_column($results, 'url'));
    }

    public function testSendsTheRequestToTheVettedAddress(): void
    {
        $options = [];
        $httpClient = new MockHttpClient(static function (string $method, string $url, array $requestOptions) use (&$options) {
            $options[] = $requestOptions['resolve'] ?? null;

            return self::createPluginAnswer();
        });
        $probe = new ReachabilityProbe($httpClient, new HostResolver(static fn () => ['10.0.0.5', '93.184.216.34']));

        $probe->probe(['https://shop.example:8443/x'], self::TOKEN);

        // the HTTP client would resolve the host again, and an attacker's DNS may answer differently
        static::assertSame([['shop.example' => '93.184.216.34']], $options);
    }

    public function testSendsTheRequestToAPrivateAddressIfThereIsNoPublicOne(): void
    {
        $options = [];
        $httpClient = new MockHttpClient(static function (string $method, string $url, array $requestOptions) use (&$options) {
            $options[] = $requestOptions['resolve'] ?? null;

            return self::createPluginAnswer();
        });
        $probe = new ReachabilityProbe($httpClient, new HostResolver(static fn () => ['10.0.0.5']));

        $probe->probe([self::URL], self::TOKEN);

        static::assertSame([['shop.example' => '10.0.0.5']], $options);
    }

    public function testDoesNotPinAnIpAddress(): void
    {
        $options = [];
        $httpClient = new MockHttpClient(static function (string $method, string $url, array $requestOptions) use (&$options) {
            $options[] = $requestOptions['resolve'] ?? null;

            return self::createPluginAnswer();
        });

        $this->createProbe($httpClient)->probe(['https://93.184.216.34/x'], self::TOKEN);

        static::assertSame([[]], $options);
    }

    public function testSendsNoRequestToAHostWithALocalAddressAmongOthers(): void
    {
        $httpClient = new MockHttpClient(self::createPluginAnswer());
        $probe = new ReachabilityProbe($httpClient, new HostResolver(static fn () => ['93.184.216.34', '127.0.0.1']));

        $results = $probe->probe([self::URL], self::TOKEN);

        static::assertSame(0, $httpClient->getRequestsCount());
        static::assertSame(ReachabilityError::HostLocal, $results[0]->error);
        static::assertSame(['93.184.216.34', '127.0.0.1'], $results[0]->addresses);
    }

    public function testShowsTheErrorsFirst(): void
    {
        $resolver = new HostResolver(static fn (string $host) => match ($host) {
            'private.example' => ['10.0.0.5'],
            'local.example' => ['127.0.0.1'],
            default => ['93.184.216.34'],
        });
        $httpClient = new MockHttpClient(static fn () => self::createPluginAnswer());

        $results = (new ReachabilityProbe($httpClient, $resolver))->probe(
            ['https://ok.example/x', 'https://private.example/x', 'https://local.example/x', 'https://also-ok.example/x'],
            self::TOKEN,
        );

        static::assertSame(
            ['https://local.example/x', 'https://private.example/x', 'https://ok.example/x', 'https://also-ok.example/x'],
            \array_column($results, 'url'),
        );
    }

    public function testProbesEachUrl(): void
    {
        $urls = [];
        $httpClient = new MockHttpClient(static function (string $method, string $url) use (&$urls) {
            $urls[] = $url;

            return self::createPluginAnswer();
        });

        $results = $this->createProbe($httpClient)->probe(['https://a.example/x', 'https://b.example/x'], self::TOKEN);

        static::assertSame(['https://a.example/x', 'https://b.example/x'], $urls);
        static::assertSame(['https://a.example/x', 'https://b.example/x'], \array_column($results, 'url'));
    }

    private static function createPluginAnswer(): MockResponse
    {
        return new MockResponse('', ['http_code' => 400, 'response_headers' => [ReachabilityProbe::HEADER . ': 1']]);
    }

    private function createProbe(HttpClientInterface $httpClient): ReachabilityProbe
    {
        return new ReachabilityProbe($httpClient, new HostResolver(static fn () => ['93.184.216.34']));
    }
}
