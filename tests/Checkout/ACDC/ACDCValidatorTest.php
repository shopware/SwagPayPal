<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\ACDC;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\ACDC\ACDCValidator;
use Swag\PayPal\Checkout\Exception\MissingPayloadException;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Card;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Card\AuthenticationResult;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Card\AuthenticationResult\ThreeDSecure;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Paypal;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;

/**
 * @internal
 */
#[Package('checkout')]
class ACDCValidatorTest extends TestCase
{
    private SystemConfigServiceMock $systemConfigService;

    private ACDCValidator $validator;

    protected function setUp(): void
    {
        $this->systemConfigService = SystemConfigServiceMock::createWithoutCredentials();
        $this->validator = new ACDCValidator($this->systemConfigService);
    }

    #[DataProvider('dataProvider3DSecureResults')]
    public function testValidation(bool $force, string $liabilityShift, ?string $enrollmentStatus, ?string $authenticationStatus, bool $result): void
    {
        $this->systemConfigService->set(Settings::ACDC_FORCE_3DS, $force);
        $order = $this->createOrder($liabilityShift, $enrollmentStatus, $authenticationStatus);

        $transaction = $this->createMock(SyncPaymentTransactionStruct::class);
        $salesChannelContext = $this->createMock(SalesChannelContext::class);

        static::assertSame($result, $this->validator->validate($order, $transaction, $salesChannelContext));
    }

    public function testValidationWithoutPaymentSource(): void
    {
        $order = $this->createOrder('', null, null);
        $order->setPaymentSource(null);

        $transaction = $this->createMock(SyncPaymentTransactionStruct::class);
        $salesChannelContext = $this->createMock(SalesChannelContext::class);

        static::expectException(MissingPayloadException::class);
        static::expectExceptionMessage('Missing request payload payment_source to order "test-order-id" not found');

        $this->validator->validate($order, $transaction, $salesChannelContext);
    }

    public function testValidationWithoutCard(): void
    {
        $order = $this->createOrder('', null, null);
        $order->getPaymentSource()?->setCard(null);

        $transaction = $this->createMock(SyncPaymentTransactionStruct::class);
        $salesChannelContext = $this->createMock(SalesChannelContext::class);

        static::expectException(MissingPayloadException::class);
        static::expectExceptionMessage('Missing request payload payment_source.card to order "test-order-id" not found');

        $this->validator->validate($order, $transaction, $salesChannelContext);
    }

    public function testValidationWithoutAuthenticationResult(): void
    {
        $order = $this->createOrder('', null, null);
        $order->getPaymentSource()?->getCard()?->setAuthenticationResult(null);

        $transaction = $this->createMock(SyncPaymentTransactionStruct::class);
        $salesChannelContext = $this->createMock(SalesChannelContext::class);

        static::expectException(MissingPayloadException::class);
        static::expectExceptionMessage('Missing request payload payment_source.card.authentication_result to order "test-order-id" not found');

        $this->validator->validate($order, $transaction, $salesChannelContext);
    }

