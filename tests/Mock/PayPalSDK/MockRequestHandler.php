<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\PayPalSDK;

use GuzzleHttp\Psr7\Response;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Context\CredentialsOAuthContext;
use Shopware\PayPalSDK\Gateway\CustomerGateway;
use Shopware\PayPalSDK\Gateway\OrderGateway;
use Shopware\PayPalSDK\Gateway\PaymentGateway;
use Shopware\PayPalSDK\Gateway\PaymentV1Gateway;
use Shopware\PayPalSDK\Gateway\TokenGateway;
use Shopware\PayPalSDK\Gateway\WebhookGateway;
use Shopware\PayPalSDK\Struct\V1\Webhook;
use Shopware\PayPalSDK\Struct\V2\Order;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Payments\Refund;
use Shopware\PayPalSDK\Test\Request\TestRequestContext;
use Swag\PayPal\Test\Checkout\ExpressCheckout\SalesChannel\ExpressPrepareCheckoutRouteTest;
use Swag\PayPal\Test\Checkout\Method\PUIHandlerTest;
use Swag\PayPal\Test\Checkout\Payment\PayPalPaymentHandlerTest;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\CaptureAuthorizationResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\ClientTokenResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\CreateTokenResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetDispute;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetDisputesList;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetPaymentAuthorizeResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetPaymentCapturedOrderResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetPaymentOrderResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetPaymentSaleResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetPaymentSaleWithRefundResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetResourceAuthorizeResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetResourceMerchantIntegrations;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetResourceOrderResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\GetResourceSaleResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\AuthorizeOrderAuthorization;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\AuthorizeOrderDenied;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CaptureAuthorization;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CaptureOrderAPM;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CaptureOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CaptureOrderDeclined;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CreateOrderAPM;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CreateOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CreateOrderPUI;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetAuthorization;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetCapturedOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetOrderAPM;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetOrderAuthorization;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetOrderCaptureLiabilityShiftNo;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetOrderCaptureLiabilityShiftUnknown;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetOrderPUIApproved;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetOrderPUICompleted;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetOrderPUIPending;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetOrderPUIVoided;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetRefund;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetRefundedOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\RefundCapture;
use Swag\PayPal\Test\RestApi\V1\Resource\PaymentResourceTest;
use Swag\PayPal\Test\RestApi\V1\Resource\WebhookResourceTest;
use Symfony\Component\HttpFoundation\Response as SymResponse;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

/**
 * @internal
 */
#[Package('checkout')]
class MockRequestHandler
{
    public const GENERAL_CLIENT_EXCEPTION_MESSAGE = 'generalClientExceptionMessage';
    public const CLIENT_EXCEPTION_MESSAGE_WITH_RESPONSE = 'clientExceptionWithoutResponse';

    public const GET_WEBHOOK_URL = 'testWebhookUrl';
    public const TEST_WEBHOOK_ID = 'testWebhookId';

    public function handle(TestRequestContext $context): Response
    {
        $request = $context->getRequest();
        $uri = $request->getUri()->getPath();

        return match (\mb_strtolower($request->getMethod())) {
            'get' => match (true) {
                \str_starts_with($uri, '/v1') => $this->handleApiV1GetRequests($uri),
                \str_starts_with($uri, '/v2') => $this->handleApiV2GetRequests($uri),
                default => throw new \RuntimeException('No fixture defined for GET ' . $uri),
            },
            'post' => match (true) {
                \str_starts_with($uri, '/v1') => $this->handleApiV1PostRequests($uri, $context),
                \str_starts_with($uri, '/v2') => $this->handleApiV2PostRequests($uri, $context),
                default => throw new \RuntimeException('No fixture defined for POST ' . $uri),
            },
            'patch' => $this->handlePatchRequests($uri),
            'delete' => $this->handleDeleteRequests($uri),
            default => throw new MethodNotAllowedException(['get', 'post', 'patch', 'delete']),
        };
    }

