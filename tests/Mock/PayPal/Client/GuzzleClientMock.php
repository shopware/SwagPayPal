<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Swag\PayPal\PayPal\Api\Common\PayPalStruct;
use Swag\PayPal\PayPal\Api\OAuthCredentials;
use Swag\PayPal\PayPal\Api\Payment\Payer\ExecutePayerInfo;
use Swag\PayPal\PayPal\RequestUri;
use Swag\PayPal\Test\Checkout\ExpressCheckout\ExpressCheckoutControllerTest;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\CaptureAuthorizationResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\CaptureOrdersResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\CreateResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\CreateTokenResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\ExecutePaymentAuthorizeResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\ExecutePaymentOrderResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\ExecutePaymentSaleResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\ExecutePuiResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\GetPaymentAuthorizeResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\GetPaymentCapturedOrderResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\GetPaymentOrderResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\GetPaymentSaleResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\GetPaymentSaleWithRefundResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\GetResourceAuthorizeResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\GetResourceOrderResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\GetResourceSaleResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\RefundCaptureResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\RefundSaleResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\VoidAuthorizationResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\VoidOrderResponseFixture;
use Swag\PayPal\Test\Payment\PayPalPaymentHandlerTest;
use Swag\PayPal\Test\PayPal\Resource\PaymentResourceTest;
use Swag\PayPal\Test\PayPal\Resource\WebhookResourceTest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class GuzzleClientMock extends Client
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
        return new Response(200, [], $this->handlePostRequests($uri, $options['json'] ?? null));
    }

    /**
     * @throws ClientException
     */
    public function patch(string $uri, array $options = []): ResponseInterface
    {
        return new Response(200, [], $this->handlePatchRequests($uri, $options['json']));
    }

    /**
     * @throws ClientException
     */
    public function delete(string $uri, array $options = []): ResponseInterface
    {
        $this->handleDeleteRequests($uri);

        return new Response(204);
    }

    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @throws ClientException
     */
    private function handleGetRequests(string $resourceUri): string
    {
        $response = [];
        if (\strncmp($resourceUri, RequestUri::WEBHOOK_RESOURCE, 22) === 0) {
            $response = $this->handleWebhookGetRequests($resourceUri);
        }

        if (\strncmp($resourceUri, RequestUri::PAYMENT_RESOURCE, 16) === 0) {
            $response = $this->handlePaymentGetRequests($resourceUri);
            if (\mb_strpos($resourceUri, ExpressCheckoutControllerTest::TEST_PAYMENT_ID_WITHOUT_STATE) !== false) {
                $response['payer']['payer_info']['shipping_address']['state'] = null;
            }

            if (\mb_strpos($resourceUri, ExpressCheckoutControllerTest::TEST_PAYMENT_ID_WITH_COUNTRY_WITHOUT_STATES) !== false) {
                $response['payer']['payer_info']['shipping_address']['country_code'] = 'NL';
            }

            if (\mb_strpos($resourceUri, ExpressCheckoutControllerTest::TEST_PAYMENT_ID_WITH_STATE_NOT_FOUND) !== false) {
                $response['payer']['payer_info']['shipping_address']['state'] = 'XY';
            }
        }

        if (\strncmp($resourceUri, RequestUri::AUTHORIZATION_RESOURCE, 22) === 0) {
            $response = GetResourceAuthorizeResponseFixture::get();
        }

        if (\strncmp($resourceUri, RequestUri::CAPTURE_RESOURCE, 16) === 0) {
            $response = CaptureAuthorizationResponseFixture::get();
        }

        if (\strncmp($resourceUri, RequestUri::ORDERS_RESOURCE, 15) === 0) {
            $response = GetResourceOrderResponseFixture::get();
        }

        if (\strncmp($resourceUri, RequestUri::SALE_RESOURCE, 13) === 0) {
            $response = GetResourceSaleResponseFixture::get();
        }

        if (\strncmp($resourceUri, 'customer/partners/', 18) === 0) {
            $response = [
                'client_id' => ConstantsForTesting::VALID_CLIENT_ID,
                'client_secret' => ConstantsForTesting::VALID_CLIENT_SECRET,
            ];
        }

        return $this->ensureValidJson($response);
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
    private function handlePostRequests(string $resourceUri, ?PayPalStruct $data): string
    {
        $response = [];
        if ($resourceUri === RequestUri::TOKEN_RESOURCE) {
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

            $response = CreateTokenResponseFixture::get();
        }

        if (\strncmp($resourceUri, RequestUri::PAYMENT_RESOURCE, 16) === 0) {
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
                $response = $this->handlePaymentExecuteRequests($data);
            } else {
                $response = CreateResponseFixture::get();
            }
        }

        if (\mb_substr($resourceUri, -22) === RequestUri::WEBHOOK_RESOURCE) {
            if ($data === null) {
                throw new \RuntimeException('Create webhook request needs valid Webhook struct');
            }
            $response = $this->handleWebhookCreateRequests($data);
        }

        if (\strncmp($resourceUri, RequestUri::SALE_RESOURCE, 13) === 0 && \mb_substr($resourceUri, -7) === '/refund') {
            $response = RefundSaleResponseFixture::get();
        }

        if (\strncmp($resourceUri, RequestUri::CAPTURE_RESOURCE, 16) === 0 && \mb_substr($resourceUri, -7) === '/refund') {
            $response = RefundCaptureResponseFixture::get();
        }

        if (\strncmp($resourceUri, RequestUri::AUTHORIZATION_RESOURCE, 22) === 0 && \mb_substr($resourceUri, -8) === '/capture') {
            $response = CaptureAuthorizationResponseFixture::get();
        }

        if (\strncmp($resourceUri, RequestUri::AUTHORIZATION_RESOURCE, 22) === 0 && \mb_substr($resourceUri, -5) === '/void') {
            $response = VoidAuthorizationResponseFixture::get();
        }

        if (\strncmp($resourceUri, RequestUri::ORDERS_RESOURCE, 15) === 0 && \mb_substr($resourceUri, -8) === '/capture') {
            $response = CaptureOrdersResponseFixture::get();
        }

        if (\strncmp($resourceUri, RequestUri::ORDERS_RESOURCE, 15) === 0 && \mb_substr($resourceUri, -8) === '/do-void') {
            $response = VoidOrderResponseFixture::get();
        }

        return $this->ensureValidJson($response);
    }

    private function handlePaymentExecuteRequests(PayPalStruct $data): array
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
    private function handleWebhookCreateRequests(PayPalStruct $data): array
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
        $response = [];
        if (\mb_strpos($resourceUri, WebhookResourceTest::THROW_EXCEPTION_INVALID_ID) !== false) {
            throw $this->createClientExceptionWithInvalidId();
        }

        if (\mb_strpos($resourceUri, self::TEST_WEBHOOK_ID) !== false) {
            throw $this->createClientExceptionWithResponse();
        }

        $this->data = $data;

        return $this->ensureValidJson($response);
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
        $jsonString = (string) \json_encode(['name' => 'TEST', 'message' => self::GENERAL_CLIENT_EXCEPTION_MESSAGE]);

        return $this->createClientExceptionFromResponseString($jsonString, $errorCode);
    }

    private function createClientExceptionDuplicateTransaction(): ClientException
    {
        $jsonString = $this->ensureValidJson(['name' => 'DUPLICATE_TRANSACTION', 'message' => 'Duplicate invoice Id detected.']);

        return $this->createClientExceptionFromResponseString($jsonString, SymfonyResponse::HTTP_BAD_REQUEST);
    }

    private function createClientExceptionWithInvalidId(): ClientException
    {
        $jsonString = (string) \json_encode(['name' => 'INVALID_RESOURCE_ID', 'message' => self::GENERAL_CLIENT_EXCEPTION_MESSAGE]);

        return $this->createClientExceptionFromResponseString($jsonString);
    }

    private function createClientExceptionWebhookAlreadyExists(): ClientException
    {
        $jsonString = (string) \json_encode(['name' => 'WEBHOOK_URL_ALREADY_EXISTS', 'message' => self::GENERAL_CLIENT_EXCEPTION_MESSAGE]);

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
     * @param array|PayPalStruct|null $data
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
