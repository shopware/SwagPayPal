<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Swag\PayPal\PayPal\Exception\PayPalApiException;

abstract class AbstractClient
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(Client $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    protected function post(string $uri, array $options): array
    {
        try {
            $response = $this->client->post($uri, $options)->getBody()->getContents();
        } catch (RequestException $requestException) {
            throw $this->handleRequestException($requestException, $options);
        }

        return $this->decodeJsonResponse($response);
    }

    protected function get(string $uri, array $options = []): array
    {
        try {
            $response = $this->client->get($uri, $options)->getBody()->getContents();
        } catch (RequestException $requestException) {
            throw $this->handleRequestException($requestException, $options);
        }

        return $this->decodeJsonResponse($response);
    }

    protected function patch(string $uri, array $options): array
    {
        try {
            $response = $this->client->patch($uri, $options)->getBody()->getContents();
        } catch (RequestException $requestException) {
            throw $this->handleRequestException($requestException, $options);
        }

        return $this->decodeJsonResponse($response);
    }

    protected function delete(string $uri, array $options = []): array
    {
        try {
            $response = $this->client->delete($uri, $options)->getBody()->getContents();
        } catch (RequestException $requestException) {
            throw $this->handleRequestException($requestException, $options);
        }

        return $this->decodeJsonResponse($response);
    }

    private function decodeJsonResponse(string $response): array
    {
        return \json_decode($response, true) ?? [];
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
            $message .= ': ';
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
