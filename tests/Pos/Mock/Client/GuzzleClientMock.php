<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Common\PosStruct;
use Swag\PayPal\Pos\Api\PosRequestUri;
use Swag\PayPal\Test\Pos\ConstantsForTesting;
use Swag\PayPal\Test\Pos\Mock\Client\_fixtures\ChangeBulkInventoryFixture;
use Swag\PayPal\Test\Pos\Mock\Client\_fixtures\CreateProductFixture;
use Swag\PayPal\Test\Pos\Mock\Client\_fixtures\CreateTokenResponseFixture;
use Swag\PayPal\Test\Pos\Mock\Client\_fixtures\DeleteProductsFixture;
use Swag\PayPal\Test\Pos\Mock\Client\_fixtures\FetchInformationResponseFixture;
use Swag\PayPal\Test\Pos\Mock\Client\_fixtures\GetInventoryFixture;
use Swag\PayPal\Test\Pos\Mock\Client\_fixtures\GetInventoryLocationsFixture;
use Swag\PayPal\Test\Pos\Mock\Client\_fixtures\GetProductCountFixture;
use Swag\PayPal\Test\Pos\Mock\Client\_fixtures\GetProductsFixture;
use Swag\PayPal\Test\Pos\Mock\Client\_fixtures\UpdateProductFixture;
use Swag\PayPal\Test\Pos\Mock\Client\_fixtures\WebhookRegisterFixture;
use Swag\PayPal\Test\Pos\Mock\Client\_fixtures\WebhookUnregisterFixture;
use Swag\PayPal\Test\Pos\Mock\Client\_fixtures\WebhookUpdateFixture;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

/**
 * @internal
 */
#[Package('checkout')]
class GuzzleClientMock implements ClientInterface
{
    public const GENERAL_CLIENT_EXCEPTION_MESSAGE = 'generalClientExceptionMessage';

    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param string|UriInterface $uri
     *
     * @throws ClientException
     * @throws \RuntimeException
     */
    public function request(string $method, $uri, array $options = []): ResponseInterface
    {
        switch (\mb_strtolower($method)) {
            case 'get':
                return new Response(200, [], $this->handleGetRequests((string) $uri));
            case 'post':
                if ($uri === PosRequestUri::TOKEN_RESOURCE) {
                    return $this->handleToken($options);
                }

                return new Response(200, [], $this->handlePostRequests((string) $uri, $options['json'] ?? null));
            case 'put':
                return new Response(200, [], $this->handlePutRequests((string) $uri, $options['json']));
            case 'delete':
                return new Response(200, [], $this->handleDeleteRequests((string) $uri, $options['query'] ?? null));
            default:
                throw new MethodNotAllowedException(['get', 'post', 'put', 'delete']);
        }
    }

    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        return new Response();
    }

    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        return new Promise();
    }

    public function requestAsync(string $method, $uri, array $options = []): PromiseInterface
    {
        return new Promise();
    }

    public function getConfig(?string $option = null)
    {
        if ($option !== null) {
            if (isset($this->config[$option])) {
                return $this->config[$option];
            }

            return null;
        }

        return $this->config;
    }

    /**
     * @throws ClientException
     */
    private function handleGetRequests(string $resourceUri): string
    {
        $response = [];
        if ($resourceUri === PosRequestUri::MERCHANT_INFORMATION) {
            $response = FetchInformationResponseFixture::get();
        } elseif ($resourceUri === PosRequestUri::INVENTORY_RESOURCE_LOCATIONS) {
            $response = GetInventoryLocationsFixture::get();
        } elseif ($resourceUri === \sprintf(PosRequestUri::INVENTORY_RESOURCE_GET, ConstantsForTesting::LOCATION_STORE)) {
            $response = GetInventoryFixture::get();
        } elseif ($resourceUri === PosRequestUri::PRODUCT_RESOURCE) {
            $response = GetProductsFixture::get();
        } elseif ($resourceUri === PosRequestUri::PRODUCT_RESOURCE_COUNT) {
            $response = GetProductCountFixture::get();
        }

        return $this->ensureValidJson($response);
    }

    /**
     * @throws ClientException
     * @throws \RuntimeException
     */
    private function handlePostRequests(string $resourceUri, ?PosStruct $data): ?string
    {
        $response = [];
        if ($resourceUri === PosRequestUri::INVENTORY_RESOURCE_BULK) {
            $response = ChangeBulkInventoryFixture::post($data);
        } elseif ($resourceUri === PosRequestUri::PRODUCT_RESOURCE) {
            $response = CreateProductFixture::post($data);
        } elseif ($resourceUri === PosRequestUri::SUBSCRIPTION_RESOURCE) {
            $response = WebhookRegisterFixture::post($data);
        }

        return $response === null ? null : $this->ensureValidJson($response);
    }

    /**
     * @throws ClientException
     */
    private function handlePutRequests(string $resourceUri, PosStruct $data): ?string
    {
        $response = [];
        if (\mb_strpos($resourceUri, PosRequestUri::PRODUCT_RESOURCE_V2) !== false) {
            $response = UpdateProductFixture::put($data);
        } elseif (\mb_strpos($resourceUri, PosRequestUri::SUBSCRIPTION_RESOURCE) !== false) {
            $response = WebhookUpdateFixture::put($resourceUri, $data);
        }

        return $response === null ? null : $this->ensureValidJson($response);
    }

    /**
     * @throws ClientException
     */
    private function handleDeleteRequests(string $resourceUri, ?string $query): ?string
    {
        $response = [];
        if (\mb_strpos($resourceUri, PosRequestUri::PRODUCT_RESOURCE) !== false) {
            if ($query !== null) {
                $response = DeleteProductsFixture::delete($query);
            }
        } elseif (\mb_strpos($resourceUri, PosRequestUri::SUBSCRIPTION_RESOURCE_DELETE) !== false) {
            $response = WebhookUnregisterFixture::delete($resourceUri);
        }

        return $response === null ? null : $this->ensureValidJson($response);
    }

    private function createClientExceptionFromResponseString(string $jsonString, int $errorCode = SymfonyResponse::HTTP_BAD_REQUEST): ClientException
    {
        return new ClientException(
            self::GENERAL_CLIENT_EXCEPTION_MESSAGE,
            new Request('TEST', ''),
            new Response($errorCode, [], $jsonString)
        );
    }

    /**
     * @param array|PosStruct|null $data
     *
     * @throws \RuntimeException
     */
    private function ensureValidJson($data): string
    {
        $encodedData = \json_encode($data);
        if ($encodedData === false) {
            throw new \RuntimeException(\print_r($data, true) . ' could not be converted to valid JSON');
        }

        return $encodedData;
    }

    private function handleToken(array $options): ResponseInterface
    {
        if (isset($options['form_params'])) {
            $authHeader = $options['form_params'];
            if ($authHeader['assertion'] === ConstantsForTesting::INVALID_API_KEY) {
                throw $this->createClientExceptionFromResponseString($this->ensureValidJson(CreateTokenResponseFixture::getError()));
            }
        }

        $response = CreateTokenResponseFixture::get();

        return new Response(200, [], $this->ensureValidJson($response));
    }
}
