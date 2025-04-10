<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\Client;

use Monolog\Level;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\HttpClient\Psr18Client;

#[Package('checkout')]
class Client implements ClientInterface
{
    private const HEADER_WHITELIST = [
        'paypal-request-id',
        'content-type',
        'date',
    ];

    private Psr18Client $client;

    /**
     * @internal
     */
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
        $this->client = new Psr18Client();
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $response = $this->client->sendRequest($request);

        $this->logger->log(
            $response->getStatusCode() >= 400 ? Level::Error : Level::Info,
            'Requesting PayPal: [{debugId}] {method} {target} {code}',
            [
                'method' => \mb_strtoupper($request->getMethod()),
                'target' => (string) $request->getUri(),
                'code' => $response->getStatusCode(),
                'debugId' => $response->getHeaderLine('paypal-debug-id'),
                'requestId' => $request->getHeaderLine('paypal-request-id') ?: null,
            ],
        );

        $this->logger->debug(
            'Requesting PayPal: [{debugId}] {method} {target} {code}',
            [
                'method' => \mb_strtoupper($request->getMethod()),
                'target' => (string) $request->getUri(),
                'code' => $response->getStatusCode(),
                'debugId' => $response->getHeaderLine('paypal-debug-id'),
                'requestId' => $request->getHeaderLine('paypal-request-id') ?: null,
                'request' => \json_decode((string) $request->getBody(), true) ?: (string) $request->getBody(),
                'requestHeaders' => $this->getHeaders($request),
                'response' => \json_decode((string) $response->getBody(), true) ?: (string) $response->getBody(),
                'responseHeaders' => $this->getHeaders($request),
            ],
        );

        return $response;
    }

    /**
     * @return array<string, string>
     */
    private function getHeaders(MessageInterface $message): array
    {
        $headers = \array_filter(
            \array_keys($message->getHeaders()),
            static fn (string $name) => \in_array(\mb_strtolower($name), self::HEADER_WHITELIST, true),
        );

        return \array_combine(
            $headers,
            \array_map(
                static fn (string $name) => $message->getHeaderLine($name),
                $headers,
            ),
        );
    }
}