    private function handleApiV1GetRequests(string $resourceUri): Response
    {
        if (\mb_strpos($resourceUri, WebhookGateway::GATEWAY_URL) !== false) {
            if (\mb_strpos($resourceUri, WebhookResourceTest::THROW_EXCEPTION_WITH_RESPONSE) !== false) {
                return $this->createClientExceptionWithResponse();
            }

            if (\mb_strpos($resourceUri, WebhookResourceTest::THROW_EXCEPTION_INVALID_ID) !== false) {
                return $this->createClientExceptionWithInvalidId();
            }

            return $this->createResponse(SymResponse::HTTP_OK, ['url' => self::GET_WEBHOOK_URL]);
        }

        if (\mb_strpos($resourceUri, PaymentV1Gateway::GATEWAY_URL . '/payment') !== false) {
            if (\mb_strpos($resourceUri, PaymentResourceTest::ORDER_PAYMENT_ID) !== false) {
                return $this->createResponse(SymResponse::HTTP_OK, GetPaymentOrderResponseFixture::get());
            }

            if (\mb_strpos($resourceUri, PaymentResourceTest::CAPTURED_ORDER_PAYMENT_ID) !== false) {
                return $this->createResponse(SymResponse::HTTP_OK, GetPaymentCapturedOrderResponseFixture::get());
            }

            if (\mb_strpos($resourceUri, PaymentResourceTest::AUTHORIZE_PAYMENT_ID) !== false) {
                return $this->createResponse(SymResponse::HTTP_OK, GetPaymentAuthorizeResponseFixture::get());
            }

            if (\mb_strpos($resourceUri, PaymentResourceTest::SALE_WITH_REFUND_PAYMENT_ID) !== false) {
                return $this->createResponse(SymResponse::HTTP_OK, GetPaymentSaleWithRefundResponseFixture::get());
            }

            return $this->createResponse(SymResponse::HTTP_OK, GetPaymentSaleResponseFixture::get());
        }

        if (\mb_strpos($resourceUri, PaymentV1Gateway::GATEWAY_URL . '/authorization') !== false) {
            return $this->createResponse(SymResponse::HTTP_OK, GetResourceAuthorizeResponseFixture::get());
        }

        if (\mb_strpos($resourceUri, PaymentV1Gateway::GATEWAY_URL . '/capture') !== false) {
            return $this->createResponse(SymResponse::HTTP_OK, CaptureAuthorizationResponseFixture::get());
        }

        if (\mb_strpos($resourceUri, PaymentV1Gateway::GATEWAY_URL . '/orders') !== false) {
            return $this->createResponse(SymResponse::HTTP_OK, GetResourceOrderResponseFixture::get());
        }

        if (\mb_strpos($resourceUri, PaymentV1Gateway::GATEWAY_URL . '/sale') !== false) {
            return $this->createResponse(SymResponse::HTTP_OK, GetResourceSaleResponseFixture::get());
        }

        if (\mb_strpos($resourceUri, 'customer/partners/') !== false) {
            if (\mb_strpos($resourceUri, '/merchant-integrations/credentials')) {
                return $this->createResponse(SymResponse::HTTP_OK, [
                    'client_id' => ConstantsForTesting::VALID_CLIENT_ID,
                    'client_secret' => ConstantsForTesting::VALID_CLIENT_SECRET,
                ]);
            }

            return $this->createResponse(SymResponse::HTTP_OK, GetResourceMerchantIntegrations::get());
        }

        if (\mb_strpos($resourceUri, CustomerGateway::GATEWAY_URL . '/disputes') !== false) {
            if (\mb_strpos($resourceUri, '/PP-') !== false) {
                return $this->createResponse(SymResponse::HTTP_OK, GetDispute::get());
            }

            return $this->createResponse(SymResponse::HTTP_OK, GetDisputesList::get());
        }

        throw new \RuntimeException('No fixture defined for GET ' . $resourceUri);
    }

