<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\OAuthCredentials;
use Swag\PayPal\RestApi\V1\Api\Payment\Payer\ExecutePayerInfo;
use Swag\PayPal\RestApi\V1\RequestUriV1;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Refund;
use Swag\PayPal\RestApi\V2\RequestUriV2;
use Swag\PayPal\Test\Checkout\ExpressCheckout\SalesChannel\ExpressPrepareCheckoutRouteTest;
use Swag\PayPal\Test\Checkout\Payment\PayPalPaymentHandlerTest;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\CaptureAuthorizationResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\CaptureOrdersResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\CreateResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\CreateTokenResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\ExecutePaymentAuthorizeResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\ExecutePaymentOrderResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\ExecutePaymentSaleResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\ExecutePuiResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetDispute;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetDisputesList;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetPaymentAuthorizeResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetPaymentCapturedOrderResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetPaymentOrderResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetPaymentSaleResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetPaymentSaleWithRefundResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetResourceAuthorizeResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetResourceOrderResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetResourceSaleResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\RefundCaptureResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\RefundSaleResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\VoidAuthorizationResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\VoidOrderResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\AuthorizeOrderAuthorization;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CaptureAuthorization;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CaptureOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CreateOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetAuthorization;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetCapturedOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetRefund;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetRefundedOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\RefundCapture;
use Swag\PayPal\Test\RestApi\V1\Resource\PaymentResourceTest;
use Swag\PayPal\Test\RestApi\V1\Resource\WebhookResourceTest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

class GuzzleClientMock implements ClientInterface
{
    public const GENERAL_CLIENT_EXCEPTION_MESSAGE = 'generalClientExceptionMessage';
    public const CLIENT_EXCEPTION_MESSAGE_WITH_RESPONSE = 'clientExceptionWithoutResponse';

    public const GET_WEBHOOK_URL = 'testWebhookUrl';
    public const TEST_WEBHOOK_ID = 'testWebhookId';

    /**
     * @var array
     */
    private $data;

    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;

        // Add the default user-agent header.
        if (!isset($this->config['headers'])) {
            $this->config['headers'] = ['User-Agent' => Utils::defaultUserAgent()];
        } else {
            // Add the User-Agent header if one was not already set.
            foreach (\array_keys($this->config['headers']) as $name) {
                if (\is_string($name) && \mb_strtolower($name) === 'user-agent') {
                    return;
                }
            }
            $this->config['headers']['User-Agent'] = Utils::defaultUserAgent();
        }
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
                return new Response(200, [], $this->handlePostRequests((string) $uri, $options['json'] ?? null));
            case 'patch':
                return new Response(200, [], $this->handlePatchRequests((string) $uri, $options['json']));
            case 'delete':
                $this->handleDeleteRequests((string) $uri);

