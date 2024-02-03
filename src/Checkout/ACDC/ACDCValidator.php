<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ACDC;

use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\Exception\MissingPayloadException;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\Setting\Settings;

#[Package('checkout')]
class ACDCValidator implements ACDCValidatorInterface
{
    private SystemConfigService $systemConfigService;

    /**
     * @internal
     */
    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * This implements the recommended actions from PayPal. Feel free to customize.
     *
     * @see https://developer.paypal.com/docs/checkout/advanced/customize/3d-secure/response-parameters/
     */
    public function validate(Order $order, SyncPaymentTransactionStruct $transaction, SalesChannelContext $salesChannelContext): bool
    {
        $paymentSource = $order->getPaymentSource();

        if ($paymentSource === null) {
            throw new MissingPayloadException($order->getId(), 'payment_source');
        }

        $card = $paymentSource->getCard();

        if ($card === null) {
            throw new MissingPayloadException($order->getId(), 'payment_source.card');
        }

        $authenticationResult = $card->getAuthenticationResult();

        if ($authenticationResult === null) {
            throw new MissingPayloadException($order->getId(), 'payment_source.card.authentication_result');
        }

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