    private function handleApiV2GetRequests(string $resourceUri): Response
    {
        if (\mb_strpos($resourceUri, OrderGateway::GATEWAY_URL) !== false) {
            if (\mb_substr($resourceUri, -17) === GetCapturedOrderCapture::ID) {
                return $this->createResponse(SymResponse::HTTP_OK, GetCapturedOrderCapture::get());
            }

            if (\mb_substr($resourceUri, -17) === GetRefundedOrderCapture::ID) {
                return $this->createResponse(SymResponse::HTTP_OK, GetRefundedOrderCapture::get());
            }

            if (\mb_strpos($resourceUri, PayPalPaymentHandlerTest::PAYPAL_ORDER_ID_INSTRUMENT_DECLINED) !== false) {
                $orderCapture = GetRefundedOrderCapture::get();
                $orderCapture['id'] = PayPalPaymentHandlerTest::PAYPAL_ORDER_ID_INSTRUMENT_DECLINED;

                return $this->createResponse(SymResponse::HTTP_OK, $orderCapture);
            }

            if (\mb_strpos($resourceUri, PayPalPaymentHandlerTest::PAYPAL_ORDER_ID_EXPIRED_SESSION) !== false) {
                return $this->createClientExceptionResourceNotFound();
            }

            if (\mb_substr($resourceUri, -17) === GetOrderAuthorization::ID) {
                return $this->createResponse(SymResponse::HTTP_OK, GetOrderAuthorization::get());
            }

            if (\mb_substr($resourceUri, -17) === AuthorizeOrderDenied::ID) {
                return $this->createResponse(SymResponse::HTTP_OK, \array_merge(GetOrderAuthorization::get(), ['id' => AuthorizeOrderDenied::ID]));
            }

            if (\mb_substr($resourceUri, -17) === GetOrderPUIPending::ID) {
                return $this->createResponse(SymResponse::HTTP_OK, GetOrderPUIPending::get());
            }

            if (\mb_substr($resourceUri, -17) === GetOrderPUIApproved::ID) {
                return $this->createResponse(SymResponse::HTTP_OK, GetOrderPUIApproved::get());
            }

            if (\mb_substr($resourceUri, -17) === GetOrderPUIVoided::ID) {
                return $this->createResponse(SymResponse::HTTP_OK, GetOrderPUIVoided::get());
            }

            if (\mb_substr($resourceUri, -17) === GetOrderPUICompleted::ID) {
                return $this->createResponse(SymResponse::HTTP_OK, GetOrderPUICompleted::get());
            }

            if (\mb_substr($resourceUri, -17) === GetOrderAPM::ID) {
                return $this->createResponse(SymResponse::HTTP_OK, GetOrderAPM::get());
            }

            if (\mb_substr($resourceUri, -17) === GetOrderCaptureLiabilityShiftNo::ID) {
                return $this->createResponse(SymResponse::HTTP_OK, GetOrderCaptureLiabilityShiftNo::get());
            }

            if (\mb_substr($resourceUri, -17) === GetOrderCaptureLiabilityShiftUnknown::ID) {
                return $this->createResponse(SymResponse::HTTP_OK, GetOrderCaptureLiabilityShiftUnknown::get());
            }

            $orderCapture = GetOrderCapture::get();
            if (\mb_substr($resourceUri, -17) === GetOrderCapture::ID) {
                return $this->createResponse(SymResponse::HTTP_OK, $orderCapture);
            }

            if (\mb_substr($resourceUri, -17) === CaptureOrderDeclined::ID) {
                $orderCapture['id'] = CaptureOrderDeclined::ID;

                return $this->createResponse(SymResponse::HTTP_OK, $orderCapture);
            }

            if (\mb_substr($resourceUri, -33) === PayPalPaymentHandlerTest::PAYPAL_ORDER_ID_DUPLICATE_ORDER_NUMBER) {
                $orderCapture['id'] = PayPalPaymentHandlerTest::PAYPAL_ORDER_ID_DUPLICATE_ORDER_NUMBER;

                return $this->createResponse(SymResponse::HTTP_OK, $orderCapture);
            }

            if (\mb_strpos($resourceUri, ExpressPrepareCheckoutRouteTest::TEST_PAYMENT_ID_WITHOUT_STATE) !== false) {
                $orderCapture['purchase_units'][0]['shipping']['address']['admin_area_1'] = null;

                return $this->createResponse(SymResponse::HTTP_OK, $orderCapture);
            }

            if (\mb_strpos($resourceUri, ExpressPrepareCheckoutRouteTest::TEST_PAYMENT_ID_WITH_COUNTRY_WITHOUT_STATES) !== false) {
                $orderCapture['purchase_units'][0]['shipping']['address']['country_code'] = 'NL';

                return $this->createResponse(SymResponse::HTTP_OK, $orderCapture);
            }

            if (\mb_strpos($resourceUri, ExpressPrepareCheckoutRouteTest::TEST_PAYMENT_ID_WITH_STATE_NOT_FOUND) !== false) {
                $orderCapture['purchase_units'][0]['shipping']['address']['admin_area_1'] = 'XY';

                return $this->createResponse(SymResponse::HTTP_OK, $orderCapture);
            }
        }

        if (\mb_strpos($resourceUri, PaymentGateway::GATEWAY_URL . '/captures') !== false) {
            return $this->createResponse(SymResponse::HTTP_OK, GetCapture::get());
        }

        if (\mb_strpos($resourceUri, PaymentGateway::GATEWAY_URL . '/refunds') !== false) {
            return $this->createResponse(SymResponse::HTTP_OK, GetRefund::get());
        }

        if (\mb_strpos($resourceUri, PaymentGateway::GATEWAY_URL . '/authorizations') !== false) {
            return $this->createResponse(SymResponse::HTTP_OK, GetAuthorization::get());
        }

        throw new \RuntimeException('No fixture defined for GET ' . $resourceUri);
    }

