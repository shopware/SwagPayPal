<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractClient
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var ClientInterface
     */
    protected $client;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(ClientInterface $client, LoggerInterface $logger)
    {
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

    protected function patch(string $uri, array $options): array
    {
        return $this->request(Request::METHOD_PATCH, $uri, $options);
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
                'response' => $body,
            ]
        );

        return \json_decode($body, true) ?? [];
    }

    private function handleRequestException(RequestException $requestException, array $data): PayPalApiException
    {
        $exceptionMessage = $requestException->getMessage();
        $exceptionResponse = $requestException->getResponse();

        if ($exceptionResponse === null) {
            $this->logger->error($exceptionMessage, [$data]);

            return new PayPalApiException('General Error', $exceptionMessage);
        }

        $error = \json_decode($exceptionResponse->getBody()->getContents(), true);
        if (\array_key_exists('error', $error) && \array_key_exists('error_description', $error)) {
            $this->logger->error($exceptionMessage, [$error, $data]);

            return new PayPalApiException($error['error'], $error['error_description'], (int) $requestException->getCode());
        }

        $message = $error['message'];

        if (isset($error['details'])) {
            $message .= ' ';
            foreach ($error['details'] as $detail) {
                if (isset($detail['description'])) {
                    $message .= \sprintf('%s ', $detail['description']);
                }
                if (isset($detail['issue'])) {
                    $message .= \sprintf('%s ', $detail['issue']);
                }
                if (isset($detail['field'])) {
                    $message .= \sprintf('(%s) ', $detail['field']);
                }
            }
        }

        $this->logger->error(\sprintf('%s %s', $exceptionMessage, $message), [$error, $data]);

        return new PayPalApiException($error['name'], $message, (int) $requestException->getCode());
    }
}
