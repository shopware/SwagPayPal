<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Card;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Card\CardValidatorInterface;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Card\AuthenticationResult;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Card\AuthenticationResult\ThreeDSecure;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;

/**
 * @internal
 */
#[Package('checkout')]
abstract class AbstractCardValidatorTestCase extends TestCase
{
    protected SystemConfigServiceMock $systemConfigService;

    protected CardValidatorInterface $validator;

    /**
     * @dataProvider dataProvider3DSecureResults
     */
    public function testValidateAuthenticationResult(bool $force, string $liabilityShift, ?string $enrollmentStatus, ?string $authenticationStatus, bool $result): void
    {
        $this->systemConfigService->set(Settings::ACDC_FORCE_3DS, $force);
        $authenticationResult = $this->createAuthenticationResult($liabilityShift, $enrollmentStatus, $authenticationStatus);

        $salesChannelContext = $this->createMock(SalesChannelContext::class);

        static::assertSame($result, $this->validator->validateAuthenticationResult($authenticationResult, $salesChannelContext));
    }

    public static function dataProvider3DSecureResults(): iterable
    {
        // data from https://developer.paypal.com/docs/checkout/advanced/customize/3d-secure/response-parameters/
        return [
            [false, CardValidatorInterface::LIABILITY_SHIFT_POSSIBLE, CardValidatorInterface::ENROLLMENT_STATUS_READY, CardValidatorInterface::AUTHENTICATION_STATUS_SUCCESSFUL, true],
            [false, CardValidatorInterface::LIABILITY_SHIFT_YES, CardValidatorInterface::ENROLLMENT_STATUS_READY, CardValidatorInterface::AUTHENTICATION_STATUS_SUCCESSFUL, true],
            [false, CardValidatorInterface::LIABILITY_SHIFT_NO, CardValidatorInterface::ENROLLMENT_STATUS_READY, CardValidatorInterface::AUTHENTICATION_STATUS_FAILED, false],
            [false, CardValidatorInterface::LIABILITY_SHIFT_NO, CardValidatorInterface::ENROLLMENT_STATUS_READY, CardValidatorInterface::AUTHENTICATION_STATUS_REJECTED, false],
            [false, CardValidatorInterface::LIABILITY_SHIFT_POSSIBLE, CardValidatorInterface::ENROLLMENT_STATUS_READY, CardValidatorInterface::AUTHENTICATION_STATUS_ATTEMPTED, true],
            [false, CardValidatorInterface::LIABILITY_SHIFT_UNKNOWN, CardValidatorInterface::ENROLLMENT_STATUS_READY, CardValidatorInterface::AUTHENTICATION_STATUS_UNABLE_TO_COMPLETE, false],
            [false, CardValidatorInterface::LIABILITY_SHIFT_NO, CardValidatorInterface::ENROLLMENT_STATUS_READY, CardValidatorInterface::AUTHENTICATION_STATUS_UNABLE_TO_COMPLETE, false],
            [false, CardValidatorInterface::LIABILITY_SHIFT_UNKNOWN, CardValidatorInterface::ENROLLMENT_STATUS_READY, CardValidatorInterface::AUTHENTICATION_STATUS_CHALLENGE_REQUIRED, false],
            [false, CardValidatorInterface::LIABILITY_SHIFT_NO, CardValidatorInterface::ENROLLMENT_STATUS_READY, null, false],
            [false, CardValidatorInterface::LIABILITY_SHIFT_NO, CardValidatorInterface::ENROLLMENT_STATUS_NOT_READY, null, true],
            [false, CardValidatorInterface::LIABILITY_SHIFT_NO, CardValidatorInterface::ENROLLMENT_STATUS_UNAVAILABLE, null, true],
            [false, CardValidatorInterface::LIABILITY_SHIFT_UNKNOWN, CardValidatorInterface::ENROLLMENT_STATUS_UNAVAILABLE, null, false],
            [false, CardValidatorInterface::LIABILITY_SHIFT_NO, CardValidatorInterface::ENROLLMENT_STATUS_BYPASSED, null, true],
            [false, CardValidatorInterface::LIABILITY_SHIFT_UNKNOWN, null, null, false],

            [true, CardValidatorInterface::LIABILITY_SHIFT_POSSIBLE, CardValidatorInterface::ENROLLMENT_STATUS_READY, CardValidatorInterface::AUTHENTICATION_STATUS_SUCCESSFUL, true],
            [true, CardValidatorInterface::LIABILITY_SHIFT_YES, CardValidatorInterface::ENROLLMENT_STATUS_READY, CardValidatorInterface::AUTHENTICATION_STATUS_SUCCESSFUL, true],
            [true, CardValidatorInterface::LIABILITY_SHIFT_NO, CardValidatorInterface::ENROLLMENT_STATUS_READY, CardValidatorInterface::AUTHENTICATION_STATUS_FAILED, false],
            [true, CardValidatorInterface::LIABILITY_SHIFT_NO, CardValidatorInterface::ENROLLMENT_STATUS_READY, CardValidatorInterface::AUTHENTICATION_STATUS_REJECTED, false],
            [true, CardValidatorInterface::LIABILITY_SHIFT_POSSIBLE, CardValidatorInterface::ENROLLMENT_STATUS_READY, CardValidatorInterface::AUTHENTICATION_STATUS_ATTEMPTED, true],
            [true, CardValidatorInterface::LIABILITY_SHIFT_UNKNOWN, CardValidatorInterface::ENROLLMENT_STATUS_READY, CardValidatorInterface::AUTHENTICATION_STATUS_UNABLE_TO_COMPLETE, false],
            [true, CardValidatorInterface::LIABILITY_SHIFT_NO, CardValidatorInterface::ENROLLMENT_STATUS_READY, CardValidatorInterface::AUTHENTICATION_STATUS_UNABLE_TO_COMPLETE, false],
            [true, CardValidatorInterface::LIABILITY_SHIFT_UNKNOWN, CardValidatorInterface::ENROLLMENT_STATUS_READY, CardValidatorInterface::AUTHENTICATION_STATUS_CHALLENGE_REQUIRED, false],
            [true, CardValidatorInterface::LIABILITY_SHIFT_NO, CardValidatorInterface::ENROLLMENT_STATUS_READY, null, false],
            [true, CardValidatorInterface::LIABILITY_SHIFT_NO, CardValidatorInterface::ENROLLMENT_STATUS_NOT_READY, null, false],
            [true, CardValidatorInterface::LIABILITY_SHIFT_NO, CardValidatorInterface::ENROLLMENT_STATUS_UNAVAILABLE, null, false],
            [true, CardValidatorInterface::LIABILITY_SHIFT_UNKNOWN, CardValidatorInterface::ENROLLMENT_STATUS_UNAVAILABLE, null, false],
            [true, CardValidatorInterface::LIABILITY_SHIFT_NO, CardValidatorInterface::ENROLLMENT_STATUS_BYPASSED, null, false],
            [true, CardValidatorInterface::LIABILITY_SHIFT_UNKNOWN, null, null, false],
        ];
    }

    private function createAuthenticationResult(string $liabilityShift, ?string $enrollmentStatus, ?string $authenticationStatus): AuthenticationResult
    {
        $authenticationResult = new AuthenticationResult();
        $authenticationResult->setLiabilityShift($liabilityShift);
        if ($enrollmentStatus) {
            $threeDSecure = new ThreeDSecure();
            $threeDSecure->setEnrollmentStatus($enrollmentStatus);
            $threeDSecure->setAuthenticationStatus($authenticationStatus);
            $authenticationResult->setThreeDSecure($threeDSecure);
        }

        return $authenticationResult;
    }
}
