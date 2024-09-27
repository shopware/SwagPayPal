<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Exception\PosException;

#[Package('checkout')]
abstract class AbstractClient
{
    protected ClientInterface $client;

    protected LoggerInterface $logger;

    public function __construct(
        ClientInterface $client,
        LoggerInterface $logger,
    ) {
        $this->client = $client;
        $this->logger = $logger;
    }

    protected function post(string $uri, array $options): ?array
    {
        try {
            $response = $this->client->request('post', $uri, $options)->getBody()->getContents();
        } catch (RequestException $requestException) {
            throw $this->handleRequestException($requestException, $options);
        }

        return $this->decodeJsonResponse($response);
    }

    protected function get(string $uri, array $options = []): ?array
    {
        try {
            $response = $this->client->request('get', $uri, $options)->getBody()->getContents();
        } catch (RequestException $requestException) {
            throw $this->handleRequestException($requestException, $options);
        }

        return $this->decodeJsonResponse($response);
    }

    protected function put(string $uri, array $options): ?array
    {
        try {
            $response = $this->client->request('put', $uri, $options)->getBody()->getContents();
        } catch (RequestException $requestException) {
            throw $this->handleRequestException($requestException, $options);
        }

        return $this->decodeJsonResponse($response);
    }

    protected function delete(string $uri, array $options = []): ?array
    {
        try {
            $response = $this->client->request('delete', $uri, $options)->getBody()->getContents();
        } catch (RequestException $requestException) {
            throw $this->handleRequestException($requestException, $options);
        }

        return $this->decodeJsonResponse($response);
    }

    protected function handleRequestException(RequestException $requestException, ?array $data): PosException
    {
        $exceptionMessage = $requestException->getMessage();
        $exceptionResponse = $requestException->getResponse();

        if ($exceptionResponse === null) {
            $this->logger->error($exceptionMessage, [$data]);

            return new PosException('General Error', $exceptionMessage, (int) $requestException->getCode());
        }

        $error = \json_decode($exceptionResponse->getBody()->getContents(), true);

        if ($error === null) {
            throw $requestException;
        }

        return $this->handleError($requestException, $error);
    }

    abstract protected function handleError(RequestException $requestException, array $error): PosException;

    private function decodeJsonResponse(string $response): ?array
    {
        return \json_decode($response, true);
    }
}
