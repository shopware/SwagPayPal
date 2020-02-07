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
use Swag\PayPal\Payment\Exception\PayPalApiException;

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
            $this->handleRequestException($requestException, $options);

            throw $requestException;
        }

        return $this->decodeJsonResponse($response);
    }

    protected function get(string $uri, array $options = []): array
    {
        try {
            $response = $this->client->get($uri, $options)->getBody()->getContents();
        } catch (RequestException $requestException) {
            $this->handleRequestException($requestException, null);

            throw $requestException;
        }

        return $this->decodeJsonResponse($response);
    }

    protected function patch(string $uri, array $options): array
    {
        try {
            $response = $this->client->patch($uri, $options)->getBody()->getContents();
        } catch (RequestException $requestException) {
            $this->handleRequestException($requestException, $options);

            throw $requestException;
        }

        return $this->decodeJsonResponse($response);
    }

    private function decodeJsonResponse(string $response): array
    {
        return json_decode($response, true);
    }

    /**
     * @throws PayPalApiException
     */
    private function handleRequestException(RequestException $requestException, ?array $data): void
    {
        $exceptionMessage = $requestException->getMessage();
        $exceptionResponse = $requestException->getResponse();

        if ($exceptionResponse === null) {
            $this->logger->error($exceptionMessage, [$data]);

            return;
        }

        $error = json_decode($exceptionResponse->getBody()->getContents(), true);
        if (\array_key_exists('error', $error) && \array_key_exists('error_description', $error)) {
            $this->logger->error($exceptionMessage, [$error, $data]);

            throw new PayPalApiException($error['error'], $error['error_description']);
        }

        $message = $error['message'];

        if (isset($error['details'])) {
            $message .= ': ';
            foreach ($error['details'] as $detail) {
                $message .= $detail['issue'] . ' (' . $detail['field'] . ') ';
            }
        }

        $this->logger->error($exceptionMessage . ' ' . $message, [$error, $data]);

        throw new PayPalApiException($error['name'], $message);
    }
}