    public static function dataProvider3DSecureResults(): iterable
    {
        // data from https://developer.paypal.com/docs/checkout/advanced/customize/3d-secure/response-parameters/
        return [
            [false, ACDCValidator::LIABILITY_SHIFT_POSSIBLE, ACDCValidator::ENROLLMENT_STATUS_READY, ACDCValidator::AUTHENTICATION_STATUS_SUCCESSFUL, true],
            [false, ACDCValidator::LIABILITY_SHIFT_YES, ACDCValidator::ENROLLMENT_STATUS_READY, ACDCValidator::AUTHENTICATION_STATUS_SUCCESSFUL, true],
            [false, ACDCValidator::LIABILITY_SHIFT_NO, ACDCValidator::ENROLLMENT_STATUS_READY, ACDCValidator::AUTHENTICATION_STATUS_FAILED, false],
            [false, ACDCValidator::LIABILITY_SHIFT_NO, ACDCValidator::ENROLLMENT_STATUS_READY, ACDCValidator::AUTHENTICATION_STATUS_REJECTED, false],
            [false, ACDCValidator::LIABILITY_SHIFT_POSSIBLE, ACDCValidator::ENROLLMENT_STATUS_READY, ACDCValidator::AUTHENTICATION_STATUS_ATTEMPTED, true],
            [false, ACDCValidator::LIABILITY_SHIFT_UNKNOWN, ACDCValidator::ENROLLMENT_STATUS_READY, ACDCValidator::AUTHENTICATION_STATUS_UNABLE_TO_COMPLETE, false],
            [false, ACDCValidator::LIABILITY_SHIFT_NO, ACDCValidator::ENROLLMENT_STATUS_READY, ACDCValidator::AUTHENTICATION_STATUS_UNABLE_TO_COMPLETE, false],
            [false, ACDCValidator::LIABILITY_SHIFT_UNKNOWN, ACDCValidator::ENROLLMENT_STATUS_READY, ACDCValidator::AUTHENTICATION_STATUS_CHALLENGE_REQUIRED, false],
            [false, ACDCValidator::LIABILITY_SHIFT_NO, ACDCValidator::ENROLLMENT_STATUS_READY, null, false],
            [false, ACDCValidator::LIABILITY_SHIFT_NO, ACDCValidator::ENROLLMENT_STATUS_NOT_READY, null, true],
            [false, ACDCValidator::LIABILITY_SHIFT_NO, ACDCValidator::ENROLLMENT_STATUS_UNAVAILABLE, null, true],
            [false, ACDCValidator::LIABILITY_SHIFT_UNKNOWN, ACDCValidator::ENROLLMENT_STATUS_UNAVAILABLE, null, false],
            [false, ACDCValidator::LIABILITY_SHIFT_NO, ACDCValidator::ENROLLMENT_STATUS_BYPASSED, null, true],
            [false, ACDCValidator::LIABILITY_SHIFT_UNKNOWN, null, null, false],

            [true, ACDCValidator::LIABILITY_SHIFT_POSSIBLE, ACDCValidator::ENROLLMENT_STATUS_READY, ACDCValidator::AUTHENTICATION_STATUS_SUCCESSFUL, true],
            [true, ACDCValidator::LIABILITY_SHIFT_YES, ACDCValidator::ENROLLMENT_STATUS_READY, ACDCValidator::AUTHENTICATION_STATUS_SUCCESSFUL, true],
            [true, ACDCValidator::LIABILITY_SHIFT_NO, ACDCValidator::ENROLLMENT_STATUS_READY, ACDCValidator::AUTHENTICATION_STATUS_FAILED, false],
            [true, ACDCValidator::LIABILITY_SHIFT_NO, ACDCValidator::ENROLLMENT_STATUS_READY, ACDCValidator::AUTHENTICATION_STATUS_REJECTED, false],
            [true, ACDCValidator::LIABILITY_SHIFT_POSSIBLE, ACDCValidator::ENROLLMENT_STATUS_READY, ACDCValidator::AUTHENTICATION_STATUS_ATTEMPTED, true],
            [true, ACDCValidator::LIABILITY_SHIFT_UNKNOWN, ACDCValidator::ENROLLMENT_STATUS_READY, ACDCValidator::AUTHENTICATION_STATUS_UNABLE_TO_COMPLETE, false],
            [true, ACDCValidator::LIABILITY_SHIFT_NO, ACDCValidator::ENROLLMENT_STATUS_READY, ACDCValidator::AUTHENTICATION_STATUS_UNABLE_TO_COMPLETE, false],
            [true, ACDCValidator::LIABILITY_SHIFT_UNKNOWN, ACDCValidator::ENROLLMENT_STATUS_READY, ACDCValidator::AUTHENTICATION_STATUS_CHALLENGE_REQUIRED, false],
            [true, ACDCValidator::LIABILITY_SHIFT_NO, ACDCValidator::ENROLLMENT_STATUS_READY, null, false],
            [true, ACDCValidator::LIABILITY_SHIFT_NO, ACDCValidator::ENROLLMENT_STATUS_NOT_READY, null, false],
            [true, ACDCValidator::LIABILITY_SHIFT_NO, ACDCValidator::ENROLLMENT_STATUS_UNAVAILABLE, null, false],
            [true, ACDCValidator::LIABILITY_SHIFT_UNKNOWN, ACDCValidator::ENROLLMENT_STATUS_UNAVAILABLE, null, false],
            [true, ACDCValidator::LIABILITY_SHIFT_NO, ACDCValidator::ENROLLMENT_STATUS_BYPASSED, null, false],
            [true, ACDCValidator::LIABILITY_SHIFT_UNKNOWN, null, null, false],
        ];
    }

    private function createOrder(string $liabilityShift, ?string $enrollmentStatus, ?string $authenticationStatus): Order
    {
        $order = new Order();
        $order->setId('test-order-id');
        $paymentSource = new PaymentSource();
        $card = new Card();
        $authenticationResult = new AuthenticationResult();
        $authenticationResult->setLiabilityShift($liabilityShift);
        if ($enrollmentStatus) {
            $threeDSecure = new ThreeDSecure();
            $threeDSecure->setEnrollmentStatus($enrollmentStatus);
            $threeDSecure->setAuthenticationStatus($authenticationStatus);
            $authenticationResult->setThreeDSecure($threeDSecure);
        }
        $card->setAuthenticationResult($authenticationResult);
        $paymentSource->setCard($card);
        $order->setPaymentSource($paymentSource);

        return $order;
    }
}
