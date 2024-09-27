<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Card;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Card\AuthenticationResult;
use Swag\PayPal\Setting\Settings;

#[Package('checkout')]
abstract class AbstractCardValidator implements CardValidatorInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly SystemConfigService $systemConfigService,
    ) {
    }

    protected function validateAuthenticationResult(AuthenticationResult $authenticationResult, SalesChannelContext $salesChannelContext): bool
    {
        if ($authenticationResult->getLiabilityShift() === self::LIABILITY_SHIFT_POSSIBLE
            || $authenticationResult->getLiabilityShift() === self::LIABILITY_SHIFT_YES) {
            return true;
        }

        if ($this->systemConfigService->getBool(Settings::ACDC_FORCE_3DS, $salesChannelContext->getSalesChannelId())) {
            return false;
        }

        if ($authenticationResult->getLiabilityShift() !== self::LIABILITY_SHIFT_NO) {
            return false;
        }

        $threeDSecure = $authenticationResult->getThreeDSecure();

        if ($threeDSecure === null) {
            return false;
        }

        return \in_array(
            $threeDSecure->getEnrollmentStatus(),
            [
                self::ENROLLMENT_STATUS_NOT_READY,
                self::ENROLLMENT_STATUS_UNAVAILABLE,
                self::ENROLLMENT_STATUS_BYPASSED,
            ],
            true
        );
    }
}
