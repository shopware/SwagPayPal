<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\PUI\Service;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\PUI\PUIFraudNetData;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\Method\PUIMethodData;

#[Package('checkout')]
class PUIFraudNetDataService
{
    private PaymentMethodDataRegistry $paymentMethodDataRegistry;

    private CredentialsUtilInterface $credentialsUtil;

    /**
     * @internal
     */
    public function __construct(
        PaymentMethodDataRegistry $paymentMethodDataRegistry,
        CredentialsUtilInterface $credentialsUtil,
    ) {
        $this->paymentMethodDataRegistry = $paymentMethodDataRegistry;
        $this->credentialsUtil = $credentialsUtil;
    }

    public function buildCheckoutData(SalesChannelContext $context): PUIFraudNetData
    {
        $paymentMethodId = $this->paymentMethodDataRegistry->getEntityIdFromData(
            $this->paymentMethodDataRegistry->getPaymentMethod(PUIMethodData::class),
            $context->getContext()
        );

        return (new PUIFraudNetData())->assign([
            'sessionIdentifier' => Uuid::randomHex(),
            'websiteIdentifier' => 'shopware6_checkout-page',
            'sandbox' => $this->credentialsUtil->isSandbox($context->getSalesChannelId()),
            'paymentMethodId' => $paymentMethodId,
        ]);
    }
}