                return new Response(204);
            default:
                throw new MethodNotAllowedException(['get', 'post', 'patch', 'delete']);
        }
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
    }

    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
    }

    public function requestAsync(string $method, $uri, array $options = []): PromiseInterface
    {
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
        if (\mb_strpos($resourceUri, 'v1') === 0) {
            $response = $this->handleApiV1GetRequests($resourceUri);
        } elseif (\mb_strpos($resourceUri, 'v2') === 0) {
            $response = $this->handleApiV2GetRequests($resourceUri);
        }

        if (!isset($response)) {
            throw new \RuntimeException('No fixture defined for ' . $resourceUri);
        }

        return $this->ensureValidJson($response);
    }

    private function handleApiV1GetRequests(string $resourceUri): array
    {
        if (\mb_strpos($resourceUri, RequestUriV1::WEBHOOK_RESOURCE) !== false) {
            return $this->handleWebhookGetRequests($resourceUri);
        }

        if (\mb_strpos($resourceUri, RequestUriV1::PAYMENT_RESOURCE) !== false) {
            return $this->handlePaymentGetRequests($resourceUri);
        }

        if (\mb_strpos($resourceUri, RequestUriV1::AUTHORIZATION_RESOURCE) !== false) {
            return GetResourceAuthorizeResponseFixture::get();
        }

        if (\mb_strpos($resourceUri, RequestUriV1::CAPTURE_RESOURCE) !== false) {
            return CaptureAuthorizationResponseFixture::get();
        }

        if (\mb_strpos($resourceUri, RequestUriV1::ORDERS_RESOURCE) !== false) {
            return GetResourceOrderResponseFixture::get();
        }

        if (\mb_strpos($resourceUri, RequestUriV1::SALE_RESOURCE) !== false) {
            return GetResourceSaleResponseFixture::get();
        }

        if (\mb_strpos($resourceUri, 'customer/partners/') !== false) {
            return [
                'client_id' => ConstantsForTesting::VALID_CLIENT_ID,
                'client_secret' => ConstantsForTesting::VALID_CLIENT_SECRET,
            ];
        }

        if (\mb_strpos($resourceUri, RequestUriV1::DISPUTES_RESOURCE) !== false) {
            if (\mb_strpos($resourceUri, '/PP-') !== false) {
                return GetDispute::get();
            }

            return GetDisputesList::get();
        }

        throw new \RuntimeException('No fixture defined for ' . $resourceUri);
    }

    private function handleApiV2GetRequests(string $resourceUri): array
    {
        if (\mb_strpos($resourceUri, RequestUriV2::ORDERS_RESOURCE) !== false) {
            if (\mb_substr($resourceUri, -17) === GetCapturedOrderCapture::ID) {
                return GetCapturedOrderCapture::get();
            }

            if (\mb_substr($resourceUri, -17) === GetRefundedOrderCapture::ID) {
                return GetRefundedOrderCapture::get();
            }

            $orderCapture = GetOrderCapture::get();
            if (\mb_strpos($resourceUri, ExpressPrepareCheckoutRouteTest::TEST_PAYMENT_ID_WITHOUT_STATE) !== false) {
                $orderCapture['payer']['address']['admin_area_1'] = null;
            }

            if (\mb_strpos($resourceUri, ExpressPrepareCheckoutRouteTest::TEST_PAYMENT_ID_WITH_COUNTRY_WITHOUT_STATES) !== false) {
                $orderCapture['payer']['address']['country_code'] = 'NL';
            }

            if (\mb_strpos($resourceUri, ExpressPrepareCheckoutRouteTest::TEST_PAYMENT_ID_WITH_STATE_NOT_FOUND) !== false) {
                $orderCapture['payer']['address']['admin_area_1'] = 'XY';
            }

            return $orderCapture;
        }

        if (\mb_strpos($resourceUri, RequestUriV2::CAPTURES_RESOURCE) !== false) {
            return GetCapture::get();
        }

        if (\mb_strpos($resourceUri, RequestUriV2::REFUNDS_RESOURCE) !== false) {
            return GetRefund::get();
        }

        if (\mb_strpos($resourceUri, RequestUriV2::AUTHORIZATIONS_RESOURCE) !== false) {
            return GetAuthorization::get();
        }

        throw new \RuntimeException('No fixture defined for ' . $resourceUri);
    }

    private function handlePaymentGetRequests(string $resourceUri): array
    {
        if (\mb_strpos($resourceUri, PaymentResourceTest::ORDER_PAYMENT_ID) !== false) {
            return GetPaymentOrderResponseFixture::get();
        }

        if (\mb_strpos($resourceUri, PaymentResourceTest::CAPTURED_ORDER_PAYMENT_ID) !== false) {
            return GetPaymentCapturedOrderResponseFixture::get();
        }

        if (\mb_strpos($resourceUri, PaymentResourceTest::AUTHORIZE_PAYMENT_ID) !== false) {
            return GetPaymentAuthorizeResponseFixture::get();
        }

        if (\mb_strpos($resourceUri, PaymentResourceTest::SALE_WITH_REFUND_PAYMENT_ID) !== false) {
            return GetPaymentSaleWithRefundResponseFixture::get();
        }

        return GetPaymentSaleResponseFixture::get();
    }

    /**
     * @throws ClientException
     */
    private function handleWebhookGetRequests(string $resourceUri): array
    {
        if (\mb_strpos($resourceUri, WebhookResourceTest::THROW_EXCEPTION_WITH_RESPONSE) !== false) {
            throw $this->createClientExceptionWithResponse();
        }

        if (\mb_strpos($resourceUri, WebhookResourceTest::THROW_EXCEPTION_INVALID_ID) !== false) {
            throw $this->createClientExceptionWithInvalidId();
        }

        return ['url' => self::GET_WEBHOOK_URL];
    }

    /**
     * @throws ClientException
     * @throws \RuntimeException
     */
    private function handlePostRequests(string $resourceUri, ?PayPalApiStruct $data): string
    {
        if (\mb_strpos($resourceUri, 'v1') === 0) {
            $response = $this->handleApiV1PostRequests($resourceUri, $data);
        } elseif (\mb_strpos($resourceUri, 'v2') === 0) {
            $response = $this->handleApiV2PostRequests($resourceUri, $data);
        }

        if (!isset($response)) {
            throw new \RuntimeException('No fixture defined for ' . $resourceUri);
        }

        return $this->ensureValidJson($response);
    }

    private function handleApiV1PostRequests(string $resourceUri, ?PayPalApiStruct $data): array
    {
        if ($resourceUri === RequestUriV1::TOKEN_RESOURCE) {
            $headers = $this->getConfig()['headers'];
            if (isset($headers['Authorization'])) {
                $authHeader = $headers['Authorization'];
                $validClientIdInvalidSecret = $this->getAuthenticationHeader(ConstantsForTesting::VALID_CLIENT_ID, ConstantsForTesting::INVALID_CLIENT_SECRET);
                $invalidClientIdInvalidSecret = $this->getAuthenticationHeader(ConstantsForTesting::INVALID_CLIENT_ID, ConstantsForTesting::INVALID_CLIENT_SECRET);
                $invalidClientIdValidSecret = $this->getAuthenticationHeader(ConstantsForTesting::INVALID_CLIENT_ID, ConstantsForTesting::VALID_CLIENT_SECRET);
                if ($authHeader === $validClientIdInvalidSecret || $authHeader === $invalidClientIdInvalidSecret) {
                    throw $this->createClientExceptionWithResponse();
                }
                if ($authHeader === $invalidClientIdValidSecret) {
                    throw $this->createClientExceptionWithResponse(SymfonyResponse::HTTP_UNAUTHORIZED);
                }
            }

            return CreateTokenResponseFixture::get();
        }

        if (\mb_strpos($resourceUri, RequestUriV1::PAYMENT_RESOURCE) !== false) {
            $dataJson = $this->ensureValidJson($data);
            $dataArray = \json_decode($dataJson, true);
            if (isset($dataArray['transactions'][0]['invoice_number']) && $dataArray['transactions'][0]['invoice_number'] === ConstantsForTesting::PAYPAL_RESOURCE_THROWS_EXCEPTION_WITH_PREFIX) {
                throw new \RuntimeException('A PayPal test error occurred.');
            }

            if (\mb_substr($resourceUri, -8) === '/execute') {
                if (($data instanceof ExecutePayerInfo) && $data->getPayerId() === ConstantsForTesting::PAYPAL_RESOURCE_THROWS_EXCEPTION) {
                    throw new \RuntimeException('A PayPal test error occurred.');
                }
                if ($data === null) {
                    throw new \RuntimeException('Execute requests needs valid ExecutePayerInfo struct');
                }

                return $this->handlePaymentExecuteRequests($data);
            }

            return CreateResponseFixture::get();
        }

        if (\mb_strpos($resourceUri, RequestUriV1::WEBHOOK_RESOURCE) !== false) {
            if ($data === null) {
                throw new \RuntimeException('Create webhook request needs valid Webhook struct');
            }

            return $this->handleWebhookCreateRequests($data);
        }

        if (\mb_strpos($resourceUri, RequestUriV1::SALE_RESOURCE) !== false && \mb_substr($resourceUri, -7) === '/refund') {
            return RefundSaleResponseFixture::get();
        }

        if (\mb_strpos($resourceUri, RequestUriV1::CAPTURE_RESOURCE) !== false && \mb_substr($resourceUri, -7) === '/refund') {
            return RefundCaptureResponseFixture::get();
        }

        if (\mb_strpos($resourceUri, RequestUriV1::AUTHORIZATION_RESOURCE) !== false && \mb_substr($resourceUri, -8) === '/capture') {
            return CaptureAuthorizationResponseFixture::get();
        }

        if (\mb_strpos($resourceUri, RequestUriV1::AUTHORIZATION_RESOURCE) !== false && \mb_substr($resourceUri, -5) === '/void') {
            return VoidAuthorizationResponseFixture::get();
        }

        if (\mb_strpos($resourceUri, RequestUriV1::ORDERS_RESOURCE) !== false && \mb_substr($resourceUri, -8) === '/capture') {
            return CaptureOrdersResponseFixture::get();
        }

        if (\mb_strpos($resourceUri, RequestUriV1::ORDERS_RESOURCE) !== false && \mb_substr($resourceUri, -8) === '/do-void') {
            return VoidOrderResponseFixture::get();
        }

        throw new \RuntimeException('No fixture defined for ' . $resourceUri);
    }

    private function handleApiV2PostRequests(string $resourceUri, ?PayPalApiStruct $data): array
    {
        if (\mb_strpos($resourceUri, RequestUriV2::ORDERS_RESOURCE) !== false) {
            if ($data instanceof Order && $data->getPurchaseUnits()[0]->getInvoiceId() === ConstantsForTesting::PAYPAL_RESOURCE_THROWS_EXCEPTION) {
                throw new \RuntimeException('A PayPal test error occurred.');
            }

            if (\mb_strpos($resourceUri, PayPalPaymentHandlerTest::PAYPAL_ORDER_ID_DUPLICATE_ORDER_NUMBER) !== false
                && CaptureOrderCapture::isDuplicateOrderNumber()) {
                CaptureOrderCapture::setDuplicateOrderNumber(false);

                throw $this->createClientExceptionDuplicateOrderNumber();
            }

            if (\mb_substr($resourceUri, -8) === '/capture') {
                return CaptureOrderCapture::get();
            }

            if (\mb_substr($resourceUri, -10) === '/authorize') {
                return AuthorizeOrderAuthorization::get();
            }

            $response = CreateOrderCapture::get();
            if ($data instanceof Order && $data->getPurchaseUnits()[0]->getInvoiceId() === ConstantsForTesting::PAYPAL_RESPONSE_HAS_NO_APPROVAL_URL) {
                $links = $response['links'];
                unset($links[1]);
                $links = \array_values($links);
                $response['links'] = $links;
            }

            return $response;
        }

        if (\mb_strpos($resourceUri, RequestUriV2::CAPTURES_RESOURCE) !== false) {
            $refundedCapture = RefundCapture::get();
            if ($data instanceof Refund) {
                $amount = $data->getAmount();
                if ($amount !== null) {
                    $refundedCapture['seller_payable_breakdown']['total_refunded_amount']['value'] = $amount->getValue();
                }

                $refundedCapture['invoice_id'] = null;
                if ($data->getInvoiceId() !== null) {
                    $refundedCapture['invoice_id'] = $data->getInvoiceId();
                }

                $refundedCapture['note_to_payer'] = null;
                if ($data->getNoteToPayer() !== null) {
                    $refundedCapture['note_to_payer'] = $data->getNoteToPayer();
                }
            }

            return $refundedCapture;
        }

        if (\mb_strpos($resourceUri, RequestUriV2::AUTHORIZATIONS_RESOURCE) !== false) {
            if (\mb_substr($resourceUri, -5) === '/void') {
                return [];
            }

            if (\mb_substr($resourceUri, -8) === '/capture') {
                return CaptureAuthorization::get();
            }
        }

        throw new \RuntimeException('No fixture defined for ' . $resourceUri);
    }

    private function handlePaymentExecuteRequests(PayPalApiStruct $data): array
    {
        /** @var ExecutePayerInfo $payerInfo */
        $payerInfo = $data;
        if ($payerInfo->getPayerId() === ConstantsForTesting::PAYER_ID_PAYMENT_AUTHORIZE) {
            return ExecutePaymentAuthorizeResponseFixture::get();
        }

        if ($payerInfo->getPayerId() === ConstantsForTesting::PAYER_ID_PAYMENT_ORDER) {
            return ExecutePaymentOrderResponseFixture::get();
        }

        if ($payerInfo->getPayerId() === ConstantsForTesting::PAYER_ID_PAYMENT_PUI) {
            return ExecutePuiResponseFixture::get();
        }

        if (ExecutePaymentSaleResponseFixture::isDuplicateTransaction()) {
            ExecutePaymentSaleResponseFixture::setDuplicateTransaction(false);

            throw $this->createClientExceptionDuplicateTransaction();
        }

        $response = ExecutePaymentSaleResponseFixture::get();
        if ($payerInfo->getPayerId() !== PayPalPaymentHandlerTest::PAYER_ID_PAYMENT_INCOMPLETE) {
            return $response;
        }

        $response['transactions'][0]['related_resources'][0]['sale']['state'] = 'denied';

        return $response;
    }

    /**
     * @throws ClientException
     */
    private function handleWebhookCreateRequests(PayPalApiStruct $data): array
    {
        $createWebhookJson = \json_encode($data);
        if ($createWebhookJson && \mb_strpos($createWebhookJson, WebhookResourceTest::TEST_URL) !== false) {
            throw $this->createClientExceptionWithResponse();
        }

        if ($createWebhookJson && \mb_strpos($createWebhookJson, WebhookResourceTest::TEST_URL_ALREADY_EXISTS) !== false) {
            throw $this->createClientExceptionWebhookAlreadyExists();
        }

        return ['id' => self::TEST_WEBHOOK_ID];
    }

    /**
     * @throws ClientException
     */
    private function handlePatchRequests(string $resourceUri, array $data): string
    {
        if (\mb_strpos($resourceUri, WebhookResourceTest::THROW_EXCEPTION_INVALID_ID) !== false) {
            throw $this->createClientExceptionWithInvalidId();
        }

        if (\mb_strpos($resourceUri, self::TEST_WEBHOOK_ID) !== false) {
            throw $this->createClientExceptionWithResponse();
        }

        if (\mb_strpos($resourceUri, PayPalPaymentHandlerTest::PAYPAL_PATCH_THROWS_EXCEPTION) !== false) {
            throw $this->createClientExceptionWithResponse();
        }

        $this->data = $data;

        return $this->ensureValidJson([]);
    }

    /**
     * @throws ClientException
     */
    private function handleDeleteRequests(string $resourceUri): void
    {
        if (\mb_strpos($resourceUri, WebhookResourceTest::THROW_EXCEPTION_INVALID_ID) !== false) {
            throw $this->createClientExceptionWithInvalidId();
        }
    }

    private function getAuthenticationHeader(string $restId, string $restSecret): string
    {
        $validOauth = new OAuthCredentials();
        $validOauth->setRestId($restId);
        $validOauth->setRestSecret($restSecret);

        return (string) $validOauth;
    }

    private function createClientExceptionWithResponse(int $errorCode = SymfonyResponse::HTTP_BAD_REQUEST): ClientException
    {
        $jsonString = (string) \json_encode([
            'name' => 'TEST',
            'message' => self::GENERAL_CLIENT_EXCEPTION_MESSAGE,
        ]);

        return $this->createClientExceptionFromResponseString($jsonString, $errorCode);
    }

    private function createClientExceptionDuplicateTransaction(): ClientException
    {
        $jsonString = $this->ensureValidJson([
            'name' => 'DUPLICATE_TRANSACTION',
            'message' => 'Duplicate invoice Id detected.',
        ]);

        return $this->createClientExceptionFromResponseString($jsonString, SymfonyResponse::HTTP_BAD_REQUEST);
    }

    private function createClientExceptionDuplicateOrderNumber(): ClientException
    {
        $jsonString = $this->ensureValidJson([
            'name' => 'UNPROCESSABLE_ENTITY',
            'message' => 'The requested action could not be performed, semantically incorrect, or failed business validation.: Duplicate Invoice ID detected. To avoid a potential duplicate transaction your account setting requires that Invoice Id be unique for each transaction. DUPLICATE_INVOICE_ID',
        ]);

        return $this->createClientExceptionFromResponseString($jsonString, SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function createClientExceptionWithInvalidId(): ClientException
    {
        $jsonString = (string) \json_encode([
            'name' => 'INVALID_RESOURCE_ID',
            'message' => self::GENERAL_CLIENT_EXCEPTION_MESSAGE,
        ]);

        return $this->createClientExceptionFromResponseString($jsonString);
    }

    private function createClientExceptionWebhookAlreadyExists(): ClientException
    {
        $jsonString = (string) \json_encode([
            'name' => 'WEBHOOK_URL_ALREADY_EXISTS',
            'message' => self::GENERAL_CLIENT_EXCEPTION_MESSAGE,
        ]);

        return $this->createClientExceptionFromResponseString($jsonString);
    }

    private function createClientExceptionFromResponseString(string $jsonString, int $errorCode = SymfonyResponse::HTTP_BAD_REQUEST): ClientException
    {
        return new ClientException(
            self::CLIENT_EXCEPTION_MESSAGE_WITH_RESPONSE,
            new Request('TEST', ''),
            new Response($errorCode, [], $jsonString)
        );
    }

    /**
     * @param array|PayPalApiStruct|null $data
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
}
