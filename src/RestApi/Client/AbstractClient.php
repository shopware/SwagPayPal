<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
abstract class AbstractClient
{
    protected const HEADER_WHITELIST = [
        'Paypal-Debug-Id',
        'PayPal-Request-Id',
        'Date',
    ];

    protected ClientInterface $client;

    protected LoggerInterface $logger;

    public function __construct(
        ClientInterface $client,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->logger = $logger;
    }

    protected function post(string $uri, array $options): array
    {
        return $this->request(Request::METHOD_POST, $uri, $options);
    }

    protected function get(string $uri, array $options = []): array
    {
        return $this->request(Request::METHOD_GET, $uri, $options);
    }

    protected function getHeaders(string $uri, array $options = []): array
    {
        return $this->requestHeaders(Request::METHOD_GET, $uri, $options);
    }

    protected function patch(string $uri, array $options): array
    {
        return $this->request(Request::METHOD_PATCH, $uri, $options);
    }

    protected function put(string $uri, array $options): array
    {
        return $this->request(Request::METHOD_PUT, $uri, $options);
    }

    protected function delete(string $uri, array $options = []): array
    {
        return $this->request(Request::METHOD_DELETE, $uri, $options);
    }

    private function request(string $method, string $uri, array $options = []): array
    {
        $this->logger->debug(
            'Sending {method} request to {uri} with the following content: {content}',
            [
                'method' => \mb_strtoupper($method),
                'uri' => $uri,
                'content' => $options,
            ]
        );

        try {
            $response = $this->client->request($method, $uri, $options);
            $body = $response->getBody()->getContents();
        } catch (RequestException $requestException) {
            throw $this->handleRequestException($requestException, $options);
        }

        $this->logger->debug(
            'Received {code} from {method} {uri} with following response: {response}',
            [
                'method' => \mb_strtoupper($method),
                'code' => \sprintf('%s %s', $response->getStatusCode(), $response->getReasonPhrase()),
                'uri' => $uri,
                'headers' => $response->getHeaders(),
                'response' => $body,
            ]
        );

        return \json_decode($body, true) ?? [];
    }

    private function requestHeaders(string $method, string $uri, array $options = []): array
    {
        $this->logger->debug(
            'Sending {method} request to {uri} with the following content: {content}',
            [
                'method' => \mb_strtoupper($method),
                'uri' => $uri,
                'content' => $options,
            ]
        );

        try {
            $response = $this->client->request($method, $uri, $options);
        } catch (RequestException $requestException) {
            throw $this->handleRequestException($requestException, $options);
        }

        $this->logger->debug(
            'Received {code} from {method} {uri} with following response: {response}',
            [
                'method' => \mb_strtoupper($method),
                'code' => \sprintf('%s %s', $response->getStatusCode(), $response->getReasonPhrase()),
                'uri' => $uri,
                'headers' => $response->getHeaders(),
            ]
        );

        return $response->getHeaders();
    }

    private function handleRequestException(RequestException $requestException, array $data): PayPalApiException
    {
        $exceptionMessage = $requestException->getMessage();
        $exceptionResponse = $requestException->getResponse();

        if ($exceptionResponse === null) {
            $this->logger->error($exceptionMessage, ['data' => $data]);

            return new PayPalApiException('General Error', $exceptionMessage);
        }

        $content = $exceptionResponse->getBody()->getContents();
        $error = \json_decode($content, true) ?: [];
        $issue = null;
        if (\array_key_exists('error', $error) && \array_key_exists('error_description', $error)) {
            $this->logger->error($exceptionMessage, [
                'error' => $error,
                'headers' => $this->extractHeaders($exceptionResponse),
                'data' => $data,
            ]);

            if ($exceptionResponse->getStatusCode() === Response::HTTP_UNAUTHORIZED) {
                $issue = PayPalApiException::ERROR_CODE_INVALID_CREDENTIALS;
            }

            return new PayPalApiException($error['error'], $error['error_description'], $exceptionResponse->getStatusCode(), $issue);
        }

        if (\is_array($error['errors'] ?? null)) {
            $error = \current($error['errors']);
        }

        $message = $error['message'] ?? $content;

        if (isset($error['details'])) {
            $message .= ' ';
            foreach ($error['details'] as $detail) {
                if (isset($detail['description'])) {
                    $message .= \sprintf('%s ', $detail['description']);
                }
                if (isset($detail['issue'])) {
                    $issue = $detail['issue'];
                    $message .= \sprintf('%s ', $detail['issue']);
                }
                if (isset($detail['field'])) {
                    $message .= \sprintf('(%s) ', $detail['field']);
                }
            }
        }

        $this->logger->error(\sprintf('%s %s', $exceptionMessage, $message), [
            'error' => $error,
            'headers' => $this->extractHeaders($exceptionResponse),
            'data' => $data,
        ]);

        return new PayPalApiException($error['name'] ?? 'UNCLASSIFIED_ERROR', $message, $exceptionResponse->getStatusCode(), $issue);
    }

    private function extractHeaders(ResponseInterface $response): array
    {
        return \array_combine(
            self::HEADER_WHITELIST,
            \array_map(static fn (string $name) => $response->getHeader($name), self::HEADER_WHITELIST),
        );
    }
}
