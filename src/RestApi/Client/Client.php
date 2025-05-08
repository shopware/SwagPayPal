<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\Client;

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
    private const HEADER_ALLOWLIST = [
        'paypal-request-id',
        'content-type',
        'date',
    ];

    /**
     * @internal
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private ClientInterface $client = new Psr18Client(),
    ) {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $response = $this->client->sendRequest($request);

        if ($response->getStatusCode() >= 400) {
            $this->logger->error(
                'Requesting PayPal: [{debugId}] {method} {target} {code}',
                [
                    'method' => \mb_strtoupper($request->getMethod()),
                    'target' => (string) $request->getUri(),
                    'code' => $response->getStatusCode(),
                    'debugId' => $response->getHeaderLine('paypal-debug-id'),
                    'requestId' => $request->getHeaderLine('paypal-request-id') ?: null,
                ],
            );
        }

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
            static fn (string $name) => \in_array(\mb_strtolower($name), self::HEADER_ALLOWLIST, true),
        );

        $headers = \array_combine(
            $headers,
            \array_map(
                static fn (string $name) => $message->getHeaderLine($name),
                $headers,
            ),
        );

        if ($message->getHeaderLine('paypal-auth-assertion')) {
            $headers['paypal-auth-assertion'] = '<redacted>';
        }

        if ($authorization = \mb_strtolower($message->getHeaderLine('authorization'))) {
            if (\str_starts_with($authorization, 'bearer')) {
                $headers['authorization'] = 'Bearer <redacted>';
            } elseif (\str_starts_with($authorization, 'basic')) {
                $headers['authorization'] = 'Basic <redacted>';
            } else {
                $headers['authorization'] = '<redacted>';
            }
        }

        return $headers;
    }
}
