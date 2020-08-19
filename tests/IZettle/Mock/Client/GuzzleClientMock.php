<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Mock\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Swag\PayPal\IZettle\Api\Common\IZettleStruct;
use Swag\PayPal\IZettle\Api\IZettleRequestUri;
use Swag\PayPal\Test\IZettle\ConstantsForTesting;
use Swag\PayPal\Test\IZettle\Mock\Client\_fixtures\ChangeBulkInventoryFixture;
use Swag\PayPal\Test\IZettle\Mock\Client\_fixtures\CreateProductFixture;
use Swag\PayPal\Test\IZettle\Mock\Client\_fixtures\CreateTokenResponseFixture;
use Swag\PayPal\Test\IZettle\Mock\Client\_fixtures\DeleteProductsFixture;
use Swag\PayPal\Test\IZettle\Mock\Client\_fixtures\FetchInformationResponseFixture;
use Swag\PayPal\Test\IZettle\Mock\Client\_fixtures\GetInventoryFixture;
use Swag\PayPal\Test\IZettle\Mock\Client\_fixtures\GetInventoryLocationsFixture;
use Swag\PayPal\Test\IZettle\Mock\Client\_fixtures\GetProductCountFixture;
use Swag\PayPal\Test\IZettle\Mock\Client\_fixtures\GetProductsFixture;
use Swag\PayPal\Test\IZettle\Mock\Client\_fixtures\UpdateProductFixture;
use Swag\PayPal\Test\IZettle\Mock\Client\_fixtures\WebhookRegisterFixture;
use Swag\PayPal\Test\IZettle\Mock\Client\_fixtures\WebhookUnregisterFixture;
use Swag\PayPal\Test\IZettle\Mock\Client\_fixtures\WebhookUpdateFixture;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class GuzzleClientMock extends Client
{
    public const GENERAL_CLIENT_EXCEPTION_MESSAGE = 'generalClientExceptionMessage';

    /**
     * @throws ClientException
     */
    public function get(string $uri, array $options = []): ResponseInterface
    {
        return new Response(200, [], $this->handleGetRequests($uri));
    }

    /**
     * @throws ClientException
     * @throws \RuntimeException
     */
    public function post(string $uri, array $options = []): ResponseInterface
    {
        if ($uri === IZettleRequestUri::TOKEN_RESOURCE) {
            return $this->handleToken($options);
        }

        return new Response(200, [], $this->handlePostRequests($uri, $options['json'] ?? null));
    }

    /**
     * @throws ClientException
     */
    public function put(string $uri, array $options = []): ResponseInterface
    {
        return new Response(200, [], $this->handlePutRequests($uri, $options['json']));
    }

    /**
     * @throws ClientException
     */
    public function delete(string $uri, array $options = []): ResponseInterface
    {
        return new Response(200, [], $this->handleDeleteRequests($uri, $options['query'] ?? null));
    }

    /**
     * @throws ClientException
     */
    private function handleGetRequests(string $resourceUri): string
    {
        $response = [];
        if ($resourceUri === IZettleRequestUri::MERCHANT_INFORMATION) {
            $response = FetchInformationResponseFixture::get();
        } elseif ($resourceUri === IZettleRequestUri::INVENTORY_RESOURCE_LOCATIONS) {
            $response = GetInventoryLocationsFixture::get();
        } elseif ($resourceUri === \sprintf(IZettleRequestUri::INVENTORY_RESOURCE_GET, ConstantsForTesting::LOCATION_STORE)) {
            $response = GetInventoryFixture::get();
        } elseif ($resourceUri === IZettleRequestUri::PRODUCT_RESOURCE) {
            $response = GetProductsFixture::get();
        } elseif ($resourceUri === IZettleRequestUri::PRODUCT_RESOURCE_COUNT) {
            $response = GetProductCountFixture::get();
        }

        return $this->ensureValidJson($response);
    }

    /**
     * @throws ClientException
     * @throws \RuntimeException
     */
    private function handlePostRequests(string $resourceUri, ?IZettleStruct $data): ?string
    {
        $response = [];
        if ($resourceUri === IZettleRequestUri::INVENTORY_RESOURCE_BULK) {
            $response = ChangeBulkInventoryFixture::post($data);
        } elseif ($resourceUri === IZettleRequestUri::PRODUCT_RESOURCE) {
            $response = CreateProductFixture::post($data);
        } elseif ($resourceUri === IZettleRequestUri::SUBSCRIPTION_RESOURCE) {
            $response = WebhookRegisterFixture::post($data);
        }

        return $response === null ? null : $this->ensureValidJson($response);
    }

    /**
     * @throws ClientException
     */
    private function handlePutRequests(string $resourceUri, IZettleStruct $data): ?string
    {
        $response = [];
        if (\mb_strpos($resourceUri, IZettleRequestUri::PRODUCT_RESOURCE_V2) !== false) {
            $response = UpdateProductFixture::put($data);
        } elseif (\mb_strpos($resourceUri, IZettleRequestUri::SUBSCRIPTION_RESOURCE) !== false) {
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
        if (\mb_strpos($resourceUri, IZettleRequestUri::PRODUCT_RESOURCE) !== false) {
            if ($query !== null) {
                $response = DeleteProductsFixture::delete($query);
            }
        } elseif (\mb_strpos($resourceUri, IZettleRequestUri::SUBSCRIPTION_RESOURCE_DELETE) !== false) {
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
     * @param array|IZettleStruct|null $data
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
