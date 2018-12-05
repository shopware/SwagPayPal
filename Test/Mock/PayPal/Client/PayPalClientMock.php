<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Mock\PayPal\Client;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use SwagPayPal\PayPal\Api\Payment\Payer\PayerInfo;
use SwagPayPal\PayPal\Api\PayPalStruct;
use SwagPayPal\PayPal\Client\PayPalClient;
use SwagPayPal\Test\Core\Checkout\Payment\Cart\PaymentHandler\PayPalPaymentTest;
use SwagPayPal\Test\Helper\ConstantsForTesting;
use SwagPayPal\Test\Mock\PayPal\Client\_fixtures\CreatePaymentResponseFixture;
use SwagPayPal\Test\Mock\PayPal\Client\_fixtures\ExecutePaymentAuthorizeResponseFixture;
use SwagPayPal\Test\Mock\PayPal\Client\_fixtures\ExecutePaymentOrderResponseFixture;
use SwagPayPal\Test\Mock\PayPal\Client\_fixtures\ExecutePaymentSaleResponseFixture;
use SwagPayPal\Test\PayPal\Resource\WebhookResourceTest;

class PayPalClientMock extends PayPalClient
{
    public const GET_WEBHOOK_URL = 'testWebhookUrl';

    public const CLIENT_EXCEPTION_MESSAGE_WITHOUT_RESPONSE = 'clientExceptionWithoutResponse';

    public const CLIENT_EXCEPTION_MESSAGE_WITH_RESPONSE = 'clientExceptionWithoutResponse';

    public function sendGetRequest(string $resourceUri): array
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

    public function sendPostRequest(string $resourceUri, PayPalStruct $data): array
    {
        if (mb_substr($resourceUri, -8) === '/execute') {
            /** @var PayerInfo $payerInfo */
            $payerInfo = $data;
            if ($payerInfo->getPayerId() === ConstantsForTesting::PAYER_ID_PAYMENT_AUTHORIZE) {
                return ExecutePaymentAuthorizeResponseFixture::get();
            }

            if ($payerInfo->getPayerId() === ConstantsForTesting::PAYER_ID_PAYMENT_ORDER) {
                return ExecutePaymentOrderResponseFixture::get();
            }

            $response = ExecutePaymentSaleResponseFixture::get();
            if ($payerInfo->getPayerId() !== PayPalPaymentTest::PAYER_ID_PAYMENT_INCOMPLETE) {
                return $response;
            }

            $response['transactions'][0]['related_resources'][0]['sale']['state'] = 'denied';

            return $response;
        }

        return CreatePaymentResponseFixture::get();
    }

    public function sendPatchRequest(string $resourceUri, array $data): array
    {
        throw $this->createClientExceptionWithInvalidId();
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

    private function createClientExceptionFromResponseString(string $jsonString): ClientException
    {
        return new ClientException(
            self::CLIENT_EXCEPTION_MESSAGE_WITH_RESPONSE,
            new Request('', ''),
            new Response(200, [], $jsonString)
        );
    }
}