    private function handleApiV1PostRequests(string $resourceUri, TestRequestContext $context): Response
    {
        if (\str_contains($resourceUri, TokenGateway::GATEWAY_URL)) {
            $authHeader = $context->getContext()->getOAuthContext()->getHeaders()['Authorization'] ?? '';
            $validClientIdInvalidSecret = $this->getAuthenticationHeader(ConstantsForTesting::VALID_CLIENT_ID, ConstantsForTesting::INVALID_CLIENT_SECRET);
            $invalidClientIdInvalidSecret = $this->getAuthenticationHeader(ConstantsForTesting::INVALID_CLIENT_ID, ConstantsForTesting::INVALID_CLIENT_SECRET);
            $invalidClientIdValidSecret = $this->getAuthenticationHeader(ConstantsForTesting::INVALID_CLIENT_ID, ConstantsForTesting::VALID_CLIENT_SECRET);

            if ($authHeader === $validClientIdInvalidSecret || $authHeader === $invalidClientIdInvalidSecret) {
                return $this->createClientExceptionWithResponse();
            }

            if ($authHeader === $invalidClientIdValidSecret) {
                return $this->createOAuthException(SymResponse::HTTP_UNAUTHORIZED);
            }

            return $this->createResponse(SymResponse::HTTP_OK, CreateTokenResponseFixture::get());
        }

        if (\mb_strpos($resourceUri, WebhookGateway::GATEWAY_URL) !== false) {
            $data = $context->getRequestBody();

            if ($data === null) {
                throw new \RuntimeException('Create webhook request needs valid Webhook struct');
            }

            $data = (new Webhook())->assign($data);

            if ($data->isset('url')) {
                if (\mb_strpos($data->getUrl(), WebhookResourceTest::TEST_URL) !== false) {
                    return $this->createClientExceptionWithResponse();
                }

                if (\mb_strpos($data->getUrl(), WebhookResourceTest::TEST_URL_ALREADY_EXISTS) !== false) {
                    return $this->createClientExceptionWebhookAlreadyExists();
                }

                if (\mb_strpos($data->getUrl(), WebhookResourceTest::TEST_URL_INVALID) !== false) {
                    return $this->createClientExceptionWebhookIsInvalid();
                }
            }

            return $this->createResponse(SymResponse::HTTP_OK, ['id' => self::TEST_WEBHOOK_ID]);
        }

        if (\mb_strpos($resourceUri, 'v1/identity/generate-token') !== false) {
            return $this->createResponse(SymResponse::HTTP_OK, ClientTokenResponseFixture::get());
        }

        throw new \RuntimeException('No fixture defined for POST ' . $resourceUri);
    }

