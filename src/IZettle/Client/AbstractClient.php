<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Swag\PayPal\IZettle\Api\Exception\IZettleException;

abstract class AbstractClient
{
    protected const PARTNER_IDENTIFIER = '456dadab-3085-4fa3-bf2b-a2efd01c3593';

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

    protected function post(string $uri, array $options): ?array
    {
        try {
            $response = $this->client->post($uri, $options)->getBody()->getContents();
        } catch (RequestException $requestException) {
            throw $this->handleRequestException($requestException, $options);
        }

        return $this->decodeJsonResponse($response);
    }

    protected function get(string $uri, array $options = []): ?array
    {
        try {
            $response = $this->client->get($uri, $options)->getBody()->getContents();
        } catch (RequestException $requestException) {
            throw $this->handleRequestException($requestException, $options);
        }

        return $this->decodeJsonResponse($response);
    }

    protected function put(string $uri, array $options): ?array
    {
        try {
            $response = $this->client->put($uri, $options)->getBody()->getContents();
        } catch (RequestException $requestException) {
            throw $this->handleRequestException($requestException, $options);
        }

        return $this->decodeJsonResponse($response);
    }

    protected function delete(string $uri, array $options = []): ?array
    {
        try {
            $response = $this->client->delete($uri, $options)->getBody()->getContents();
        } catch (RequestException $requestException) {
            throw $this->handleRequestException($requestException, $options);
        }

        return $this->decodeJsonResponse($response);
    }

    protected function handleRequestException(RequestException $requestException, ?array $data): IZettleException
    {
        $exceptionMessage = $requestException->getMessage();
        $exceptionResponse = $requestException->getResponse();

        if ($exceptionResponse === null) {
            $this->logger->error($exceptionMessage, [$data]);

            return new IZettleException('General Error', $exceptionMessage, (int) $requestException->getCode());
        }

        $error = \json_decode($exceptionResponse->getBody()->getContents(), true);

        if ($error === null) {
            throw $requestException;
        }

        return $this->handleError($requestException, $error);
    }

    abstract protected function handleError(RequestException $requestException, array $error): IZettleException;

    private function decodeJsonResponse(string $response): ?array
    {
        return \json_decode($response, true);
    }
}
