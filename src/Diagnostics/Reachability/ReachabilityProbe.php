<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Reachability;

use Shopware\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\TimeoutExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * The test runs from the shop's server, so a pass doesn't prove that PayPal gets through.
 *
 * @internal
 */
#[Package('checkout')]
class ReachabilityProbe
{
    // the plugin's routes echo it
    public const HEADER = 'sw-paypal-reachability-test';

    // PayPal waits 5 seconds for the answer
    private const TIMEOUT = 5;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly HostResolver $hostResolver,
    ) {
    }

    public static function isProbe(Request $request): bool
    {
        return $request->headers->has(self::HEADER);
    }

    public static function answer(Response $response): Response
    {
        $response->headers->set(self::HEADER, '1');

        return $response;
    }

    /**
     * @param list<string> $urls
     * @param string $token the throw-away token of the URLs, shown as `<token>` in the results
     *
     * @return list<ReachabilityResult>
     */
    public function probe(array $urls, string $token): array
    {
        $hosts = [];
        foreach ($urls as $url) {
            $key = self::hostOf($url);
            $hosts[$key] ??= $key === '' ? new ResolvedHost(HostResolution::Unresolved) : $this->hostResolver->resolve($key);
        }

        $responses = [];
        foreach ($urls as $url) {
            $host = $hosts[self::hostOf($url)];

            // the host alone decides these findings, so loopback and link-local addresses get no request
            if (\in_array($host->resolution, [HostResolution::Local, HostResolution::Unresolved], true)) {
                continue;
            }

            $responses[$url] = $this->httpClient->request(Request::METHOD_POST, $url, [
                'headers' => ['Content-Type' => 'application/json', self::HEADER => '1'],
                'body' => '{}',
                'max_redirects' => 0,
                'timeout' => self::TIMEOUT,
                'max_duration' => self::TIMEOUT,
                // the client would resolve the host again, possibly to an address the resolver didn't vet
                'resolve' => $host->pinnedAddress === null ? [] : [self::hostOf($url) => $host->pinnedAddress],
            ]);
        }

        $results = [];
        foreach (\array_unique($urls) as $url) {
            $host = $hosts[self::hostOf($url)];
            $shownUrl = \str_replace($token, '<token>', $url);
            $results[] = isset($responses[$url])
                ? $this->evaluate($shownUrl, $host, $responses[$url])
                : new ReachabilityResult($shownUrl, ReachabilityError::fromResolution($host->resolution), $host->addresses);
        }

        // errors first, the reachable ones last
        \usort($results, static fn (ReachabilityResult $a, ReachabilityResult $b) => match (true) {
            $a->error === null || $b->error === null => (int) ($a->error === null) <=> (int) ($b->error === null),
            default => $a->error->severity()->compare($b->error->severity()),
        });

        return $results;
    }

    private static function hostOf(string $url): string
    {
        $host = \parse_url($url, \PHP_URL_HOST);

        return \is_string($host) ? $host : '';
    }

    private function evaluate(string $url, ResolvedHost $host, ResponseInterface $response): ReachabilityResult
    {
        $statusCode = null;
        $location = null;
        $detail = null;

        try {
            $statusCode = $response->getStatusCode();

            if (isset($response->getHeaders(false)[self::HEADER])) {
                $requestError = null;
                // The plugin's own error status would only confuse next to a host error
                $statusCode = null;
            } elseif ($statusCode >= 300 && $statusCode < 400) {
                $requestError = ReachabilityError::Redirected;
                $location = $response->getHeaders(false)['location'][0] ?? null;
            } else {
                $requestError = match (true) {
                    $statusCode === Response::HTTP_UNAUTHORIZED, $statusCode === Response::HTTP_FORBIDDEN => ReachabilityError::Denied,
                    $statusCode === Response::HTTP_NOT_FOUND => ReachabilityError::NotFound,
                    $statusCode >= 500 => ReachabilityError::ServerError,
                    default => ReachabilityError::UnexpectedAnswer,
                };
            }
        } catch (TimeoutExceptionInterface $e) {
            $requestError = ReachabilityError::Timeout;
            $detail = $e->getMessage();
        } catch (TransportExceptionInterface $e) {
            // Transport errors only carry a message
            $requestError = \stripos($e->getMessage(), 'certificate') !== false ? ReachabilityError::CertificateInvalid : ReachabilityError::ConnectionFailed;
            $detail = $e->getMessage();
        }

        return new ReachabilityResult(
            $url,
            ReachabilityError::first(ReachabilityError::fromResolution($host->resolution), $requestError),
            $host->addresses,
            $statusCode,
            $location,
            $detail,
        );
    }
}