    private function handleApiV2PostRequests(string $resourceUri, TestRequestContext $context): Response
    {
        if (\mb_strpos($resourceUri, OrderGateway::GATEWAY_URL) !== false) {
            if (\mb_strpos($resourceUri, PayPalPaymentHandlerTest::PAYPAL_ORDER_ID_DUPLICATE_ORDER_NUMBER) !== false
                && CaptureOrderCapture::isDuplicateOrderNumber()) {
                CaptureOrderCapture::setDuplicateOrderNumber(false);

                return $this->createClientExceptionDuplicateOrderNumber();
            }

            if (\mb_strpos($resourceUri, PayPalPaymentHandlerTest::PAYPAL_ORDER_ID_INSTRUMENT_DECLINED) !== false) {
                return $this->createClientExceptionInstrumentDeclined();
            }

            if (\mb_substr($resourceUri, -8) === '/capture' && \mb_strpos($resourceUri, CaptureOrderAPM::ID)) {
                return $this->createResponse(SymResponse::HTTP_OK, CaptureOrderAPM::get());
            }

            if (\mb_substr($resourceUri, -8) === '/capture' && \mb_strpos($resourceUri, CaptureOrderDeclined::ID)) {
                return $this->createResponse(SymResponse::HTTP_OK, CaptureOrderDeclined::get());
            }

            if (\mb_substr($resourceUri, -8) === '/capture') {
                return $this->createResponse(SymResponse::HTTP_OK, CaptureOrderCapture::get());
            }

            if (\mb_substr($resourceUri, -10) === '/authorize' && \mb_strpos($resourceUri, AuthorizeOrderDenied::ID)) {
                return $this->createResponse(SymResponse::HTTP_OK, AuthorizeOrderDenied::get());
            }

            if (\mb_substr($resourceUri, -10) === '/authorize') {
                return $this->createResponse(SymResponse::HTTP_OK, AuthorizeOrderAuthorization::get());
            }

            if ($order = $context->getRequestBody()) {
                $order = (new Order())->assign($order);

                if (($paymentSource = $order->getPaymentSource())
                    && $paymentSource->getPaypal() === null
                    && $paymentSource->getToken() === null) {
                    if ($payUponInvoice = $paymentSource->getPayUponInvoice()) {
                        if ($payUponInvoice->getEmail() === PUIHandlerTest::PAYMENT_SOURCE_DECLINED_BY_PROCESSOR) {
                            return $this->createClientExceptionPaymentSourceDeclinedByProcessor();
                        }

                        if ($payUponInvoice->getEmail() === PUIHandlerTest::PAYMENT_SOURCE_INFO_CANNOT_BE_VERIFIED) {
                            return $this->createClientExceptionPaymentSourceInfoCannotBeVerified();
                        }

                        return $this->createResponse(SymResponse::HTTP_OK, CreateOrderPUI::get());
                    }

                    return $this->createResponse(SymResponse::HTTP_OK, CreateOrderAPM::get((string) \array_key_first($paymentSource->jsonSerialize())));
                }

                $response = CreateOrderCapture::get();

                if (\mb_stripos((string) $order->getPurchaseUnits()->first()?->getInvoiceId(), ConstantsForTesting::PAYPAL_RESPONSE_HAS_NO_APPROVAL_URL) !== false) {
                    $links = $response['links'];
                    unset($links[1]);
                    $links = \array_values($links);
                    $response['links'] = $links;
                }

                return $this->createResponse(SymResponse::HTTP_OK, $response);
            }
        }

        if (\mb_strpos($resourceUri, PaymentGateway::GATEWAY_URL . '/captures') !== false) {
            $refundedCapture = RefundCapture::get();

            if ($refund = $context->getRequestBody()) {
                $refund = (new Refund())->assign($refund);

                $amount = $refund->getAmount();
                if ($amount !== null) {
                    $refundedCapture['seller_payable_breakdown']['total_refunded_amount']['value'] = $amount->getValue();
                }

                $refundedCapture['invoice_id'] = null;
                if ($refund->getInvoiceId() !== null) {
                    $refundedCapture['invoice_id'] = $refund->getInvoiceId();
                }

                $refundedCapture['note_to_payer'] = null;
                if ($refund->getNoteToPayer() !== null) {
                    $refundedCapture['note_to_payer'] = $refund->getNoteToPayer();
                }
            }

            return $this->createResponse(SymResponse::HTTP_OK, $refundedCapture);
        }

        if (\mb_strpos($resourceUri, PaymentGateway::GATEWAY_URL . '/authorizations') !== false) {
            if (\mb_substr($resourceUri, -5) === '/void') {
                return $this->createResponse(SymResponse::HTTP_OK, []);
            }

            if (\mb_substr($resourceUri, -8) === '/capture') {
                return $this->createResponse(SymResponse::HTTP_OK, CaptureAuthorization::get());
            }
        }

        throw new \RuntimeException('No fixture defined for POST ' . $resourceUri);
    }

