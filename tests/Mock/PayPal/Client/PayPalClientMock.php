<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPal\Client;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Monolog\Logger;
use Swag\PayPal\PayPal\Api\Common\PayPalStruct;
use Swag\PayPal\PayPal\Api\Payment\Payer\PayerInfo;
use Swag\PayPal\PayPal\Client\PayPalClient;
use Swag\PayPal\PayPal\PartnerAttributionId;
use Swag\PayPal\PayPal\Resource\TokenResource;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\CaptureAuthorizationResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\CaptureOrdersResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\CreateResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\ExecuteAuthorizeResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\ExecuteOrderResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\ExecutePuiResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\ExecuteSaleResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\GetAuthorizeResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\GetCapturedOrderResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\GetOrderResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\GetSaleResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\GetSaleWithRefundResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\RefundCaptureResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\RefundSaleResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\VoidAuthorizationResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\VoidOrderResponseFixture;
use Swag\PayPal\Test\Payment\PayPalPaymentHandlerTest;
use Swag\PayPal\Test\PayPal\Resource\PaymentResourceTest;
use Swag\PayPal\Test\PayPal\Resource\WebhookResourceTest;

class PayPalClientMock extends PayPalClient
{
    public const GET_WEBHOOK_URL = 'testWebhookUrl';

    public const TEST_WEBHOOK_ID = 'testWebhookId';

    public const CLIENT_EXCEPTION_MESSAGE_WITHOUT_RESPONSE = 'clientExceptionWithoutResponse';

    public const CLIENT_EXCEPTION_MESSAGE_WITH_RESPONSE = 'clientExceptionWithoutResponse';

    /**
     * @var array
     */
    private $data = [];

    public function __construct(
        TokenResource $tokenResource,
        SwagPayPalSettingStruct $settings,
        string $partnerAttributionId = PartnerAttributionId::PAYPAL_CLASSIC
    ) {
        parent::__construct($tokenResource, $settings, new Logger('testLogger'), $partnerAttributionId);
    }

    public function sendGetRequest(string $resourceUri): array
    {
        if (strncmp($resourceUri, 'notifications/webhooks/', 23) === 0) {
            return $this->handleWebhookGetRequests($resourceUri);
        }

        if (strncmp($resourceUri, 'payments/payment/', 17) === 0) {
            return $this->handlePaymentGetRequests($resourceUri);
        }

        return [];
    }

    public function sendPostRequest(string $resourceUri, PayPalStruct $data): array
    {
        if (mb_substr($resourceUri, -8) === '/execute') {
            return $this->handlePaymentExecuteRequests($data);
        }

        if (mb_substr($resourceUri, -22) === 'notifications/webhooks') {
            return $this->handleWebhookCreateRequests($data);
        }

        if (strncmp($resourceUri, 'payments/sale/', 14) === 0 && mb_substr($resourceUri, -7) === '/refund') {
            return RefundSaleResponseFixture::get();
        }

        if (strncmp($resourceUri, 'payments/capture/', 17) === 0 && mb_substr($resourceUri, -7) === '/refund') {
            return RefundCaptureResponseFixture::get();
        }

        if (strncmp($resourceUri, 'payments/authorization/', 23) === 0 && mb_substr($resourceUri, -8) === '/capture') {
            return CaptureAuthorizationResponseFixture::get();
        }

        if (strncmp($resourceUri, 'payments/authorization/', 23) === 0 && mb_substr($resourceUri, -5) === '/void') {
            return VoidAuthorizationResponseFixture::get();
        }

        if (strncmp($resourceUri, 'payments/orders/', 16) === 0 && mb_substr($resourceUri, -8) === '/capture') {
            return CaptureOrdersResponseFixture::get();
        }

        if (strncmp($resourceUri, 'payments/orders/', 16) === 0 && mb_substr($resourceUri, -8) === '/do-void') {
            return VoidOrderResponseFixture::get();
        }

        return CreateResponseFixture::get();
    }

    public function sendPatchRequest(string $resourceUri, array $data): array
    {
        if (strpos($resourceUri, WebhookResourceTest::THROW_EXCEPTION_INVALID_ID) !== false) {
            throw $this->createClientExceptionWithInvalidId();
        }

        if (strpos($resourceUri, WebhookResourceTest::WEBHOOK_ID) !== false) {
            throw $this->createClientExceptionWithResponse();
        }

        $this->data = $data;

        return [];
    }