    private function handlePatchRequests(string $resourceUri): Response
    {
        if (\mb_strpos($resourceUri, WebhookResourceTest::THROW_EXCEPTION_INVALID_ID) !== false) {
            return $this->createClientExceptionWithInvalidId();
        }

        if (\mb_strpos($resourceUri, WebhookResourceTest::THROW_EXCEPTION_INVALID_URL) !== false) {
            return $this->createClientExceptionWebhookIsInvalid();
        }

        if (\mb_strpos($resourceUri, self::TEST_WEBHOOK_ID) !== false) {
            return $this->createClientExceptionWithResponse();
        }

        if (\mb_strpos($resourceUri, PayPalPaymentHandlerTest::PAYPAL_PATCH_THROWS_EXCEPTION) !== false) {
            return $this->createClientExceptionWithResponse();
        }

        return $this->createResponse(SymResponse::HTTP_OK, []);
    }

    private function handleDeleteRequests(string $resourceUri): Response
    {
        if (\mb_strpos($resourceUri, WebhookResourceTest::THROW_EXCEPTION_INVALID_ID) !== false) {
            return $this->createClientExceptionWithInvalidId();
        }

        return $this->createResponse(SymResponse::HTTP_NO_CONTENT, null);
    }

    private function getAuthenticationHeader(string $restId, string $restSecret): string
    {
        $context = new CredentialsOAuthContext($restId, $restSecret);

        return (string) $context->getHeaders()['Authorization'];
    }

    private function createOAuthException(int $errorCode = SymResponse::HTTP_UNAUTHORIZED): Response
    {
        return $this->createResponse($errorCode, [
            'error' => 'TEST',
            'error_description' => self::GENERAL_CLIENT_EXCEPTION_MESSAGE,
        ]);
    }

    private function createClientExceptionWithResponse(int $errorCode = SymResponse::HTTP_BAD_REQUEST): Response
    {
        return $this->createResponse($errorCode, [
            'name' => 'TEST',
            'message' => self::GENERAL_CLIENT_EXCEPTION_MESSAGE,
        ]);
    }

    private function createClientExceptionDuplicateOrderNumber(): Response
    {
        return $this->createResponse(SymResponse::HTTP_UNPROCESSABLE_ENTITY, [
            'name' => 'UNPROCESSABLE_ENTITY',
            'details' => [
                [
                    'location' => 'body',
                    'issue' => 'DUPLICATE_INVOICE_ID',
                    'description' => 'Duplicate Invoice ID detected. To avoid a potential duplicate transaction your account setting requires that Invoice Id be unique for each transaction.',
                ],
            ],
            'message' => 'The requested action could not be performed, semantically incorrect, or failed business validation.',
        ]);
    }

    private function createClientExceptionInstrumentDeclined(): Response
    {
        return $this->createResponse(SymResponse::HTTP_UNPROCESSABLE_ENTITY, [
            'name' => 'UNPROCESSABLE_ENTITY',
            'details' => [
                [
                    'location' => 'body',
                    'issue' => 'INSTRUMENT_DECLINED',
                    'description' => 'The instrument presented was either declined by the processor or bank, or it can\'t be used for this payment.',
                ],
            ],
            'message' => 'The requested action could not be completed, was semantically incorrect, or failed business validation.',
        ]);
    }

    private function createClientExceptionResourceNotFound(): Response
    {
        return $this->createResponse(SymResponse::HTTP_NOT_FOUND, [
            'name' => 'RESOURCE_NOT_FOUND',
            'details' => [
                [
                    'location' => 'path',
                    'issue' => 'INVALID_RESOURCE_ID',
                    'description' => 'The specified resource does not exist.',
                ],
            ],
            'message' => 'The specified resource does not exist.',
        ]);
    }

    private function createClientExceptionPaymentSourceInfoCannotBeVerified(): Response
    {
        return $this->createResponse(SymResponse::HTTP_UNPROCESSABLE_ENTITY, [
            'name' => 'UNPROCESSABLE_ENTITY',
            'details' => [
                [
                    'location' => 'body',
                    'issue' => 'PAYMENT_SOURCE_INFO_CANNOT_BE_VERIFIED',
                    'description' => 'The combination of the payment_source name, billing address, shipping name and shipping address could not be verified. Please correct this information and try again by creating a new order.',
                ],
            ],
            'message' => 'The requested action could not be performed, semantically incorrect, or failed business validation.',
        ]);
    }

    private function createClientExceptionPaymentSourceDeclinedByProcessor(): Response
    {
        return $this->createResponse(SymResponse::HTTP_UNPROCESSABLE_ENTITY, [
            'name' => 'UNPROCESSABLE_ENTITY',
            'details' => [
                [
                    'location' => 'body',
                    'issue' => 'PAYMENT_SOURCE_DECLINED_BY_PROCESSOR',
                    'description' => 'The provided payment source is declined by the processor. Please try again with a different payment source by creating a new order.',
                ],
            ],
            'message' => 'The requested action could not be performed, semantically incorrect, or failed business validation.',
        ]);
    }

    private function createClientExceptionWithInvalidId(): Response
    {
        return $this->createResponse(SymResponse::HTTP_BAD_REQUEST, [
            'name' => 'INVALID_RESOURCE_ID',
            'message' => self::GENERAL_CLIENT_EXCEPTION_MESSAGE,
        ]);
    }

    private function createClientExceptionWebhookAlreadyExists(): Response
    {
        return $this->createResponse(SymResponse::HTTP_BAD_REQUEST, [
            'name' => 'WEBHOOK_URL_ALREADY_EXISTS',
            'message' => self::GENERAL_CLIENT_EXCEPTION_MESSAGE,
        ]);
    }

    private function createClientExceptionWebhookIsInvalid(): Response
    {
        return $this->createResponse(SymResponse::HTTP_BAD_REQUEST, [
            'name' => 'VALIDATION_ERROR',
            'message' => self::GENERAL_CLIENT_EXCEPTION_MESSAGE,
        ]);
    }

    private function createResponse(int $statusCode, array|\JsonSerializable|null $body): Response
    {
        if ($body !== null) {
            $body = \json_encode($body, \JSON_THROW_ON_ERROR);
        }

        return new Response($statusCode, [], $body);
    }
}