    public function getData(): array
    {
        return $this->data;
    }

    private function handleWebhookGetRequests(string $resourceUri): array
    {
        if (strpos($resourceUri, WebhookResourceTest::THROW_EXCEPTION_WITHOUT_RESPONSE) !== false) {
            throw $this->createClientException();
        }

        if (strpos($resourceUri, WebhookResourceTest::THROW_EXCEPTION_WITH_RESPONSE) !== false) {
            throw $this->createClientExceptionWithResponse();
        }

        if (strpos($resourceUri, WebhookResourceTest::THROW_EXCEPTION_INVALID_ID) !== false) {
            throw $this->createClientExceptionWithInvalidId();
        }

        return ['url' => self::GET_WEBHOOK_URL];
    }

    private function handlePaymentGetRequests(string $resourceUri): array
    {
        if (strpos($resourceUri, PaymentResourceTest::ORDER_PAYMENT_ID) !== false) {
            return GetOrderResponseFixture::get();
        }

        if (strpos($resourceUri, PaymentResourceTest::CAPTURED_ORDER_PAYMENT_ID) !== false) {
            return GetCapturedOrderResponseFixture::get();
        }

        if (strpos($resourceUri, PaymentResourceTest::AUTHORIZE_PAYMENT_ID) !== false) {
            return GetAuthorizeResponseFixture::get();
        }

        if (strpos($resourceUri, PaymentResourceTest::SALE_WITH_REFUND_PAYMENT_ID) !== false) {
            return GetSaleWithRefundResponseFixture::get();
        }

        return GetSaleResponseFixture::get();
    }

    private function handlePaymentExecuteRequests(PayPalStruct $data): array
    {
        /** @var PayerInfo $payerInfo */
        $payerInfo = $data;
        if ($payerInfo->getPayerId() === ConstantsForTesting::PAYER_ID_PAYMENT_AUTHORIZE) {
            return ExecuteAuthorizeResponseFixture::get();
        }

        if ($payerInfo->getPayerId() === ConstantsForTesting::PAYER_ID_PAYMENT_ORDER) {
            return ExecuteOrderResponseFixture::get();
        }

        if ($payerInfo->getPayerId() === ConstantsForTesting::PAYER_ID_PAYMENT_PUI) {
            return ExecutePuiResponseFixture::get();
        }

        $response = ExecuteSaleResponseFixture::get();
        if ($payerInfo->getPayerId() !== PayPalPaymentHandlerTest::PAYER_ID_PAYMENT_INCOMPLETE) {
            return $response;
        }

        $response['transactions'][0]['related_resources'][0]['sale']['state'] = 'denied';

        return $response;
    }

    private function handleWebhookCreateRequests(PayPalStruct $data): array
    {
        $createWebhookJson = json_encode($data);
        if ($createWebhookJson && strpos($createWebhookJson, WebhookResourceTest::TEST_URL) !== false) {
            throw $this->createClientExceptionWithResponse();
        }

        if ($createWebhookJson && strpos($createWebhookJson, WebhookResourceTest::TEST_URL_ALREADY_EXISTS) !== false) {
            throw $this->createClientExceptionWebhookAlreadyExists();
        }

        return ['id' => self::TEST_WEBHOOK_ID];
    }

    private function createClientException(): ClientException
    {
        return new ClientException(self::CLIENT_EXCEPTION_MESSAGE_WITHOUT_RESPONSE, new Request('', ''));
    }

    private function createClientExceptionWithResponse(): ClientException
    {
        $jsonString = (string) json_encode(['foo' => 'bar']);

        return $this->createClientExceptionFromResponseString($jsonString);
    }

    private function createClientExceptionWithInvalidId(): ClientException
    {
        $jsonString = (string) json_encode(['name' => 'INVALID_RESOURCE_ID']);

        return $this->createClientExceptionFromResponseString($jsonString);
    }

    private function createClientExceptionWebhookAlreadyExists(): ClientException
    {
        $jsonString = (string) json_encode(['name' => 'WEBHOOK_URL_ALREADY_EXISTS']);

        return $this->createClientExceptionFromResponseString($jsonString);
    }

    private function createClientExceptionFromResponseString(string $jsonString): ClientException
    {
        return new ClientException(
            self::CLIENT_EXCEPTION_MESSAGE_WITH_RESPONSE,
            new Request('TEST', ''),
            new Response(200, [], $jsonString)
        );
    }
}
